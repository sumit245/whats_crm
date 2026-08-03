<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowNodeEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['flow_id', 'session_id', 'node_id', 'node_type', 'event_type'];

    protected $casts = ['created_at' => 'datetime'];

    public function flow()
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }

    public function session()
    {
        return $this->belongsTo(ChatbotSession::class, 'session_id');
    }
}
