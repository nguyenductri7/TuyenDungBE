<?php

namespace Database\Factories;

use App\Models\NganhNghe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NganhNgheFactory extends Factory
{
    protected $model = NganhNghe::class;

    public function definition(): array
    {
        $ten = $this->faker->unique()->randomElement([
            'Marketing Online',
            'An ninh mạng',
            'Trí tuệ nhân tạo',
            'Game Developer',
            'Cloud Computing',
            'IoT Engineer',
            'Blockchain Developer',
            'Data Science',
            'Machine Learning',
        ]);

        return [
            'ten_nganh' => $ten,
            'slug' => Str::slug($ten),
            'mo_ta' => "Ngành {$ten} - Mô tả ngắn.",
            'danh_muc_cha_id' => null,
            'icon' => '💼',
            'trang_thai' => NganhNghe::TRANG_THAI_HIEN_THI,
        ];
    }

    public function an(): static
    {
        return $this->state(fn() => ['trang_thai' => NganhNghe::TRANG_THAI_AN]);
    }

    public function conCua(int $chaId): static
    {
        return $this->state(fn() => ['danh_muc_cha_id' => $chaId]);
    }
}
