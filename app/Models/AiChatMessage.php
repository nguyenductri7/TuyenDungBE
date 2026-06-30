<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }
}
