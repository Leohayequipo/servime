<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLanding extends Model
{
    protected $table = 'chat_landing';

    protected $fillable = [
        'session_id',
        'user_message',
        'bot_response',
        'user_ip',
    ];
}
