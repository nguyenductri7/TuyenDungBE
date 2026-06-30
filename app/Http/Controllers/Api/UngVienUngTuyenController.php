<?php

namespace App\Http\Controllers\Api;

use App\Events\ApplicationChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\UngTuyen\NopHoSoRequest;
use App\Http\Requests\UngTuyen\PhanHoiOfferRequest;
use App\Http\Requests\UngTuyen\XacNhanPhongVanRequest;
use App\Models\InterviewRound;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use App\Services\AppNotificationService;
use App\Services\ApplicationTimelineService;
use App\Services\AuditLogService;
use App\Services\OnboardingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UngVienUngTuyenController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly AppNotificationService $appNotificationService,
        private readonly OnboardingService $onboardingService,
        private readonly ApplicationTimelineService $applicationTimelineService,
    ) {
    }

    private function applicationAuditSnapshot(UngTuyen $ungTuyen): array
    {
        return $ungTuyen->only([
            'id',
            'tin_tuyen_dung_id',
            'ho_so_id',
            'trang_thai',
            'trang_thai_tham_gia_phong_van',
            'thoi_gian_ung_tuyen',
            'da_rut_don',
            'thoi_gian_rut_don',
            'thoi_gian_gui_offer',
            'trang_thai_offer',
            'thoi_gian_phan_hoi_offer',
            'han_phan_hoi_offer',
            'ghi_chu_offer',
            'ghi_chu_phan_hoi_offer',
            'link_offer',
        ]);
    }

    private function nowUtc(): Carbon
    {
        return Carbon::now('Asia/Ho_Chi_Minh')->utc();
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Phiên đăng nhập không còn hợp lệ.',
        ], 401);
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

    private function broadcastApplicationChanged(UngTuyen $application, string $changeType, array $payload = []): void
    {
        $event = ApplicationChanged::fromApplication($application, $changeType, $payload);

        if (!$event) {
            return;
        }

        try {
            broadcast($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function isInterviewResponseLocked(UngTuyen $ungTuyen): bool
    {
        return (bool) $ungTuyen->da_rut_don || in_array((int) $ungTuyen->trang_thai, [
            UngTuyen::TRANG_THAI_QUA_PHONG_VAN,
            UngTuyen::TRANG_THAI_TRUNG_TUYEN,
            UngTuyen::TRANG_THAI_TU_CHOI,
        ], true);
    }

    private function isOfferResponseAvailable(UngTuyen $ungTuyen): bool
    {
        if ($ungTuyen->da_rut_don) {
            return false;
        }

        if ((int) $ungTuyen->trang_thai_offer !== UngTuyen::OFFER_DA_GUI) {
            return false;
        }

        if (!$ungTuyen->thoi_gian_gui_offer) {
            return false;
        }

        return !$ungTuyen->han_phan_hoi_offer || $ungTuyen->han_phan_hoi_offer->isFuture();
    }

    private function emailActionSignatureFailure(Request $request): ?string
    {
        if ($request->hasValidSignature()) {
            return null;
        }

        $expiresAt = $request->query('expires');

        if (is_numeric($expiresAt) && (int) $expiresAt < now()->timestamp) {
            return 'expired';
        }

        return 'invalid';
    }

    /**
     * Xem danh sách các công việc đã nộp hồ sơ
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->unauthorizedResponse();
        }

        $withdrawn = $request->boolean('da_rut_don', false);

        // Query các ứng tuyển thông qua hồ sơ của user hiện tại
        $query = UngTuyen::whereHas('hoSo', function ($q) use ($userId) {
            // Bao gồm cả hoSo đã soft delete
            $q->withTrashed()->where('nguoi_dung_id', $userId);
        })
        ->whereNotNull('thoi_gian_ung_tuyen')
        ->where('da_rut_don', $withdrawn)
        ->with([
            'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
            'tinTuyenDung.congTy:id,ten_cong_ty,logo',
            'hoSo' => function ($q) {
                // Bao gồm cả hồ sơ bị xóa
                $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
            },
            'interviewRounds.interviewer:id,ho_ten,email',
            'onboardingPlan.tasks.completedBy:id,ho_ten,email',
            'onboardingPlan.hrPhuTrach:id,ho_ten,email',
        ]);

        // Lọc theo trạng thái ứng tuyển (nếu có)
        if ($request->has('trang_thai') && $request->trang_thai !== '') {
            $query->where('trang_thai', $request->trang_thai);
        }

        $query->orderBy('thoi_gian_ung_tuyen', 'desc');

        $ungTuyens = $query->paginate((int) $request->get('per_page', 10));
        $ungTuyens->getCollection()->transform(function (UngTuyen $ungTuyen) {
            $ungTuyen->setAttribute('application_timeline', $this->applicationTimelineService->build($ungTuyen, false));
            $ungTuyen->makeHidden(['ghi_chu']);
            $ungTuyen->setRelation(
                'interviewRounds',
                $ungTuyen->interviewRounds
                    ->filter(fn (InterviewRound $round) => $round->loai_vong !== InterviewRound::LOAI_HR)
                    ->values(),
            );

            return $ungTuyen;
        });

        return response()->json([
            'success' => true,
            'data' => $ungTuyens
        ]);
    }

    /**
     * Nộp hồ sơ vào 1 tin tuyển dụng
     */
    public function store(NopHoSoRequest $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->unauthorizedResponse();
        }

        $tinId = $request->tin_tuyen_dung_id;
        $hoSoId = (int) $request->ho_so_id;

        // 1. Kiểm tra tin tuyển dụng có còn hoạt động không
        $tin = TinTuyenDung::with('congTy')->find($tinId);
        if ($tin->trang_thai != 1 || ($tin->ngay_het_han && \Carbon\Carbon::parse($tin->ngay_het_han)->isPast())) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng đã hết hạn hoặc tạm ngưng.'
            ], 400);
        }

        // 2. Kiểm tra cty có đang hoạt động không
        if ($tin->congTy && $tin->congTy->trang_thai != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Công ty tuyển dụng đang bị khóa hoặc chưa duyệt.'
            ], 400);
        }

        $acceptedEmployment = $this->findAcceptedEmploymentForUser($userId);
        if ($acceptedEmployment) {
            return $this->acceptedEmploymentResponse($acceptedEmployment, $tin);
        }

        $tin->loadCount([
            'acceptedApplications as so_luong_da_nhan',
        ]);

        if ($tin->so_luong_con_lai <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng này đã tuyển đủ chỉ tiêu.',
                'data' => [
                    'so_luong_tuyen' => $tin->so_luong_tuyen,
                    'so_luong_da_nhan' => $tin->so_luong_da_nhan,
                    'so_luong_con_lai' => $tin->so_luong_con_lai,
                ],
            ], 422);
        }

        // 3. Nếu đã có nháp cover letter cho tin này thì tái sử dụng nháp đó.
        $ungTuyenNhaps = UngTuyen::where('tin_tuyen_dung_id', $tinId)
            ->whereHas('hoSo', function ($q) use ($userId) {
                $q->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNull('thoi_gian_ung_tuyen')
            ->orderByDesc('updated_at')
            ->get();

        $ungTuyenNhap = $ungTuyenNhaps->first(function (UngTuyen $item) {
            return !empty($item->thu_xin_viec_ai) || empty($item->thu_xin_viec);
        });

        if ($ungTuyenNhap) {
            $ungTuyenNhap->fill([
                'ho_so_id' => $hoSoId,
                'thu_xin_viec' => $request->thu_xin_viec ?: $ungTuyenNhap->thu_xin_viec_ai,
                'trang_thai' => UngTuyen::TRANG_THAI_CHO_DUYET,
                'thoi_gian_ung_tuyen' => $this->nowUtc(),
            ]);
            $ungTuyenNhap->save();
            $ungTuyenNhap->load([
                'tinTuyenDung:id,tieu_de',
                'hoSo:id,tieu_de_ho_so'
            ]);
            $this->auditLogService->logModelAction(
                actor: $request->user(),
                action: 'candidate_application_submitted',
                description: "Ứng viên nộp hồ sơ vào tin #{$tinId}.",
                target: $ungTuyenNhap,
                company: $tin->congTy,
                after: $this->applicationAuditSnapshot($ungTuyenNhap),
                metadata: [
                    'scope' => 'candidate_application',
                    'tin_tuyen_dung_id' => $tinId,
                    'ho_so_id' => $hoSoId,
                    'reused_draft' => true,
                ],
                request: $request,
            );
            $this->notifyEmployerAboutSubmittedApplication($ungTuyenNhap, $tin);
            $this->broadcastApplicationChanged($ungTuyenNhap, 'submitted');

            return response()->json([
                'success' => true,
                'message' => 'Nộp hồ sơ thành công!',
                'data' => $ungTuyenNhap
            ], 201);
        }

        // 4. Kiểm tra xem người dùng này ĐÃ nộp hồ sơ hoàn chỉnh vào tin này CHƯA
        // (Dù nộp bằng hồ sơ khác cũng không cho, 1 tài khoản chỉ nộp 1 lần/tin)
        $daNop = UngTuyen::where('tin_tuyen_dung_id', $tinId)
            ->whereHas('hoSo', function ($q) use ($userId) {
                $q->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->exists();

        if ($daNop) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã nộp hồ sơ vào tin này rồi, không thể nộp thêm.'
            ], 400);
        }

        // Tạo ứng tuyển
        $ungTuyen = UngTuyen::create([
            'tin_tuyen_dung_id' => $tinId,
            'ho_so_id' => $hoSoId,
            'thu_xin_viec' => $request->thu_xin_viec,
            'trang_thai' => UngTuyen::TRANG_THAI_CHO_DUYET,
            'thoi_gian_ung_tuyen' => $this->nowUtc(),
        ]);

        // Load relationship trả về
        $ungTuyen->load([
            'tinTuyenDung:id,tieu_de',
            'hoSo:id,tieu_de_ho_so'
        ]);
        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'candidate_application_submitted',
            description: "Ứng viên nộp hồ sơ vào tin #{$tinId}.",
            target: $ungTuyen,
            company: $tin->congTy,
            after: $this->applicationAuditSnapshot($ungTuyen),
            metadata: [
                'scope' => 'candidate_application',
                'tin_tuyen_dung_id' => $tinId,
                'ho_so_id' => $hoSoId,
                'reused_draft' => false,
            ],
            request: $request,
        );
        $this->notifyEmployerAboutSubmittedApplication($ungTuyen, $tin);
        $this->broadcastApplicationChanged($ungTuyen, 'submitted');

        return response()->json([
            'success' => true,
            'message' => 'Nộp hồ sơ thành công!',
            'data' => $ungTuyen
        ], 201);
    }

    /**
     * Cập nhật CV/thư xin việc cho đơn đã nộp.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!auth()->id()) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'ho_so_id' => [
                'required',
                'integer',
                'exists:ho_sos,id,deleted_at,NULL,nguoi_dung_id,' . auth()->id()
            ],
            'thu_xin_viec' => ['nullable', 'string', 'max:5000'],
        ], [
            'ho_so_id.required' => 'Vui lòng chọn hồ sơ muốn cập nhật.',
            'ho_so_id.exists' => 'Hồ sơ không hợp lệ hoặc không thuộc quyền sở hữu của bạn.',
        ]);

        $ungTuyen = UngTuyen::query()
            ->where('id', $id)
            ->whereHas('hoSo', function ($query) {
                $query->withTrashed()->where('nguoi_dung_id', auth()->id());
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->firstOrFail();

        if ($ungTuyen->trang_thai !== UngTuyen::TRANG_THAI_CHO_DUYET) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể cập nhật hồ sơ khi đơn vẫn đang chờ duyệt.',
            ], 422);
        }

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $ungTuyen->fill([
            'ho_so_id' => (int) $validated['ho_so_id'],
            'thu_xin_viec' => $validated['thu_xin_viec'] ?: null,
        ]);
        $ungTuyen->save();
        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'candidate_application_updated',
            description: "Ứng viên cập nhật hồ sơ ứng tuyển #{$ungTuyen->id}.",
            target: $ungTuyen,
            company: $ungTuyen->tinTuyenDung?->congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyen),
            metadata: [
                'scope' => 'candidate_application',
                'tin_tuyen_dung_id' => $ungTuyen->tin_tuyen_dung_id,
            ],
            request: $request,
        );
        $this->broadcastApplicationChanged($ungTuyen, 'candidate_updated');

        $ungTuyen->load([
            'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
            'tinTuyenDung.congTy:id,ten_cong_ty,logo',
            'hoSo' => function ($q) {
                $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
            }
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật hồ sơ ứng tuyển.',
            'data' => $ungTuyen,
        ]);
    }

    public function xacNhanPhongVan(XacNhanPhongVanRequest $request, int $id): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->unauthorizedResponse();
        }

        $ungTuyen = UngTuyen::query()
            ->where('id', $id)
            ->whereHas('hoSo', function ($query) use ($userId) {
                $query->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->with([
                'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
                'tinTuyenDung.congTy:id,ten_cong_ty,logo',
                'hoSo' => function ($q) {
                    $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
                },
                'interviewRounds.interviewer:id,ho_ten,email',
            ])
            ->firstOrFail();

        $round = $ungTuyen->currentInterviewRound();

        if (!$round?->ngay_hen_phong_van) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng tuyển này chưa có lịch phỏng vấn để xác nhận.',
            ], 422);
        }

        if ($this->isInterviewResponseLocked($ungTuyen)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển này đã được chuyển sang giai đoạn xử lý tiếp theo nên không thể phản hồi lịch phỏng vấn nữa.',
            ], 422);
        }

        if ($round->ngay_hen_phong_van->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Lịch phỏng vấn đã qua nên không thể cập nhật xác nhận tham gia nữa.',
            ], 422);
        }

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $status = (int) $request->input('trang_thai_tham_gia_phong_van');
        $round->forceFill([
            'trang_thai_tham_gia' => $status,
            'thoi_gian_phan_hoi' => $this->nowUtc(),
        ])->save();
        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'candidate_interview_responded',
            description: "Ứng viên phản hồi lịch phỏng vấn cho đơn #{$ungTuyen->id}.",
            target: $ungTuyen,
            company: $ungTuyen->tinTuyenDung?->congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyen),
            metadata: [
                'scope' => 'candidate_application',
                'interview_round_id' => $round->id,
                'response_status' => $status,
            ],
            request: $request,
        );
        $this->notifyEmployerAboutCandidateInterviewRoundResponse($ungTuyen, $round->fresh());
        $this->broadcastApplicationChanged($ungTuyen, 'interview_response');

        $message = $status === UngTuyen::PHONG_VAN_DA_XAC_NHAN
            ? 'Bạn đã xác nhận tham gia phỏng vấn.'
            : 'Bạn đã báo không thể tham gia buổi phỏng vấn này.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $ungTuyen->fresh([
                'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
                'tinTuyenDung.congTy:id,ten_cong_ty,logo',
                'hoSo' => function ($q) {
                    $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
                },
                'interviewRounds.interviewer:id,ho_ten,email',
            ]),
        ]);
    }

    public function xacNhanVongPhongVan(XacNhanPhongVanRequest $request, int $id, int $roundId): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->unauthorizedResponse();
        }

        $ungTuyen = UngTuyen::query()
            ->where('id', $id)
            ->whereHas('hoSo', function ($query) use ($userId) {
                $query->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->with(['tinTuyenDung.congTy', 'hoSo' => fn ($q) => $q->withTrashed()])
            ->firstOrFail();
        $round = $ungTuyen->interviewRounds()->whereKey($roundId)->firstOrFail();

        if (!$round->ngay_hen_phong_van) {
            return response()->json([
                'success' => false,
                'message' => 'Vòng phỏng vấn này chưa có lịch để xác nhận.',
            ], 422);
        }

        if ($this->isInterviewResponseLocked($ungTuyen) || (int) $round->trang_thai !== InterviewRound::TRANG_THAI_DA_LEN_LICH) {
            return response()->json([
                'success' => false,
                'message' => 'Vòng phỏng vấn này đã được xử lý nên không thể phản hồi nữa.',
            ], 422);
        }

        if ($round->ngay_hen_phong_van->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Lịch phỏng vấn đã qua nên không thể cập nhật xác nhận tham gia nữa.',
            ], 422);
        }

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $status = (int) $request->input('trang_thai_tham_gia_phong_van');
        $round->forceFill([
            'trang_thai_tham_gia' => $status,
            'thoi_gian_phan_hoi' => $this->nowUtc(),
        ])->save();

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'candidate_interview_round_responded',
            description: "Ứng viên phản hồi vòng phỏng vấn #{$round->id} cho đơn #{$ungTuyen->id}.",
            target: $round,
            company: $ungTuyen->tinTuyenDung?->congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyen->fresh()),
            metadata: [
                'scope' => 'candidate_interview_round',
                'interview_round_id' => $round->id,
                'response_status' => $status,
            ],
            request: $request,
        );
        $this->notifyEmployerAboutCandidateInterviewRoundResponse($ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']), $round->fresh());
        $this->broadcastApplicationChanged($ungTuyen->fresh(), 'interview_round_response', [
            'interview_round_id' => $round->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $status === UngTuyen::PHONG_VAN_DA_XAC_NHAN
                ? 'Bạn đã xác nhận tham gia vòng phỏng vấn.'
                : 'Bạn đã báo không thể tham gia vòng phỏng vấn này.',
            'data' => $ungTuyen->fresh([
                'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
                'tinTuyenDung.congTy:id,ten_cong_ty,logo',
                'hoSo' => fn ($q) => $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv'),
                'interviewRounds.interviewer:id,ho_ten,email',
            ]),
        ]);
    }

    public function rutDon(Request $request, int $id): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->unauthorizedResponse();
        }

        $ungTuyen = UngTuyen::query()
            ->where('id', $id)
            ->whereHas('hoSo', function ($query) use ($userId) {
                $query->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->with([
                'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
                'tinTuyenDung.congTy:id,ten_cong_ty,logo',
                'hoSo' => function ($q) {
                    $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
                },
            ])
            ->firstOrFail();

        if ($ungTuyen->da_rut_don) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển này đã được rút trước đó.',
            ], 422);
        }

        if (in_array((int) $ungTuyen->trang_thai, UngTuyen::TRANG_THAI_CUOI, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển đã có kết quả cuối nên không thể rút nữa.',
            ], 422);
        }

        if ((int) $ungTuyen->trang_thai_tham_gia_phong_van !== UngTuyen::PHONG_VAN_KHONG_THAM_GIA) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể rút đơn sau khi bạn đã phản hồi không tham gia phỏng vấn.',
            ], 422);
        }

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $ungTuyen->fill([
            'da_rut_don' => true,
            'thoi_gian_rut_don' => $this->nowUtc(),
        ]);
        $ungTuyen->save();
        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'candidate_application_withdrawn',
            description: "Ứng viên rút đơn ứng tuyển #{$ungTuyen->id}.",
            target: $ungTuyen,
            company: $ungTuyen->tinTuyenDung?->congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyen),
            metadata: [
                'scope' => 'candidate_application',
                'tin_tuyen_dung_id' => $ungTuyen->tin_tuyen_dung_id,
            ],
            request: $request,
        );
        $this->notifyEmployerAboutCandidateWithdrawal($ungTuyen);
        $this->broadcastApplicationChanged($ungTuyen, 'withdrawn');

        return response()->json([
            'success' => true,
            'message' => 'Đã rút đơn ứng tuyển và chuyển sang mục lưu trữ.',
            'data' => $ungTuyen->fresh([
                'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai',
                'tinTuyenDung.congTy:id,ten_cong_ty,logo',
                'hoSo' => function ($q) {
                    $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
                },
            ]),
        ]);
    }

    public function phanHoiOffer(PhanHoiOfferRequest $request, int $id): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->unauthorizedResponse();
        }

        $ungTuyen = UngTuyen::query()
            ->where('id', $id)
            ->whereHas('hoSo', function ($query) use ($userId) {
                $query->withTrashed()->where('nguoi_dung_id', $userId);
            })
            ->whereNotNull('thoi_gian_ung_tuyen')
            ->with([
                'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai,hr_phu_trach_id',
                'tinTuyenDung.congTy',
                'hoSo' => function ($q) {
                    $q->withTrashed()->with('nguoiDung');
                },
            ])
            ->firstOrFail();

        if (!$this->isOfferResponseAvailable($ungTuyen)) {
            return response()->json([
                'success' => false,
                'message' => 'Offer này không còn khả dụng để phản hồi.',
            ], 422);
        }

        $accepted = $request->input('action') === 'accept';
        $before = $this->applicationAuditSnapshot($ungTuyen);
        $ungTuyen->forceFill([
            'trang_thai_offer' => $accepted ? UngTuyen::OFFER_DA_CHAP_NHAN : UngTuyen::OFFER_TU_CHOI,
            'trang_thai' => $accepted ? UngTuyen::TRANG_THAI_TRUNG_TUYEN : UngTuyen::TRANG_THAI_TU_CHOI,
            'thoi_gian_phan_hoi_offer' => $this->nowUtc(),
            'ghi_chu_phan_hoi_offer' => $request->input('ghi_chu_phan_hoi_offer'),
        ])->save();

        $ungTuyenAfter = $ungTuyen->fresh([
            'tinTuyenDung:id,cong_ty_id,tieu_de,dia_diem_lam_viec,muc_luong_tu,muc_luong_den,trang_thai,hr_phu_trach_id',
            'tinTuyenDung.congTy',
            'hoSo' => function ($q) {
                $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv');
            },
        ]);

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: $accepted ? 'candidate_offer_accepted' : 'candidate_offer_declined',
            description: "Ứng viên " . ($accepted ? 'chấp nhận' : 'từ chối') . " offer cho đơn #{$ungTuyenAfter->id}.",
            target: $ungTuyenAfter,
            company: $ungTuyenAfter->tinTuyenDung?->congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyenAfter),
            metadata: [
                'scope' => 'candidate_offer',
                'response' => $accepted ? 'accept' : 'decline',
            ],
            request: $request,
        );

        $this->notifyEmployerAboutCandidateOfferResponse($ungTuyenAfter, $accepted);
        if ($accepted) {
            $this->onboardingService->ensurePlanForAcceptedOffer($ungTuyenAfter, $request->user()?->id);
            $ungTuyenAfter->load('onboardingPlan.tasks.completedBy', 'onboardingPlan.hrPhuTrach');
        }
        $this->broadcastApplicationChanged($ungTuyenAfter, $accepted ? 'offer_accepted' : 'offer_declined', [
            'trang_thai_offer' => $accepted ? UngTuyen::OFFER_DA_CHAP_NHAN : UngTuyen::OFFER_TU_CHOI,
        ]);

        return response()->json([
            'success' => true,
            'message' => $accepted
                ? 'Bạn đã chấp nhận offer. Nhà tuyển dụng sẽ liên hệ các bước nhận việc tiếp theo.'
                : 'Đã ghi nhận phản hồi từ chối offer của bạn.',
            'data' => $ungTuyenAfter,
        ]);
    }

    public function xacNhanPhongVanQuaEmail(Request $request, int $id, string $action): RedirectResponse
    {
        if ($signatureFailure = $this->emailActionSignatureFailure($request)) {
            return redirect($this->buildInterviewResponseRedirectUrl($signatureFailure, $id));
        }

        $ungTuyen = UngTuyen::query()
            ->with([
                'hoSo' => function ($query) {
                    $query->withTrashed()->with('nguoiDung');
                },
                'tinTuyenDung.congTy',
                'interviewRounds.interviewer:id,ho_ten,email',
            ])
            ->findOrFail($id);

        $ownerId = (int) ($ungTuyen->hoSo?->nguoiDung?->id ?? 0);
        $expectedOwnerId = (int) $request->integer('user');

        if (!$ownerId || $ownerId !== $expectedOwnerId) {
            return redirect($this->buildInterviewResponseRedirectUrl('invalid', $id));
        }

        $round = $ungTuyen->currentInterviewRound();

        if (!$round?->ngay_hen_phong_van) {
            return redirect($this->buildInterviewResponseRedirectUrl('missing_schedule', $id));
        }

        if ($this->isInterviewResponseLocked($ungTuyen)) {
            return redirect($this->buildInterviewResponseRedirectUrl('locked', $id));
        }

        if ($round->ngay_hen_phong_van->isPast()) {
            return redirect($this->buildInterviewResponseRedirectUrl('expired', $id));
        }

        $normalizedAction = strtolower(trim($action));
        $status = match ($normalizedAction) {
            'accept' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
            'decline' => UngTuyen::PHONG_VAN_KHONG_THAM_GIA,
            default => null,
        };

        if ($status === null) {
            return redirect($this->buildInterviewResponseRedirectUrl('invalid', $id));
        }

        $round->forceFill([
            'trang_thai_tham_gia' => $status,
            'thoi_gian_phan_hoi' => $this->nowUtc(),
        ])->save();
        $this->notifyEmployerAboutCandidateInterviewRoundResponse($ungTuyen, $round->fresh());
        $this->broadcastApplicationChanged($ungTuyen, 'interview_response_email');

        return redirect($this->buildInterviewResponseRedirectUrl(
            $status === UngTuyen::PHONG_VAN_DA_XAC_NHAN ? 'accepted' : 'declined',
            $id
        ));
    }

    public function xacNhanVongPhongVanQuaEmail(Request $request, int $id, int $roundId, string $action): RedirectResponse
    {
        if ($signatureFailure = $this->emailActionSignatureFailure($request)) {
            return redirect($this->buildInterviewResponseRedirectUrl($signatureFailure, $id));
        }

        $ungTuyen = UngTuyen::query()
            ->with([
                'hoSo' => function ($query) {
                    $query->withTrashed()->with('nguoiDung');
                },
                'tinTuyenDung.congTy',
            ])
            ->findOrFail($id);
        $round = $ungTuyen->interviewRounds()->whereKey($roundId)->firstOrFail();

        $ownerId = (int) ($ungTuyen->hoSo?->nguoiDung?->id ?? 0);
        $expectedOwnerId = (int) $request->integer('user');

        if (!$ownerId || $ownerId !== $expectedOwnerId) {
            return redirect($this->buildInterviewResponseRedirectUrl('invalid', $id));
        }

        if (!$round->ngay_hen_phong_van) {
            return redirect($this->buildInterviewResponseRedirectUrl('missing_schedule', $id));
        }

        if ($this->isInterviewResponseLocked($ungTuyen) || (int) $round->trang_thai !== InterviewRound::TRANG_THAI_DA_LEN_LICH) {
            return redirect($this->buildInterviewResponseRedirectUrl('locked', $id));
        }

        if ($round->ngay_hen_phong_van->isPast()) {
            return redirect($this->buildInterviewResponseRedirectUrl('expired', $id));
        }

        $normalizedAction = strtolower(trim($action));
        $status = match ($normalizedAction) {
            'accept' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
            'decline' => UngTuyen::PHONG_VAN_KHONG_THAM_GIA,
            default => null,
        };

        if ($status === null) {
            return redirect($this->buildInterviewResponseRedirectUrl('invalid', $id));
        }

        $round->forceFill([
            'trang_thai_tham_gia' => $status,
            'thoi_gian_phan_hoi' => $this->nowUtc(),
        ])->save();
        $this->notifyEmployerAboutCandidateInterviewRoundResponse($ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']), $round->fresh());
        $this->broadcastApplicationChanged($ungTuyen->fresh(), 'interview_round_response_email', [
            'interview_round_id' => $round->id,
        ]);

        return redirect($this->buildInterviewResponseRedirectUrl(
            $status === UngTuyen::PHONG_VAN_DA_XAC_NHAN ? 'accepted' : 'declined',
            $id
        ));
    }

    public function phanHoiOfferQuaEmail(Request $request, int $id, string $action): RedirectResponse
    {
        if ($signatureFailure = $this->emailActionSignatureFailure($request)) {
            return redirect($this->buildOfferResponseRedirectUrl($signatureFailure, $id));
        }

        $ungTuyen = UngTuyen::query()
            ->with([
                'tinTuyenDung.congTy',
                'hoSo' => function ($query) {
                    $query->withTrashed()->with('nguoiDung');
                },
            ])
            ->findOrFail($id);

        $ownerId = (int) ($ungTuyen->hoSo?->nguoiDung?->id ?? 0);
        $expectedOwnerId = (int) $request->integer('user');

        if (!$ownerId || $ownerId !== $expectedOwnerId) {
            return redirect($this->buildOfferResponseRedirectUrl('invalid', $id));
        }

        if (!$this->isOfferResponseAvailable($ungTuyen)) {
            return redirect($this->buildOfferResponseRedirectUrl('locked', $id));
        }

        $normalizedAction = strtolower(trim($action));
        $accepted = match ($normalizedAction) {
            'accept' => true,
            'decline' => false,
            default => null,
        };

        if ($accepted === null) {
            return redirect($this->buildOfferResponseRedirectUrl('invalid', $id));
        }

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $ungTuyen->forceFill([
            'trang_thai_offer' => $accepted ? UngTuyen::OFFER_DA_CHAP_NHAN : UngTuyen::OFFER_TU_CHOI,
            'trang_thai' => $accepted ? UngTuyen::TRANG_THAI_TRUNG_TUYEN : UngTuyen::TRANG_THAI_TU_CHOI,
            'thoi_gian_phan_hoi_offer' => $this->nowUtc(),
        ])->save();

        $ungTuyenAfter = $ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
        $this->auditLogService->logModelAction(
            actor: $ungTuyenAfter->hoSo?->nguoiDung,
            action: $accepted ? 'candidate_offer_accepted_email' : 'candidate_offer_declined_email',
            description: "Ứng viên phản hồi offer qua email cho đơn #{$ungTuyenAfter->id}.",
            target: $ungTuyenAfter,
            company: $ungTuyenAfter->tinTuyenDung?->congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyenAfter),
            metadata: [
                'scope' => 'candidate_offer',
                'response' => $accepted ? 'accept' : 'decline',
                'source' => 'email',
            ],
            request: $request,
        );

        $this->notifyEmployerAboutCandidateOfferResponse($ungTuyenAfter, $accepted);
        if ($accepted) {
            $this->onboardingService->ensurePlanForAcceptedOffer($ungTuyenAfter, $ungTuyenAfter->hoSo?->nguoiDung?->id);
        }
        $this->broadcastApplicationChanged($ungTuyenAfter, $accepted ? 'offer_accepted_email' : 'offer_declined_email', [
            'trang_thai_offer' => $accepted ? UngTuyen::OFFER_DA_CHAP_NHAN : UngTuyen::OFFER_TU_CHOI,
        ]);

        return redirect($this->buildOfferResponseRedirectUrl(
            $accepted ? 'accepted' : 'declined',
            $id
        ));
    }

    private function notifyEmployerAboutSubmittedApplication(UngTuyen $ungTuyen, TinTuyenDung $tin): void
    {
        $tin->loadMissing('congTy');
        $ungTuyen->loadMissing('hoSo.nguoiDung');
        $company = $tin->congTy;

        if (!$company) {
            return;
        }

        $candidateName = $ungTuyen->hoSo?->nguoiDung?->ho_ten ?: 'Ứng viên';
        $recipients = $this->appNotificationService->recruitmentRecipients($company, $tin->hr_phu_trach_id);

        $this->appNotificationService->createForUsers(
            $recipients,
            'employer_application_submitted',
            'Có hồ sơ ứng tuyển mới',
            "{$candidateName} vừa nộp hồ sơ cho vị trí {$tin->tieu_de}.",
            '/employer/interviews',
            ['ung_tuyen_id' => $ungTuyen->id, 'tin_tuyen_dung_id' => $tin->id],
        );
    }

    private function notifyEmployerAboutCandidateInterviewResponse(UngTuyen $ungTuyen): void
    {
        $ungTuyen->loadMissing(['interviewRounds', 'tinTuyenDung.congTy', 'hoSo.nguoiDung']);
        $round = $ungTuyen->currentInterviewRound();

        if ($round) {
            $this->notifyEmployerAboutCandidateInterviewRoundResponse($ungTuyen, $round);
        }
    }

    private function notifyEmployerAboutCandidateInterviewRoundResponse(UngTuyen $ungTuyen, InterviewRound $round): void
    {
        $ungTuyen->loadMissing(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
        $company = $ungTuyen->tinTuyenDung?->congTy;

        if (!$company) {
            return;
        }

        $candidateName = $ungTuyen->hoSo?->nguoiDung?->ho_ten ?: 'Ứng viên';
        $accepted = (int) $round->trang_thai_tham_gia === UngTuyen::PHONG_VAN_DA_XAC_NHAN;

        $this->appNotificationService->createForUsers(
            $this->appNotificationService->recruitmentRecipients($company, $ungTuyen->tinTuyenDung?->hr_phu_trach_id),
            $accepted ? 'employer_interview_round_confirmed' : 'employer_interview_round_declined',
            $accepted ? 'Ứng viên xác nhận vòng phỏng vấn' : 'Ứng viên báo không tham gia vòng phỏng vấn',
            "{$candidateName} đã " . ($accepted ? 'xác nhận tham gia' : 'báo không thể tham gia') . " {$round->ten_vong}.",
            '/employer/interviews',
            ['ung_tuyen_id' => $ungTuyen->id, 'interview_round_id' => $round->id],
        );
    }

    private function notifyEmployerAboutCandidateOfferResponse(UngTuyen $ungTuyen, bool $accepted): void
    {
        $ungTuyen->loadMissing(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
        $company = $ungTuyen->tinTuyenDung?->congTy;

        if (!$company) {
            return;
        }

        $candidateName = $ungTuyen->hoSo?->nguoiDung?->ho_ten ?: 'Ứng viên';
        $jobTitle = $ungTuyen->tinTuyenDung?->tieu_de ?: 'vị trí ứng tuyển';

        $this->appNotificationService->createForUsers(
            $this->appNotificationService->recruitmentRecipients($company, $ungTuyen->tinTuyenDung?->hr_phu_trach_id),
            $accepted ? 'employer_offer_accepted' : 'employer_offer_declined',
            $accepted ? 'Ứng viên đã chấp nhận offer' : 'Ứng viên đã từ chối offer',
            "{$candidateName} đã " . ($accepted ? 'chấp nhận' : 'từ chối') . " đề nghị nhận việc cho vị trí {$jobTitle}.",
            '/employer/interviews',
            ['ung_tuyen_id' => $ungTuyen->id, 'tin_tuyen_dung_id' => $ungTuyen->tin_tuyen_dung_id],
        );
    }

    private function notifyEmployerAboutCandidateWithdrawal(UngTuyen $ungTuyen): void
    {
        $ungTuyen->loadMissing(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
        $company = $ungTuyen->tinTuyenDung?->congTy;

        if (!$company) {
            return;
        }

        $candidateName = $ungTuyen->hoSo?->nguoiDung?->ho_ten ?: 'Ứng viên';

        $this->appNotificationService->createForUsers(
            $this->appNotificationService->recruitmentRecipients($company, $ungTuyen->tinTuyenDung?->hr_phu_trach_id),
            'employer_application_withdrawn',
            'Ứng viên đã rút đơn',
            "{$candidateName} đã rút đơn ứng tuyển.",
            '/employer/interviews',
            ['ung_tuyen_id' => $ungTuyen->id],
        );
    }

    private function buildInterviewResponseRedirectUrl(string $status, int $applicationId): string
    {
        $frontEndUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return $frontEndUrl . '/application-action-result?type=interview'
            . '&status=' . urlencode($status)
            . '&application_id=' . urlencode((string) $applicationId);
    }

    private function buildOfferResponseRedirectUrl(string $status, int $applicationId): string
    {
        $frontEndUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return $frontEndUrl . '/application-action-result?type=offer'
            . '&status=' . urlencode($status)
            . '&application_id=' . urlencode((string) $applicationId);
    }
}
