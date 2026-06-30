<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KetQuaMatching extends Model
{
    use HasFactory;

    protected $table = 'ket_qua_matchings';

    protected $fillable = [
        'ho_so_id',
        'tin_tuyen_dung_id',
        'diem_phu_hop',
        'diem_ky_nang',
        'diem_kinh_nghiem',
        'diem_hoc_van',
        'chi_tiet_diem',
        'matched_skills_json',
        'missing_skills_json',
        'explanation',
        'danh_sach_ky_nang_thieu',
        'model_version',
        'thoi_gian_match'
    ];

    protected $casts = [
        'diem_phu_hop' => 'float',
        'diem_ky_nang' => 'float',
        'diem_kinh_nghiem' => 'float',
        'diem_hoc_van' => 'float',
        'chi_tiet_diem' => 'array',
        'matched_skills_json' => 'array',
        'missing_skills_json' => 'array',
        'thoi_gian_match' => 'datetime',
    ];

    /**
     * Hồ sơ được AI chấm điểm.
     */
    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'ho_so_id')->withTrashed();
    }

    /**
     * Tin tuyển dụng được AI mang ra so khớp với Hồ sơ.
     */
    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }
}
