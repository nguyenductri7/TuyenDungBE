<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createCompanyForEmployer(\App\Models\NguoiDung $employer, array $attributes = []): \App\Models\CongTy
{
    return \App\Models\CongTy::factory()->create([
        'nguoi_dung_id' => $employer->id,
        ...$attributes,
    ]);
}

function createJobForCompany(\App\Models\CongTy $company, array $attributes = []): \App\Models\TinTuyenDung
{
    return \App\Models\TinTuyenDung::factory()->create([
        'cong_ty_id' => $company->id,
        'hr_phu_trach_id' => $attributes['hr_phu_trach_id'] ?? $company->nguoi_dung_id,
        ...$attributes,
    ]);
}

function createApplicationForCandidate(
    \App\Models\NguoiDung $candidate,
    \App\Models\TinTuyenDung $job,
    array $profileAttributes = [],
    array $applicationAttributes = [],
): \App\Models\UngTuyen {
    $profile = \App\Models\HoSo::factory()->forNguoiDung($candidate->id)->create($profileAttributes);

    return \App\Models\UngTuyen::create([
        'tin_tuyen_dung_id' => $job->id,
        'ho_so_id' => $profile->id,
        'hr_phu_trach_id' => $job->hr_phu_trach_id,
        'trang_thai' => \App\Models\UngTuyen::TRANG_THAI_CHO_DUYET,
        'thoi_gian_ung_tuyen' => now(),
        ...$applicationAttributes,
    ]);
}
