<?php

use App\Models\BienDongVi;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use App\Models\NguoiDungGoiDichVu;
use App\Models\ViNguoiDung;
use Database\Seeders\GoiDichVuSeeder;
use Illuminate\Support\Facades\Http;

it('lists active plans and current subscription for candidate', function () {
    $this->seed(GoiDichVuSeeder::class);

    $candidate = NguoiDung::factory()->ungVien()->create();

    $plansResponse = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/goi-dich-vus');

    $plansResponse
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($plansResponse->json('data'))->toHaveCount(3);

    $currentResponse = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/goi-dich-vu-hien-tai');

    $currentResponse
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);
});

it('creates momo subscription payment and activates pro plan on local return', function () {
    config()->set('app.frontend_url', 'http://localhost:5173');
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
        'https://test-payment.momo.vn/*' => Http::response([
            'resultCode' => 0,
            'message' => 'Success',
            'payUrl' => 'https://pay.momo.test/subscription-checkout',
        ], 200),
    ]);

    $this->seed(GoiDichVuSeeder::class);
    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();
    $candidate = NguoiDung::factory()->ungVien()->create();

    $createResponse = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/goi-dich-vus/mua/momo', [
            'ma_goi' => 'PRO_MONTHLY',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pay_url', 'https://pay.momo.test/subscription-checkout')
        ->assertJsonPath('data.plan.ma_goi', 'PRO_MONTHLY');

    $payment = GiaoDichThanhToan::query()->firstOrFail();

    expect($payment->loai_giao_dich)->toBe(GiaoDichThanhToan::LOAI_MUA_GOI);
    expect((int) $payment->goi_dich_vu_id)->toBe($plan->id);

    $response = $this->get('/api/v1/payments/momo/return?' . http_build_query([
        'partnerCode' => 'MOMO_TEST',
        'orderId' => $payment->ma_giao_dich_noi_bo,
        'requestId' => $payment->ma_yeu_cau,
        'amount' => $plan->gia,
        'orderInfo' => $payment->noi_dung,
        'resultCode' => 0,
        'message' => 'Thành công.',
        'transId' => 'SUB123456',
    ]));

    $response->assertRedirect('http://localhost:5173/plans?subscription=success&plan=PRO_MONTHLY&orderId=' . urlencode($payment->ma_giao_dich_noi_bo) . '&message=Th%C3%A0nh+c%C3%B4ng.');

    $subscription = NguoiDungGoiDichVu::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();

    expect($payment->fresh()->trang_thai)->toBe(GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
    expect($subscription->goiDichVu->ma_goi)->toBe('PRO_MONTHLY');
    expect($subscription->trang_thai)->toBe(NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG);
    expect($subscription->giao_dich_thanh_toan_id)->toBe($payment->id);
});

it('purchases a subscription directly from wallet balance and activates plan immediately', function () {
    $this->seed(GoiDichVuSeeder::class);

    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();
    $candidate = NguoiDung::factory()->ungVien()->create();
    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 100000,
        'so_du_tam_giu' => 5000,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/goi-dich-vus/mua/vi', [
            'ma_goi' => 'PRO_MONTHLY',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.payment.gateway', GiaoDichThanhToan::GATEWAY_WALLET)
        ->assertJsonPath('data.payment.trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG)
        ->assertJsonPath('data.plan.ma_goi', 'PRO_MONTHLY')
        ->assertJsonPath('data.subscription.goi_dich_vu_id', $plan->id)
        ->assertJsonPath('data.wallet_transaction.loai_bien_dong', BienDongVi::LOAI_SUBSCRIPTION_PURCHASE_DEBIT)
        ->assertJsonPath('data.pay_url', null);

    $payment = GiaoDichThanhToan::query()->firstOrFail();
    $subscription = NguoiDungGoiDichVu::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();
    $walletDebit = BienDongVi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai_bien_dong', BienDongVi::LOAI_SUBSCRIPTION_PURCHASE_DEBIT)
        ->first();

    expect($payment->gateway)->toBe(GiaoDichThanhToan::GATEWAY_WALLET);
    expect($payment->loai_giao_dich)->toBe(GiaoDichThanhToan::LOAI_MUA_GOI);
    expect($payment->paid_at)->not->toBeNull();
    expect($wallet->fresh()->so_du_hien_tai)->toBe(100000 - (int) $plan->gia);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(5000);
    expect($subscription->goiDichVu->ma_goi)->toBe('PRO_MONTHLY');
    expect($subscription->trang_thai)->toBe(NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG);
    expect($subscription->giao_dich_thanh_toan_id)->toBe($payment->id);
    expect($walletDebit)->not->toBeNull();
    expect((int) $walletDebit->so_tien)->toBe((int) $plan->gia);
    expect($walletDebit->metadata_json['gateway'])->toBe(GiaoDichThanhToan::GATEWAY_WALLET);
});

it('rejects subscription wallet purchase when available balance is insufficient', function () {
    $this->seed(GoiDichVuSeeder::class);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 10000,
        'so_du_tam_giu' => 2000,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/ung-vien/goi-dich-vus/mua/vi', [
            'ma_goi' => 'PRO_MONTHLY',
        ]);

    $response
        ->assertStatus(402)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'WALLET_SUBSCRIPTION_INSUFFICIENT_BALANCE');

    expect(GiaoDichThanhToan::query()->count())->toBe(0);
    expect(NguoiDungGoiDichVu::query()->count())->toBe(0);
});
