<?php

namespace Database\Factories;

use App\Models\CongTy;
use App\Models\TinTuyenDung;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TinTuyenDung>
 */
class TinTuyenDungFactory extends Factory
{
    protected $model = TinTuyenDung::class;

    public function definition(): array
    {
        return [
            'tieu_de' => $this->faker->randomElement([
                'Backend Developer Laravel',
                'Frontend Developer Vue',
                'Data Analyst',
                'QA Automation Engineer',
            ]),
            'mo_ta_cong_viec' => $this->faker->paragraph(),
            'dia_diem_lam_viec' => $this->faker->randomElement(['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng']),
            'hinh_thuc_lam_viec' => $this->faker->randomElement(TinTuyenDung::HINH_THUC_LIST),
            'cap_bac' => $this->faker->randomElement(['Junior', 'Middle', 'Senior']),
            'so_luong_tuyen' => 3,
            'muc_luong_tu' => 12000000,
            'muc_luong_den' => 20000000,
            'don_vi_luong' => 'VND',
            'kinh_nghiem_yeu_cau' => '1 năm',
            'trinh_do_yeu_cau' => 'Đại học',
            'ngay_het_han' => now()->addDays(14),
            'luot_xem' => 0,
            'cong_ty_id' => CongTy::factory(),
            'hr_phu_trach_id' => null,
            'trang_thai' => TinTuyenDung::TRANG_THAI_HOAT_DONG,
            'published_at' => now(),
            'reactivated_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'ngay_het_han' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'trang_thai' => TinTuyenDung::TRANG_THAI_TAM_NGUNG,
        ]);
    }
}
