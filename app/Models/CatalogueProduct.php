<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogueProduct extends Model
{
    protected $fillable = [
        'catalogue_id', 'retailer_id', 'name', 'description',
        'price', 'sale_price', 'currency', 'image_url', 'product_url',
        'availability', 'condition', 'brand', 'category',
    ];

    public function catalogue(): BelongsTo
    {
        return $this->belongsTo(Catalogue::class);
    }

    public function getPriceDecimalAttribute(): float
    {
        return $this->price / 100;
    }

    public function getSalePriceDecimalAttribute(): ?float
    {
        return $this->sale_price ? $this->sale_price / 100 : null;
    }
}
