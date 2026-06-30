<?php

namespace App\Http\Controllers\Api;

use App\Events\FollowedCompanyJobActivated;
use App\Exceptions\BillingException;
use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\TinTuyenDung\TaoTinTuyenDungRequest;
use App\Http\Requests\TinTuyenDung\CapNhatTinTuyenDungRequest;
use App\Models\CongTy;
use App\Models\TinTuyenDung;
use App\Services\AppNotificationService;
use App\Services\AuditLogService;
use App\Services\Billing\FeatureAccessService;
use App\Support\EncodedId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * NhaTuyenDungTinTuyenDungController - Quyền: Nhà Tuyển Dụng
 */
class NhaTuyenDungTinTuyenDungController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly AppNotificationService $appNotificationService,
        private readonly FeatureAccessService $featureAccessService,
    ) {
    }

    private function jobAuditSnapshot(TinTuyenDung $tin): array
    {
        return $tin->only([
            'id',
            'cong_ty_id',
            'tieu_de',
            'trang_thai',
            'hr_phu_trach_id',
            'ngay_het_han',
            'so_luong_tuyen',
            'muc_luong_tu',
            'muc_luong_den',
            'don_vi_luong',
            'dia_diem_lam_viec',
            'featured_activated_at',
            'featured_until',
        ]);
    }

    private function decodeRouteId(int|string $id): int
    {
        return EncodedId::decodeOrFail($id);
    }

    private function featuredJobOptions(): array
    {
        return (array) config('billing.featured_job_options', []);
    }

    private function resolveFeaturedOption(string $featureCode): ?array
    {
        $option = $this->featuredJobOptions()[$featureCode] ?? null;

        if (!is_array($option) || (int) ($option['days'] ?? 0) <= 0) {
            return null;
        }

        return $option;
    }

    private function normalizeSalaryPayload(array $data): array
    {
        $salaryFrom = array_key_exists('muc_luong_tu', $data) ? $data['muc_luong_tu'] : null;
        $salaryTo = array_key_exists('muc_luong_den', $data) ? $data['muc_luong_den'] : null;

        if ($salaryFrom !== null && $salaryTo !== null) {
            $data['don_vi_luong'] = $data['don_vi_luong'] ?? 'VND/tháng';
        } elseif ($salaryFrom !== null) {
            $data['don_vi_luong'] = $data['don_vi_luong'] ?? 'VND/tháng';
        } elseif (array_key_exists('muc_luong_tu', $data) && array_key_exists('muc_luong_den', $data)) {
            $data['don_vi_luong'] = $data['don_vi_luong'] ?? 'VND/tháng';
        }

        return $data;
    }

    private function resolveValidHrPhuTrachId(?int $memberId, CongTy $congTy, int $fallbackUserId): int
    {
        if (!$memberId) {
            return $fallbackUserId;
        }

        $exists = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->exists();

        return $exists ? $memberId : $fallbackUserId;
    }

    private function isPubliclyActive(TinTuyenDung $tin): bool
    {
        $tin->loadMissing('congTy:id,trang_thai');

        if ((int) $tin->trang_thai !== TinTuyenDung::TRANG_THAI_HOAT_DONG) {
            return false;
        }

        if ((int) optional($tin->congTy)->trang_thai !== CongTy::TRANG_THAI_HOAT_DONG) {
            return false;
        }

        return !$tin->ngay_het_han || $tin->ngay_het_han->greaterThanOrEqualTo(now());
    }

    private function broadcastJobActivityIfNeeded(TinTuyenDung $tin, bool $wasPubliclyActive = false): void
    {
        if ($wasPubliclyActive || !$this->isPubliclyActive($tin)) {
            return;
        }

        $activityType = $tin->published_at
            ? FollowedCompanyJobActivated::TYPE_REOPENED
            : FollowedCompanyJobActivated::TYPE_PUBLISHED;

        $tin->forceFill([
            'published_at' => $activityType === FollowedCompanyJobActivated::TYPE_PUBLISHED
                ? ($tin->published_at ?? now())
                : $tin->published_at,
            'reactivated_at' => $activityType === FollowedCompanyJobActivated::TYPE_REOPENED
                ? now()
                : null,
        ])->save();

        $event = FollowedCompanyJobActivated::fromJob($tin->fresh(), $activityType);

        if (!$event) {
            return;
        }

        try {
            broadcast($event);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $recipientIds = $event->recipientIds();

        if (!$recipientIds) {
            return;
        }

        $payload = $event->notificationPayload();
        $job = $payload['job'] ?? [];
        $company = $payload['company'] ?? [];

        $encodedJobId = isset($job['encoded_id'])
            ? (string) $job['encoded_id']
            : (isset($job['id']) ? EncodedId::encode((int) $job['id']) : null);

        $this->appNotificationService->createForUsers(
            $recipientIds,
            (string) ($payload['type'] ?? 'followed_company_job'),
            (string) (($payload['activity_type'] ?? '') === FollowedCompanyJobActivated::TYPE_REOPENED
                ? 'Công ty bạn theo dõi vừa mở lại tin tuyển dụng'
                : 'Công ty bạn theo dõi vừa đăng tin tuyển dụng mới'),
            (string) ($payload['message'] ?? 'Công ty bạn theo dõi vừa có cập nhật tuyển dụng.'),
            $encodedJobId ? "/jobs/{$encodedJobId}" : '/followed-companies',
            [
                'company' => $company,
                'job' => $job,
                'source' => 'followed_company_job_activity',
            ],
        );
    }

    /**
     * Lấy ID công ty của NTD đang đăng nhập.
     */
    private function getCongTyId(): ?int
    {
        return $this->getCurrentEmployerCompany()?->id;
    }

    /**
     * Danh sách tin của công ty NTD.
     */
    public function index(Request $request): JsonResponse
    {
        $congTyId = $this->getCongTyId();
        if (!$congTyId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tạo cấu hình công ty. Vui lòng tạo tài khoản doanh nghiệp trước.',
            ], 404);
        }

        $query = TinTuyenDung::with(['nganhNghes:id,ten_nganh', 'hrPhuTrach:id,ho_ten,email'])
            ->withCount([
                'acceptedApplications as so_luong_da_nhan',
                'ungTuyens as tong_ung_tuyen_thuc_te' => fn ($query) => $query->whereNotNull('thoi_gian_ung_tuyen'),
            ])
            ->where('cong_ty_id', $congTyId);

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('tieu_de', 'like', "%{$search}%");
        }

        if ($request->filled('hr_phu_trach_id')) {
            $hrPhuTrachId = $request->input('hr_phu_trach_id') === 'me'
                ? (int) auth()->id()
                : (int) $request->input('hr_phu_trach_id');

            $query->where('hr_phu_trach_id', $hrPhuTrachId);
        }

        $data = $query
            ->orderFeaturedFirst()
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Tạo tin tuyển dụng mới.
     */
    public function store(TaoTinTuyenDungRequest $request): JsonResponse
    {
        $congTyId = $this->getCongTyId();
        if (!$congTyId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần thiết lập thông tin công ty trước khi tạo tin.',
            ], 403);
        }

        $data = $this->normalizeSalaryPayload($request->validated());
        $nganhNgheIds = $data['nganh_nghes'];
        unset($data['nganh_nghes']);

        $data['cong_ty_id'] = $congTyId;
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$this->coTheQuanLyTatCaTinTuyenDung($user, $congTy)) {
            $data['hr_phu_trach_id'] = (int) auth()->id();
        } else {
            $data['hr_phu_trach_id'] = $this->resolveValidHrPhuTrachId(
                isset($data['hr_phu_trach_id']) ? (int) $data['hr_phu_trach_id'] : null,
                $congTy,
                (int) auth()->id(),
            );
        }

        $tin = TinTuyenDung::create($data);
        $tin->nganhNghes()->attach($nganhNgheIds);
        $tin->load(['nganhNghes:id,ten_nganh', 'hrPhuTrach:id,ho_ten,email']);

        $this->broadcastJobActivityIfNeeded($tin);
        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_job_created',
            description: "Tạo tin tuyển dụng {$tin->tieu_de}.",
            target: $tin,
            company: $congTy,
            after: [
                ...$this->jobAuditSnapshot($tin),
                'nganh_nghe_ids' => $nganhNgheIds,
            ],
            metadata: ['scope' => 'employer_job'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo tin tuyển dụng thành công.',
            'data' => $tin,
        ], 201);
    }

    /**
     * Cập nhật tin tuyển dụng.
     */
    public function update(CapNhatTinTuyenDungRequest $request, string $id): JsonResponse
    {
        $decodedId = $this->decodeRouteId($id);
        $congTyId = $this->getCongTyId();
        $tin = TinTuyenDung::where('cong_ty_id', $congTyId)->findOrFail($decodedId);
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $this->abortIfCannotManageJobRecord($user, $congTy, $tin);
        $wasPubliclyActive = $this->isPubliclyActive($tin);

        $data = $this->normalizeSalaryPayload($request->validated());
        $before = [
            ...$this->jobAuditSnapshot($tin),
            'nganh_nghe_ids' => $tin->nganhNghes()->pluck('nganh_nghes.id')->all(),
        ];
        $nganhNgheIds = $data['nganh_nghes'] ?? null;

        if (isset($data['nganh_nghes'])) {
            $tin->nganhNghes()->sync($data['nganh_nghes']);
            unset($data['nganh_nghes']);
        }

        if (array_key_exists('hr_phu_trach_id', $data)) {
            if (!$this->coTheQuanLyTatCaTinTuyenDung($user, $congTy)) {
                $data['hr_phu_trach_id'] = (int) auth()->id();
            } else {
                $data['hr_phu_trach_id'] = $this->resolveValidHrPhuTrachId(
                    $data['hr_phu_trach_id'] ? (int) $data['hr_phu_trach_id'] : null,
                    $congTy,
                    (int) auth()->id(),
                );
            }
        }

        $tin->update($data);
        $tin = $tin->fresh()->load(['nganhNghes:id,ten_nganh', 'hrPhuTrach:id,ho_ten,email']);

        $this->broadcastJobActivityIfNeeded($tin, $wasPubliclyActive);
        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_job_updated',
            description: "Cập nhật tin tuyển dụng {$tin->tieu_de}.",
            target: $tin,
            company: $congTy,
            before: $before,
            after: [
                ...$this->jobAuditSnapshot($tin),
                'nganh_nghe_ids' => $nganhNgheIds ?? $tin->nganhNghes()->pluck('nganh_nghes.id')->all(),
            ],
            metadata: ['scope' => 'employer_job'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tin thành công.',
            'data' => $tin,
        ]);
    }

    /**
     * Xem chi tiết tin.
     */
    public function show(string $id): JsonResponse
    {
        $decodedId = $this->decodeRouteId($id);
        $congTyId = $this->getCongTyId();
        $tin = TinTuyenDung::with([
                'nganhNghes:id,ten_nganh',
                'hrPhuTrach:id,ho_ten,email',
                'parsing:id,tin_tuyen_dung_id,parsed_skills_json,parsed_requirements_json,parsed_benefits_json,parsed_salary_json,parsed_location_json,parse_status,parser_version,confidence_score,error_message,updated_at',
                'kyNangYeuCaus.kyNang:id,ten_ky_nang,icon',
            ])
            ->withCount([
                'acceptedApplications as so_luong_da_nhan',
                'ungTuyens as tong_ung_tuyen_thuc_te' => fn ($query) => $query->whereNotNull('thoi_gian_ung_tuyen'),
                'ungTuyens as tong_ho_so',
                'ungTuyens as ho_so_dang_cho' => fn ($query) => $query->where('trang_thai', \App\Models\UngTuyen::TRANG_THAI_CHO_DUYET),
                'ungTuyens as ho_so_da_xem' => fn ($query) => $query->where('trang_thai', \App\Models\UngTuyen::TRANG_THAI_DA_XEM),
                'ungTuyens as ho_so_phong_van' => fn ($query) => $query->where('trang_thai', \App\Models\UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN),
                'ungTuyens as ho_so_qua_phong_van' => fn ($query) => $query->where('trang_thai', \App\Models\UngTuyen::TRANG_THAI_QUA_PHONG_VAN),
                'ungTuyens as ho_so_da_nhan' => fn ($query) => $query
                    ->where('trang_thai', \App\Models\UngTuyen::TRANG_THAI_TRUNG_TUYEN)
                    ->where('trang_thai_offer', \App\Models\UngTuyen::OFFER_DA_CHAP_NHAN),
                'ungTuyens as ho_so_tu_choi' => fn ($query) => $query->where('trang_thai', \App\Models\UngTuyen::TRANG_THAI_TU_CHOI),
            ])
            ->where('cong_ty_id', $congTyId)
            ->findOrFail($decodedId);

        return response()->json([
            'success' => true,
            'data' => $tin,
        ]);
    }

    public function sponsor(Request $request, string $id): JsonResponse
    {
        $decodedId = $this->decodeRouteId($id);
        if (!TinTuyenDung::supportsFeaturedListing()) {
            return response()->json([
                'success' => false,
                'code' => 'FEATURED_LISTING_UNAVAILABLE',
                'message' => 'Môi trường hiện tại chưa hoàn tất migration featured listing. Vui lòng chạy migrate trước khi kích hoạt.',
            ], 503);
        }

        $validated = $request->validate([
            'feature_code' => ['required', 'string'],
        ]);

        $congTyId = $this->getCongTyId();
        $tin = TinTuyenDung::where('cong_ty_id', $congTyId)->findOrFail($decodedId);
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $this->abortIfCannotManageJobRecord($user, $congTy, $tin);

        $featureCode = (string) $validated['feature_code'];
        $option = $this->resolveFeaturedOption($featureCode);

        if (!$option) {
            return response()->json([
                'success' => false,
                'message' => 'Gói featured job không hợp lệ.',
            ], 422);
        }

        if ((int) $tin->trang_thai !== TinTuyenDung::TRANG_THAI_HOAT_DONG) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể đẩy nổi bật cho tin đang hoạt động.',
            ], 422);
        }

        if ($tin->ngay_het_han && $tin->ngay_het_han->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng đã hết hạn nên không thể đẩy nổi bật.',
            ], 422);
        }

        $usage = null;
        $before = $this->jobAuditSnapshot($tin);

        try {
            $usage = $this->featureAccessService->beginUsage(
                $user,
                $featureCode,
                'tin_tuyen_dung',
                $tin->id,
                [
                    'scope' => 'featured_job',
                    'cong_ty_id' => $congTy?->id,
                    'duration_days' => (int) $option['days'],
                ],
                $request->header('X-Idempotency-Key'),
            );

            $featuredActivatedAt = now();
            $featuredBase = $tin->featured_until && $tin->featured_until->isFuture()
                ? $tin->featured_until->copy()
                : $featuredActivatedAt->copy();
            $featuredUntil = $featuredBase->copy()->addDays((int) $option['days']);

            $tin->forceFill([
                'featured_activated_at' => $featuredActivatedAt,
                'featured_until' => $featuredUntil,
            ])->save();

            $tin = $tin->fresh([
                'nganhNghes:id,ten_nganh',
                'hrPhuTrach:id,ho_ten,email',
            ]);

            $this->auditLogService->logModelAction(
                actor: $user,
                action: 'employer_job_featured_purchased',
                description: "Kích hoạt gói nổi bật cho tin tuyển dụng {$tin->tieu_de}.",
                target: $tin,
                company: $congTy,
                before: $before,
                after: $this->jobAuditSnapshot($tin),
                metadata: [
                    'scope' => 'employer_job_featured',
                    'feature_code' => $featureCode,
                    'duration_days' => (int) $option['days'],
                    'featured_until' => $featuredUntil->toISOString(),
                ],
                request: $request,
            );

            $usage = $this->featureAccessService->commitUsage($usage, [
                'scope' => 'featured_job',
                'duration_days' => (int) $option['days'],
                'featured_until' => $featuredUntil->toISOString(),
            ]);
        } catch (BillingException $exception) {
            if ($usage) {
                $this->featureAccessService->failUsage($usage, $exception->getMessage(), [
                    'scope' => 'featured_job',
                    'feature_code' => $featureCode,
                ]);
            }

            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        } catch (Throwable $exception) {
            if ($usage) {
                $this->featureAccessService->failUsage($usage, $exception->getMessage(), [
                    'scope' => 'featured_job',
                    'feature_code' => $featureCode,
                ]);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã kích hoạt featured listing cho tin tuyển dụng.',
            'data' => [
                'job' => $tin,
                'billing' => [
                    'feature_code' => $featureCode,
                    'usage_id' => $usage?->id,
                    'featured_until' => $tin->featured_until?->toISOString(),
                    'duration_days' => (int) $option['days'],
                ],
            ],
        ]);
    }

    /**
     * Bật / tắt hiển thị (đổi trạng thái).
     */
    public function doiTrangThai(string $id): JsonResponse
    {
        $decodedId = $this->decodeRouteId($id);
        $congTyId = $this->getCongTyId();
        $tin = TinTuyenDung::where('cong_ty_id', $congTyId)->findOrFail($decodedId);
        $user = $this->getAuthenticatedEmployer();
        $congTy = $this->getCurrentEmployerCompany();
        $this->abortIfCannotManageJobRecord($user, $congTy, $tin);
        $wasPubliclyActive = $this->isPubliclyActive($tin);
        $before = $this->jobAuditSnapshot($tin);

        $tin->trang_thai = $tin->trang_thai == 1 ? 0 : 1;
        $tin->save();
        $tin = $tin->fresh();

        $this->broadcastJobActivityIfNeeded($tin, $wasPubliclyActive);
        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_job_status_toggled',
            description: "Đổi trạng thái tin tuyển dụng {$tin->tieu_de}.",
            target: $tin,
            company: $congTy,
            before: $before,
            after: $this->jobAuditSnapshot($tin),
            metadata: ['scope' => 'employer_job'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Chuyển trạng thái thành công.',
            'data' => $tin,
        ]);
    }

    /**
     * Xoá tin.
     */
    public function destroy(string $id): JsonResponse
    {
        $decodedId = $this->decodeRouteId($id);
        $congTyId = $this->getCongTyId();
        $tin = TinTuyenDung::where('cong_ty_id', $congTyId)->findOrFail($decodedId);
        $user = $this->getAuthenticatedEmployer();
        $congTy = $this->getCurrentEmployerCompany();
        $this->abortIfCannotManageJobRecord($user, $congTy, $tin);

        if ($tin->ungTuyens()->whereNotNull('thoi_gian_ung_tuyen')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tin tuyển dụng này đã có đơn ứng tuyển. Bạn chỉ có thể tạm ngưng thay vì xóa.',
            ], 422);
        }

        $before = $this->jobAuditSnapshot($tin);
        $tin->delete();
        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_job_deleted',
            description: "Xóa tin tuyển dụng {$before['tieu_de']}.",
            target: $tin,
            company: $congTy,
            before: $before,
            metadata: ['scope' => 'employer_job'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Xoá tin tuyển dụng thành công.',
        ]);
    }
}
