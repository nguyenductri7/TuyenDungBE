<?php

namespace Database\Factories;

use App\Models\CongTy;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CongTy>
 */
class CongTyFactory extends Factory
{
    protected $model = CongTy::class;

    public function definition(): array
    {
        return [
            'nguoi_dung_id' => NguoiDung::factory()->nhaTuyenDung(),
            'ten_cong_ty' => $this->faker->company(),
            'ma_so_thue' => $this->faker->unique()->numerify('##########'),
            'mo_ta' => $this->faker->sentence(12),
            'dia_chi' => $this->faker->city(),
            'dien_thoai' => '0' . $this->faker->numerify('#########'),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
            'logo' => null,
            'nganh_nghe_id' => null,
            'quy_mo' => $this->faker->randomElement(CongTy::QUY_MO_LIST),
            'trang_thai' => CongTy::TRANG_THAI_HOAT_DONG,
        ];
    }
}
