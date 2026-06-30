<?php

use App\Models\BienDongVi;
use App\Models\GiaoDichThanhToan;
use App\Models\NguoiDung;
use App\Models\ViNguoiDung;
use Illuminate\Support\Facades\Http;

it('creates momo top-up payment and credits wallet on valid ipn', function () {
    config()->set('services.momo.base_url', 'https://test-payment.momo.vn');
    config()->set('services.momo.partner_code', 'MOMO_TEST');
    config()->set('services.momo.partner_name', 'KhanhMai');
    config()->set('services.momo.store_id', 'KhanhMaiStore');
    config()->set('services.momo.access_key', 'access-key');
    config()->set('services.momo.secret_key', 'secret-key');
    config()->set('services.momo.request_type', 'captureWallet');
    config()->set('services.momo.redirect_url', 'http://localhost/payment-return');
    config()->set('services.momo.ipn_url', 'http://localhost/api/v1/payments/momo/ipn');

    Http::fake([
        'https://test-payment.momo.vn/*' => Http::response([
            'resultCode' => 0,
            'message' => 'Success',
            'payUrl' => 'https://pay.momo.test/checkout',
        ], 200),
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();

    $createResponse = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/vi/nap-tien/momo', [
            'so_tien' => 20000,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pay_url', 'https://pay.momo.test/checkout')
        ->assertJsonPath('data.payment.trang_thai', GiaoDichThanhToan::TRANG_THAI_PENDING);

    $payment = GiaoDichThanhToan::query()->firstOrFail();
    $wallet = ViNguoiDung::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();

    $ipnPayload = [
        'partnerCode' => 'MOMO_TEST',
        'orderId' => $payment->ma_giao_dich_noi_bo,
        'requestId' => $payment->ma_yeu_cau,
        'amount' => 20000,
        'orderInfo' => $payment->noi_dung,
        'orderType' => 'momo_wallet',
        'transId' => 99887766,
        'resultCode' => 0,
        'message' => 'Successful.',
        'payType' => 'qr',
        'responseTime' => 1721720663942,
        'extraData' => (string) ($payment->raw_request_json['extraData'] ?? ''),
    ];
    $ipnPayload['signature'] = hash_hmac('sha256', implode('&', [
        'accessKey=access-key',
        'amount=20000',
        'extraData=' . $ipnPayload['extraData'],
        'message=Successful.',
        'orderId=' . $payment->ma_giao_dich_noi_bo,
        'orderInfo=' . $payment->noi_dung,
        'orderType=momo_wallet',
        'partnerCode=MOMO_TEST',
        'payType=qr',
        'requestId=' . $payment->ma_yeu_cau,
        'responseTime=1721720663942',
        'resultCode=0',
        'transId=99887766',
    ]), 'secret-key');

    $this->postJson('/api/v1/payments/momo/ipn', $ipnPayload)
        ->assertNoContent();

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
    expect($wallet->fresh()->so_du_hien_tai)->toBe(20000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);

    $creditTx = BienDongVi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai_bien_dong', BienDongVi::LOAI_TOPUP_CREDIT)
        ->first();

    expect($creditTx)->not->toBeNull();
    expect((int) $creditTx->so_tien)->toBe(20000);
});

it('auto completes top-up from momo return on local environment', function () {
    config()->set('app.frontend_url', 'http://localhost:5173');
    config()->set('services.momo.auto_complete_return_local', true);

    $candidate = NguoiDung::factory()->ungVien()->create();
    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 0,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $payment = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'TOPUP-LOCAL-DEMO',
        'ma_yeu_cau' => 'REQ-LOCAL-DEMO',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 50000,
        'noi_dung' => 'Nap vi AI 50000 VND',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);

    $response = $this->get('/api/v1/payments/momo/return?' . http_build_query([
        'partnerCode' => 'MOMO_TEST',
        'orderId' => $payment->ma_giao_dich_noi_bo,
        'requestId' => $payment->ma_yeu_cau,
        'amount' => 50000,
        'orderInfo' => $payment->noi_dung,
        'resultCode' => 0,
        'message' => 'Thành công.',
        'transId' => '123456789',
    ]));

    $response->assertRedirect('http://localhost:5173/wallet?topup=success&orderId=TOPUP-LOCAL-DEMO&message=N%E1%BA%A1p+ti%E1%BB%81n+th%C3%A0nh+c%C3%B4ng');

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
    expect($payment->fresh()->ma_giao_dich_gateway)->toBe('123456789');
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($wallet->fresh()->so_du_hien_tai)->toBe(50000);

    $creditTx = BienDongVi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai_bien_dong', BienDongVi::LOAI_TOPUP_CREDIT)
        ->first();

    expect($creditTx)->not->toBeNull();
    expect((int) $creditTx->so_tien)->toBe(50000);
});
