<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TrackedLink extends Model
{
    protected $fillable = ['user_id', 'campaign_id', 'slug', 'original_url'];

    public function clicks()
    {
        return $this->hasMany(LinkClick::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Find or create a tracked link for a given campaign + URL.
     * Slug is unique per link but shared across all contacts in the campaign.
     */
    public static function findOrMake(int $userId, int $campaignId, string $originalUrl): self
    {
        return static::firstOrCreate(
            ['campaign_id' => $campaignId, 'original_url' => $originalUrl],
            ['user_id' => $userId, 'slug' => static::uniqueSlug()]
        );
    }

    private static function uniqueSlug(): string
    {
        do {
            $slug = Str::random(8);
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Build the public redirect URL for this link, optionally embedding the contact number.
     */
    public function redirectUrl(?string $contactNumber = null): string
    {
        $base = rtrim(config('app.url'), '/') . '/t/' . $this->slug;
        return $contactNumber ? $base . '?c=' . urlencode($contactNumber) : $base;
    }
}
