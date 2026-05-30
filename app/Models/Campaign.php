<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'sender', 'name', 'phonebook_id', 'type', 'status',
        'message', 'schedule', 'delay', 'category', 'template_id', 'device_id', 'job_batch_id',
    ];

    protected $casts = [
        'message' => 'json',
    ];

    public function blasts()
    {
        return $this->hasMany(Blast::class);
    }

    public function phonebook()
    {
        return $this->belongsTo(Tag::class, 'phonebook_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function wabaTemplate()
    {
        return $this->belongsTo(WabaTemplate::class, 'template_id');
    }

    public function scopeFilter($query, $request)
    {
        return $query
            ->when($request->device, fn ($q) => $q->whereHas('device', fn ($q) => $q->where('body', $request->device)))
            ->when($request->status && $request->status !== 'all', fn ($q) => $q->where('status', $request->status));
    }
}
