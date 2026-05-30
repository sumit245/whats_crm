<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['user_id', 'name', 'routing_rules'];

    protected $casts = [
        'routing_rules' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function onlineAgents()
    {
        return $this->hasMany(Agent::class)->where('status', 'online');
    }
}
