<?php

use App\Events\BillingAiFeatureUsed;
use App\Events\BillingPaymentCompleted;
use App\Events\BillingSubscriptionActivated;
use App\Models\BangGiaTinhNangAi;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\HoSo;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use App\Models\ViNguoiDung;
use Database\Seeders\GoiDichVuSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

if (! function_exists('signVnpayQueryResponsePayload')) {
    function signVnpayQueryResponsePayload(array $payload, string $secret = 'secret-key'): array
    {
        $hashData = implode('|', [
            (string) ($payload['vnp_ResponseId'] ?? ''),
            (string) ($payload['vnp_Command'] ?? ''),
            (string) ($payload['vnp_ResponseCode'] ?? ''),
            (string) ($payload['vnp_Message'] ?? ''),
            (string) ($payload['vnp_TmnCode'] ?? ''),
            (string) ($payload['vnp_TxnRef'] ?? ''),
            (string) ($payload['vnp_Amount'] ?? ''),
            (string) ($payload['vnp_BankCode'] ?? ''),
            (string) ($payload['vnp_PayDate'] ?? ''),
            (string) ($payload['vnp_TransactionNo'] ?? ''),
            (string) ($payload['vnp_TransactionType'] ?? ''),
            (string) ($payload['vnp_TransactionStatus'] ?? ''),
            (string) ($payload['vnp_OrderInfo'] ?? ''),
            (string) ($payload['vnp_PromotionCode'] ?? ''),
            (string) ($payload['vnp_PromotionAmount'] ?? ''),
        ]);

        $payload['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);

        return $payload;
    }
}

it('dispatches billing domain events for ai usage and subscription activation', function () {
    Event::fake([
        BillingAiFeatureUsed::class,
        BillingPaymentCompleted::class,
        BillingSubscriptionActivated::class,
    ]);

    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.career_report_generation', 0);
    config()->set('services.momo.base_url', 'https://test-payment.momo.vn');
    config()->set('services.momo.partner_code', 'MOMO_TEST');
    config()->set('services.momo.partner_name', 'KhanhMai');
    config()->set('services.momo.store_id', 'KhanhMaiStore');
    config()->set('services.momo.access_key', 'access-key');
    config()->set('services.momo.secret_key', 'secret-key');
    config()->set('services.momo.request_type', 'captureWallet');
    config()->set('services.momo.redirect_url', 'http://localhost:5173/plans');
    config()->set('services.momo.ipn_url', 'http://localhost/api/v1/payments/momo/ipn');
    config()->set('services.momo.auto_complete_return_local', true);

    Http::fake([
        'http://127.0.0.1:8001/*' => Http::response([
            'success' => true,
            'model_version' => 'career_report_v1',
            'data' => [
                'nghe_de_xuat' => 'Backend Developer',
                'muc_do_phu_hop' => 88,
                'goi_y_ky_nang_bo_sung' => ['Docker'],
                'bao_cao_chi_tiet' => 'Bao cao chi tiet',
            ],
        ], 200),
        'https://test-payment.momo.vn/*' => Http::response([
            'resultCode' => 0,
            'message' => 'Success',
            'payUrl' => 'https://pay.momo.test/subscription-checkout',
        ], 200),
    ]);

    BangGiaTinhNangAi::query()->create([
        'feature_code' => 'career_report_generation',
        'ten_hien_thi' => 'Sinh báo cáo định hướng nghề nghiệp',
        'don_gia' => 5000,
        'don_vi_tinh' => 'request',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $this->seed(GoiDichVuSeeder::class);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 10000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);
    $profile = HoSo::factory()->forNguoiDung($candidate->id)->create();

    $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'scale-event-career-report')
        ->postJson("/api/v1/ung-vien/ho-sos/{$profile->id}/career-report")
        ->assertOk();

    Event::assertDispatched(BillingAiFeatureUsed::class, function (BillingAiFeatureUsed $event) use ($candidate) {
        return $event->userId === $candidate->id
            && $event->featureCode === 'career_report_generation'
            && $event->billingMode === SuDungTinhNangAi::BILLING_MODE_WALLET
            && $event->status === SuDungTinhNangAi::TRANG_THAI_THANH_CONG;
    });

    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();

    $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/goi-dich-vus/mua/momo', [
            'ma_goi' => 'PRO_MONTHLY',
        ])
        ->assertCreated();

    $payment = GiaoDichThanhToan::query()
        ->where('loai_giao_dich', GiaoDichThanhToan::LOAI_MUA_GOI)
        ->firstOrFail();

    $this->get('/api/v1/payments/momo/return?' . http_build_query([
        'partnerCode' => 'MOMO_TEST',
        'orderId' => $payment->ma_giao_dich_noi_bo,
        'requestId' => $payment->ma_yeu_cau,
        'amount' => $plan->gia,
        'orderInfo' => $payment->noi_dung,
        'resultCode' => 0,
        'message' => 'Thành công.',
        'transId' => 'SUB9988',
    ]))->assertRedirect();

    Event::assertDispatched(BillingSubscriptionActivated::class, function (BillingSubscriptionActivated $event) use ($candidate, $plan) {
        return $event->userId === $candidate->id
            && $event->planId === $plan->id
            && $event->planCode === 'PRO_MONTHLY';
    });

    Event::assertDispatched(BillingPaymentCompleted::class, function (BillingPaymentCompleted $event) use ($candidate, $payment) {
        return $event->userId === $candidate->id
            && $event->paymentId === $payment->id
            && $event->transactionType === GiaoDichThanhToan::LOAI_MUA_GOI;
    });
});

it('reconciles stale pending momo payments via artisan command', function () {
    config()->set('services.momo.base_url', 'https://test-payment.momo.vn');
    config()->set('services.momo.partner_code', 'MOMO_TEST');
    config()->set('services.momo.access_key', 'access-key');
    config()->set('services.momo.secret_key', 'secret-key');

    $candidate = NguoiDung::factory()->ungVien()->create();
    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 0,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $stale = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'TOPUP-STALE-001',
        'ma_yeu_cau' => 'REQ-STALE-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 20000,
        'noi_dung' => 'Stale pending payment',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);
    $stale->timestamps = false;
    $stale->forceFill([
        'created_at' => now()->subHours(5),
        'updated_at' => now()->subHours(5),
    ])->save();
    $stale->timestamps = true;

    $recent = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'TOPUP-RECENT-001',
        'ma_yeu_cau' => 'REQ-RECENT-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 30000,
        'noi_dung' => 'Recent pending payment',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);
    $recent->timestamps = false;
    $recent->forceFill([
        'created_at' => now()->subMinutes(20),
        'updated_at' => now()->subMinutes(20),
    ])->save();
    $recent->timestamps = true;

    Http::fake([
        'https://test-payment.momo.vn/v2/gateway/api/query' => Http::response([
            'partnerCode' => 'MOMO_TEST',
            'orderId' => $stale->ma_giao_dich_noi_bo,
            'requestId' => $stale->ma_yeu_cau,
            'amount' => 20000,
            'resultCode' => 1000,
            'message' => 'Transaction is pending.',
        ], 200),
    ]);

    $this->artisan('billing:reconcile-pending-payments', [
        '--minutes' => 60,
    ])
        ->assertExitCode(0);

    expect($stale->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_HUY);
    expect($recent->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_PENDING);
});

it('reconciles stale pending vnpay payments via artisan command', function () {
    config()->set('services.vnpay.base_url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    config()->set('services.vnpay.tmn_code', 'VNPAYTST');
    config()->set('services.vnpay.hash_secret', 'secret-key');

    $candidate = NguoiDung::factory()->ungVien()->create();
    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 0,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $stale = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_VNPAY,
        'ma_giao_dich_noi_bo' => 'VNPAY-STALE-001',
        'ma_yeu_cau' => 'VNPAY-REQ-STALE-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 50000,
        'noi_dung' => 'Stale pending VNPAY payment',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
        'raw_request_json' => [
            'vnp_CreateDate' => '20260428100000',
        ],
    ]);
    $stale->timestamps = false;
    $stale->forceFill([
        'created_at' => now()->subHours(5),
        'updated_at' => now()->subHours(5),
    ])->save();
    $stale->timestamps = true;

    $recent = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_VNPAY,
        'ma_giao_dich_noi_bo' => 'VNPAY-RECENT-001',
        'ma_yeu_cau' => 'VNPAY-REQ-RECENT-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 30000,
        'noi_dung' => 'Recent pending VNPAY payment',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);
    $recent->timestamps = false;
    $recent->forceFill([
        'created_at' => now()->subMinutes(20),
        'updated_at' => now()->subMinutes(20),
    ])->save();
    $recent->timestamps = true;

    Http::fake([
        'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction' => Http::response(
            signVnpayQueryResponsePayload([
                'vnp_ResponseId' => 'VNPAY-QUERY-STALE-001',
                'vnp_Command' => 'querydr',
                'vnp_ResponseCode' => '00',
                'vnp_Message' => 'Query success',
                'vnp_TmnCode' => 'VNPAYTST',
                'vnp_TxnRef' => $stale->ma_giao_dich_noi_bo,
                'vnp_Amount' => 50000 * 100,
                'vnp_BankCode' => 'NCB',
                'vnp_PayDate' => '',
                'vnp_TransactionNo' => 'VNPAY-TRANS-PENDING-001',
                'vnp_TransactionType' => '01',
                'vnp_TransactionStatus' => '01',
                'vnp_OrderInfo' => 'Stale pending VNPAY payment',
                'vnp_PromotionCode' => '',
                'vnp_PromotionAmount' => '',
            ]),
            200
        ),
    ]);

    $this->artisan('billing:reconcile-pending-payments', [
        '--minutes' => 60,
    ])
        ->assertExitCode(0);

    expect($stale->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_HUY);
    expect($recent->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_PENDING);
    Http::assertSentCount(1);
});
