<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkClick extends Model
{
    public $timestamps = false;

    protected $fillable = ['tracked_link_id', 'contact_number', 'ip_address', 'user_agent', 'clicked_at'];

    protected $casts = ['clicked_at' => 'datetime'];

    public function trackedLink()
    {
        return $this->belongsTo(TrackedLink::class);
    }
}
