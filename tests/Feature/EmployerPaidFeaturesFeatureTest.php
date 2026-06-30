<?php

use App\Models\BangGiaTinhNangAi;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use App\Models\ViNguoiDung;
use Illuminate\Support\Facades\Http;

if (! function_exists('createEmployerWalletForPaidFeatureTests')) {
    function createEmployerWalletForPaidFeatureTests(NguoiDung $employer, int $balance = 50000): ViNguoiDung
    {
        return ViNguoiDung::query()->create([
            'nguoi_dung_id' => $employer->id,
            'so_du_hien_tai' => $balance,
            'so_du_tam_giu' => 0,
            'don_vi_tien_te' => 'VND',
            'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
        ]);
    }
}

if (! function_exists('createEmployerFeaturePriceForPaidFeatureTests')) {
    function createEmployerFeaturePriceForPaidFeatureTests(string $featureCode, int $price, string $label, string $unit = 'lượt'): BangGiaTinhNangAi
    {
        return BangGiaTinhNangAi::query()->create([
            'feature_code' => $featureCode,
            'ten_hien_thi' => $label,
            'don_gia' => $price,
            'don_vi_tinh' => $unit,
            'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
        ]);
    }
}

it('charges employer wallet and prioritizes featured jobs in public listing', function () {
    createEmployerFeaturePriceForPaidFeatureTests('employer_featured_job_7d', 12000, 'Featured Job 7 ngày', 'listing');

    $featuredEmployer = NguoiDung::factory()->nhaTuyenDung()->create();
    $featuredCompany = createCompanyForEmployer($featuredEmployer);
    $featuredWallet = createEmployerWalletForPaidFeatureTests($featuredEmployer, 25000);
    $featuredJob = createJobForCompany($featuredCompany, [
        'tieu_de' => 'Featured Laravel Job',
        'created_at' => now()->subDays(2),
    ]);

    $plainEmployer = NguoiDung::factory()->nhaTuyenDung()->create();
    $plainCompany = createCompanyForEmployer($plainEmployer);
    createJobForCompany($plainCompany, [
        'tieu_de' => 'Plain Newer Job',
        'created_at' => now(),
    ]);

    $this->actingAs($featuredEmployer, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'featured-job-charge-1')
        ->postJson("/api/v1/nha-tuyen-dung/tin-tuyen-dungs/{$featuredJob->id}/sponsor", [
            'feature_code' => 'employer_featured_job_7d',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.billing.feature_code', 'employer_featured_job_7d')
        ->assertJsonPath('data.job.is_featured', true);

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $featuredEmployer->id)
        ->where('feature_code', 'employer_featured_job_7d')
        ->firstOrFail();

    expect($usage->billing_mode)->toBe(SuDungTinhNangAi::BILLING_MODE_WALLET);
    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($featuredWallet->fresh()->so_du_hien_tai)->toBe(13000);
    expect($featuredWallet->fresh()->so_du_tam_giu)->toBe(0);
    expect($featuredJob->fresh()->featured_until)->not->toBeNull();

    $this->getJson('/api/v1/tin-tuyen-dungs')
        ->assertOk()
        ->assertJsonPath('data.data.0.id', $featuredJob->id)
        ->assertJsonPath('data.data.0.is_featured', true);
});

it('charges employer wallet when generating interview copilot succeeds', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');

    Http::fake([
        'http://127.0.0.1:8001/interview/copilot/generate' => Http::response([
            'success' => true,
            'data' => [
                'candidate_summary' => 'Ứng viên có nền tảng backend PHP khá phù hợp với JD.',
                'focus_areas' => ['Kiểm tra chiều sâu Laravel', 'Làm rõ kinh nghiệm API'],
                'questions' => [
                    ['group' => 'Kỹ thuật', 'items' => ['Bạn tối ưu queue Laravel như thế nào?']],
                ],
                'rubric' => [
                    ['criterion' => 'Laravel', 'weight' => 40, 'expectation' => 'Có ví dụ production thực tế.'],
                ],
                'red_flags' => ['Thiếu minh chứng về scale hệ thống.'],
                'model_version' => 'interview_copilot_test_v1',
            ],
        ], 200),
    ]);

    createEmployerFeaturePriceForPaidFeatureTests('interview_copilot_generate', 5000, 'Interview Copilot Generate');

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $wallet = createEmployerWalletForPaidFeatureTests($employer, 10000);
    $job = createJobForCompany($company, ['tieu_de' => 'Senior PHP Engineer']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);

    $this->actingAs($employer, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'interview-copilot-generate-1')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-copilot/generate")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.copilot.pre_interview.candidate_summary', 'Ứng viên có nền tảng backend PHP khá phù hợp với JD.');

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $employer->id)
        ->where('feature_code', 'interview_copilot_generate')
        ->firstOrFail();

    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($wallet->fresh()->so_du_hien_tai)->toBe(5000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);
});

it('does not charge employer wallet when interview copilot generate falls back', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');

    Http::fake([
        'http://127.0.0.1:8001/interview/copilot/generate' => Http::response([
            'message' => 'AI service tam thoi khong kha dung.',
        ], 500),
    ]);

    createEmployerFeaturePriceForPaidFeatureTests('interview_copilot_generate', 5000, 'Interview Copilot Generate');

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $wallet = createEmployerWalletForPaidFeatureTests($employer, 9000);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Platform Engineer']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);

    $this->actingAs($employer, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'interview-copilot-generate-fallback-1')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-copilot/generate")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.copilot.used_fallback', true);

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $employer->id)
        ->where('feature_code', 'interview_copilot_generate')
        ->firstOrFail();

    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THAT_BAI);
    expect($wallet->fresh()->so_du_hien_tai)->toBe(9000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);
});

it('charges employer wallet when evaluating interview copilot succeeds', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');

    Http::fake([
        'http://127.0.0.1:8001/interview/copilot/evaluate' => Http::response([
            'success' => true,
            'data' => [
                'summary' => 'Ứng viên trả lời tốt phần kỹ thuật cốt lõi và phù hợp để vào vòng cuối.',
                'strengths' => ['Nắm chắc Laravel và REST API'],
                'concerns' => ['Cần kiểm tra thêm về tối ưu hệ thống lớn'],
                'next_steps' => ['Hẹn vòng final với Tech Lead'],
                'recommendation' => 'Qua vòng',
                'model_version' => 'interview_copilot_test_v1',
            ],
        ], 200),
    ]);

    createEmployerFeaturePriceForPaidFeatureTests('interview_copilot_evaluate', 4000, 'Interview Copilot Evaluate');

    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $wallet = createEmployerWalletForPaidFeatureTests($employer, 9000);
    $job = createJobForCompany($company, ['tieu_de' => 'Laravel Team Lead']);
    $application = createApplicationForCandidate($candidate, $job, [], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN,
    ]);

    $this->actingAs($employer, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'interview-copilot-evaluate-1')
        ->postJson("/api/v1/nha-tuyen-dung/ung-tuyens/{$application->id}/interview-copilot/evaluate", [
            'notes' => 'Ứng viên trả lời tốt phần chuyên môn, cần xác nhận thêm về scale.',
            'scores' => ['Laravel' => 8.5],
            'decision' => 'Qua vòng',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.copilot.post_interview.summary', 'Ứng viên trả lời tốt phần kỹ thuật cốt lõi và phù hợp để vào vòng cuối.');

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $employer->id)
        ->where('feature_code', 'interview_copilot_evaluate')
        ->firstOrFail();

    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($wallet->fresh()->so_du_hien_tai)->toBe(5000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);
});

it('charges employer wallet for ai shortlist explanation and candidate compare', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');

    Http::fake([
        'http://127.0.0.1:8001/match/cv-jd' => Http::response([
            'success' => true,
            'data' => [
                'match_score' => 86,
                'explanation' => 'Ứng viên có nền tảng phù hợp với JD và đủ tín hiệu để ưu tiên phỏng vấn.',
                'strengths' => ['Có kinh nghiệm backend', 'CV có minh chứng dự án'],
                'risks' => ['Thiếu thêm dữ liệu về system design'],
                'questions' => ['Bạn đã tối ưu API có tải cao như thế nào?'],
                'recommendation' => 'Nên phỏng vấn',
                'model_version' => 'cv_jd_match_test_v1',
            ],
        ], 200),
    ]);

    createEmployerFeaturePriceForPaidFeatureTests('employer_shortlist_ai_explanation', 3000, 'AI Shortlist Explanation');
    createEmployerFeaturePriceForPaidFeatureTests('employer_candidate_compare_ai', 2000, 'AI Candidate Compare');

    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $wallet = createEmployerWalletForPaidFeatureTests($employer, 12000);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer']);

    $candidateA = NguoiDung::factory()->ungVien()->create();
    $candidateB = NguoiDung::factory()->ungVien()->create();

    createApplicationForCandidate($candidateA, $job, [
        'tieu_de_ho_so' => 'Senior Backend CV',
        'kinh_nghiem_nam' => 4,
    ], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);
    createApplicationForCandidate($candidateB, $job, [
        'tieu_de_ho_so' => 'API Platform CV',
        'kinh_nghiem_nam' => 3,
    ], [
        'trang_thai' => UngTuyen::TRANG_THAI_DA_XEM,
    ]);

    $shortlistResponse = $this->actingAs($employer, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'shortlist-ai-charge-1')
        ->getJson("/api/v1/nha-tuyen-dung/tin-tuyen-dungs/{$job->id}/shortlist?scope=applied&ai_explain=1&limit=5");

    $shortlistResponse
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.billing.feature_code', 'employer_shortlist_ai_explanation');

    $shortlistedProfileIds = collect($shortlistResponse->json('data.items'))
        ->pluck('ho_so.id')
        ->filter()
        ->take(2)
        ->values()
        ->all();

    expect($shortlistedProfileIds)->toHaveCount(2);

    $this->actingAs($employer, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'shortlist-compare-charge-1')
        ->postJson("/api/v1/nha-tuyen-dung/tin-tuyen-dungs/{$job->id}/shortlist/compare", [
            'ho_so_ids' => $shortlistedProfileIds,
            'ai_explain' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.billing.feature_code', 'employer_candidate_compare_ai');

    $shortlistUsage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $employer->id)
        ->where('feature_code', 'employer_shortlist_ai_explanation')
        ->firstOrFail();
    $compareUsage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $employer->id)
        ->where('feature_code', 'employer_candidate_compare_ai')
        ->firstOrFail();

    expect($shortlistUsage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($compareUsage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($wallet->fresh()->so_du_hien_tai)->toBe(7000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);
});
