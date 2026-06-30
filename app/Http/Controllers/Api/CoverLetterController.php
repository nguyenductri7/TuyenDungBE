<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\HoSo;
use App\Models\KetQuaMatching;
use App\Models\SuDungTinhNangAi;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use App\Services\Ai\AiClientService;
use App\Services\Billing\FeatureAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CoverLetterController extends Controller
{
    public function __construct(
        private readonly AiClientService $aiClientService,
        private readonly FeatureAccessService $featureAccessService,
    ) {
    }

    private function nowUtc(): Carbon
    {
        return Carbon::now('Asia/Ho_Chi_Minh')->utc();
    }

    private function findAcceptedEmploymentForUser(int $userId): ?UngTuyen
    {
        return UngTuyen::query()
            ->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])
            ->whereHas('hoSo', function ($query) use ($userId) {
                $query->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->where('da_rut_don', false)
            ->where('trang_thai', UngTuyen::TRANG_THAI_TRUNG_TUYEN)
            ->where('trang_thai_offer', UngTuyen::OFFER_DA_CHAP_NHAN)
            ->latest('thoi_gian_phan_hoi_offer')
            ->latest('updated_at')
            ->first();
    }

    private function acceptedEmploymentResponse(UngTuyen $employment, TinTuyenDung $targetJob): JsonResponse
    {
        $employmentCompany = $employment->tinTuyenDung?->congTy;
        $targetCompany = $targetJob->congTy;
        $sameCompany = $employmentCompany && $targetCompany && (int) $employmentCompany->id === (int) $targetCompany->id;
        $companyName = $employmentCompany?->ten_cong_ty ?: 'một công ty';
        $jobTitle = $employment->tinTuyenDung?->tieu_de ?: 'một vị trí';

        $message = $sameCompany
            ? "Bạn đã trúng tuyển và nhận việc tại {$companyName} cho vị trí {$jobTitle}. Hệ thống xem bạn đã có việc nên không thể ứng tuyển thêm vị trí khác tại công ty này."
            : "Bạn đã trúng tuyển và nhận việc tại {$companyName} cho vị trí {$jobTitle}. Hệ thống xem bạn đã có việc nên không thể tiếp tục ứng tuyển tin mới.";

        return response()->json([
            'success' => false,
            'code' => 'CANDIDATE_ALREADY_EMPLOYED',
            'message' => $message,
            'data' => [
                'employment_application_id' => $employment->id,
                'employment_company_id' => $employmentCompany?->id,
                'employment_company_name' => $employmentCompany?->ten_cong_ty,
                'employment_job_id' => $employment->tin_tuyen_dung_id,
                'employment_job_title' => $employment->tinTuyenDung?->tieu_de,
                'target_company_id' => $targetCompany?->id,
                'target_company_name' => $targetCompany?->ten_cong_ty,
                'target_job_id' => $targetJob->id,
                'target_job_title' => $targetJob->tieu_de,
                'same_company' => $sameCompany,
            ],
        ], 409);
    }

    public function generate(Request $request): JsonResponse
    {
        /** @var SuDungTinhNangAi|null $billingUsage */
        $billingUsage = null;

        $request->validate([
            'ho_so_id' => ['required', 'integer', 'exists:ho_sos,id'],
            'tin_tuyen_dung_id' => ['required', 'integer', 'exists:tin_tuyen_dungs,id'],
        ]);

        $hoSo = HoSo::with('parsing')
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail((int) $request->ho_so_id);

        $tin = TinTuyenDung::with(['parsing', 'kyNangYeuCaus.kyNang', 'congTy'])
            ->findOrFail((int) $request->tin_tuyen_dung_id);

        $acceptedEmployment = $this->findAcceptedEmploymentForUser((int) $request->user()->id);
        if ($acceptedEmployment) {
            return $this->acceptedEmploymentResponse($acceptedEmployment, $tin);
        }

        $tin->loadCount([
            'acceptedApplications as so_luong_da_nhan',
        ]);

        if ($tin->so_luong_con_lai <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng này đã tuyển đủ chỉ tiêu, không thể tạo thêm thư xin việc mới.',
                'data' => [
                    'so_luong_tuyen' => $tin->so_luong_tuyen,
                    'so_luong_da_nhan' => $tin->so_luong_da_nhan,
                    'so_luong_con_lai' => $tin->so_luong_con_lai,
                ],
            ], 422);
        }

        $matching = KetQuaMatching::query()
            ->where('ho_so_id', $hoSo->id)
            ->where('tin_tuyen_dung_id', $tin->id)
            ->latest('updated_at')
            ->first();

        $cvProfile = [
            'ho_ten' => $request->user()->ho_ten,
            'tieu_de_ho_so' => $hoSo->tieu_de_ho_so,
            'trinh_do' => $hoSo->trinh_do,
            'kinh_nghiem_nam' => $hoSo->kinh_nghiem_nam,
            'parsed_name' => $hoSo->parsing?->parsed_name,
            'parsed_skills' => $hoSo->parsing?->parsed_skills_json ?? [],
            'parsed_experience' => $hoSo->parsing?->parsed_experience_json ?? [],
            'parsed_education' => $hoSo->parsing?->parsed_education_json ?? [],
            'raw_text' => $hoSo->parsing?->raw_text,
        ];

        $jdProfile = [
            'tieu_de' => $tin->tieu_de,
            'ten_cong_ty' => $tin->congTy?->ten_cong_ty,
            'cap_bac' => $tin->cap_bac,
            'kinh_nghiem_yeu_cau' => $tin->kinh_nghiem_yeu_cau,
            'trinh_do_yeu_cau' => $tin->trinh_do_yeu_cau,
            'dia_diem_lam_viec' => $tin->dia_diem_lam_viec,
            'hinh_thuc_lam_viec' => $tin->hinh_thuc_lam_viec,
            'muc_luong_tu' => $tin->muc_luong_tu,
            'muc_luong_den' => $tin->muc_luong_den,
            'don_vi_luong' => $tin->don_vi_luong,
            'raw_text' => $tin->parsing?->raw_text ?? $tin->mo_ta_cong_viec,
            'parsed_skills' => $tin->parsing?->parsed_skills_json ?? [],
            'required_skills' => $tin->kyNangYeuCaus
                ->map(function ($item) {
                    return [
                        'skill_name' => $item->kyNang?->ten_ky_nang,
                        'bat_buoc' => (bool) $item->bat_buoc,
                        'trong_so' => $item->trong_so ?? 1.0,
                    ];
                })
                ->filter(fn ($item) => !empty($item['skill_name']))
                ->values()
                ->all(),
        ];

        $matchingProfile = $matching ? [
            'diem_phu_hop' => $matching->diem_phu_hop,
            'diem_ky_nang' => $matching->diem_ky_nang,
            'diem_kinh_nghiem' => $matching->diem_kinh_nghiem,
            'diem_hoc_van' => $matching->diem_hoc_van,
            'chi_tiet_diem' => $matching->chi_tiet_diem ?? [],
            'matched_skills_json' => $matching->matched_skills_json ?? [],
            'missing_skills_json' => $matching->missing_skills_json ?? [],
            'explanation' => $matching->explanation,
        ] : [
            // AI service yêu cầu matching_profile luôn là object/dictionary.
            'diem_phu_hop' => null,
            'diem_ky_nang' => null,
            'diem_kinh_nghiem' => null,
            'diem_hoc_van' => null,
            'chi_tiet_diem' => [],
            'matched_skills_json' => [],
            'missing_skills_json' => [],
            'explanation' => null,
        ];

        $ungTuyenDaNop = UngTuyen::query()
            ->where('tin_tuyen_dung_id', (int) $request->tin_tuyen_dung_id)
            ->whereHas('hoSo', function ($query) use ($request) {
                $query->withTrashed()->where('nguoi_dung_id', $request->user()->id);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->first();

        if ($ungTuyenDaNop) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã nộp hồ sơ vào tin này rồi, không thể tạo thêm thư xin việc mới.',
            ], 422);
        }

        try {
            $billingUsage = $this->featureAccessService->beginUsage(
                $request->user(),
                'cover_letter_generation',
                'ung_tuyen',
                null,
                [
                    'ho_so_id' => $hoSo->id,
                    'tin_tuyen_dung_id' => $tin->id,
                ],
                $this->resolveIdempotencyKey($request, $hoSo->id, $tin->id),
            );

            $result = $this->aiClientService->generateCoverLetter(
                $hoSo->id,
                $tin->id,
                $cvProfile,
                $jdProfile,
                $matchingProfile,
            );
            $data = $result['data'] ?? [];
            $draft = $data['thu_xin_viec_ai'] ?? $data['content'] ?? null;

            $ungTuyen = UngTuyen::query()
                ->where('tin_tuyen_dung_id', (int) $request->tin_tuyen_dung_id)
                ->whereHas('hoSo', function ($query) use ($request) {
                    $query->withTrashed()->where('nguoi_dung_id', $request->user()->id);
                })
                ->whereNull('thoi_gian_ung_tuyen')
                ->latest('updated_at')
                ->first()
                ?? new UngTuyen([
                    'tin_tuyen_dung_id' => (int) $request->tin_tuyen_dung_id,
                ]);

            $ungTuyen->ho_so_id = $hoSo->id;
            $ungTuyen->thu_xin_viec_ai = $draft;
            $ungTuyen->thu_xin_viec = null;
            if (!$ungTuyen->exists) {
                $ungTuyen->trang_thai = UngTuyen::TRANG_THAI_CHO_DUYET;
            }
            $ungTuyen->save();

            $billingUsage = $this->featureAccessService->commitUsage($billingUsage, [
                'ung_tuyen_id' => $ungTuyen->id,
            ]);
        } catch (BillingException $e) {
            return response()->json([
                'success' => false,
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                ...$e->context,
            ], $e->status);
        } catch (RuntimeException $e) {
            if ($billingUsage) {
                $this->safeFailUsage($billingUsage, $e->getMessage());
            }

            $message = $e->getMessage();

            if (str_contains($message, 'matching_profile')) {
                $message = 'Hệ thống đang đồng bộ dữ liệu matching cho CV này. Vui lòng thử sinh thư AI lại sau ít phút.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 502);
        } catch (Throwable $e) {
            if ($billingUsage) {
                $this->safeFailUsage($billingUsage, $e->getMessage());
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo nháp thư xin việc thành công.',
            'data' => [
                'ung_tuyen_id' => $ungTuyen->id,
                'thu_xin_viec_ai' => $ungTuyen->thu_xin_viec_ai,
                'quality_warnings' => $data['quality_warnings'] ?? [],
                'skill_audit' => $data['skill_audit'] ?? null,
                'billing_usage_id' => $billingUsage?->id,
            ],
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'thu_xin_viec' => ['nullable', 'string'],
        ]);

        $ungTuyen = UngTuyen::where('id', $id)
            ->whereHas('hoSo', function ($query) use ($request) {
                $query->withTrashed()->where('nguoi_dung_id', $request->user()->id);
            })
            ->firstOrFail();

        $finalContent = trim((string) ($request->thu_xin_viec ?? $ungTuyen->thu_xin_viec_ai ?? ''));
        if ($finalContent === '') {
            return response()->json([
                'success' => false,
                'message' => 'Chưa có nội dung thư xin việc để xác nhận.',
            ], 422);
        }

        $ungTuyen->thu_xin_viec = $finalContent;
        $ungTuyen->thoi_gian_ung_tuyen ??= $this->nowUtc();
        if ($ungTuyen->trang_thai === null) {
            $ungTuyen->trang_thai = UngTuyen::TRANG_THAI_CHO_DUYET;
        }

        $tin = TinTuyenDung::query()
            ->with('congTy')
            ->withCount([
                'acceptedApplications as so_luong_da_nhan',
            ])
            ->findOrFail((int) $ungTuyen->tin_tuyen_dung_id);

        $acceptedEmployment = $this->findAcceptedEmploymentForUser((int) $request->user()->id);
        if ($acceptedEmployment && (int) $acceptedEmployment->id !== (int) $ungTuyen->id) {
            return $this->acceptedEmploymentResponse($acceptedEmployment, $tin);
        }

        if ($tin->so_luong_con_lai <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng này đã tuyển đủ chỉ tiêu, không thể xác nhận thêm hồ sơ mới.',
                'data' => [
                    'so_luong_tuyen' => $tin->so_luong_tuyen,
                    'so_luong_da_nhan' => $tin->so_luong_da_nhan,
                    'so_luong_con_lai' => $tin->so_luong_con_lai,
                ],
            ], 422);
        }

        $ungTuyen->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã xác nhận thư xin việc thành bản chính thức.',
            'data' => [
                'ung_tuyen_id' => $ungTuyen->id,
                'thu_xin_viec' => $ungTuyen->thu_xin_viec,
                'thu_xin_viec_ai' => $ungTuyen->thu_xin_viec_ai,
            ],
        ]);
    }

    private function resolveIdempotencyKey(Request $request, int $hoSoId, int $tinTuyenDungId): string
    {
        $headerKey = trim((string) $request->header('X-Idempotency-Key', ''));
        if ($headerKey !== '') {
            return $headerKey;
        }

        return 'cover-letter:' . $request->user()->id . ':' . $hoSoId . ':' . $tinTuyenDungId . ':' . Str::uuid();
    }

    private function safeFailUsage(SuDungTinhNangAi $usage, string $reason): void
    {
        try {
            $this->featureAccessService->failUsage($usage, $reason);
        } catch (Throwable) {
            // Không làm hỏng response chính nếu rollback billing gặp lỗi.
        }
    }
}
