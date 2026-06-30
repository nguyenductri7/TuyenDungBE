<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UngTuyen extends Model
{
    use HasFactory;

    protected $table = 'ung_tuyens';

    protected $fillable = [
        'tin_tuyen_dung_id',
        'ho_so_id',
        'trang_thai',
        'da_rut_don',
        'thoi_gian_rut_don',
        'thu_xin_viec',
        'thu_xin_viec_ai',
        'thoi_gian_gui_offer',
        'trang_thai_offer',
        'thoi_gian_phan_hoi_offer',
        'han_phan_hoi_offer',
        'ghi_chu_offer',
        'ghi_chu_phan_hoi_offer',
        'link_offer',
        'ghi_chu',
        'thoi_gian_ung_tuyen'
    ];

    /**
     * Các trạng thái ứng tuyển
     */
    public const TRANG_THAI_CHO_DUYET = 0;
    public const TRANG_THAI_DA_XEM = 1;
    public const TRANG_THAI_DA_HEN_PHONG_VAN = 2;
    public const TRANG_THAI_QUA_PHONG_VAN = 3;
    public const TRANG_THAI_TRUNG_TUYEN = 4;
    public const TRANG_THAI_TU_CHOI = 5;

    // Alias tương thích ngược với các chỗ đang dùng tên cũ.
    public const TRANG_THAI_CHAP_NHAN = self::TRANG_THAI_TRUNG_TUYEN;

    public const TRANG_THAI_LIST = [
        self::TRANG_THAI_CHO_DUYET,
        self::TRANG_THAI_DA_XEM,
        self::TRANG_THAI_DA_HEN_PHONG_VAN,
        self::TRANG_THAI_QUA_PHONG_VAN,
        self::TRANG_THAI_TRUNG_TUYEN,
        self::TRANG_THAI_TU_CHOI,
    ];

    public const TRANG_THAI_CUOI = [
        self::TRANG_THAI_TRUNG_TUYEN,
        self::TRANG_THAI_TU_CHOI,
    ];

    public const PHONG_VAN_CHO_XAC_NHAN = 0;
    public const PHONG_VAN_DA_XAC_NHAN = 1;
    public const PHONG_VAN_KHONG_THAM_GIA = 2;

    public const PHONG_VAN_TRANG_THAI_LIST = [
        self::PHONG_VAN_CHO_XAC_NHAN,
        self::PHONG_VAN_DA_XAC_NHAN,
        self::PHONG_VAN_KHONG_THAM_GIA,
    ];

    public const OFFER_CHUA_GUI = 0;
    public const OFFER_DA_GUI = 1;
    public const OFFER_DA_CHAP_NHAN = 2;
    public const OFFER_TU_CHOI = 3;

    public const OFFER_TRANG_THAI_LIST = [
        self::OFFER_CHUA_GUI,
        self::OFFER_DA_GUI,
        self::OFFER_DA_CHAP_NHAN,
        self::OFFER_TU_CHOI,
    ];

    protected $appends = [
        'ngay_hen_phong_van',
        'vong_phong_van_hien_tai',
        'trang_thai_tham_gia_phong_van',
        'thoi_gian_phan_hoi_phong_van',
        'thoi_gian_gui_nhac_lich',
        'hinh_thuc_phong_van',
        'ten_nguoi_phong_van',
        'link_phong_van',
        'ket_qua_phong_van',
        'rubric_danh_gia_phong_van',
    ];

    protected $casts = [
        'thoi_gian_ung_tuyen' => 'datetime',
        'thoi_gian_rut_don' => 'datetime',
        'thoi_gian_gui_offer' => 'datetime',
        'thoi_gian_phan_hoi_offer' => 'datetime',
        'han_phan_hoi_offer' => 'datetime',
        'trang_thai' => 'integer',
        'trang_thai_offer' => 'integer',
        'da_rut_don' => 'boolean',
    ];

    /**
     * Tin tuyển dụng mà hồ sơ nộp vào.
     */
    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }

    /**
     * Hồ sơ được nộp.
     */
    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'ho_so_id')->withTrashed(); // Lấy cả hồ sơ bị xoá mềm để lưu vết
    }

    public function getHrPhuTrachAttribute()
    {
        return $this->tinTuyenDung?->hrPhuTrach;
    }

    public function interviewRounds()
    {
        return $this->hasMany(InterviewRound::class, 'ung_tuyen_id')->orderBy('thu_tu');
    }

    public function currentInterviewRound(): ?InterviewRound
    {
        return $this->candidateInterviewRound();
    }

    public function latestInterviewRound(): ?InterviewRound
    {
        $rounds = $this->relationLoaded('interviewRounds')
            ? $this->interviewRounds
            : $this->interviewRounds()->get();

        return $rounds
            ->sortByDesc(fn (InterviewRound $round) => sprintf(
                '%05d-%05d',
                (int) ($round->thu_tu ?? 0),
                (int) ($round->id ?? 0),
            ))
            ->first();
    }

    public function candidateInterviewRound(): ?InterviewRound
    {
        $rounds = $this->relationLoaded('interviewRounds')
            ? $this->interviewRounds
            : $this->interviewRounds()->get();

        return $rounds
            ->filter(fn (InterviewRound $round) => $round->loai_vong !== InterviewRound::LOAI_HR && $round->ngay_hen_phong_van)
            ->sortByDesc(fn (InterviewRound $round) => sprintf(
                '%05d-%05d',
                (int) ($round->thu_tu ?? 0),
                (int) ($round->id ?? 0),
            ))
            ->first();
    }

    public function getNgayHenPhongVanAttribute()
    {
        return $this->currentInterviewRound()?->ngay_hen_phong_van;
    }

    public function getVongPhongVanHienTaiAttribute()
    {
        return $this->latestInterviewRound()?->loai_vong;
    }

    public function getTrangThaiThamGiaPhongVanAttribute()
    {
        return $this->currentInterviewRound()?->trang_thai_tham_gia;
    }

    public function getThoiGianPhanHoiPhongVanAttribute()
    {
        return $this->currentInterviewRound()?->thoi_gian_phan_hoi;
    }

    public function getThoiGianGuiNhacLichAttribute()
    {
        return $this->currentInterviewRound()?->thoi_gian_gui_nhac_lich;
    }

    public function getHinhThucPhongVanAttribute()
    {
        return $this->currentInterviewRound()?->hinh_thuc_phong_van;
    }

    public function getTenNguoiPhongVanAttribute(): ?string
    {
        $round = $this->currentInterviewRound();

        // Ưu tiên FK interviewer_user_id (system user), fallback về legacy text
        return $round?->interviewer?->ho_ten ?? $round?->nguoi_phong_van;
    }

    public function getLinkPhongVanAttribute()
    {
        return $this->currentInterviewRound()?->link_phong_van;
    }

    public function getKetQuaPhongVanAttribute()
    {
        return $this->latestInterviewRound()?->ket_qua;
    }

    public function getRubricDanhGiaPhongVanAttribute()
    {
        return $this->latestInterviewRound()?->rubric_danh_gia_json;
    }

    public function onboardingPlan()
    {
        return $this->hasOne(OnboardingPlan::class, 'ung_tuyen_id');
    }
}
