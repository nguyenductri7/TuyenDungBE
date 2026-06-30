<?php

use App\Models\AppNotification;
use App\Models\BienDongVi;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use App\Models\NguoiDungGoiDichVu;
use App\Models\ViNguoiDung;
use Database\Seeders\GoiDichVuSeeder;

if (! function_exists('signVnpayTestPayload')) {
    function signVnpayTestPayload(array $payload, string $secret = 'secret-key'): array
    {
        unset($payload['vnp_SecureHash'], $payload['vnp_SecureHashType']);
        ksort($payload);

        $hashData = implode('&', array_map(
            static fn ($key, $value) => urlencode((string) $key) . '=' . urlencode((string) $value),
            array_keys($payload),
            $payload,
        ));

        $payload['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);

        return $payload;
    }
}

beforeEach(function () {
    config()->set('services.vnpay.base_url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    config()->set('services.vnpay.tmn_code', 'VNPAYTST');
    config()->set('services.vnpay.hash_secret', 'secret-key');
    config()->set('services.vnpay.return_url', 'http://localhost/api/v1/payments/vnpay/return');
    config()->set('services.vnpay.ipn_url', 'http://localhost/api/v1/payments/vnpay/ipn');
    config()->set('services.vnpay.locale', 'vn');
    config()->set('services.vnpay.order_type', 'other');
});

it('creates vnpay top-up payment and credits wallet on valid ipn', function () {
    config()->set('services.vnpay.auto_complete_return_local', false);

    $candidate = NguoiDung::factory()->ungVien()->create();

    $createResponse = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/vi/nap-tien/vnpay', [
            'so_tien' => 45000,
            'bank_code' => 'NCB',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.payment.gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
        ->assertJsonPath('data.payment.trang_thai', GiaoDichThanhToan::TRANG_THAI_PENDING);

    expect($createResponse->json('data.pay_url'))->toContain('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    expect($createResponse->json('data.pay_url'))->toContain('vnp_SecureHash=');

    $payment = GiaoDichThanhToan::query()->firstOrFail();
    $wallet = ViNguoiDung::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();

    $ipnPayload = signVnpayTestPayload([
        'vnp_TmnCode' => 'VNPAYTST',
        'vnp_Amount' => 45000 * 100,
        'vnp_BankCode' => 'NCB',
        'vnp_BankTranNo' => 'VNPAYBANK123',
        'vnp_CardType' => 'ATM',
        'vnp_OrderInfo' => $payment->noi_dung,
        'vnp_PayDate' => '20260428101530',
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => $payment->ma_giao_dich_noi_bo,
        'vnp_TransactionNo' => '14123456',
        'vnp_TransactionStatus' => '00',
    ]);

    $this->getJson('/api/v1/payments/vnpay/ipn?' . http_build_query($ipnPayload))
        ->assertOk()
        ->assertJsonPath('RspCode', '00');

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
    expect($payment->fresh()->ma_giao_dich_gateway)->toBe('14123456');
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($wallet->fresh()->so_du_hien_tai)->toBe(45000);

    $creditTx = BienDongVi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai_bien_dong', BienDongVi::LOAI_TOPUP_CREDIT)
        ->first();

    expect($creditTx)->not->toBeNull();
    expect((int) $creditTx->so_tien)->toBe(45000);
    expect($creditTx->metadata_json['gateway'])->toBe(GiaoDichThanhToan::GATEWAY_VNPAY);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/vi/nap-tien/' . $payment->ma_giao_dich_noi_bo)
        ->assertOk()
        ->assertJsonPath('data.gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
        ->assertJsonPath('data.trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
});

it('does not duplicate vnpay top-up notification when successful return is processed twice', function () {
    config()->set('app.frontend_url', 'http://localhost:5173');
    config()->set('services.vnpay.auto_complete_return_local', true);

    $candidate = NguoiDung::factory()->ungVien()->create();

    $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/vi/nap-tien/vnpay', [
            'so_tien' => 200000,
            'bank_code' => 'NCB',
        ])
        ->assertCreated();

    $payment = GiaoDichThanhToan::query()->firstOrFail();

    $returnPayload = signVnpayTestPayload([
        'vnp_TmnCode' => 'VNPAYTST',
        'vnp_Amount' => 200000 * 100,
        'vnp_BankCode' => 'NCB',
        'vnp_OrderInfo' => $payment->noi_dung,
        'vnp_PayDate' => '20260428101530',
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => $payment->ma_giao_dich_noi_bo,
        'vnp_TransactionNo' => '14123457',
        'vnp_TransactionStatus' => '00',
    ]);

    $returnUrl = '/api/v1/payments/vnpay/return?' . http_build_query($returnPayload);

    $this->get($returnUrl)->assertRedirect();
    $this->get($returnUrl)->assertRedirect();

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
    expect($payment->viNguoiDung->fresh()->so_du_hien_tai)->toBe(200000);
    expect(BienDongVi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai_bien_dong', BienDongVi::LOAI_TOPUP_CREDIT)
        ->count())->toBe(1);
    expect(AppNotification::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai', 'billing_payment_completed')
        ->where('duong_dan', '/payments/' . $payment->ma_giao_dich_noi_bo)
        ->count())->toBe(1);
});

it('creates vnpay subscription payment and activates plan from local return', function () {
    config()->set('app.frontend_url', 'http://localhost:5173');
    config()->set('services.vnpay.auto_complete_return_local', true);

    $this->seed(GoiDichVuSeeder::class);
    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();
    $candidate = NguoiDung::factory()->ungVien()->create();

    $createResponse = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/goi-dich-vus/mua/vnpay', [
            'ma_goi' => 'PRO_MONTHLY',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.payment.gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
        ->assertJsonPath('data.plan.ma_goi', 'PRO_MONTHLY');

    $payment = GiaoDichThanhToan::query()->firstOrFail();

    expect($payment->loai_giao_dich)->toBe(GiaoDichThanhToan::LOAI_MUA_GOI);
    expect((int) $payment->goi_dich_vu_id)->toBe($plan->id);

    $returnPayload = signVnpayTestPayload([
        'vnp_TmnCode' => 'VNPAYTST',
        'vnp_Amount' => (int) $plan->gia * 100,
        'vnp_BankCode' => 'NCB',
        'vnp_OrderInfo' => $payment->noi_dung,
        'vnp_PayDate' => '20260428101530',
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => $payment->ma_giao_dich_noi_bo,
        'vnp_TransactionNo' => '14999999',
        'vnp_TransactionStatus' => '00',
    ]);

    $response = $this->get('/api/v1/payments/vnpay/return?' . http_build_query($returnPayload));

    $response->assertRedirect(
        'http://localhost:5173/plans?subscription=success&plan=PRO_MONTHLY&orderId='
        . urlencode($payment->ma_giao_dich_noi_bo)
        . '&message=VNPay+response+00'
    );

    $subscription = NguoiDungGoiDichVu::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
    expect($payment->fresh()->ma_giao_dich_gateway)->toBe('14999999');
    expect($subscription->goiDichVu->ma_goi)->toBe('PRO_MONTHLY');
    expect($subscription->trang_thai)->toBe(NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG);
    expect($subscription->giao_dich_thanh_toan_id)->toBe($payment->id);
});

it('keeps vnpay top-up pending when user returns without completing payment', function () {
    config()->set('app.frontend_url', 'http://localhost:5173');
    config()->set('services.vnpay.auto_complete_return_local', true);

    $candidate = NguoiDung::factory()->ungVien()->create();

    $createResponse = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/vi/nap-tien/vnpay', [
            'so_tien' => 50000,
            'bank_code' => 'NCB',
        ]);

    $payment = GiaoDichThanhToan::query()->firstOrFail();

    $returnPayload = signVnpayTestPayload([
        'vnp_TmnCode' => 'VNPAYTST',
        'vnp_Amount' => 50000 * 100,
        'vnp_BankCode' => 'NCB',
        'vnp_OrderInfo' => $payment->noi_dung,
        'vnp_ResponseCode' => '24',
        'vnp_TxnRef' => $payment->ma_giao_dich_noi_bo,
        'vnp_TransactionStatus' => '02',
    ]);

    $response = $this->get('/api/v1/payments/vnpay/return?' . http_build_query($returnPayload));

    $response->assertRedirect(
        'http://localhost:5173/wallet/payment-result/' . urlencode($payment->ma_giao_dich_noi_bo)
        . '?resultCode=24&transactionStatus=02&orderId=' . urlencode($payment->ma_giao_dich_noi_bo)
    );

    expect($createResponse->json('data.payment.trang_thai'))->toBe(GiaoDichThanhToan::TRANG_THAI_PENDING);
    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_PENDING);
    expect($payment->fresh()->ma_giao_dich_gateway)->toBeNull();
});

it('keeps vnpay subscription pending on return without completed payment', function () {
    config()->set('app.frontend_url', 'http://localhost:5173');
    config()->set('services.vnpay.auto_complete_return_local', true);

    $this->seed(GoiDichVuSeeder::class);
    $candidate = NguoiDung::factory()->ungVien()->create();

    $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/goi-dich-vus/mua/vnpay', [
            'ma_goi' => 'PRO_MONTHLY',
        ]);

    $payment = GiaoDichThanhToan::query()->firstOrFail();

    $returnPayload = signVnpayTestPayload([
        'vnp_TmnCode' => 'VNPAYTST',
        'vnp_Amount' => (int) $payment->so_tien * 100,
        'vnp_BankCode' => 'NCB',
        'vnp_OrderInfo' => $payment->noi_dung,
        'vnp_ResponseCode' => '24',
        'vnp_TxnRef' => $payment->ma_giao_dich_noi_bo,
        'vnp_TransactionStatus' => '02',
    ]);

    $response = $this->get('/api/v1/payments/vnpay/return?' . http_build_query($returnPayload));

    $response->assertRedirect(
        'http://localhost:5173/plans?subscription=pending&plan=PRO_MONTHLY&orderId='
        . urlencode($payment->ma_giao_dich_noi_bo)
        . '&message=VNPay+response+24'
    );

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_PENDING);
    expect(NguoiDungGoiDichVu::query()->count())->toBe(0);
});
