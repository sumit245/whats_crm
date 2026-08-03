<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationLabel extends Model
{
    protected $fillable = ['user_id', 'name', 'color', 'sort_order'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_label',
            'label_id',
            'conversation_id'
        );
    }
}
