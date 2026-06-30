<?php

namespace App\Services;

use App\Models\InterviewRound;
use App\Models\OnboardingPlan;
use App\Models\UngTuyen;
use Illuminate\Support\Collection;

class ApplicationTimelineService
{
    public function build(UngTuyen $application, bool $includeInternalRounds = true): array
    {
        $items = collect();

        $this->pushApplicationMilestones($items, $application, $includeInternalRounds);
        $this->pushInterviewMilestones($items, $application, $includeInternalRounds);
        $this->pushOfferMilestones($items, $application);
        $this->pushOnboardingMilestones($items, $application);

        return $items
            ->sortBy(fn (array $item) => sprintf(
                '%03d|%s',
                $item['stage_order'] ?? 999,
                $item['sort_at'] ?? $item['occurred_at'] ?? $item['scheduled_at'] ?? $application->created_at?->toISOString()
            ))
            ->values()
            ->map(function (array $item, int $index): array {
                unset($item['sort_at']);
                unset($item['stage_order']);
                $item['order'] = $index + 1;

                return $item;
            })
            ->all();
    }

    private function pushApplicationMilestones(Collection $items, UngTuyen $application, bool $includeInternalNotes): void
    {
        $items->push([
            'key' => 'application_submitted',
            'group' => 'application',
            'title' => 'Ứng viên nộp hồ sơ',
            'description' => $application->hoSo?->tieu_de_ho_so
                ? 'Hồ sơ sử dụng: ' . $application->hoSo->tieu_de_ho_so
                : 'Hồ sơ đã được gửi tới nhà tuyển dụng.',
            'status' => 'completed',
            'occurred_at' => $application->thoi_gian_ung_tuyen?->toISOString(),
            'icon' => 'send',
            'stage_order' => 10,
            'sort_at' => $application->thoi_gian_ung_tuyen?->toISOString() ?: $application->created_at?->toISOString(),
        ]);

        if ($application->da_rut_don) {
            $items->push([
                'key' => 'application_withdrawn',
                'group' => 'application',
                'title' => 'Ứng viên đã rút đơn',
                'description' => 'Quy trình ứng tuyển đã dừng theo yêu cầu của ứng viên.',
                'status' => 'cancelled',
                'occurred_at' => $application->thoi_gian_rut_don?->toISOString(),
                'icon' => 'undo',
                'stage_order' => 99,
                'sort_at' => $application->thoi_gian_rut_don?->toISOString() ?: $application->updated_at?->toISOString(),
            ]);
        }

        if ((int) $application->trang_thai === UngTuyen::TRANG_THAI_DA_XEM) {
            $items->push([
                'key' => 'application_reviewed',
                'group' => 'application',
                'title' => 'Nhà tuyển dụng đã xem hồ sơ',
                'description' => 'Hồ sơ đã qua bước sàng lọc ban đầu.',
                'status' => 'completed',
                'occurred_at' => $application->updated_at?->toISOString(),
                'icon' => 'visibility',
                'stage_order' => 20,
                'sort_at' => $application->updated_at?->toISOString(),
            ]);
        }

        if (in_array((int) $application->trang_thai, [UngTuyen::TRANG_THAI_QUA_PHONG_VAN, UngTuyen::TRANG_THAI_TRUNG_TUYEN, UngTuyen::TRANG_THAI_TU_CHOI], true)) {
            $items->push([
                'key' => 'application_final_status',
                'group' => 'application',
                'title' => $this->applicationStatusLabel((int) $application->trang_thai),
                'description' => $includeInternalNotes && $application->ghi_chu
                    ? $application->ghi_chu
                    : 'Trạng thái ứng tuyển đã được cập nhật.',
                'status' => (int) $application->trang_thai === UngTuyen::TRANG_THAI_TU_CHOI ? 'cancelled' : 'completed',
                'occurred_at' => $application->updated_at?->toISOString(),
                'icon' => (int) $application->trang_thai === UngTuyen::TRANG_THAI_TU_CHOI ? 'cancel' : 'task_alt',
                'stage_order' => 50,
                'sort_at' => $application->updated_at?->toISOString(),
            ]);
        }
    }

    private function pushInterviewMilestones(Collection $items, UngTuyen $application, bool $includeInternalRounds): void
    {
        $rounds = $application->relationLoaded('interviewRounds')
            ? $application->interviewRounds
            : $application->interviewRounds()->get();

        if (!$includeInternalRounds) {
            $rounds = $rounds
                ->filter(fn (InterviewRound $round) => $round->loai_vong !== InterviewRound::LOAI_HR)
                ->values();
        }

        if ($rounds->isEmpty() && $application->ngay_hen_phong_van) {
            $items->push([
                'key' => 'legacy_interview',
                'group' => 'interview',
                'title' => 'Lịch phỏng vấn tổng',
                'description' => trim(implode(' • ', array_filter([
                    $this->interviewModeLabel($application->hinh_thuc_phong_van),
                    $application->ten_nguoi_phong_van ? 'Người phỏng vấn: ' . $application->ten_nguoi_phong_van : null,
                    $application->ket_qua_phong_van ? 'Kết quả: ' . $application->ket_qua_phong_van : null,
                ]))),
                'status' => $this->interviewAttendanceStatus($application->trang_thai_tham_gia_phong_van),
                'scheduled_at' => $application->ngay_hen_phong_van?->toISOString(),
                'occurred_at' => $application->thoi_gian_phan_hoi_phong_van?->toISOString(),
                'icon' => 'event',
                'stage_order' => 30,
                'sort_at' => $application->ngay_hen_phong_van?->toISOString(),
            ]);
        }

        foreach ($rounds as $round) {
            $items->push([
                'key' => 'interview_round_' . $round->id,
                'group' => 'interview',
                'source_id' => $round->id,
                'title' => sprintf('Vòng %s: %s', $round->thu_tu ?: '?', $round->ten_vong ?: $this->roundTypeLabel($round->loai_vong)),
                'description' => trim(implode(' • ', array_filter([
                    $this->roundTypeLabel($round->loai_vong),
                    $this->interviewModeLabel($round->hinh_thuc_phong_van),
                    ($round->interviewer?->ho_ten ?? $round->nguoi_phong_van) ? 'Người phỏng vấn: ' . ($round->interviewer?->ho_ten ?? $round->nguoi_phong_van) : null,
                    $round->ket_qua ? 'Kết quả: ' . $round->ket_qua : null,
                ]))),
                'status' => $this->roundStatus($round),
                'scheduled_at' => $round->ngay_hen_phong_van?->toISOString(),
                'occurred_at' => $round->thoi_gian_phan_hoi?->toISOString(),
                'icon' => 'groups',
                'stage_order' => 30 + min(max((int) ($round->thu_tu ?: 1), 1), 19),
                'sort_at' => $round->ngay_hen_phong_van?->toISOString() ?: $round->created_at?->toISOString(),
            ]);
        }
    }

    private function pushOfferMilestones(Collection $items, UngTuyen $application): void
    {
        if ((int) ($application->trang_thai_offer ?? UngTuyen::OFFER_CHUA_GUI) === UngTuyen::OFFER_CHUA_GUI) {
            return;
        }

        $items->push([
            'key' => 'offer_sent',
            'group' => 'offer',
            'title' => 'Nhà tuyển dụng gửi offer',
            'description' => $application->ghi_chu_offer ?: 'Ứng viên đã nhận offer và có thể phản hồi.',
            'status' => (int) $application->trang_thai_offer === UngTuyen::OFFER_DA_GUI ? 'current' : 'completed',
            'occurred_at' => $application->thoi_gian_gui_offer?->toISOString(),
            'due_at' => $application->han_phan_hoi_offer?->toISOString(),
            'icon' => 'workspace_premium',
            'stage_order' => 60,
            'sort_at' => $application->thoi_gian_gui_offer?->toISOString(),
        ]);

        if (in_array((int) $application->trang_thai_offer, [UngTuyen::OFFER_DA_CHAP_NHAN, UngTuyen::OFFER_TU_CHOI], true)) {
            $accepted = (int) $application->trang_thai_offer === UngTuyen::OFFER_DA_CHAP_NHAN;
            $items->push([
                'key' => 'offer_response',
                'group' => 'offer',
                'title' => $accepted ? 'Ứng viên chấp nhận offer' : 'Ứng viên từ chối offer',
                'description' => $application->ghi_chu_phan_hoi_offer ?: ($accepted ? 'Quy trình chuyển sang onboarding.' : 'Quy trình tuyển dụng đã dừng ở bước offer.'),
                'status' => $accepted ? 'completed' : 'cancelled',
                'occurred_at' => $application->thoi_gian_phan_hoi_offer?->toISOString(),
                'icon' => $accepted ? 'verified' : 'block',
                'stage_order' => 70,
                'sort_at' => $application->thoi_gian_phan_hoi_offer?->toISOString(),
            ]);
        }
    }

    private function pushOnboardingMilestones(Collection $items, UngTuyen $application): void
    {
        $plan = $application->relationLoaded('onboardingPlan') ? $application->onboardingPlan : null;
        if (!$plan) {
            return;
        }

        $items->push([
            'key' => 'onboarding_plan',
            'group' => 'onboarding',
            'source_id' => $plan->id,
            'title' => 'Onboarding sau nhận việc',
            'description' => trim(implode(' • ', array_filter([
                $plan->dia_diem_lam_viec,
                $plan->progress ? "Tiến độ {$plan->progress['done']}/{$plan->progress['total']} checklist" : null,
            ]))) ?: 'HR và ứng viên theo dõi checklist nhận việc.',
            'status' => $this->onboardingStatus($plan),
            'scheduled_at' => $plan->ngay_bat_dau?->toDateString(),
            'occurred_at' => $plan->hoan_tat_luc?->toISOString(),
            'icon' => 'fact_check',
            'stage_order' => 80,
            'sort_at' => $plan->ngay_bat_dau?->toDateString() ?: $plan->created_at?->toISOString(),
        ]);
    }

    private function applicationStatusLabel(int $status): string
    {
        return match ($status) {
            UngTuyen::TRANG_THAI_DA_XEM => 'Nhà tuyển dụng đã xem hồ sơ',
            UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN => 'Đã hẹn phỏng vấn',
            UngTuyen::TRANG_THAI_QUA_PHONG_VAN => 'Ứng viên qua phỏng vấn',
            UngTuyen::TRANG_THAI_TRUNG_TUYEN => 'Ứng viên trúng tuyển',
            UngTuyen::TRANG_THAI_TU_CHOI => 'Ứng viên chưa phù hợp',
            default => 'Đang chờ duyệt hồ sơ',
        };
    }

    private function interviewAttendanceStatus(?int $attendance): string
    {
        return match ($attendance) {
            UngTuyen::PHONG_VAN_DA_XAC_NHAN => 'completed',
            UngTuyen::PHONG_VAN_KHONG_THAM_GIA => 'cancelled',
            default => 'current',
        };
    }

    private function roundStatus(InterviewRound $round): string
    {
        if ((int) $round->trang_thai === InterviewRound::TRANG_THAI_HUY) {
            return 'cancelled';
        }

        if ((int) $round->trang_thai === InterviewRound::TRANG_THAI_HOAN_THANH) {
            return 'completed';
        }

        return $this->interviewAttendanceStatus($round->trang_thai_tham_gia);
    }

    private function onboardingStatus(OnboardingPlan $plan): string
    {
        return match ($plan->trang_thai) {
            OnboardingPlan::TRANG_THAI_HOAN_TAT => 'completed',
            OnboardingPlan::TRANG_THAI_HUY => 'cancelled',
            OnboardingPlan::TRANG_THAI_DANG_THUC_HIEN => 'current',
            default => 'pending',
        };
    }

    private function roundTypeLabel(?string $type): string
    {
        return match ($type) {
            'hr' => 'HR screening',
            'technical' => 'Kỹ thuật',
            'manager' => 'Quản lý',
            'final' => 'Final',
            'culture' => 'Văn hóa',
            default => 'Phỏng vấn',
        };
    }

    private function interviewModeLabel(?string $mode): ?string
    {
        return match ($mode) {
            'online' => 'Online',
            'offline' => 'Trực tiếp',
            'phone' => 'Điện thoại',
            default => $mode,
        };
    }
}
