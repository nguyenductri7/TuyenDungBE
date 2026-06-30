<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatSession extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_sessions';

    protected $fillable = [
        'nguoi_dung_id',
        'session_type',
        'related_ho_so_id',
        'related_tin_tuyen_dung_id',
        'status',
        'title',
        'summary',
        'metadata',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'related_ho_so_id' => 'integer',
        'related_tin_tuyen_dung_id' => 'integer',
        'status' => 'integer',
        'metadata' => 'array',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'related_ho_so_id')->withTrashed();
    }

    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'related_tin_tuyen_dung_id');
    }

    public function messages()
    {
        return $this->hasMany(AiChatMessage::class, 'session_id');
    }

    public function interviewReport()
    {
        return $this->hasOne(AiInterviewReport::class, 'session_id');
    }
}
