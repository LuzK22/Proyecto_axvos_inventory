<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AxiMessage extends Model
{
    protected $fillable = ['conversation_id', 'role', 'content'];

    public function conversation()
    {
        return $this->belongsTo(AxiConversation::class, 'conversation_id');
    }
}
