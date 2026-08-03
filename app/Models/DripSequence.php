<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DripSequence extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'status',
        'trigger_type', 'trigger_phonebook_id', 'default_device_id',
    ];

    public function steps()
    {
        return $this->hasMany(DripStep::class, 'sequence_id')->orderBy('step_order');
    }

    public function enrollments()
    {
        return $this->hasMany(DripEnrollment::class, 'sequence_id');
    }

    public function triggerPhonebook()
    {
        return $this->belongsTo(Tag::class, 'trigger_phonebook_id');
    }

    public function defaultDevice()
    {
        return $this->belongsTo(Device::class, 'default_device_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Enroll a contact. Returns enrollment or null if already active. */
    public function enroll(string $contactNumber, int $userId, int $deviceId): ?DripEnrollment
    {
        // Skip if already actively enrolled
        $existing = DripEnrollment::where('sequence_id', $this->id)
            ->where('contact_number', $contactNumber)
            ->whereIn('status', ['active', 'paused'])
            ->first();

        if ($existing) return null;

        $firstStep = $this->steps()->first();
        $nextRunAt = $firstStep
            ? now()->add($firstStep->delay_unit, $firstStep->delay_value)
            : null;

        return DripEnrollment::create([
            'sequence_id'    => $this->id,
            'user_id'        => $userId,
            'contact_number' => $contactNumber,
            'device_id'      => $deviceId,
            'current_step'   => 1,
            'status'         => 'active',
            'next_run_at'    => $nextRunAt,
        ]);
    }
}
