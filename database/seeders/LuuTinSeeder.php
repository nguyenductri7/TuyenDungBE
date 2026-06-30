<?php

namespace Database\Seeders;

use App\Models\CongTy;
use App\Models\NguoiDung;
use App\Models\TinTuyenDung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LuuTinSeeder extends Seeder
{
    public function run(): void
    {
        $savedJobs = [
            'ungvien.backend@demo.vn' => ['Backend Developer Laravel', 'DevOps Engineer', 'Frontend Developer Vue.js'],
            'ungvien.frontend@demo.vn' => ['Frontend Developer Vue.js', 'React Native Developer', 'Graphic Designer Marketing'],
            'ungvien.data@demo.vn' => ['Data Analyst', 'BI Developer', 'CRM Specialist'],
            'ungvien.marketing@demo.vn' => ['Digital Marketing Executive', 'Social Media Executive', 'Content Marketing Intern'],
            'ungvien.qa@demo.vn' => ['QA Engineer Manual/API', 'Backend Developer Laravel'],
            'ungvien.sales@demo.vn' => ['Sales Supervisor FMCG', 'Chuyên viên kinh doanh bất động sản', 'Customer Service Online'],
            'ungvien.accounting@demo.vn' => ['Kế toán tổng hợp', 'Financial Analyst'],
            'ungvien.hr@demo.vn' => ['IT Recruiter', 'Learning & Development Specialist'],
            'ungvien.teacher@demo.vn' => ['Giáo viên tiếng Anh online', 'Chuyên viên học vụ LMS'],
            'ungvien.nurse@demo.vn' => ['Điều dưỡng phòng khám', 'Dược sĩ tư vấn'],
            'ungvien.construction@demo.vn' => ['Kỹ sư thiết kế Revit/AutoCAD', 'Chuyên viên kinh doanh bất động sản'],
            'ungvien.logistics@demo.vn' => ['Logistics Coordinator', 'E-commerce Operations Executive'],
        ];

        $followedCompanies = [
            'ungvien.backend@demo.vn' => ['TechViet Solutions', 'SaigonCloud Infrastructure'],
            'ungvien.frontend@demo.vn' => ['TechViet Solutions', 'MobileWave Studio', 'Bloom Media House'],
            'ungvien.data@demo.vn' => ['NorthStar Analytics', 'An Phát Retail Group'],
            'ungvien.marketing@demo.vn' => ['DigiGrowth Agency', 'Bloom Media House'],
            'ungvien.qa@demo.vn' => ['TechViet Solutions'],
            'ungvien.sales@demo.vn' => ['Mekong Commerce', 'An Phát Retail Group', 'GreenHome Real Estate'],
            'ungvien.accounting@demo.vn' => ['Lotus Finance Advisory', 'FinCore Accounting Services'],
            'ungvien.hr@demo.vn' => ['TalentBridge Vietnam', 'PeopleSphere HR Consulting'],
            'ungvien.teacher@demo.vn' => ['EduSpark Learning', 'Sunrise Academy'],
            'ungvien.nurse@demo.vn' => ['MediLink Clinic Network', 'HealCare Pharmacy'],
            'ungvien.construction@demo.vn' => ['Skyline Build Design', 'GreenHome Real Estate'],
            'ungvien.logistics@demo.vn' => ['VietLogix Supply Chain', 'Mekong Commerce'],
        ];

        $users = NguoiDung::where('vai_tro', NguoiDung::VAI_TRO_UNG_VIEN)->get()->keyBy('email');
        $jobs = TinTuyenDung::all()->keyBy('tieu_de');
        $companies = CongTy::all()->keyBy('ten_cong_ty');
        $now = now();
        $savedCount = 0;
        $followCount = 0;

        foreach ($savedJobs as $email => $titles) {
            $user = $users->get($email);

            if (!$user) {
                continue;
            }

            foreach ($titles as $title) {
                $job = $jobs->get($title);

                if (!$job) {
                    continue;
                }

                DB::table('luu_tins')->updateOrInsert(
                    [
                        'nguoi_dung_id' => $user->id,
                        'tin_tuyen_dung_id' => $job->id,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $savedCount++;
            }
        }

        foreach ($followedCompanies as $email => $companyNames) {
            $user = $users->get($email);

            if (!$user) {
                continue;
            }

            foreach ($companyNames as $companyName) {
                $company = $companies->get($companyName);

                if (!$company) {
                    continue;
                }

                DB::table('theo_doi_cong_tys')->updateOrInsert(
                    [
                        'nguoi_dung_id' => $user->id,
                        'cong_ty_id' => $company->id,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $followCount++;
            }
        }

        $this->command->info("✅ LuuTinSeeder: Đã tạo {$savedCount} tin đã lưu và {$followCount} lượt theo dõi công ty phục vụ demo.");
    }
}
