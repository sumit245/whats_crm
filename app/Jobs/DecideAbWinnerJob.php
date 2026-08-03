<?php

namespace App\Jobs;

use App\Models\AbHoldoutContact;
use App\Models\AbTest;
use App\Models\Blast;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DecideAbWinnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $abTestId) {}

    public function handle(): void
    {
        $test = AbTest::with(['variants.template', 'device'])->find($this->abTestId);

        if (!$test || $test->status !== 'running') return;

        // ── Evaluate metrics per variant ────────────────────────────────────
        $best       = null;
        $bestScore  = -1;
        $metric     = $test->winner_metric; // read_rate|delivery_rate|click_rate

        foreach ($test->variants as $variant) {
            $m = $variant->metrics();
            $score = $m[$metric] ?? 0;

            Log::info("AbTest #{$test->id} variant {$variant->label}: {$metric} = {$score}%");

            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $variant;
            }
        }

        if (!$best) {
            Log::warning("AbTest #{$test->id}: no winner found, all metrics zero — picking first variant.");
            $best = $test->variants->first();
        }

        // ── Build winner campaign for holdout contacts ──────────────────────
        $holdouts = AbHoldoutContact::where('ab_test_id', $test->id)->pluck('contact_number');

        $winnerCampaign = null;
        if ($holdouts->isNotEmpty()) {
            $isTemplate  = $best->message_type === 'template';
            $tmpl        = $isTemplate ? $best->template : null;
            $campaignMsg = $isTemplate
                ? ['template_name' => $tmpl?->name, 'language' => $tmpl?->language]
                : ['text' => $best->message_body];

            $winnerCampaign = Campaign::create([
                'user_id'      => $test->user_id,
                'device_id'    => $test->device_id,
                'phonebook_id' => $test->phonebook_id,
                'name'         => "[AB Winner] {$test->name} — Variant {$best->label}",
                'type'         => $isTemplate ? 'metatemplate' : 'text',
                'message'      => $campaignMsg,
                'template_id'  => $best->template_id,
                'status'       => 'waiting',
                'schedule'     => now(),
                'delay'        => 1,
            ]);

            $blastRows = $holdouts->map(fn ($number) => [
                'user_id'     => $test->user_id,
                'sender'      => $test->device?->body ?? '',
                'campaign_id' => $winnerCampaign->id,
                'receiver'    => $number,
                'type'        => $isTemplate ? 'template' : 'text',
                'status'      => 'pending',
                'message'     => json_encode($campaignMsg),
                'created_at'  => now(),
                'updated_at'  => now(),
            ])->values()->all();

            Blast::insert($blastRows);
        }

        // ── Mark test complete ──────────────────────────────────────────────
        $test->update([
            'winner_variant_id'  => $best->id,
            'winner_campaign_id' => $winnerCampaign?->id,
            'status'             => 'completed',
        ]);

        Log::info("AbTest #{$test->id} complete. Winner: Variant {$best->label} ({$bestScore}% {$metric}).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("DecideAbWinnerJob #{$this->abTestId} failed: " . $e->getMessage());
        AbTest::where('id', $this->abTestId)->update(['status' => 'cancelled']);
    }
}
