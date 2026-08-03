<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ChatSetting extends Model
{
    protected $fillable = [
        'user_id', 'timezone', 'working_hours',
        'off_hours_enabled', 'off_hours_message',
        'auto_resolve_enabled', 'auto_resolve_hours',
    ];

    protected $casts = [
        'working_hours'        => 'array',
        'off_hours_enabled'    => 'boolean',
        'auto_resolve_enabled' => 'boolean',
    ];

    // ── Default working hours (Mon–Fri 09:00–18:00) ─────────────────────────
    public static function defaultWorkingHours(): array
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        return collect($days)->mapWithKeys(fn ($d) => [
            $d => [
                'enabled' => in_array($d, ['mon','tue','wed','thu','fri']),
                'start'   => '09:00',
                'end'     => '18:00',
            ]
        ])->all();
    }

    // ── Find or create with defaults ─────────────────────────────────────────
    public static function forUser(int $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'timezone'       => 'UTC',
                'working_hours'  => static::defaultWorkingHours(),
                'off_hours_enabled'    => false,
                'off_hours_message'    => null,
                'auto_resolve_enabled' => false,
                'auto_resolve_hours'   => 24,
            ]
        );
    }

    // ── Is the current moment within configured working hours? ───────────────
    public function isWithinWorkingHours(): bool
    {
        $hours = $this->working_hours ?? static::defaultWorkingHours();
        if (empty($hours)) return true;

        try {
            $now  = Carbon::now($this->timezone ?? 'UTC');
        } catch (\Throwable) {
            $now  = Carbon::now('UTC');
        }

        $dayKey = strtolower($now->format('D')); // 'mon','tue',...
        $slot   = $hours[$dayKey] ?? null;

        if (!$slot || empty($slot['enabled'])) return false;

        $start = Carbon::createFromFormat('H:i', $slot['start'] ?? '09:00', $this->timezone)->setDateFrom($now);
        $end   = Carbon::createFromFormat('H:i', $slot['end']   ?? '18:00', $this->timezone)->setDateFrom($now);

        return $now->between($start, $end);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
