<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewRound extends Model
{
    use HasFactory;

    protected $table = 'interview_rounds';

    protected $fillable = [
        'ung_tuyen_id',
        'thu_tu',
        'ten_vong',
        'loai_vong',
        'trang_thai',
        'ngay_hen_phong_van',
        'hinh_thuc_phong_van',
        'interviewer_user_id',
        'link_phong_van',
        'trang_thai_tham_gia',
        'thoi_gian_phan_hoi',
        'thoi_gian_gui_nhac_lich',
        'ket_qua',
        'diem_so',
        'ghi_chu',
        'rubric_danh_gia_json',
        'created_by',
        'updated_by',
    ];

    public const TRANG_THAI_DA_LEN_LICH = 0;
    public const TRANG_THAI_HOAN_THANH = 1;
    public const TRANG_THAI_HUY = 2;

    public const TRANG_THAI_LIST = [
        self::TRANG_THAI_DA_LEN_LICH,
        self::TRANG_THAI_HOAN_THANH,
        self::TRANG_THAI_HUY,
    ];

    public const LOAI_HR = 'hr';
    public const LOAI_TECHNICAL = 'technical';
    public const LOAI_MANAGER = 'manager';
    public const LOAI_FINAL = 'final';
    public const LOAI_CULTURE = 'culture';
    public const LOAI_OTHER = 'other';

    public const LOAI_VONG_LIST = [
        self::LOAI_HR,
        self::LOAI_TECHNICAL,
        self::LOAI_MANAGER,
        self::LOAI_FINAL,
        self::LOAI_CULTURE,
        self::LOAI_OTHER,
    ];

    public const KET_QUA_DAT = 'pass';
    public const KET_QUA_ROT = 'fail';

    public const KET_QUA_LIST = [
        self::KET_QUA_DAT,
        self::KET_QUA_ROT,
    ];

    protected $casts = [
        'ung_tuyen_id' => 'integer',
        'thu_tu' => 'integer',
        'trang_thai' => 'integer',
        'ngay_hen_phong_van' => 'datetime',
        'interviewer_user_id' => 'integer',
        'trang_thai_tham_gia' => 'integer',
        'thoi_gian_phan_hoi' => 'datetime',
        'thoi_gian_gui_nhac_lich' => 'datetime',
        'diem_so' => 'float',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function ungTuyen()
    {
        return $this->belongsTo(UngTuyen::class, 'ung_tuyen_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(NguoiDung::class, 'interviewer_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(NguoiDung::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(NguoiDung::class, 'updated_by');
    }
}
