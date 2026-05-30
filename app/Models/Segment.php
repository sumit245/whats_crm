<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Segment extends Model
{
    protected $fillable = ['user_id', 'name', 'rules', 'contact_count', 'last_computed_at'];

    protected $casts = [
        'rules'            => 'array',
        'last_computed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function computeCount(): int
    {
        $engine = new \App\Services\SegmentEngine();
        $count = $engine->resolve($this)->count();

        $this->update([
            'contact_count'   => $count,
            'last_computed_at' => now(),
        ]);

        return $count;
    }
}
