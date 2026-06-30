<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTask extends Model
{
    use HasFactory;

    public const NGUOI_PHU_TRACH_UNG_VIEN = 'candidate';
    public const NGUOI_PHU_TRACH_HR = 'hr';

    public const TRANG_THAI_CHO_LAM = 'pending';
    public const TRANG_THAI_DANG_LAM = 'in_progress';
    public const TRANG_THAI_HOAN_TAT = 'done';
    public const TRANG_THAI_BO_QUA = 'skipped';

    public const NGUOI_PHU_TRACH_LIST = [
        self::NGUOI_PHU_TRACH_UNG_VIEN,
        self::NGUOI_PHU_TRACH_HR,
    ];

    public const TRANG_THAI_LIST = [
        self::TRANG_THAI_CHO_LAM,
        self::TRANG_THAI_DANG_LAM,
        self::TRANG_THAI_HOAN_TAT,
        self::TRANG_THAI_BO_QUA,
    ];

    protected $fillable = [
        'onboarding_plan_id',
        'tieu_de',
        'mo_ta',
        'han_hoan_tat',
        'nguoi_phu_trach',
        'trang_thai',
        'thu_tu',
        'hoan_tat_luc',
        'completed_by',
        'metadata_json',
    ];

    protected $casts = [
        'onboarding_plan_id' => 'integer',
        'han_hoan_tat' => 'date',
        'thu_tu' => 'integer',
        'hoan_tat_luc' => 'datetime',
        'completed_by' => 'integer',
        'metadata_json' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(OnboardingPlan::class, 'onboarding_plan_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(NguoiDung::class, 'completed_by');
    }
}
