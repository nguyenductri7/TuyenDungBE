<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Models\CongTy;
use App\Models\OnboardingPlan;
use App\Models\OnboardingTask;
use App\Models\UngTuyen;
use App\Services\AppNotificationService;
use App\Services\AuditLogService;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly AuditLogService $auditLogService,
        private readonly AppNotificationService $notificationService,
    ) {
    }

    public function showForEmployer(int $ungTuyenId): JsonResponse
    {
        $company = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $application = $this->findCompanyAcceptedApplication($ungTuyenId, $company);
        $this->abortIfCannotManageApplicationRecord($user, $company, $application);

        $plan = $application->onboardingPlan ?: $this->onboardingService->ensurePlanForAcceptedOffer($application, $user?->id, false);

        return response()->json([
            'success' => true,
            'data' => $this->onboardingService->mapPlan($plan->fresh(['tasks.completedBy', 'hrPhuTrach']), true),
        ]);
    }

    public function updateForEmployer(Request $request, int $ungTuyenId): JsonResponse
    {
        $company = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $application = $this->findCompanyAcceptedApplication($ungTuyenId, $company);
        $this->abortIfCannotManageApplicationRecord($user, $company, $application);

        $data = $request->validate([
            'ngay_bat_dau' => ['nullable', 'date'],
            'dia_diem_lam_viec' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['nullable', Rule::in(OnboardingPlan::TRANG_THAI_LIST)],
            'loi_chao_mung' => ['nullable', 'string', 'max:4000'],
            'ghi_chu_noi_bo' => ['nullable', 'string', 'max:4000'],
            'ghi_chu_ung_vien' => ['nullable', 'string', 'max:4000'],
            'tai_lieu_can_chuan_bi' => ['nullable', 'array'],
            'tai_lieu_can_chuan_bi.*' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = $application->onboardingPlan ?: $this->onboardingService->ensurePlanForAcceptedOffer($application, $user?->id, false);
        $before = $this->onboardingService->mapPlan($plan, true);

        $plan->forceFill([
            'ngay_bat_dau' => $data['ngay_bat_dau'] ?? null,
            'dia_diem_lam_viec' => $data['dia_diem_lam_viec'] ?? null,
            'trang_thai' => $data['trang_thai'] ?? $plan->trang_thai,
            'loi_chao_mung' => $data['loi_chao_mung'] ?? null,
            'ghi_chu_noi_bo' => $data['ghi_chu_noi_bo'] ?? null,
            'ghi_chu_ung_vien' => $data['ghi_chu_ung_vien'] ?? null,
            'tai_lieu_can_chuan_bi_json' => array_values(array_filter($data['tai_lieu_can_chuan_bi'] ?? [])),
            'hoan_tat_luc' => ($data['trang_thai'] ?? null) === OnboardingPlan::TRANG_THAI_HOAN_TAT ? ($plan->hoan_tat_luc ?? now()) : null,
            'updated_by' => $user?->id,
        ])->save();

        $afterPlan = $plan->fresh(['tasks.completedBy', 'hrPhuTrach']);
        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_onboarding_updated',
            description: "Cập nhật onboarding cho đơn ứng tuyển #{$application->id}.",
            target: $application,
            company: $company,
            before: $before,
            after: $this->onboardingService->mapPlan($afterPlan, true),
            metadata: ['scope' => 'onboarding'],
            request: $request,
        );

        $this->notifyCandidatePlanUpdated($afterPlan);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật kế hoạch onboarding.',
            'data' => $this->onboardingService->mapPlan($afterPlan, true),
        ]);
    }

    public function storeTask(Request $request, int $ungTuyenId): JsonResponse
    {
        $company = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $application = $this->findCompanyAcceptedApplication($ungTuyenId, $company);
        $this->abortIfCannotManageApplicationRecord($user, $company, $application);
        $plan = $application->onboardingPlan ?: $this->onboardingService->ensurePlanForAcceptedOffer($application, $user?->id, false);
        $data = $this->validateTask($request);

        $task = $plan->tasks()->create([
            ...$data,
            'thu_tu' => $data['thu_tu'] ?? ((int) $plan->tasks()->max('thu_tu') + 1),
            'trang_thai' => $data['trang_thai'] ?? OnboardingTask::TRANG_THAI_CHO_LAM,
            'hoan_tat_luc' => ($data['trang_thai'] ?? null) === OnboardingTask::TRANG_THAI_HOAN_TAT ? now() : null,
            'completed_by' => ($data['trang_thai'] ?? null) === OnboardingTask::TRANG_THAI_HOAN_TAT ? $user?->id : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm checklist onboarding.',
            'data' => $this->onboardingService->mapPlan($this->onboardingService->syncPlanCompletion($plan), true),
            'task' => $this->onboardingService->mapTask($task->fresh('completedBy')),
        ], 201);
    }

    public function updateTask(Request $request, int $ungTuyenId, int $taskId): JsonResponse
    {
        $company = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $application = $this->findCompanyAcceptedApplication($ungTuyenId, $company);
        $this->abortIfCannotManageApplicationRecord($user, $company, $application);
        $plan = $application->onboardingPlan ?: $this->onboardingService->ensurePlanForAcceptedOffer($application, $user?->id, false);
        $task = $plan->tasks()->findOrFail($taskId);
        $data = $this->validateTask($request, true);

        $task->forceFill([
            ...$data,
            'hoan_tat_luc' => ($data['trang_thai'] ?? $task->trang_thai) === OnboardingTask::TRANG_THAI_HOAN_TAT ? ($task->hoan_tat_luc ?? now()) : null,
            'completed_by' => ($data['trang_thai'] ?? $task->trang_thai) === OnboardingTask::TRANG_THAI_HOAN_TAT ? ($task->completed_by ?? $user?->id) : null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật checklist onboarding.',
            'data' => $this->onboardingService->mapPlan($this->onboardingService->syncPlanCompletion($plan), true),
        ]);
    }

    public function destroyTask(int $ungTuyenId, int $taskId): JsonResponse
    {
        $company = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();
        $application = $this->findCompanyAcceptedApplication($ungTuyenId, $company);
        $this->abortIfCannotManageApplicationRecord($user, $company, $application);
        $plan = $application->onboardingPlan ?: $this->onboardingService->ensurePlanForAcceptedOffer($application, $user?->id, false);
        $plan->tasks()->findOrFail($taskId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa checklist onboarding.',
            'data' => $this->onboardingService->mapPlan($this->onboardingService->syncPlanCompletion($plan), true),
        ]);
    }

    public function showForCandidate(Request $request, int $ungTuyenId): JsonResponse
    {
        $application = $this->findCandidateAcceptedApplication($request, $ungTuyenId);
        $plan = $application->onboardingPlan;

        return response()->json([
            'success' => true,
            'data' => $plan ? $this->onboardingService->mapPlan($plan->fresh(['tasks.completedBy', 'hrPhuTrach']), false) : null,
        ]);
    }

    public function updateCandidateTask(Request $request, int $ungTuyenId, int $taskId): JsonResponse
    {
        $application = $this->findCandidateAcceptedApplication($request, $ungTuyenId);
        $plan = $application->onboardingPlan;

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Checklist onboarding chưa được tạo.'], 404);
        }

        $data = $request->validate([
            'trang_thai' => ['required', Rule::in([
                OnboardingTask::TRANG_THAI_CHO_LAM,
                OnboardingTask::TRANG_THAI_DANG_LAM,
                OnboardingTask::TRANG_THAI_HOAN_TAT,
            ])],
        ]);

        $task = $plan->tasks()
            ->where('nguoi_phu_trach', OnboardingTask::NGUOI_PHU_TRACH_UNG_VIEN)
            ->findOrFail($taskId);

        $task->forceFill([
            'trang_thai' => $data['trang_thai'],
            'hoan_tat_luc' => $data['trang_thai'] === OnboardingTask::TRANG_THAI_HOAN_TAT ? ($task->hoan_tat_luc ?? now()) : null,
            'completed_by' => $data['trang_thai'] === OnboardingTask::TRANG_THAI_HOAN_TAT ? $request->user()->id : null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật checklist onboarding.',
            'data' => $this->onboardingService->mapPlan($this->onboardingService->syncPlanCompletion($plan), false),
        ]);
    }

    private function findCompanyAcceptedApplication(int $id, ?CongTy $company): UngTuyen
    {
        abort_if(!$company, 403, 'Vui lòng thiết lập thông tin công ty trước.');

        $application = UngTuyen::with(['tinTuyenDung.congTy', 'hoSo.nguoiDung', 'onboardingPlan.tasks.completedBy', 'onboardingPlan.hrPhuTrach'])
            ->whereHas('tinTuyenDung', fn ($query) => $query->where('cong_ty_id', $company->id))
            ->findOrFail($id);

        abort_if((int) $application->trang_thai_offer !== UngTuyen::OFFER_DA_CHAP_NHAN, 422, 'Chỉ tạo onboarding sau khi ứng viên chấp nhận offer.');

        return $application;
    }

    private function findCandidateAcceptedApplication(Request $request, int $id): UngTuyen
    {
        $application = UngTuyen::with(['tinTuyenDung.congTy', 'hoSo.nguoiDung', 'onboardingPlan.tasks.completedBy', 'onboardingPlan.hrPhuTrach'])
            ->whereHas('hoSo', fn ($query) => $query->withTrashed()->where('nguoi_dung_id', $request->user()->id))
            ->findOrFail($id);

        abort_if((int) $application->trang_thai_offer !== UngTuyen::OFFER_DA_CHAP_NHAN, 403, 'Onboarding chỉ khả dụng sau khi bạn chấp nhận offer.');

        return $application;
    }

    private function validateTask(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'tieu_de' => [$required, 'string', 'max:180'],
            'mo_ta' => ['nullable', 'string', 'max:4000'],
            'han_hoan_tat' => ['nullable', 'date'],
            'nguoi_phu_trach' => [$partial ? 'sometimes' : 'required', Rule::in(OnboardingTask::NGUOI_PHU_TRACH_LIST)],
            'trang_thai' => ['nullable', Rule::in(OnboardingTask::TRANG_THAI_LIST)],
            'thu_tu' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);
    }

    private function notifyCandidatePlanUpdated(OnboardingPlan $plan): void
    {
        $this->notificationService->createForUser(
            $plan->nguoi_dung_id,
            'candidate_onboarding_updated',
            'Checklist onboarding vừa được cập nhật',
            'HR đã cập nhật thông tin chuẩn bị nhận việc của bạn.',
            '/applications',
            [
                'source' => 'onboarding',
                'onboarding_plan_id' => $plan->id,
                'ung_tuyen_id' => $plan->ung_tuyen_id,
            ],
        );
    }
}
