<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryTemplate extends Model
{
    protected $fillable = [
        'name', 'language', 'category', 'topic', 'usecase', 'industry',
        'header', 'body', 'footer', 'buttons', 'meta_id', 'synced_at',
    ];

    protected $casts = [
        'buttons'   => 'array',
        'synced_at' => 'datetime',
    ];

    public function hasUrlButton(): bool
    {
        return collect($this->buttons ?? [])->contains('type', 'URL');
    }

    public function scopeCategory($query, ?string $cat)
    {
        return $cat ? $query->where('category', $cat) : $query;
    }

    public function scopeLanguage($query, ?string $lang)
    {
        return $lang ? $query->where('language', $lang) : $query;
    }

    public function scopeIndustry($query, ?string $industry)
    {
        return $industry ? $query->where('industry', $industry) : $query;
    }

    public function scopeUsecase($query, ?string $usecase)
    {
        return $usecase ? $query->where('usecase', $usecase) : $query;
    }

    public function scopeSearch($query, ?string $q)
    {
        return $q ? $query->where(function ($sub) use ($q) {
            $sub->where('name', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%")
                ->orWhere('usecase', 'like', "%{$q}%")
                ->orWhere('industry', 'like', "%{$q}%");
        }) : $query;
    }
}
