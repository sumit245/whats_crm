<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDripStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public readonly int $enrollmentId) {}

    public function handle(): void
    {
        $enrollment = DripEnrollment::with(['sequence.steps', 'device'])->find($this->enrollmentId);

        if (!$enrollment || !$enrollment->isActive()) {
            return;
        }

        $sequence = $enrollment->sequence;
        if (!$sequence) {
            $enrollment->update(['status' => 'failed', 'last_error' => 'Sequence not found']);
            return;
        }

        $step = $sequence->steps->firstWhere('step_order', $enrollment->current_step);

        if (!$step) {
            // No more steps — complete
            $enrollment->update(['status' => 'completed', 'completed_at' => now(), 'next_run_at' => null]);
            return;
        }

        // Skip if contact replied (and this step has skip_if_replied enabled)
        if ($step->skip_if_replied && $enrollment->contactHasReplied()) {
            Log::info("DripStep: enrollment {$enrollment->id} step {$step->step_order} skipped (contact replied)");
            $this->advanceToNextStep($enrollment, $sequence, $step);
            return;
        }

        // Send the message
        try {
            $device = $enrollment->device;
            if (!$device) {
                throw new \Exception("Device not found for enrollment {$enrollment->id}");
            }

            $api     = new MetaCloudApiService($device);
            $contact = Contact::where('user_id', $enrollment->user_id)
                ->where('number', $enrollment->contact_number)
                ->first();

            $contactName = $contact?->name ?: $enrollment->contact_number;

            if ($step->message_type === 'template' && $step->template) {
                $tmpl = $step->template;
                $req  = (object) [
                    'template_name' => $tmpl->name,
                    'language'      => $tmpl->language ?? 'en',
                    'components'    => [],
                ];
                $api->sendTemplate($req, $enrollment->contact_number);
            } else {
                $body = $this->resolveVariables($step->message_body ?? '', $contactName, $enrollment->contact_number);
                $req  = (object) ['message' => $body];
                $api->sendText($req, $enrollment->contact_number);
            }
        } catch (\Throwable $e) {
            Log::error("DripStep: enrollment {$enrollment->id} step {$step->step_order} failed: " . $e->getMessage());
            $enrollment->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
            return;
        }

        $this->advanceToNextStep($enrollment, $sequence, $step);
    }

    private function advanceToNextStep(DripEnrollment $enrollment, $sequence, DripStep $currentStep): void
    {
        $nextStep = $sequence->steps->firstWhere('step_order', $currentStep->step_order + 1);

        if (!$nextStep) {
            $enrollment->update(['status' => 'completed', 'completed_at' => now(), 'next_run_at' => null]);
            return;
        }

        $nextRunAt = now()->add($nextStep->delay_unit, $nextStep->delay_value);

        $enrollment->update([
            'current_step' => $nextStep->step_order,
            'next_run_at'  => $nextRunAt,
        ]);
    }

    private function resolveVariables(string $text, string $contactName, string $contactNumber): string
    {
        return str_replace(
            ['{{contact_name}}', '{{contact_number}}', '{{name}}', '{{number}}'],
            [$contactName, $contactNumber, $contactName, $contactNumber],
            $text
        );
    }

    public function failed(\Throwable $e): void
    {
        DripEnrollment::where('id', $this->enrollmentId)
            ->where('status', 'active')
            ->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
    }
}
