<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingPlan extends Model
{
    use HasFactory;

    public const TRANG_THAI_CHUA_BAT_DAU = 'not_started';
    public const TRANG_THAI_DANG_CHUAN_BI = 'preparing';
    public const TRANG_THAI_DANG_THUC_HIEN = 'in_progress';
    public const TRANG_THAI_HOAN_TAT = 'completed';
    public const TRANG_THAI_HUY = 'cancelled';

    public const TRANG_THAI_LIST = [
        self::TRANG_THAI_CHUA_BAT_DAU,
        self::TRANG_THAI_DANG_CHUAN_BI,
        self::TRANG_THAI_DANG_THUC_HIEN,
        self::TRANG_THAI_HOAN_TAT,
        self::TRANG_THAI_HUY,
    ];

    protected $fillable = [
        'ung_tuyen_id',
        'cong_ty_id',
        'nguoi_dung_id',
        'hr_phu_trach_id',
        'ngay_bat_dau',
        'dia_diem_lam_viec',
        'trang_thai',
        'loi_chao_mung',
        'ghi_chu_noi_bo',
        'ghi_chu_ung_vien',
        'tai_lieu_can_chuan_bi_json',
        'hoan_tat_luc',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ung_tuyen_id' => 'integer',
        'cong_ty_id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'hr_phu_trach_id' => 'integer',
        'ngay_bat_dau' => 'date',
        'tai_lieu_can_chuan_bi_json' => 'array',
        'hoan_tat_luc' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $appends = [
        'tai_lieu_can_chuan_bi',
        'progress',
    ];

    public function ungTuyen()
    {
        return $this->belongsTo(UngTuyen::class, 'ung_tuyen_id');
    }

    public function congTy()
    {
        return $this->belongsTo(CongTy::class, 'cong_ty_id');
    }

    public function ungVien()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function hrPhuTrach()
    {
        return $this->belongsTo(NguoiDung::class, 'hr_phu_trach_id');
    }

    public function tasks()
    {
        return $this->hasMany(OnboardingTask::class, 'onboarding_plan_id')->orderBy('thu_tu')->orderBy('id');
    }

    public function getTaiLieuCanChuanBiAttribute(): array
    {
        return $this->tai_lieu_can_chuan_bi_json ?: [];
    }

    public function getProgressAttribute(): array
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : collect();
        $activeTasks = $tasks->where('trang_thai', '!=', OnboardingTask::TRANG_THAI_BO_QUA);
        $total = $activeTasks->count();
        $done = $activeTasks->where('trang_thai', OnboardingTask::TRANG_THAI_HOAN_TAT)->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
        ];
    }
}
