<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactAttribute extends Model
{
    protected $fillable = ['user_id', 'contact_number', 'key', 'value'];

    public static function setFor(int $userId, string $contactNumber, string $key, ?string $value): self
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'contact_number' => $contactNumber, 'key' => $key],
            ['value' => $value]
        );
    }

    public static function allFor(int $userId, string $contactNumber): array
    {
        return static::where('user_id', $userId)
            ->where('contact_number', $contactNumber)
            ->pluck('value', 'key')
            ->toArray();
    }
}
