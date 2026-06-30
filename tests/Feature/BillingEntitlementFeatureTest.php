<?php

use App\Models\BangGiaTinhNangAi;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use App\Models\NguoiDungGoiDichVu;
use App\Models\SuDungTinhNangAi;
use Database\Seeders\GoiDichVuSeeder;

it('returns free and subscription quota snapshots by feature', function () {
    $this->seed(GoiDichVuSeeder::class);

    BangGiaTinhNangAi::query()->create([
        'feature_code' => 'career_report_generation',
        'ten_hien_thi' => 'Sinh báo cáo định hướng nghề nghiệp',
        'don_gia' => 5000,
        'don_vi_tinh' => 'request',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    BangGiaTinhNangAi::query()->create([
        'feature_code' => 'chatbot_message',
        'ten_hien_thi' => 'Chatbot tư vấn nghề nghiệp',
        'don_gia' => 1000,
        'don_vi_tinh' => 'message',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();

    $subscription = NguoiDungGoiDichVu::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'goi_dich_vu_id' => $plan->id,
        'ngay_bat_dau' => now()->subDay(),
        'ngay_het_han' => now()->addMonth(),
        'trang_thai' => NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG,
        'auto_renew' => false,
    ]);

    SuDungTinhNangAi::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'feature_code' => 'career_report_generation',
        'so_luong' => 1,
        'don_gia_ap_dung' => 0,
        'so_tien_du_kien' => 0,
        'so_tien_thuc_te' => 0,
        'billing_mode' => SuDungTinhNangAi::BILLING_MODE_FREE,
        'trang_thai' => SuDungTinhNangAi::TRANG_THAI_THANH_CONG,
        'idempotency_key' => 'free-career-report-1',
        'tham_chieu_loai' => 'career_report',
        'tham_chieu_id' => 1,
        'metadata_json' => ['free_quota' => true],
    ]);

    SuDungTinhNangAi::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'feature_code' => 'chatbot_message',
        'so_luong' => 1,
        'don_gia_ap_dung' => 0,
        'so_tien_du_kien' => 0,
        'so_tien_thuc_te' => 0,
        'billing_mode' => SuDungTinhNangAi::BILLING_MODE_SUBSCRIPTION,
        'trang_thai' => SuDungTinhNangAi::TRANG_THAI_THANH_CONG,
        'idempotency_key' => 'subscription-chatbot-1',
        'tham_chieu_loai' => 'ai_chat',
        'tham_chieu_id' => 1,
        'metadata_json' => [
            'subscription_id' => $subscription->id,
            'plan_code' => 'PRO_MONTHLY',
        ],
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/billing/entitlements');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.current_subscription.goi_dich_vu.ma_goi', 'PRO_MONTHLY');

    $entitlements = collect($response->json('data.entitlements'))->keyBy('feature_code');

    expect($entitlements->get('career_report_generation'))->toMatchArray([
        'free_quota_total' => 1,
        'free_quota_used' => 1,
        'free_quota_remaining' => 0,
        'subscription_included' => true,
        'subscription_quota_total' => 10,
        'subscription_quota_used' => 0,
        'subscription_quota_remaining' => 10,
        'wallet_price' => 5000,
    ]);

    expect($entitlements->get('chatbot_message'))->toMatchArray([
        'free_quota_total' => 20,
        'free_quota_used' => 0,
        'free_quota_remaining' => 20,
        'subscription_included' => true,
        'subscription_quota_total' => 200,
        'subscription_quota_used' => 1,
        'subscription_quota_remaining' => 199,
        'wallet_price' => 1000,
    ]);
});
