<?php

use App\Models\InterviewRound;
use App\Models\NguoiDung;
use App\Models\OnboardingPlan;
use App\Models\OnboardingTask;
use App\Models\UngTuyen;

it('exports candidate application dossier as a server-side pdf', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN,
    ]);
    InterviewRound::create([
        'ung_tuyen_id' => $application->id,
        'thu_tu' => 1,
        'ten_vong' => 'Technical Interview',
        'loai_vong' => 'technical',
        'trang_thai' => InterviewRound::TRANG_THAI_DA_LEN_LICH,
        'ngay_hen_phong_van' => now()->addDays(3),
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->get("/api/v1/ung-vien/ung-tuyens/{$application->id}/export/full");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('application-dossier');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('exports employer offer interview and onboarding documents with ownership checks', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $otherEmployer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    createCompanyForEmployer($otherEmployer);
    $job = createJobForCompany($company, ['tieu_de' => 'Senior PHP Engineer']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_TRUNG_TUYEN,
        'trang_thai_offer' => UngTuyen::OFFER_DA_CHAP_NHAN,
        'thoi_gian_gui_offer' => now()->subDay(),
        'thoi_gian_phan_hoi_offer' => now()->subHour(),
        'ghi_chu_offer' => 'Offer chính thức',
    ]);

    InterviewRound::create([
        'ung_tuyen_id' => $application->id,
        'thu_tu' => 1,
        'ten_vong' => 'Technical Interview',
        'loai_vong' => 'technical',
        'trang_thai' => InterviewRound::TRANG_THAI_HOAN_THANH,
        'ngay_hen_phong_van' => now()->subDays(2),
        'ket_qua' => InterviewRound::KET_QUA_DAT,
    ]);

    $plan = OnboardingPlan::create([
        'ung_tuyen_id' => $application->id,
        'cong_ty_id' => $company->id,
        'nguoi_dung_id' => $candidate->id,
        'hr_phu_trach_id' => $employer->id,
        'trang_thai' => OnboardingPlan::TRANG_THAI_DANG_CHUAN_BI,
    ]);
    $plan->tasks()->create([
        'tieu_de' => 'Chuẩn bị giấy tờ',
        'nguoi_phu_trach' => OnboardingTask::NGUOI_PHU_TRACH_UNG_VIEN,
        'trang_thai' => OnboardingTask::TRANG_THAI_CHO_LAM,
    ]);

    foreach (['offer', 'interview', 'onboarding'] as $document) {
        $response = $this->actingAs($employer, 'sanctum')
            ->get("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/export/{$document}");

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
    }

    $this->actingAs($otherEmployer, 'sanctum')
        ->get("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/export/full")
        ->assertNotFound();
});
