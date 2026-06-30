<?php

use App\Models\CongTy;
use App\Models\NguoiDung;
use App\Models\PermissionDefinition;
use Illuminate\Support\Facades\Notification;

it('lets company owner create a new hr account directly', function () {
    Notification::fake();

    $owner = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $owner->id,
    ]);
    $company->thanhViens()->attach($owner->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/nha-tuyen-dung/cong-ty/thanh-viens', [
            'ho_ten' => 'Nguyen HR',
            'email' => 'hr.created@example.com',
            'mat_khau' => 'Password123!',
            'so_dien_thoai' => '0900000001',
            'vai_tro_noi_bo' => 'recruiter',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.hr.email', 'hr.created@example.com')
        ->assertJsonFragment(['email' => 'hr.created@example.com']);

    $hr = NguoiDung::query()->where('email', 'hr.created@example.com')->firstOrFail();

    expect($hr->isNhaTuyenDung())->toBeTrue();
    expect($hr->email_verified_at)->not->toBeNull();
    expect($hr->layVaiTroNoiBoCongTy($company))->toBe(CongTy::VAI_TRO_NOI_BO_MEMBER);

    Notification::assertNothingSent();
});

it('does not create an hr account with an existing email', function () {
    Notification::fake();

    $owner = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $owner->id,
    ]);
    $company->thanhViens()->attach($owner->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    NguoiDung::factory()->nhaTuyenDung()->create([
        'email' => 'existing.hr@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/nha-tuyen-dung/cong-ty/thanh-viens', [
            'ho_ten' => 'Existing HR',
            'email' => 'existing.hr@example.com',
            'mat_khau' => 'Password123!',
            'vai_tro_noi_bo' => 'recruiter',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    Notification::assertNothingSent();
});

it('rejects custom internal role management because only owner and hr member remain', function () {
    Notification::fake();

    $owner = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $owner->id,
    ]);
    $company->thanhViens()->attach($owner->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roleResponse = $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo', [
            'ten_vai_tro' => 'Talent Acquisition Lead',
            'mo_ta' => 'Lead tuyển dụng nội bộ',
            'vai_tro_goc' => 'recruiter',
        ]);

    $roleResponse
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Hệ thống hiện chỉ giữ 2 vai trò nội bộ: Owner và HR thường.');

    $this->actingAs($owner, 'sanctum')
        ->patchJson('/api/v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo/999999', [
            'ten_vai_tro' => 'Senior Talent Lead',
            'mo_ta' => 'Lead tuyển dụng senior',
            'vai_tro_goc' => 'admin_hr',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Hệ thống hiện chỉ giữ 2 vai trò nội bộ: Owner và HR thường.');
});

it('lets company owner configure feature permissions for each hr account', function () {
    $owner = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $owner->id,
    ]);
    $company->thanhViens()->attach($owner->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $hr = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company->thanhViens()->attach($hr->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_MEMBER,
        'quyen_noi_bo' => json_encode(CongTy::normalizeHrPermissions(null)),
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $permissions = CongTy::normalizeHrPermissions([
        'jobs' => true,
        'applications' => true,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/nha-tuyen-dung/cong-ty/thanh-viens/{$hr->id}/permissions", [
            'quyen_noi_bo' => $permissions,
        ])
        ->assertOk()
        ->assertJsonPath('data.quyen_noi_bo.jobs', true)
        ->assertJsonPath('data.quyen_noi_bo.members', false);

    expect($hr->fresh()->coQuyenNoiBoCongTy('jobs', $company))->toBeTrue();
    expect($hr->fresh()->coQuyenNoiBoCongTy('members', $company))->toBeFalse();
});

it('maps a newly created hr permission to an existing employer feature', function () {
    $owner = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $owner->id,
    ]);
    $company->thanhViens()->attach($owner->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $hr = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company->thanhViens()->attach($hr->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_MEMBER,
        'quyen_noi_bo' => json_encode(CongTy::normalizeHrPermissions(null)),
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $definitionResponse = $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/nha-tuyen-dung/cong-ty/thanh-viens/permissions/definitions', [
            'label' => 'Quản lý ví',
            'description' => 'Cho phép HR thao tác ví employer.',
            'mapped_permission_key' => 'billing',
        ]);

    $definitionResponse
        ->assertCreated()
        ->assertJsonPath('data.permission.mapped_permission_key', 'billing');

    $permissionKey = $definitionResponse->json('data.permission.key');
    $permissions = CongTy::normalizeHrPermissions([
        $permissionKey => true,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/nha-tuyen-dung/cong-ty/thanh-viens/{$hr->id}/permissions", [
            'quyen_noi_bo' => $permissions,
        ])
        ->assertOk()
        ->assertJsonPath("data.quyen_noi_bo.{$permissionKey}", true)
        ->assertJsonPath('data.quyen_noi_bo.billing', true);

    expect(PermissionDefinition::query()->where('key', $permissionKey)->value('mapped_permission_key'))->toBe('billing');
    expect($hr->fresh()->coQuyenNoiBoCongTy('billing', $company))->toBeTrue();
});

it('prevents hr without members permission from reading hr management data', function () {
    $owner = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company = CongTy::factory()->create([
        'nguoi_dung_id' => $owner->id,
    ]);
    $company->thanhViens()->attach($owner->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $hr = NguoiDung::factory()->nhaTuyenDung()->create([
        'email_verified_at' => now(),
    ]);
    $company->thanhViens()->attach($hr->id, [
        'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_MEMBER,
        'quyen_noi_bo' => json_encode(CongTy::normalizeHrPermissions([
            'company_profile' => true,
            'jobs' => true,
            'applications' => true,
        ])),
        'duoc_tao_boi' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($hr, 'sanctum')
        ->getJson('/api/v1/nha-tuyen-dung/cong-ty/thanh-viens')
        ->assertForbidden()
        ->assertJsonPath('code', 'COMPANY_ROLE_FORBIDDEN');

    $this->actingAs($hr, 'sanctum')
        ->getJson('/api/v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo')
        ->assertForbidden()
        ->assertJsonPath('code', 'COMPANY_ROLE_FORBIDDEN');

    $this->actingAs($hr, 'sanctum')
        ->getJson('/api/v1/nha-tuyen-dung/cong-ty')
        ->assertOk()
        ->assertJsonPath('data.quyen_noi_bo.members', false)
        ->assertJsonPath('data.thanh_viens', [])
        ->assertJsonPath('data.catalog_quyen_noi_bo', []);
});
