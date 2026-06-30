<?php

namespace Database\Seeders;

use App\Models\KyNang;
use App\Models\NguoiDung;
use App\Models\NguoiDungKyNang;
use Illuminate\Database\Seeder;

class NguoiDungKyNangSeeder extends Seeder
{
    public function run(): void
    {
        $skillsByCandidate = [
            'ungvien.backend@demo.vn' => [
                ['PHP', 4, 3, 1],
                ['Laravel', 4, 3, 1],
                ['REST API', 5, 3, 0],
                ['MySQL', 4, 3, 1],
                ['Redis', 3, 2, 0],
                ['Docker', 3, 2, 0],
                ['Git', 4, 4, 0],
                ['JavaScript', 3, 2, 0],
                ['Vue.js', 3, 1, 0],
            ],
            'ungvien.frontend@demo.vn' => [
                ['JavaScript', 4, 3, 0],
                ['TypeScript', 4, 2, 0],
                ['Vue.js', 4, 2, 0],
                ['React', 4, 2, 0],
                ['Tailwind CSS', 4, 2, 0],
                ['HTML/CSS', 5, 4, 0],
                ['Figma', 4, 3, 1],
                ['Git', 3, 2, 0],
            ],
            'ungvien.data@demo.vn' => [
                ['SQL', 5, 4, 0],
                ['Power BI', 4, 3, 1],
                ['Microsoft Excel', 5, 5, 1],
                ['Python', 3, 2, 0],
                ['Data Analysis', 5, 4, 0],
                ['Data Visualization', 4, 3, 0],
                ['ETL', 3, 2, 0],
                ['Presentation', 4, 4, 0],
            ],
            'ungvien.marketing@demo.vn' => [
                ['Facebook Ads', 4, 2, 1],
                ['Google Ads', 4, 2, 1],
                ['TikTok Ads', 3, 1, 0],
                ['Content Marketing', 4, 2, 0],
                ['SEO', 3, 2, 0],
                ['Google Analytics', 4, 2, 0],
                ['Social Media Marketing', 4, 3, 0],
            ],
            'ungvien.qa@demo.vn' => [
                ['Manual Testing', 5, 5, 0],
                ['Postman', 4, 4, 0],
                ['TestRail', 4, 3, 0],
                ['Automation Testing', 3, 2, 0],
                ['Selenium', 3, 2, 0],
                ['Problem Solving', 4, 5, 0],
            ],
            'ungvien.sales@demo.vn' => [
                ['Sales B2B', 4, 3, 0],
                ['Sales B2C', 4, 3, 0],
                ['Telesales', 4, 2, 0],
                ['CRM', 4, 3, 0],
                ['Negotiation', 4, 3, 0],
                ['Customer Service', 4, 4, 0],
                ['Lead Generation', 3, 2, 0],
            ],
            'ungvien.accounting@demo.vn' => [
                ['Accounting', 5, 4, 1],
                ['Bookkeeping', 4, 4, 0],
                ['Tax Declaration', 4, 3, 0],
                ['Payroll', 3, 2, 0],
                ['MISA', 4, 3, 1],
                ['Microsoft Excel', 5, 5, 1],
                ['Financial Analysis', 3, 2, 0],
            ],
            'ungvien.hr@demo.vn' => [
                ['Recruitment', 5, 3, 0],
                ['Talent Acquisition', 4, 3, 0],
                ['Interviewing', 4, 3, 0],
                ['Onboarding', 4, 2, 0],
                ['Employee Relations', 3, 2, 0],
                ['Communication', 5, 5, 0],
            ],
            'ungvien.teacher@demo.vn' => [
                ['Lesson Planning', 4, 3, 0],
                ['Classroom Management', 4, 3, 0],
                ['Online Teaching', 4, 3, 0],
                ['LMS', 3, 2, 0],
                ['Tiếng Anh', 5, 6, 1],
                ['Presentation', 4, 4, 0],
            ],
            'ungvien.nurse@demo.vn' => [
                ['Patient Care', 5, 4, 1],
                ['Medical Records', 4, 3, 0],
                ['Nursing Care', 5, 4, 1],
                ['Clinical Assistance', 4, 3, 0],
                ['Customer Service', 4, 4, 0],
                ['Communication', 4, 4, 0],
            ],
            'ungvien.construction@demo.vn' => [
                ['AutoCAD', 5, 5, 1],
                ['Revit', 4, 3, 0],
                ['SketchUp', 4, 3, 0],
                ['Project Management', 3, 2, 0],
                ['Document Control', 4, 4, 0],
                ['Problem Solving', 4, 5, 0],
            ],
            'ungvien.logistics@demo.vn' => [
                ['Warehouse Management', 4, 3, 0],
                ['Inventory Management', 4, 3, 0],
                ['Procurement', 3, 2, 0],
                ['Transportation Management', 4, 3, 0],
                ['Supply Chain', 4, 3, 0],
                ['Microsoft Excel', 4, 4, 0],
            ],
        ];

        $skillCatalog = KyNang::all()->keyBy('ten_ky_nang');
        $count = 0;

        foreach ($skillsByCandidate as $email => $skills) {
            $candidate = NguoiDung::where('email', $email)->first();

            if (!$candidate) {
                continue;
            }

            foreach ($skills as [$skillName, $level, $years, $certificates]) {
                $skill = $skillCatalog->get($skillName);

                if (!$skill) {
                    continue;
                }

                NguoiDungKyNang::updateOrCreate(
                    [
                        'nguoi_dung_id' => $candidate->id,
                        'ky_nang_id' => $skill->id,
                    ],
                    [
                        'muc_do' => $level,
                        'nam_kinh_nghiem' => $years,
                        'so_chung_chi' => $certificates,
                        'hinh_anh' => $certificates > 0 ? \Illuminate\Support\Str::slug($skillName) . '-certificate.pdf' : null,
                    ]
                );

                $count++;
            }
        }

        $this->command->info("✅ NguoiDungKyNangSeeder: Đã tạo {$count} kỹ năng cá nhân khớp catalog AI.");
    }
}
