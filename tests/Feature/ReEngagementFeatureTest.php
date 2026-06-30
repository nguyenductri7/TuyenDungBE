<?php

use App\Models\AppNotification;
use App\Models\KyNang;
use App\Models\NganhNghe;
use App\Models\NguoiDung;
use App\Services\ReEngagementService;

it('returns expiring, stale and similar saved job insights for candidate', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $industry = NganhNghe::factory()->create(['ten_nganh' => 'Công nghệ thông tin']);
    $skill = KyNang::factory()->create(['ten_ky_nang' => 'Laravel']);

    $expiringJob = createJobForCompany($company, [
        'tieu_de' => 'Backend Developer Laravel',
        'ngay_het_han' => now()->addDays(2),
    ]);
    $staleJob = createJobForCompany($company, [
        'tieu_de' => 'PHP API Developer',
        'ngay_het_han' => now()->addDays(20),
    ]);
    $similarJob = createJobForCompany($company, [
        'tieu_de' => 'Laravel Backend Engineer',
        'ngay_het_han' => now()->addDays(30),
    ]);

    foreach ([$expiringJob, $staleJob, $similarJob] as $job) {
        $job->nganhNghes()->attach($industry->id);
        $job->kyNangYeuCaus()->create(['ky_nang_id' => $skill->id, 'bat_buoc' => true]);
    }

    $candidate->tinDaLuus()->attach($expiringJob->id, [
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);
    $candidate->tinDaLuus()->attach($staleJob->id, [
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/re-engagement/insights')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary.expiring_saved_jobs', 1)
        ->assertJsonPath('data.summary.stale_saved_jobs', 1)
        ->assertJsonPath('data.expiring_saved_jobs.0.id', $expiringJob->id)
        ->assertJsonPath('data.stale_saved_jobs.0.id', $staleJob->id)
        ->assertJsonFragment(['id' => $similarJob->id]);
});

it('creates deduplicated re-engagement notifications', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, [
        'tieu_de' => 'Backend Developer Laravel',
        'ngay_het_han' => now()->addDays(1),
    ]);

    $candidate->tinDaLuus()->attach($job->id, [
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);

    app(ReEngagementService::class)->runForCandidate($candidate);
    app(ReEngagementService::class)->runForCandidate($candidate);

    expect(AppNotification::where('nguoi_dung_id', $candidate->id)
        ->where('loai', 'candidate_saved_job_expiring')
        ->where('du_lieu_bo_sung->tin_tuyen_dung_id', $job->id)
        ->count())->toBe(1);

    expect(AppNotification::where('nguoi_dung_id', $candidate->id)
        ->where('loai', 'candidate_saved_job_follow_up')
        ->where('du_lieu_bo_sung->tin_tuyen_dung_id', $job->id)
        ->count())->toBe(1);
});
