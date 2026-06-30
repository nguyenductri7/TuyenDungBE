<?php

use App\Models\BangGiaTinhNangAi;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use App\Models\NguoiDungGoiDichVu;
use App\Models\PermissionDefinition;
use App\Models\ViNguoiDung;
use Database\Seeders\GoiDichVuSeeder;
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

it('returns billing overview aggregates for admin dashboard', function () {
    $this->seed(GoiDichVuSeeder::class);

    $admin = NguoiDung::factory()->admin()->create();
    $candidate = NguoiDung::factory()->ungVien()->create();
    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();

    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 120000,
        'so_du_tam_giu' => 3000,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'ADMIN-TOPUP-001',
        'ma_yeu_cau' => 'ADMIN-REQ-TOPUP-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 80000,
        'noi_dung' => 'Nap vi admin dashboard',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
        'paid_at' => now()->subHour(),
    ]);

    $subscriptionPayment = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'goi_dich_vu_id' => $plan->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'ADMIN-SUB-001',
        'ma_yeu_cau' => 'ADMIN-REQ-SUB-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_MUA_GOI,
        'so_tien' => 59000,
        'noi_dung' => 'Mua goi Pro thang',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
        'paid_at' => now()->subMinutes(30),
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'ADMIN-PENDING-001',
        'ma_yeu_cau' => 'ADMIN-REQ-PENDING-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 25000,
        'noi_dung' => 'Nap vi dang cho',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'ADMIN-FAILED-001',
        'ma_yeu_cau' => 'ADMIN-REQ-FAILED-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 15000,
        'noi_dung' => 'Nap vi that bai',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THAT_BAI,
    ]);

    NguoiDungGoiDichVu::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'goi_dich_vu_id' => $plan->id,
        'giao_dich_thanh_toan_id' => $subscriptionPayment->id,
        'ngay_bat_dau' => now()->subDay(),
        'ngay_het_han' => now()->addMonth(),
        'trang_thai' => NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG,
        'auto_renew' => false,
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/billing/overview');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.totals.processed_amount', 139000)
        ->assertJsonPath('data.totals.topup_amount', 80000)
        ->assertJsonPath('data.totals.subscription_revenue', 59000)
        ->assertJsonPath('data.totals.topup_count', 1)
        ->assertJsonPath('data.totals.subscription_count', 1)
        ->assertJsonPath('data.totals.pending_count', 1)
        ->assertJsonPath('data.totals.active_subscription_count', 1)
        ->assertJsonPath('data.totals.wallet_balance_amount', 120000)
        ->assertJsonPath('data.totals.wallet_hold_amount', 3000)
        ->assertJsonPath('data.status_breakdown.pending', 1)
        ->assertJsonPath('data.status_breakdown.success', 2)
        ->assertJsonPath('data.status_breakdown.failed', 1)
        ->assertJsonPath('data.status_breakdown.cancelled', 0)
        ->assertJsonPath('data.top_plans.0.plan_code', 'PRO_MONTHLY')
        ->assertJsonPath('data.top_plans.0.purchase_count', 1)
        ->assertJsonPath('data.top_plans.0.revenue', 59000)
        ->assertJsonCount(6, 'data.monthly_overview');

    expect(collect($response->json('data.recent_payments'))->pluck('ma_giao_dich_noi_bo')->all())
        ->toContain('ADMIN-SUB-001', 'ADMIN-TOPUP-001');
});

it('forbids non admin from billing overview', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/admin/billing/overview')
        ->assertForbidden();
});

it('forbids regular admin without explicit billing permission', function () {
    $admin = NguoiDung::factory()->admin()->create([
        'quyen_admin' => null,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/billing/overview')
        ->assertForbidden()
        ->assertJsonPath('code', 'ADMIN_PERMISSION_DENIED');
});

it('maps a newly created admin permission to an existing admin feature', function () {
    $superAdmin = NguoiDung::factory()->admin()->create([
        'cap_admin' => NguoiDung::CAP_ADMIN_SUPER_ADMIN,
        'quyen_admin' => null,
    ]);
    $admin = NguoiDung::factory()->admin()->create([
        'quyen_admin' => NguoiDung::defaultAdminPermissions(),
    ]);

    $definitionResponse = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/v1/admin/admins/permissions/definitions', [
            'label' => 'Quản lý ví hệ thống',
            'description' => 'Cho phép admin thao tác module billing.',
            'mapped_permission_key' => 'billing',
        ]);

    $definitionResponse
        ->assertCreated()
        ->assertJsonPath('data.permission.mapped_permission_key', 'billing');

    $permissionKey = $definitionResponse->json('data.permission.key');
    $permissions = NguoiDung::normalizeAdminPermissions([
        $permissionKey => true,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/v1/admin/admins/{$admin->id}/permissions", [
            'quyen_admin' => $permissions,
        ])
        ->assertOk()
        ->assertJsonPath("data.quyen_admin.{$permissionKey}", true)
        ->assertJsonPath('data.quyen_admin.billing', true);

    expect(PermissionDefinition::query()->where('key', $permissionKey)->value('mapped_permission_key'))->toBe('billing');
    expect($admin->fresh()->hasAdminPermission('billing'))->toBeTrue();
});

it('lets admin manage billing plans prices and list operational records', function () {
    $this->seed(GoiDichVuSeeder::class);

    $admin = NguoiDung::factory()->admin()->create();
    $candidate = NguoiDung::factory()->ungVien()->create();
    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 0,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'ADMIN-LIST-PENDING-001',
        'ma_yeu_cau' => 'ADMIN-LIST-REQ-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 30000,
        'noi_dung' => 'Pending payment',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);

    $planResponse = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/billing/plans', [
            'ma_goi' => 'TEAM_PRO',
            'ten_goi' => 'Team Pro',
            'mo_ta' => 'Gói demo cho quản trị billing.',
            'gia' => 99000,
            'chu_ky' => 'monthly',
            'trang_thai' => 'active',
            'is_free' => false,
            'features' => [
                [
                    'feature_code' => 'chatbot_message',
                    'quota' => 500,
                    'reset_cycle' => 'monthly',
                    'is_unlimited' => false,
                ],
            ],
        ]);

    $planResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.ma_goi', 'TEAM_PRO')
        ->assertJsonPath('data.tinh_nangs.0.feature_code', 'chatbot_message');

    $planId = $planResponse->json('data.id');

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/billing/plans/{$planId}", [
            'ma_goi' => 'TEAM_PRO',
            'ten_goi' => 'Team Pro Updated',
            'mo_ta' => 'Đã cập nhật.',
            'gia' => 109000,
            'chu_ky' => 'monthly',
            'trang_thai' => 'inactive',
            'is_free' => false,
            'features' => [
                [
                    'feature_code' => 'career_report_generation',
                    'quota' => 50,
                    'reset_cycle' => 'monthly',
                    'is_unlimited' => false,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.ten_goi', 'Team Pro Updated')
        ->assertJsonPath('data.tinh_nangs.0.feature_code', 'career_report_generation');

    $priceResponse = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/billing/prices', [
            'feature_code' => 'cover_letter_generation',
            'ten_hien_thi' => 'Cover Letter AI',
            'don_gia' => 4000,
            'don_vi_tinh' => 'request',
            'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
        ]);

    $priceResponse
        ->assertCreated()
        ->assertJsonPath('data.feature_code', 'cover_letter_generation');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/billing/payments?q=ADMIN-LIST')
        ->assertOk()
        ->assertJsonPath('data.data.0.ma_giao_dich_noi_bo', 'ADMIN-LIST-PENDING-001');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/billing/subscriptions')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('lets admin reconcile pending momo payment from gateway query', function () {
    config()->set('services.momo.base_url', 'https://test-payment.momo.vn');
    config()->set('services.momo.partner_code', 'MOMO_TEST');
    config()->set('services.momo.access_key', 'access-key');
    config()->set('services.momo.secret_key', 'secret-key');

    Http::fake([
        'https://test-payment.momo.vn/v2/gateway/api/query' => Http::response([
            'partnerCode' => 'MOMO_TEST',
            'orderId' => 'ADMIN-RECONCILE-001',
            'requestId' => 'REQ-GATEWAY',
            'amount' => 45000,
            'resultCode' => 0,
            'message' => 'Successful.',
            'transId' => 'MOMO-TRANS-001',
        ], 200),
    ]);

    $admin = NguoiDung::factory()->admin()->create();
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
        'ma_giao_dich_noi_bo' => 'ADMIN-RECONCILE-001',
        'ma_yeu_cau' => 'ADMIN-RECONCILE-REQ-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 45000,
        'noi_dung' => 'Pending reconcile',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/billing/payments/{$payment->ma_giao_dich_noi_bo}/reconcile")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG);

    expect($payment->fresh()->ma_giao_dich_gateway)->toBe('MOMO-TRANS-001');
    expect($wallet->fresh()->so_du_hien_tai)->toBe(45000);
});

it('lets admin reconcile pending vnpay payment from gateway query', function () {
    config()->set('services.vnpay.base_url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    config()->set('services.vnpay.tmn_code', 'VNPAYTST');
    config()->set('services.vnpay.hash_secret', 'secret-key');

    Http::fake([
        'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction' => Http::response(
            signVnpayQueryResponsePayload([
                'vnp_ResponseId' => 'VNPAY-QUERY-RESP-001',
                'vnp_Command' => 'querydr',
                'vnp_ResponseCode' => '00',
                'vnp_Message' => 'Query success',
                'vnp_TmnCode' => 'VNPAYTST',
                'vnp_TxnRef' => 'ADMIN-VNPAY-RECONCILE-001',
                'vnp_Amount' => 50000 * 100,
                'vnp_BankCode' => 'NCB',
                'vnp_PayDate' => '20260428101530',
                'vnp_TransactionNo' => 'VNPAY-TRANS-001',
                'vnp_TransactionType' => '01',
                'vnp_TransactionStatus' => '00',
                'vnp_OrderInfo' => 'Pending VNPAY reconcile',
                'vnp_PromotionCode' => '',
                'vnp_PromotionAmount' => '',
            ]),
            200
        ),
    ]);

    $admin = NguoiDung::factory()->admin()->create();
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
        'gateway' => GiaoDichThanhToan::GATEWAY_VNPAY,
        'ma_giao_dich_noi_bo' => 'ADMIN-VNPAY-RECONCILE-001',
        'ma_yeu_cau' => 'ADMIN-VNPAY-REQ-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 50000,
        'noi_dung' => 'Pending VNPAY reconcile',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
        'raw_request_json' => [
            'vnp_CreateDate' => '20260428100000',
        ],
    ]);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/billing/payments/{$payment->ma_giao_dich_noi_bo}/reconcile")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
        ->assertJsonPath('data.trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG);

    expect($payment->fresh()->ma_giao_dich_gateway)->toBe('VNPAY-TRANS-001');
    expect($wallet->fresh()->so_du_hien_tai)->toBe(50000);

    Http::assertSentCount(1);
});
