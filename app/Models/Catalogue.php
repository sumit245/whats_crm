<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Catalogue extends Model
{
    protected $fillable = [
        'device_id', 'meta_catalog_id', 'name', 'description',
        'vertical', 'product_count', 'business_id', 'is_linked', 'synced_at',
    ];

    protected $casts = [
        'is_linked'  => 'boolean',
        'synced_at'  => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CatalogueProduct::class);
    }

    public function getPriceFormattedAttribute(): string
    {
        return $this->currency ? strtoupper($this->currency) : '';
    }
}
