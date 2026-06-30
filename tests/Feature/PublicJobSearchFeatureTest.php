<?php

it('matches public jobs by canonical and legacy Ho Chi Minh location names', function () {
    $company = createCompanyForEmployer(App\Models\NguoiDung::factory()->nhaTuyenDung()->create());

    $legacyLocationJob = createJobForCompany($company, [
        'tieu_de' => 'Backend Developer',
        'dia_diem_lam_viec' => 'TP. Hồ Chí Minh',
    ]);

    $otherLocationJob = createJobForCompany($company, [
        'tieu_de' => 'Frontend Developer',
        'dia_diem_lam_viec' => 'Thành phố Hà Nội',
    ]);

    $this->getJson('/api/v1/tin-tuyen-dungs?dia_diem=' . urlencode('Thành phố Hồ Chí Minh'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonFragment(['id' => $legacyLocationJob->id])
        ->assertJsonMissing(['id' => $otherLocationJob->id]);
});
