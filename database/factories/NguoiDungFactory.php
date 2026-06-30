<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NguoiDung>
 */
class NguoiDungFactory extends Factory
{
    protected $model = NguoiDung::class;

    protected static ?string $password;

    public function definition(): array
    {
        $tinhThanh = [
            'Hà Nội',
            'TP. Hồ Chí Minh',
            'Đà Nẵng',
            'Hải Phòng',
            'Cần Thơ',
            'An Giang',
            'Bình Dương',
            'Đồng Nai',
        ];

        return [
            'ho_ten' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'mat_khau' => static::$password ??= Hash::make('password123'),
            'so_dien_thoai' => '0' . $this->faker->numerify('#########'),
            'ngay_sinh' => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'gioi_tinh' => $this->faker->randomElement(['nam', 'nu', 'khac']),
            'dia_chi' => $this->faker->streetAddress() . ', ' . $this->faker->randomElement($tinhThanh),
            'anh_dai_dien' => null,
            'vai_tro' => NguoiDung::VAI_TRO_UNG_VIEN,
            'trang_thai' => 1,
        ];
    }

    /** Tạo tài khoản Admin */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => NguoiDung::VAI_TRO_ADMIN,
            'cap_admin' => NguoiDung::CAP_ADMIN_ADMIN,
            'quyen_admin' => NguoiDung::allAdminPermissions(),
        ]);
    }

    /** Tạo tài khoản Nhà tuyển dụng */
    public function nhaTuyenDung(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => NguoiDung::VAI_TRO_NHA_TUYEN_DUNG,
        ]);
    }

    /** Tạo tài khoản Ứng viên */
    public function ungVien(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => NguoiDung::VAI_TRO_UNG_VIEN,
        ]);
    }

    /** Tạo tài khoản bị vô hiệu hóa */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'trang_thai' => 0,
        ]);
    }
}
