<?php

namespace App\Services;

use App\Models\OnboardingPlan;
use App\Models\OnboardingTask;
use App\Models\UngTuyen;
class OnboardingService
{
    public function __construct(private readonly AppNotificationService $notificationService)
    {
    }

    public function ensurePlanForAcceptedOffer(UngTuyen $application, ?int $actorId = null, bool $notify = true): ?OnboardingPlan
    {
        $application->loadMissing(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);

        $candidate = $application->hoSo?->nguoiDung;
        $job = $application->tinTuyenDung;
        $company = $job?->congTy;

        if (!$candidate || !$job || !$company) {
            return null;
        }

        $plan = OnboardingPlan::firstOrCreate(
            ['ung_tuyen_id' => (int) $application->id],
            [
                'cong_ty_id' => (int) $company->id,
                'nguoi_dung_id' => (int) $candidate->id,
                'hr_phu_trach_id' => $application->tinTuyenDung?->hr_phu_trach_id,
                'ngay_bat_dau' => now('Asia/Ho_Chi_Minh')->addDays(14)->toDateString(),
                'dia_diem_lam_viec' => $job->dia_diem_lam_viec,
                'trang_thai' => OnboardingPlan::TRANG_THAI_DANG_CHUAN_BI,
                'loi_chao_mung' => "Chào mừng bạn đến với {$company->ten_cong_ty}. HR sẽ cập nhật các bước chuẩn bị nhận việc tại đây.",
                'tai_lieu_can_chuan_bi_json' => $this->defaultDocuments(),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        if ($plan->wasRecentlyCreated) {
            $this->createDefaultTasks($plan);
        }

        if ($notify) {
            $this->notifyCandidate($plan->fresh(['ungTuyen.tinTuyenDung.congTy']));
        }

        return $plan->fresh(['tasks', 'ungTuyen.tinTuyenDung.congTy', 'hrPhuTrach']);
    }

    public function syncPlanCompletion(OnboardingPlan $plan): OnboardingPlan
    {
        $plan->loadMissing('tasks');
        $activeTasks = $plan->tasks->where('trang_thai', '!=', OnboardingTask::TRANG_THAI_BO_QUA);

        if ($activeTasks->isNotEmpty() && $activeTasks->every(fn (OnboardingTask $task) => $task->trang_thai === OnboardingTask::TRANG_THAI_HOAN_TAT)) {
            $plan->forceFill([
                'trang_thai' => OnboardingPlan::TRANG_THAI_HOAN_TAT,
                'hoan_tat_luc' => $plan->hoan_tat_luc ?? now(),
            ])->save();
        } elseif ($plan->trang_thai === OnboardingPlan::TRANG_THAI_HOAN_TAT) {
            $plan->forceFill([
                'trang_thai' => OnboardingPlan::TRANG_THAI_DANG_THUC_HIEN,
                'hoan_tat_luc' => null,
            ])->save();
        }

        return $plan->fresh(['tasks', 'hrPhuTrach']);
    }

    public function mapPlan(OnboardingPlan $plan, bool $includeInternal = false): array
    {
        $plan->loadMissing(['tasks.completedBy:id,ho_ten,email', 'hrPhuTrach:id,ho_ten,email']);
        $tasks = $plan->tasks ?? collect();
        $activeTasks = $tasks->where('trang_thai', '!=', OnboardingTask::TRANG_THAI_BO_QUA);
        $doneCount = $activeTasks->where('trang_thai', OnboardingTask::TRANG_THAI_HOAN_TAT)->count();
        $totalCount = $activeTasks->count();

        return [
            'id' => $plan->id,
            'ung_tuyen_id' => $plan->ung_tuyen_id,
            'cong_ty_id' => $plan->cong_ty_id,
            'nguoi_dung_id' => $plan->nguoi_dung_id,
            'hr_phu_trach_id' => $plan->hr_phu_trach_id,
            'ngay_bat_dau' => optional($plan->ngay_bat_dau)->toDateString(),
            'dia_diem_lam_viec' => $plan->dia_diem_lam_viec,
            'trang_thai' => $plan->trang_thai,
            'loi_chao_mung' => $plan->loi_chao_mung,
            'ghi_chu_ung_vien' => $plan->ghi_chu_ung_vien,
            'ghi_chu_noi_bo' => $includeInternal ? $plan->ghi_chu_noi_bo : null,
            'tai_lieu_can_chuan_bi' => $plan->tai_lieu_can_chuan_bi_json ?: [],
            'hoan_tat_luc' => optional($plan->hoan_tat_luc)->toISOString(),
            'progress' => [
                'done' => $doneCount,
                'total' => $totalCount,
                'percent' => $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0,
            ],
            'hr_phu_trach' => $plan->hrPhuTrach ? [
                'id' => $plan->hrPhuTrach->id,
                'ho_ten' => $plan->hrPhuTrach->ho_ten,
                'email' => $plan->hrPhuTrach->email,
            ] : null,
            'tasks' => $tasks->map(fn (OnboardingTask $task) => $this->mapTask($task))->values()->all(),
        ];
    }

    public function mapTask(OnboardingTask $task): array
    {
        return [
            'id' => $task->id,
            'onboarding_plan_id' => $task->onboarding_plan_id,
            'tieu_de' => $task->tieu_de,
            'mo_ta' => $task->mo_ta,
            'han_hoan_tat' => optional($task->han_hoan_tat)->toDateString(),
            'nguoi_phu_trach' => $task->nguoi_phu_trach,
            'trang_thai' => $task->trang_thai,
            'thu_tu' => $task->thu_tu,
            'hoan_tat_luc' => optional($task->hoan_tat_luc)->toISOString(),
            'completed_by' => $task->completedBy ? [
                'id' => $task->completedBy->id,
                'ho_ten' => $task->completedBy->ho_ten,
                'email' => $task->completedBy->email,
            ] : null,
        ];
    }

    private function createDefaultTasks(OnboardingPlan $plan): void
    {
        collect([
            ['tieu_de' => 'Xác nhận ngày bắt đầu làm việc', 'nguoi_phu_trach' => 'candidate'],
            ['tieu_de' => 'Chuẩn bị giấy tờ cá nhân', 'nguoi_phu_trach' => 'candidate', 'mo_ta' => 'CCCD/CMND, thông tin tài khoản ngân hàng và bằng cấp liên quan.'],
            ['tieu_de' => 'HR xác nhận hợp đồng và thông tin nhận việc', 'nguoi_phu_trach' => 'hr'],
            ['tieu_de' => 'Thiết lập tài khoản/công cụ làm việc', 'nguoi_phu_trach' => 'hr'],
            ['tieu_de' => 'Hoàn tất buổi nhận việc đầu tiên', 'nguoi_phu_trach' => 'hr'],
        ])->each(function (array $task, int $index) use ($plan) {
            $plan->tasks()->create([
                ...$task,
                'trang_thai' => OnboardingTask::TRANG_THAI_CHO_LAM,
                'thu_tu' => $index + 1,
            ]);
        });
    }

    private function defaultDocuments(): array
    {
        return [
            'CCCD/CMND bản scan hoặc bản photo',
            'Thông tin tài khoản ngân hàng',
            'Bằng cấp/chứng chỉ liên quan nếu có',
            'Ảnh chân dung hoặc hồ sơ cá nhân theo yêu cầu công ty',
        ];
    }

    private function notifyCandidate(OnboardingPlan $plan): void
    {
        $plan->loadMissing(['ungTuyen.tinTuyenDung.congTy']);
        $job = $plan->ungTuyen?->tinTuyenDung;
        $company = $job?->congTy;

        $this->notificationService->createForUser(
            $plan->nguoi_dung_id,
            'candidate_onboarding_started',
            'Checklist onboarding đã sẵn sàng',
            "Bạn đã có checklist chuẩn bị nhận việc cho vị trí {$job?->tieu_de}.",
            '/applications',
            [
                'source' => 'onboarding',
                'onboarding_plan_id' => $plan->id,
                'ung_tuyen_id' => $plan->ung_tuyen_id,
                'company' => $company ? ['id' => $company->id, 'name' => $company->ten_cong_ty] : null,
                'job' => $job ? ['id' => $job->id, 'title' => $job->tieu_de] : null,
            ],
        );
    }
}
