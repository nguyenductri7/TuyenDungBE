<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiInterviewReport extends Model
{
    use HasFactory;

    protected $table = 'ai_interview_reports';

    protected $fillable = [
        'session_id',
        'nguoi_dung_id',
        'tin_tuyen_dung_id',
        'tong_diem',
        'diem_ky_thuat',
        'diem_giao_tiep',
        'diem_phu_hop_jd',
        'diem_manh',
        'diem_yeu',
        'de_xuat_cai_thien',
        'metadata',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'tin_tuyen_dung_id' => 'integer',
        'tong_diem' => 'float',
        'diem_ky_thuat' => 'float',
        'diem_giao_tiep' => 'float',
        'diem_phu_hop_jd' => 'float',
        'diem_manh' => 'array',
        'diem_yeu' => 'array',
        'metadata' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }
}
