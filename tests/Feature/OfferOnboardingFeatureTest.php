<?php

use App\Models\AppNotification;
use App\Models\OnboardingPlan;
use App\Models\OnboardingTask;
use App\Models\NguoiDung;
use App\Models\UngTuyen;

it('creates onboarding checklist when candidate accepts an offer', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_TRUNG_TUYEN,
        'trang_thai_offer' => UngTuyen::OFFER_DA_GUI,
        'thoi_gian_gui_offer' => now()->subHour(),
        'han_phan_hoi_offer' => now()->addDays(3),
        'ghi_chu_offer' => 'Offer thử việc 2 tháng',
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/ung-vien/ung-tuyens/{$application->id}/phan-hoi-offer", [
            'action' => 'accept',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($application->fresh()->trang_thai_offer)->toBe(UngTuyen::OFFER_DA_CHAP_NHAN);
    expect(OnboardingPlan::where('ung_tuyen_id', $application->id)->exists())->toBeTrue();
    expect(OnboardingTask::whereHas('plan', fn ($query) => $query->where('ung_tuyen_id', $application->id))->count())->toBeGreaterThan(0);
    expect(AppNotification::where('nguoi_dung_id', $candidate->id)
        ->where('loai', 'candidate_onboarding_started')
        ->exists())->toBeTrue();
});

it('allows candidate to update only candidate-owned onboarding task', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_TRUNG_TUYEN,
        'trang_thai_offer' => UngTuyen::OFFER_DA_CHAP_NHAN,
        'thoi_gian_gui_offer' => now()->subDay(),
        'thoi_gian_phan_hoi_offer' => now()->subHour(),
    ]);

    $plan = OnboardingPlan::create([
        'ung_tuyen_id' => $application->id,
        'cong_ty_id' => $company->id,
        'nguoi_dung_id' => $candidate->id,
        'hr_phu_trach_id' => $employer->id,
        'trang_thai' => OnboardingPlan::TRANG_THAI_DANG_CHUAN_BI,
    ]);
    $candidateTask = $plan->tasks()->create([
        'tieu_de' => 'Chuẩn bị giấy tờ',
        'nguoi_phu_trach' => OnboardingTask::NGUOI_PHU_TRACH_UNG_VIEN,
        'trang_thai' => OnboardingTask::TRANG_THAI_CHO_LAM,
    ]);
    $hrTask = $plan->tasks()->create([
        'tieu_de' => 'Tạo tài khoản nội bộ',
        'nguoi_phu_trach' => OnboardingTask::NGUOI_PHU_TRACH_HR,
        'trang_thai' => OnboardingTask::TRANG_THAI_CHO_LAM,
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/ung-vien/ung-tuyens/{$application->id}/onboarding/tasks/{$candidateTask->id}", [
            'trang_thai' => OnboardingTask::TRANG_THAI_HOAN_TAT,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($candidateTask->fresh()->trang_thai)->toBe(OnboardingTask::TRANG_THAI_HOAN_TAT);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/ung-vien/ung-tuyens/{$application->id}/onboarding/tasks/{$hrTask->id}", [
            'trang_thai' => OnboardingTask::TRANG_THAI_HOAN_TAT,
        ])
        ->assertNotFound();
});
