<?php

use App\Models\CongTy;
use App\Models\NguoiDung;
use Illuminate\Support\Facades\Notification;

it('creates an employer company and makes the registered employer its owner', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/dang-ky', [
        'ho_ten' => 'Doan Khanh Mai',
        'ten_cong_ty' => 'TechViet Solutions',
        'email' => 'owner.registration@example.com',
        'so_dien_thoai' => '0900000000',
        'mat_khau' => 'Password123!',
        'mat_khau_confirmation' => 'Password123!',
        'vai_tro' => NguoiDung::VAI_TRO_NHA_TUYEN_DUNG,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'owner.registration@example.com')
        ->assertJsonPath('data.vai_tro', NguoiDung::VAI_TRO_NHA_TUYEN_DUNG);

    $owner = NguoiDung::query()
        ->where('email', 'owner.registration@example.com')
        ->firstOrFail();
    $company = CongTy::query()
        ->where('nguoi_dung_id', $owner->id)
        ->firstOrFail();

    expect($owner->isNhaTuyenDung())->toBeTrue();
    expect($owner->isActive())->toBeTrue();
    expect($company->ten_cong_ty)->toBe('TechViet Solutions');
    expect($company->ma_so_thue)->toBe('DKNTD' . $owner->id);
    expect($company->isHoatDong())->toBeTrue();
    expect($owner->fresh()->congTyHienTai()?->id)->toBe($company->id);
    expect($owner->fresh()->layVaiTroNoiBoCongTy($company))->toBe(CongTy::VAI_TRO_NOI_BO_OWNER);
    expect($owner->fresh()->coQuyenNoiBoCongTy('members', $company))->toBeTrue();
    expect($owner->fresh()->coQuyenNoiBoCongTy('billing', $company))->toBeTrue();

    $this->actingAs($owner->fresh(), 'sanctum')
        ->getJson('/api/v1/nha-tuyen-dung/cong-ty')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.ten_cong_ty', 'TechViet Solutions')
        ->assertJsonPath('data.la_chu_so_huu', true)
        ->assertJsonPath('data.vai_tro_noi_bo_hien_tai', CongTy::VAI_TRO_NOI_BO_OWNER)
        ->assertJsonPath('data.quyen_noi_bo.members', true);
});

