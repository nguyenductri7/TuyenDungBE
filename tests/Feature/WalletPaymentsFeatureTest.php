<?php

use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\NguoiDung;
use App\Models\ViNguoiDung;
use Database\Seeders\GoiDichVuSeeder;

it('lists only the authenticated candidate payments with filters', function () {
    $this->seed(GoiDichVuSeeder::class);

    $candidate = NguoiDung::factory()->ungVien()->create();
    $otherCandidate = NguoiDung::factory()->ungVien()->create();
    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_MONTHLY')->firstOrFail();

    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 150000,
        'so_du_tam_giu' => 5000,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $otherWallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $otherCandidate->id,
        'so_du_hien_tai' => 90000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'PAY-TOPUP-001',
        'ma_yeu_cau' => 'REQ-TOPUP-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 50000,
        'noi_dung' => 'Nap vi 50000',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
        'paid_at' => now()->subMinutes(10),
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'goi_dich_vu_id' => $plan->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'PAY-SUB-001',
        'ma_yeu_cau' => 'REQ-SUB-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_MUA_GOI,
        'so_tien' => 59000,
        'noi_dung' => 'Mua goi PRO_MONTHLY',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_PENDING,
    ]);

    GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $otherCandidate->id,
        'vi_nguoi_dung_id' => $otherWallet->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'PAY-OTHER-001',
        'ma_yeu_cau' => 'REQ-OTHER-001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_NAP_VI,
        'so_tien' => 99000,
        'noi_dung' => 'Nap vi user khac',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/payments?loai_giao_dich=topup_wallet&trang_thai=success');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.ma_giao_dich_noi_bo', 'PAY-TOPUP-001')
        ->assertJsonPath('data.data.0.loai_giao_dich', GiaoDichThanhToan::LOAI_NAP_VI)
        ->assertJsonPath('data.data.0.trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG);
});

it('shows payment detail only for the authenticated candidate owner', function () {
    $this->seed(GoiDichVuSeeder::class);

    $candidate = NguoiDung::factory()->ungVien()->create();
    $otherCandidate = NguoiDung::factory()->ungVien()->create();
    $plan = GoiDichVu::query()->where('ma_goi', 'PRO_YEARLY')->firstOrFail();

    $wallet = ViNguoiDung::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 300000,
        'so_du_tam_giu' => 10000,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);

    $payment = GiaoDichThanhToan::query()->create([
        'nguoi_dung_id' => $candidate->id,
        'vi_nguoi_dung_id' => $wallet->id,
        'goi_dich_vu_id' => $plan->id,
        'gateway' => GiaoDichThanhToan::GATEWAY_MOMO,
        'ma_giao_dich_noi_bo' => 'PAY-DETAIL-001',
        'ma_yeu_cau' => 'REQ-DETAIL-001',
        'ma_giao_dich_gateway' => 'MOMO-999001',
        'loai_giao_dich' => GiaoDichThanhToan::LOAI_MUA_GOI,
        'so_tien' => 499000,
        'noi_dung' => 'Mua goi PRO_YEARLY',
        'trang_thai' => GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
        'paid_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/payments/PAY-DETAIL-001')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.ma_giao_dich_noi_bo', 'PAY-DETAIL-001')
        ->assertJsonPath('data.goi_dich_vu.ma_goi', 'PRO_YEARLY')
        ->assertJsonPath('data.vi_nguoi_dung.so_du_hien_tai', 300000)
        ->assertJsonPath('data.vi_nguoi_dung.so_du_tam_giu', 10000);

    $this->actingAs($otherCandidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/payments/' . $payment->ma_giao_dich_noi_bo)
        ->assertNotFound();
});
