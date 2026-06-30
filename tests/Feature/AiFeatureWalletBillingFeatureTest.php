<?php

use App\Models\BangGiaTinhNangAi;
use App\Models\BienDongVi;
use App\Models\CongTy;
use App\Models\GiaoDichThanhToan;
use App\Models\HoSo;
use App\Models\NguoiDung;
use App\Models\SuDungTinhNangAi;
use App\Models\TinTuyenDung;
use App\Models\TuVanNgheNghiep;
use App\Models\UngTuyen;
use App\Models\ViNguoiDung;
use Illuminate\Support\Facades\Http;

it('charges wallet successfully when generating a career report', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.career_report_generation', 0);

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
    ]);

    BangGiaTinhNangAi::create([
        'feature_code' => 'career_report_generation',
        'ten_hien_thi' => 'Sinh báo cáo định hướng nghề nghiệp',
        'don_gia' => 5000,
        'don_vi_tinh' => 'request',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 15000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);
    $profile = HoSo::factory()->forNguoiDung($candidate->id)->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'career-report-test-1')
        ->postJson("/api/v1/ung-vien/ho-sos/{$profile->id}/career-report");

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nghe_de_xuat', 'Backend Developer');

    $wallet = ViNguoiDung::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();
    expect($wallet->fresh()->so_du_hien_tai)->toBe(10000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);

    $usage = SuDungTinhNangAi::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();
    expect($usage->feature_code)->toBe('career_report_generation');
    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect($usage->bien_dong_vi_reserve_id)->not->toBeNull();
    expect($usage->bien_dong_vi_ket_toan_id)->not->toBeNull();
});

it('lets candidate delete a generated career report from history', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.career_report_generation', 0);

    Http::fake([
        'http://127.0.0.1:8001/*' => Http::response([
            'success' => true,
            'model_version' => 'career_report_v2',
            'data' => [
                'nghe_de_xuat' => 'Backend Developer',
                'muc_do_phu_hop' => 88,
                'goi_y_ky_nang_bo_sung' => ['skills' => ['Docker']],
                'bao_cao_chi_tiet' => 'Bao cao chi tiet',
            ],
        ], 200),
    ]);

    BangGiaTinhNangAi::create([
        'feature_code' => 'career_report_generation',
        'ten_hien_thi' => 'Sinh báo cáo định hướng nghề nghiệp',
        'don_gia' => 5000,
        'don_vi_tinh' => 'request',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 15000,
        'so_du_tam_giu' => 0,
        'don_vi_tien_te' => 'VND',
        'trang_thai' => ViNguoiDung::TRANG_THAI_HOAT_DONG,
    ]);
    $profile = HoSo::factory()->forNguoiDung($candidate->id)->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->withHeader('X-Idempotency-Key', 'career-report-delete-1')
        ->postJson("/api/v1/ung-vien/ho-sos/{$profile->id}/career-report");

    $response->assertOk()->assertJsonPath('success', true);

    expect(TuVanNgheNghiep::query()->where('nguoi_dung_id', $candidate->id)->count())->toBe(1);
    expect(SuDungTinhNangAi::query()->where('nguoi_dung_id', $candidate->id)->count())->toBe(1);

    $this->actingAs($candidate, 'sanctum')
        ->deleteJson('/api/v1/ung-vien/tu-van-nghe-nghieps/' . $response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(TuVanNgheNghiep::query()->where('nguoi_dung_id', $candidate->id)->count())->toBe(0);
});

it('charges wallet successfully when generating a cover letter draft', function () {
    config()->set('services.ai_service.base_url', 'http://127.0.0.1:8001');
    config()->set('billing.free_quota.cover_letter_generation', 0);

    Http::fake([
        'http://127.0.0.1:8001/*' => Http::response([
            'success' => true,
            'model_version' => 'cover_letter_v1',
            'data' => [
                'thu_xin_viec_ai' => 'Noi dung thu xin viec AI',
            ],
        ], 200),
    ]);

    BangGiaTinhNangAi::create([
        'feature_code' => 'cover_letter_generation',
        'ten_hien_thi' => 'Sinh thư xin việc AI',
        'don_gia' => 3000,
        'don_vi_tinh' => 'request',
        'trang_thai' => BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG,
    ]);

    $candidate = NguoiDung::factory()->ungVien()->create();
    ViNguoiDung::create([
        'nguoi_dung_id' => $candidate->id,
        'so_du_hien_tai' => 9000,
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
        ->withHeader('X-Idempotency-Key', 'cover-letter-test-1')
        ->postJson('/api/v1/ung-vien/ung-tuyens/generate-cover-letter', [
            'ho_so_id' => $profile->id,
            'tin_tuyen_dung_id' => $job->id,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.thu_xin_viec_ai', 'Noi dung thu xin viec AI');

    $wallet = ViNguoiDung::query()->where('nguoi_dung_id', $candidate->id)->firstOrFail();
    expect($wallet->fresh()->so_du_hien_tai)->toBe(6000);
    expect($wallet->fresh()->so_du_tam_giu)->toBe(0);

    $usage = SuDungTinhNangAi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('feature_code', 'cover_letter_generation')
        ->firstOrFail();

    expect($usage->trang_thai)->toBe(SuDungTinhNangAi::TRANG_THAI_THANH_CONG);
    expect(UngTuyen::query()->where('tin_tuyen_dung_id', $job->id)->exists())->toBeTrue();

    $captureTx = BienDongVi::query()
        ->where('nguoi_dung_id', $candidate->id)
        ->where('loai_bien_dong', BienDongVi::LOAI_USAGE_CAPTURE)
        ->first();

    expect($captureTx)->not->toBeNull();
    expect((int) $captureTx->so_tien)->toBe(3000);
});
