<?php

use App\Models\AppNotification;
use App\Models\InterviewRound;
use App\Models\NguoiDung;
use App\Models\UngTuyen;
use App\Notifications\InterviewScheduledNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('keeps HR screening round internal even when employer enters schedule details', function () {
    Notification::fake();

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);

    $scheduledAt = now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(10, 0);

    $this->actingAs($employer, 'sanctum')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-rounds", [
            'ten_vong' => 'HR Screening - Vòng CV',
            'loai_vong' => 'hr',
            'ngay_hen_phong_van' => $scheduledAt->format('Y-m-d H:i:s'),
            'hinh_thuc_phong_van' => 'phone',
            'nguoi_phong_van' => 'HR Team',
            'ghi_chu' => 'CV phù hợp, chuyển technical.',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Đã lưu vòng sàng lọc nội bộ.');

    $round = InterviewRound::where('ung_tuyen_id', $application->id)->firstOrFail();
    $application->refresh();

    expect($round->trang_thai_tham_gia)->toBeNull();
    expect($application->trang_thai)->toBe(UngTuyen::TRANG_THAI_DA_XEM);
    expect($application->vong_phong_van_hien_tai)->toBe('hr');
    expect($application->ngay_hen_phong_van)->toBeNull();
    expect(AppNotification::where('nguoi_dung_id', $candidate->id)
        ->where('loai', 'candidate_interview_round_scheduled')
        ->exists())->toBeFalse();

    Notification::assertNotSentTo($candidate, InterviewScheduledNotification::class);

    $candidateResponse = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/ung-tuyens')
        ->assertOk();

    expect($candidateResponse->json('data.data.0.interview_rounds'))->toBe([]);
    expect(collect($candidateResponse->json('data.data.0.application_timeline'))->pluck('key')->all())
        ->not->toContain('interview_round_' . $round->id);
});

it('uses selected company interviewer and derives application status from round result', function () {
    Notification::fake();

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create(['ho_ten' => 'HR Owner']);
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_CHO_DUYET,
    ]);

    $this->actingAs($employer, 'sanctum')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-rounds", [
            'ten_vong' => 'HR Screening - Vòng CV',
            'loai_vong' => 'hr',
            'interviewer_user_id' => $employer->id,
            'ket_qua' => InterviewRound::KET_QUA_DAT,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nguoi_phong_van', 'HR Owner');

    $round = InterviewRound::where('ung_tuyen_id', $application->id)->firstOrFail();

    expect($round->interviewer_user_id)->toBe($employer->id);
    expect($round->nguoi_phong_van)->toBe('HR Owner');
    expect($application->fresh()->trang_thai)->toBe(UngTuyen::TRANG_THAI_DA_XEM);
    Notification::assertNotSentTo($candidate, InterviewScheduledNotification::class);
});

it('returns field level validation errors for invalid interview round payload', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company);
    $application = createApplicationForCandidate($candidate, $job);

    $this->actingAs($employer, 'sanctum')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-rounds", [
            'ten_vong' => '',
            'loai_vong' => 'unknown',
            'interviewer_user_id' => 999999,
            'ket_qua' => 'maybe',
            'diem_so' => 11,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ten_vong', 'loai_vong', 'interviewer_user_id', 'ket_qua', 'diem_so'])
        ->assertJsonPath('errors.ket_qua.0', 'Kết quả vòng không hợp lệ. Vui lòng chọn Đậu hoặc Rớt.');
});

it('lets employer create interview round and candidate confirm attendance', function () {
    Notification::fake();

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);

    $scheduledAt = now('Asia/Ho_Chi_Minh')->addDays(3)->setTime(9, 30);

    $this->actingAs($employer, 'sanctum')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-rounds", [
            'ten_vong' => 'Technical Interview',
            'loai_vong' => 'technical',
            'ngay_hen_phong_van' => $scheduledAt->format('Y-m-d H:i:s'),
            'hinh_thuc_phong_van' => 'online',
            'nguoi_phong_van' => 'Tech Lead',
            'link_phong_van' => 'https://meet.example.test/backend',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.ten_vong', 'Technical Interview');

    $round = InterviewRound::where('ung_tuyen_id', $application->id)->firstOrFail();

    expect($application->fresh()->vong_phong_van_hien_tai)->toBe('technical');
    expect(AppNotification::where('nguoi_dung_id', $candidate->id)
        ->where('loai', 'candidate_interview_round_scheduled')
        ->where('du_lieu_bo_sung->interview_round_id', $round->id)
        ->exists())->toBeTrue();
    Notification::assertSentTo($candidate, InterviewScheduledNotification::class);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/ung-vien/ung-tuyens/{$application->id}/interview-rounds/{$round->id}/xac-nhan", [
            'trang_thai_tham_gia_phong_van' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($round->fresh()->trang_thai_tham_gia)->toBe(UngTuyen::PHONG_VAN_DA_XAC_NHAN);
    expect($application->fresh()->trang_thai_tham_gia_phong_van)->toBe(UngTuyen::PHONG_VAN_DA_XAC_NHAN);

    $timelineResponse = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/ung-tuyens');

    $timelineResponse
        ->assertOk()
        ->assertJsonPath('data.data.0.application_timeline.0.key', 'application_submitted');

    expect(collect($timelineResponse->json('data.data.0.application_timeline'))->pluck('key')->all())
        ->toContain('interview_round_' . $round->id);
});

it('moves application to interview passed when technical round passes without requiring final round', function () {
    Notification::fake();

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN,
    ]);

    $round = InterviewRound::create([
        'ung_tuyen_id' => $application->id,
        'thu_tu' => 2,
        'ten_vong' => 'Technical Interview',
        'loai_vong' => 'technical',
        'trang_thai' => InterviewRound::TRANG_THAI_DA_LEN_LICH,
        'ngay_hen_phong_van' => now('Asia/Ho_Chi_Minh')->addDays(2),
        'trang_thai_tham_gia' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
    ]);

    $this->actingAs($employer, 'sanctum')
        ->putJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-rounds/{$round->id}", [
            'ten_vong' => 'Technical Interview',
            'loai_vong' => 'technical',
            'trang_thai' => InterviewRound::TRANG_THAI_HOAN_THANH,
            'ket_qua' => InterviewRound::KET_QUA_DAT,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('application.trang_thai', UngTuyen::TRANG_THAI_QUA_PHONG_VAN);

    expect($application->fresh()->trang_thai)->toBe(UngTuyen::TRANG_THAI_QUA_PHONG_VAN);
    expect($round->fresh()->trang_thai)->toBe(InterviewRound::TRANG_THAI_HOAN_THANH);
});

it('blocks creating interview round for final application', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_TU_CHOI,
    ]);

    $this->actingAs($employer, 'sanctum')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-rounds", [
            'ten_vong' => 'Final Interview',
            'ngay_hen_phong_van' => now('Asia/Ho_Chi_Minh')->addDays(2)->format('Y-m-d H:i:s'),
        ])
        ->assertUnprocessable();
});

it('redirects email interview actions to frontend result page', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);
    $round = InterviewRound::create([
        'ung_tuyen_id' => $application->id,
        'thu_tu' => 1,
        'ten_vong' => 'Technical Interview',
        'loai_vong' => 'technical',
        'trang_thai' => InterviewRound::TRANG_THAI_DA_LEN_LICH,
        'ngay_hen_phong_van' => now()->addDays(3),
        'trang_thai_tham_gia' => UngTuyen::PHONG_VAN_CHO_XAC_NHAN,
    ]);

    $validUrl = URL::temporarySignedRoute(
        'ung-vien.ung-tuyens.interview-rounds.confirm-email',
        now()->addDay(),
        [
            'id' => $application->id,
            'roundId' => $round->id,
            'action' => 'accept',
            'user' => $candidate->id,
        ],
    );

    $validResponse = $this->get($validUrl);
    $validResponse->assertRedirect();
    expect($validResponse->headers->get('Location'))
        ->toContain('/application-action-result?type=interview')
        ->toContain('status=accepted')
        ->toContain('application_id=' . $application->id);

    $expiredUrl = URL::temporarySignedRoute(
        'ung-vien.ung-tuyens.interview-rounds.confirm-email',
        now()->subMinute(),
        [
            'id' => $application->id,
            'roundId' => $round->id,
            'action' => 'decline',
            'user' => $candidate->id,
        ],
    );

    $expiredResponse = $this->get($expiredUrl);
    $expiredResponse->assertRedirect();
    expect($expiredResponse->headers->get('Location'))
        ->toContain('/application-action-result?type=interview')
        ->toContain('status=expired');
});
