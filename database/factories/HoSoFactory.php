<?php

namespace Database\Factories;

use App\Models\HoSo;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HoSo>
 */
class HoSoFactory extends Factory
{
    protected $model = HoSo::class;

    public function definition(): array
    {
        $trinhDoList = ['trung_hoc', 'trung_cap', 'cao_dang', 'dai_hoc', 'thac_si', 'tien_si', 'khac'];

        $tieuDeList = [
            'Hồ sơ Backend Developer',
            'Hồ sơ Frontend Developer',
            'Hồ sơ Full-stack Developer',
            'Hồ sơ DevOps Engineer',
            'Hồ sơ Data Analyst',
            'Hồ sơ Mobile Developer',
            'Hồ sơ QA/Tester',
            'Hồ sơ UI/UX Designer',
            'Hồ sơ Project Manager',
            'Hồ sơ Business Analyst',
        ];

        $mucTieuList = [
            'Mong muốn ứng tuyển vào vị trí lập trình viên tại công ty công nghệ hàng đầu.',
            'Tìm kiếm cơ hội phát triển sự nghiệp trong lĩnh vực phần mềm.',
            'Mục tiêu trở thành Senior Developer trong 2 năm tới.',
            'Muốn được làm việc trong môi trường quốc tế, sử dụng công nghệ mới.',
            'Định hướng phát triển theo hướng quản lý dự án phần mềm.',
        ];

        $moTaList = [
            'Tôi là một người chăm chỉ, ham học hỏi, có khả năng làm việc nhóm tốt.',
            'Có kinh nghiệm làm việc với nhiều dự án thực tế, kỹ năng giao tiếp tốt.',
            'Thành thạo nhiều ngôn ngữ lập trình, luôn cập nhật công nghệ mới.',
            'Có khả năng phân tích vấn đề và đưa ra giải pháp hiệu quả.',
            'Yêu thích công nghệ, luôn tìm tòi và áp dụng các phương pháp tối ưu.',
        ];

        return [
            'nguoi_dung_id' => NguoiDung::where('vai_tro', NguoiDung::VAI_TRO_UNG_VIEN)->inRandomOrder()->first()?->id ?? 1,
            'tieu_de_ho_so' => $this->faker->randomElement($tieuDeList),
            'muc_tieu_nghe_nghiep' => $this->faker->randomElement($mucTieuList),
            'trinh_do' => $this->faker->randomElement($trinhDoList),
            'kinh_nghiem_nam' => $this->faker->numberBetween(0, 15),
            'mo_ta_ban_than' => $this->faker->randomElement($moTaList),
            'file_cv' => null,
            'trang_thai' => HoSo::TRANG_THAI_CONG_KHAI,
        ];
    }

    /** Tạo hồ sơ trạng thái ẩn */
    public function an(): static
    {
        return $this->state(fn(array $attributes) => [
            'trang_thai' => HoSo::TRANG_THAI_AN,
        ]);
    }

    /** Tạo hồ sơ trạng thái công khai */
    public function congKhai(): static
    {
        return $this->state(fn(array $attributes) => [
            'trang_thai' => HoSo::TRANG_THAI_CONG_KHAI,
        ]);
    }

    /** Gán hồ sơ cho người dùng cụ thể */
    public function forNguoiDung(int $nguoiDungId): static
    {
        return $this->state(fn(array $attributes) => [
            'nguoi_dung_id' => $nguoiDungId,
        ]);
    }
}
