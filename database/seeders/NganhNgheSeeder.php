<?php

namespace Database\Seeders;

use App\Models\NganhNghe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NganhNgheSeeder extends Seeder
{
    /**
     * Seed dữ liệu bảng nganh_nghes.
     *
     * Cấu trúc 2 cấp:
     *   Ngành gốc (danh_muc_cha_id = null)
     *     └── Ngành con
     */
    public function run(): void
    {
        // =========================================
        // NGÀNH GỐC + NGÀNH CON (Dữ liệu cố định)
        // =========================================
        $danhMuc = [
            [
                'ten_nganh' => 'Công nghệ thông tin',
                'icon' => '💻',
                'mo_ta' => 'Lĩnh vực công nghệ thông tin, phần mềm, hệ thống.',
                'con' => [
                    ['ten_nganh' => 'Lập trình Backend', 'icon' => '⚙️', 'mo_ta' => 'PHP, Java, Node.js, Python, Go...'],
                    ['ten_nganh' => 'Lập trình Frontend', 'icon' => '🎨', 'mo_ta' => 'React, Vue.js, Angular, HTML/CSS...'],
                    ['ten_nganh' => 'Lập trình Mobile', 'icon' => '📱', 'mo_ta' => 'iOS, Android, Flutter, React Native...'],
                    ['ten_nganh' => 'DevOps / SysAdmin', 'icon' => '🔧', 'mo_ta' => 'Docker, Kubernetes, CI/CD, Linux...'],
                    ['ten_nganh' => 'Kiểm thử phần mềm (QA)', 'icon' => '🧪', 'mo_ta' => 'Manual Testing, Automation, Performance...'],
                    ['ten_nganh' => 'Phân tích dữ liệu', 'icon' => '📊', 'mo_ta' => 'Data Analysis, BI, SQL, Python...'],
                ],
            ],
            [
                'ten_nganh' => 'Kinh doanh / Bán hàng',
                'icon' => '💰',
                'mo_ta' => 'Lĩnh vực kinh doanh, thương mại, bán hàng.',
                'con' => [
                    ['ten_nganh' => 'Nhân viên kinh doanh', 'icon' => '🤝', 'mo_ta' => 'B2B, B2C, telesales...'],
                    ['ten_nganh' => 'Quản lý bán hàng', 'icon' => '📋', 'mo_ta' => 'Sales Manager, Key Account...'],
                    ['ten_nganh' => 'Thương mại điện tử', 'icon' => '🛒', 'mo_ta' => 'E-commerce, Shopee, Lazada...'],
                ],
            ],
            [
                'ten_nganh' => 'Marketing / Truyền thông',
                'icon' => '📢',
                'mo_ta' => 'Lĩnh vực marketing, quảng cáo, truyền thông.',
                'con' => [
                    ['ten_nganh' => 'Digital Marketing', 'icon' => '🌐', 'mo_ta' => 'SEO, SEM, Social Media, Google Ads...'],
                    ['ten_nganh' => 'Content Marketing', 'icon' => '✍️', 'mo_ta' => 'Copywriting, Content Strategy...'],
                    ['ten_nganh' => 'Thiết kế đồ hoạ', 'icon' => '🖌️', 'mo_ta' => 'Photoshop, Illustrator, Figma...'],
                ],
            ],
            [
                'ten_nganh' => 'Kế toán / Tài chính',
                'icon' => '🏦',
                'mo_ta' => 'Lĩnh vực kế toán, kiểm toán, tài chính, ngân hàng.',
                'con' => [
                    ['ten_nganh' => 'Kế toán tổng hợp', 'icon' => '📒', 'mo_ta' => 'Kế toán nội bộ, thuế, báo cáo...'],
                    ['ten_nganh' => 'Tài chính doanh nghiệp', 'icon' => '💵', 'mo_ta' => 'Corporate Finance, FP&A...'],
                ],
            ],
            [
                'ten_nganh' => 'Nhân sự / Hành chính',
                'icon' => '👥',
                'mo_ta' => 'Lĩnh vực nhân sự, tuyển dụng, đào tạo, hành chính.',
                'con' => [
                    ['ten_nganh' => 'Tuyển dụng (Recruiter)', 'icon' => '🔍', 'mo_ta' => 'Headhunter, In-house Recruiter...'],
                    ['ten_nganh' => 'Đào tạo & Phát triển', 'icon' => '📚', 'mo_ta' => 'Training, L&D, OD...'],
                ],
            ],
            [
                'ten_nganh' => 'Giáo dục / Đào tạo',
                'icon' => '🎓',
                'mo_ta' => 'Lĩnh vực giáo dục, giảng dạy, nghiên cứu.',
                'con' => [
                    ['ten_nganh' => 'Giảng viên / Giáo viên', 'icon' => '👨‍🏫', 'mo_ta' => 'Giảng dạy tại trường, trung tâm...'],
                    ['ten_nganh' => 'Gia sư / Dạy kèm', 'icon' => '📖', 'mo_ta' => 'Dạy kèm, gia sư online/offline...'],
                ],
            ],
            [
                'ten_nganh' => 'Y tế / Sức khoẻ',
                'icon' => '🏥',
                'mo_ta' => 'Lĩnh vực chăm sóc sức khoẻ, y tế, dược phẩm.',
                'con' => [],
            ],
            [
                'ten_nganh' => 'Xây dựng / Bất động sản',
                'icon' => '🏗️',
                'mo_ta' => 'Lĩnh vực xây dựng, kiến trúc, bất động sản.',
                'con' => [],
            ],
        ];

        $tongGoc = 0;
        $tongCon = 0;

        foreach ($danhMuc as $goc) {
            $slugGoc = Str::slug($goc['ten_nganh']);
            $nganhGoc = NganhNghe::updateOrCreate([
                'slug' => $slugGoc,
            ], [
                'ten_nganh' => $goc['ten_nganh'],
                'slug' => $slugGoc,
                'mo_ta' => $goc['mo_ta'],
                'danh_muc_cha_id' => null,
                'icon' => $goc['icon'],
                'trang_thai' => NganhNghe::TRANG_THAI_HIEN_THI,
            ]);
            $tongGoc++;

            foreach ($goc['con'] as $con) {
                $slugCon = Str::slug($con['ten_nganh']);
                NganhNghe::updateOrCreate([
                    'slug' => $slugCon,
                ], [
                    'ten_nganh' => $con['ten_nganh'],
                    'slug' => $slugCon,
                    'mo_ta' => $con['mo_ta'],
                    'danh_muc_cha_id' => $nganhGoc->id,
                    'icon' => $con['icon'],
                    'trang_thai' => NganhNghe::TRANG_THAI_HIEN_THI,
                ]);
                $tongCon++;
            }
        }

        // Tạo 1 ngành bị ẩn (để test)
        NganhNghe::updateOrCreate([
            'slug' => 'nganh-test-an',
        ], [
            'ten_nganh' => 'Ngành test (ẩn)',
            'slug' => 'nganh-test-an',
            'mo_ta' => 'Ngành nghề tạm ẩn để test.',
            'danh_muc_cha_id' => null,
            'icon' => '🚫',
            'trang_thai' => NganhNghe::TRANG_THAI_AN,
        ]);
        $tongGoc++;

        $this->command->info('✅ NganhNgheSeeder: Đã tạo dữ liệu thành công!');
        $this->command->table(
            ['Loại', 'Số lượng'],
            [
                ['Ngành gốc (hiển thị)', $tongGoc - 1],
                ['Ngành gốc (ẩn)', '1'],
                ['Ngành con', $tongCon],
                ['Tổng cộng', $tongGoc + $tongCon],
            ]
        );
    }
}
