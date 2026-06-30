<?php

use App\Models\AiChatSession;
use App\Models\BangGiaTinhNangAi;
use App\Models\CongTy;
use App\Models\HoSo;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use App\Models\TinTuyenDung;
use App\Models\ViNguoiDung;
use Illuminate\Support\Facades\Http;

it('uses free quota first then falls back to wallet for career report generation', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.career_report_generation', 1);

    Http::fake([
        'http://127.0.0.1:8001/*' => Http::response([
            'success' => true,
            'model_version' => 'career_report_v1',
            'data' => [
                'nghe_de_xuat' => 'Backend Developer',
                'muc_do_phu_hop' => 85,
                'goi_y_ky_nang_bo_sung' => ['Docker'],
                'bao_cao_chi_tiet' => 'Bao cao chi tiet',
            ],
        ], 200),
    ]);

    BangGiaTinhNangAi::query()->create([
        'feature_code' => 'career_report_generation',
        'ten_hien_thi' => 'Sinh báo cáo định hướng nghề nghiệp',
        'don_gia' => 5000,
        'don_vi_tinh' => 'request',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 5000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);
    $profile = HoSo::factory()->forNguoiDung($candidate->id)->create();

    $first = $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'career-report-free-1')
        ->postJson("/api/v1/ung-vien/ho-sos/{$profile->id}/career-report");

    $second = $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'career-report-wallet-2')
        ->postJson("/api/v1/ung-vien/ho-sos/{$profile->id}/career-report");

    $first->assertOk()->assertJsonPath('success', true);
    $second->assertOk()->assertJsonPath('success', true);

    $usages = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('feature_code', 'career_report_generation')
        ->orderBy('id')
        ->get();

    expect($usages)->toHaveCount(2);
    expect($usages[0]->billing_mode)->toBe(SuDungTinhNangAi::BILLING_MODE_FREE);
    expect($usages[1]->billing_mode)->toBe(SuDungTinhNangAi::BILLING_MODE_WALLET);
    expect($wallet->fresh()->so_du_hien_tai)->toBe(0);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);
});

it('charges wallet for chatbot message after free quota is disabled', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.chatbot_message', 0);

    Http::fake([
        'http://127.0.0.1:8001/*' => Http::response([
            'success' => true,
            'model_version' => 'career_chat_v1',
            'data' => [
                'answer' => 'Bạn nên tập trung cải thiện CV trước.',
                'provider' => 'mock-provider',
                'guardrail_triggered' => false,
                'intent' => 'cv_improvement',
            ],
        ], 200),
    ]);

    BangGiaTinhNangAi::query()->create([
        'feature_code' => 'chatbot_message',
        'ten_hien_thi' => 'Chatbot tư vấn nghề nghiệp',
        'don_gia' => 1000,
        'don_vi_tinh' => 'message',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 5000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $session = AiChatSession::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'session_type' => 'career_consultant',
        'title' => 'Tu van nghe nghiep',
        'status' => 1,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'chat-billing-1')
        ->postJson('/api/v1/ai-chat/messages', [
            'session_id' => $session->id,
            'message' => 'Tư vấn CV giúp tôi',
            'force_model' => false,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.assistant_message.content', 'Bạn nên tập trung cải thiện CV trước.');

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('feature_code', 'chatbot_message')
        ->firstOrFail();

    expect($usage->billing_mode)->toBe(SuDungTinhNangAi::BILLING_MODE_WALLET);
    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($candidate->viNguoiDung()->firstOrFail()->so_du_hien_tai)->toBe(4000);
});

it('charges wallet when creating a mock interview session after free quota is disabled', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.mock_interview_session', 0);

    Http::fake([
        'http://127.0.0.1:8001/*' => Http::response([
            'success' => true,
            'model_version' => 'mock_interview_v1',
            'data' => [
                'question_text' => 'Hãy giới thiệu ngắn gọn về kinh nghiệm của bạn.',
                'question_index' => 1,
                'max_questions' => 5,
                'question_type' => 'intro',
                'interview_stage_label' => 'Khởi động',
                'focus_skills' => ['communication'],
                'suggested_answer_points' => ['Tóm tắt kinh nghiệm'],
                'generation_provider' => 'mock-provider',
            ],
        ], 200),
    ]);

    BangGiaTinhNangAi::query()->create([
        'feature_code' => 'mock_interview_session',
        'ten_hien_thi' => 'Phiên mock interview',
        'don_gia' => 7000,
        'don_vi_tinh' => 'session',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 10000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);
    $profile = HoSo::factory()->forNguoiDung($candidate->id)->create();

    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $employer->id,
        'trang_thai' => CongTy::TRANG_THAI_HOAT_DONG,
    ]);
    $job = TinTuyenDung::factory()->create([
        'cong_ty_id' => $company->id,
        'hr_phu_trach_id' => $employer->id,
        'trang_thai' => TinTuyenDung::TRANG_THAI_HOAT_DONG,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'mock-interview-billing-1')
        ->postJson('/api/v1/mock-interview/sessions', [
            'related_ho_so_id' => $profile->id,
            'related_tin_tuyen_dung_id' => $job->id,
            'auto_generate_first_question' => true,
            'question_count' => 5,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.first_question_message.content', 'Hãy giới thiệu ngắn gọn về kinh nghiệm của bạn.');

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('feature_code', 'mock_interview_session')
        ->firstOrFail();

    expect($usage->billing_mode)->toBe(SuDungTinhNangAi::BILLING_MODE_WALLET);
    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($candidate->viNguoiDung()->firstOrFail()->so_du_hien_tai)->toBe(3000);
});
