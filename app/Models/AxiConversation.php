<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AxiConversation extends Model
{
    protected $fillable = ['user_id', 'title'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(AxiMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(AxiMessage::class, 'conversation_id')->latestOfMany();
    }
}
