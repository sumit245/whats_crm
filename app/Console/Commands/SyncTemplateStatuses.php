<?php

namespace App\Console\Commands;

use App\Models\TemplateStatusNotification;
use App\Models\User;
use App\Models\WabaTemplate;
use App\Services\MetaTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTemplateStatuses extends Command
{
    protected $signature   = 'templates:sync-statuses {--user= : Sync only a specific user ID}';
    protected $description = 'Poll Meta API for template status updates for all connected devices';

    public function handle(MetaTemplateService $service): int
    {
        $query = User::query()
            ->whereHas('devices', fn ($q) => $q->where('status', 'Connected'))
            ->with(['devices' => fn ($q) => $q->where('status', 'Connected')]);

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No users with connected devices found.');
            return 0;
        }

        $totalSynced  = 0;
        $totalChanged = 0;

        foreach ($users as $user) {
            foreach ($user->devices as $device) {
                try {
                    [$synced, $changed] = $this->syncDevice($service, $user, $device);
                    $totalSynced  += $synced;
                    $totalChanged += $changed;
                } catch (\Throwable $e) {
                    Log::error("SyncTemplateStatuses: device {$device->id} failed: " . $e->getMessage());
                    $this->warn("  Device {$device->id} ({$device->body}) error: " . $e->getMessage());
                }
            }
        }

        $this->info("Done. Synced {$totalSynced} templates, {$totalChanged} status changes.");
        return 0;
    }

    private function syncDevice(MetaTemplateService $service, User $user, $device): array
    {
        $metaTemplates = $service->fetchTemplates($device);

        if (empty($metaTemplates)) {
            return [0, 0];
        }

        $synced  = 0;
        $changed = 0;

        foreach ($metaTemplates as $mt) {
            $newStatus = strtoupper($mt['status'] ?? 'PENDING');

            $existing = WabaTemplate::where('meta_template_id', $mt['id'])
                ->where('user_id', $user->id)
                ->first();

            $oldStatus = $existing?->status;

            $template = WabaTemplate::updateOrCreate(
                ['meta_template_id' => $mt['id'], 'user_id' => $user->id],
                [
                    'device_id'        => $device->id,
                    'name'             => $mt['name'],
                    'category'         => $mt['category'],
                    'language'         => $mt['language'] ?? 'en',
                    'status'           => $newStatus,
                    'components'       => $mt['components'] ?? [],
                    'rejection_reason' => $mt['rejected_reason'] ?? null,
                    'meta_synced_at'   => now(),
                ]
            );

            $synced++;

            if ($oldStatus && $oldStatus !== $newStatus) {
                $changed++;

                TemplateStatusNotification::create([
                    'user_id'          => $user->id,
                    'template_id'      => $template->id,
                    'template_name'    => $template->name,
                    'old_status'       => $oldStatus,
                    'new_status'       => $newStatus,
                    'rejection_reason' => $mt['rejected_reason'] ?? null,
                ]);

                $this->line("  <fg=green>CHANGED</> {$template->name}: {$oldStatus} → {$newStatus}");
                Log::info("Template {$template->name} (user {$user->id}): {$oldStatus} → {$newStatus}");
            }
        }

        return [$synced, $changed];
    }
}
