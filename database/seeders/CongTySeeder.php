<?php

namespace Database\Seeders;

use App\Models\CongTy;
use App\Models\NganhNghe;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CongTySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'owner_email' => 'hr.techviet@demo.vn',
                'ten_cong_ty' => 'TechViet Solutions',
                'ma_so_thue' => '0314827561',
                'nganh' => 'Công nghệ thông tin',
                'quy_mo' => '51-200',
                'dia_chi' => '25 Nguyễn Thị Minh Khai, Quận 1, TP. Hồ Chí Minh',
                'dien_thoai' => '02838246891',
                'email' => 'careers@techviet-solutions.vn',
                'website' => 'https://techviet-solutions.vn',
                'mo_ta' => 'Công ty product outsourcing phát triển hệ thống SaaS cho bán lẻ, logistics và vận hành nội bộ. Đội ngũ tập trung vào Laravel, Vue.js, mobile app, realtime dashboard và tích hợp API doanh nghiệp.',
            ],
            [
                'owner_email' => 'hr.saigoncloud@demo.vn',
                'ten_cong_ty' => 'SaigonCloud Infrastructure',
                'ma_so_thue' => '0315901842',
                'nganh' => 'Công nghệ thông tin',
                'quy_mo' => '51-200',
                'dia_chi' => '18 Tôn Đức Thắng, Quận 1, TP. Hồ Chí Minh',
                'dien_thoai' => '02839112233',
                'email' => 'talent@saigoncloud.vn',
                'website' => 'https://saigoncloud.vn',
                'mo_ta' => 'Doanh nghiệp cung cấp cloud managed service, DevOps outsourcing, monitoring, backup và bảo mật hạ tầng cho khách hàng thương mại điện tử, fintech và giáo dục.',
            ],
            [
                'owner_email' => 'hr.northstar@demo.vn',
                'ten_cong_ty' => 'NorthStar Analytics',
                'ma_so_thue' => '0109172648',
                'nganh' => 'Công nghệ thông tin',
                'quy_mo' => '11-50',
                'dia_chi' => '14 Duy Tân, Cầu Giấy, Hà Nội',
                'dien_thoai' => '02437654321',
                'email' => 'jobs@northstar-analytics.vn',
                'website' => 'https://northstar-analytics.vn',
                'mo_ta' => 'Công ty tư vấn dữ liệu triển khai data warehouse, dashboard BI, phân tích vận hành và mô hình dự báo cho khối tài chính, bán lẻ và sản xuất.',
            ],
            [
                'owner_email' => 'hr.mobilewave@demo.vn',
                'ten_cong_ty' => 'MobileWave Studio',
                'ma_so_thue' => '0402135789',
                'nganh' => 'Công nghệ thông tin',
                'quy_mo' => '11-50',
                'dia_chi' => '86 Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
                'dien_thoai' => '02363881234',
                'email' => 'recruitment@mobilewave.vn',
                'website' => 'https://mobilewave.vn',
                'mo_ta' => 'Studio phát triển ứng dụng iOS, Android, Flutter và React Native cho startup trong lĩnh vực đặt lịch, giáo dục và thương mại dịch vụ.',
            ],
            [
                'owner_email' => 'hr.mekongcommerce@demo.vn',
                'ten_cong_ty' => 'Mekong Commerce',
                'ma_so_thue' => '1801674520',
                'nganh' => 'Kinh doanh / Bán hàng',
                'quy_mo' => '51-200',
                'dia_chi' => '9 Trần Văn Khéo, Ninh Kiều, Cần Thơ',
                'dien_thoai' => '02923880011',
                'email' => 'hr@mekongcommerce.vn',
                'website' => 'https://mekongcommerce.vn',
                'mo_ta' => 'Doanh nghiệp thương mại điện tử vận hành gian hàng đa sàn, kho nội vùng, livestream bán hàng và hệ thống CRM chăm sóc khách hàng miền Tây.',
            ],
            [
                'owner_email' => 'hr.anphatretail@demo.vn',
                'ten_cong_ty' => 'An Phát Retail Group',
                'ma_so_thue' => '0108293741',
                'nganh' => 'Kinh doanh / Bán hàng',
                'quy_mo' => '201-500',
                'dia_chi' => '312 Nguyễn Trãi, Thanh Xuân, Hà Nội',
                'dien_thoai' => '02435556677',
                'email' => 'tuyendung@anphatretail.vn',
                'website' => 'https://anphatretail.vn',
                'mo_ta' => 'Chuỗi bán lẻ hàng tiêu dùng nhanh và thiết bị gia dụng, có đội ngũ bán hàng B2C, quản lý cửa hàng, vận hành CRM và thương mại điện tử.',
            ],
            [
                'owner_email' => 'hr.digigrowth@demo.vn',
                'ten_cong_ty' => 'DigiGrowth Agency',
                'ma_so_thue' => '0401985236',
                'nganh' => 'Marketing / Truyền thông',
                'quy_mo' => '11-50',
                'dia_chi' => '88 Bạch Đằng, Hải Châu, Đà Nẵng',
                'dien_thoai' => '02363876543',
                'email' => 'talent@digigrowth.vn',
                'website' => 'https://digigrowth.vn',
                'mo_ta' => 'Agency chuyên performance marketing, social commerce, SEO, content và vận hành chiến dịch tăng trưởng cho doanh nghiệp SME và startup.',
            ],
            [
                'owner_email' => 'hr.bloommedia@demo.vn',
                'ten_cong_ty' => 'Bloom Media House',
                'ma_so_thue' => '0317042158',
                'nganh' => 'Marketing / Truyền thông',
                'quy_mo' => '51-200',
                'dia_chi' => '43 Võ Văn Tần, Quận 3, TP. Hồ Chí Minh',
                'dien_thoai' => '02839334455',
                'email' => 'people@bloommedia.vn',
                'website' => 'https://bloommedia.vn',
                'mo_ta' => 'Đơn vị truyền thông tích hợp, sản xuất nội dung số, key visual, social campaign và booking KOL cho thương hiệu tiêu dùng, giáo dục và lifestyle.',
            ],
            [
                'owner_email' => 'hr.lotusfinance@demo.vn',
                'ten_cong_ty' => 'Lotus Finance Advisory',
                'ma_so_thue' => '0107753184',
                'nganh' => 'Kế toán / Tài chính',
                'quy_mo' => '11-50',
                'dia_chi' => '55 Phan Chu Trinh, Hoàn Kiếm, Hà Nội',
                'dien_thoai' => '02439998877',
                'email' => 'career@lotusfinance.vn',
                'website' => 'https://lotusfinance.vn',
                'mo_ta' => 'Công ty tư vấn tài chính doanh nghiệp, kế toán quản trị, thuế, kiểm toán nội bộ và lập kế hoạch ngân sách cho SME.',
            ],
            [
                'owner_email' => 'hr.fincore@demo.vn',
                'ten_cong_ty' => 'FinCore Accounting Services',
                'ma_so_thue' => '0318123654',
                'nganh' => 'Kế toán / Tài chính',
                'quy_mo' => '11-50',
                'dia_chi' => '17 Nguyễn Thị Minh Khai, Quận 1, TP. Hồ Chí Minh',
                'dien_thoai' => '02837776655',
                'email' => 'jobs@fincore.vn',
                'website' => 'https://fincore.vn',
                'mo_ta' => 'Đơn vị cung cấp dịch vụ kế toán, báo cáo thuế, payroll và tư vấn phần mềm kế toán MISA/QuickBooks cho doanh nghiệp dịch vụ.',
            ],
            [
                'owner_email' => 'hr.talentbridge@demo.vn',
                'ten_cong_ty' => 'TalentBridge Vietnam',
                'ma_so_thue' => '0319158420',
                'nganh' => 'Nhân sự / Hành chính',
                'quy_mo' => '51-200',
                'dia_chi' => '201 Hoàng Văn Thụ, Phú Nhuận, TP. Hồ Chí Minh',
                'dien_thoai' => '02839995566',
                'email' => 'hiring@talentbridge.vn',
                'website' => 'https://talentbridge.vn',
                'mo_ta' => 'Công ty dịch vụ tuyển dụng, RPO, headhunt và đào tạo kỹ năng phỏng vấn cho các doanh nghiệp công nghệ, tài chính, bán lẻ.',
            ],
            [
                'owner_email' => 'hr.peoplesphere@demo.vn',
                'ten_cong_ty' => 'PeopleSphere HR Consulting',
                'ma_so_thue' => '0108724612',
                'nganh' => 'Nhân sự / Hành chính',
                'quy_mo' => '11-50',
                'dia_chi' => '24 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội',
                'dien_thoai' => '02436669988',
                'email' => 'career@peoplesphere.vn',
                'website' => 'https://peoplesphere.vn',
                'mo_ta' => 'Đơn vị tư vấn xây dựng khung năng lực, lộ trình đào tạo nội bộ, chính sách nhân sự và hệ thống onboarding cho doanh nghiệp vừa.',
            ],
            [
                'owner_email' => 'hr.eduspark@demo.vn',
                'ten_cong_ty' => 'EduSpark Learning',
                'ma_so_thue' => '0402267891',
                'nganh' => 'Giáo dục / Đào tạo',
                'quy_mo' => '51-200',
                'dia_chi' => '42 Lê Lợi, Hải Châu, Đà Nẵng',
                'dien_thoai' => '02363555123',
                'email' => 'teachers@eduspark.vn',
                'website' => 'https://eduspark.vn',
                'mo_ta' => 'Trung tâm đào tạo tiếng Anh, kỹ năng số và lớp học online cho học sinh, sinh viên, có đội ngũ giáo viên, học vụ và LMS riêng.',
            ],
            [
                'owner_email' => 'hr.sunriseacademy@demo.vn',
                'ten_cong_ty' => 'Sunrise Academy',
                'ma_so_thue' => '0316689021',
                'nganh' => 'Giáo dục / Đào tạo',
                'quy_mo' => '11-50',
                'dia_chi' => '66 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh',
                'dien_thoai' => '02836661122',
                'email' => 'hr@sunriseacademy.vn',
                'website' => 'https://sunriseacademy.vn',
                'mo_ta' => 'Học viện đào tạo kỹ năng văn phòng, phân tích dữ liệu, digital marketing và lớp kèm online cho người đi làm.',
            ],
            [
                'owner_email' => 'hr.medilink@demo.vn',
                'ten_cong_ty' => 'MediLink Clinic Network',
                'ma_so_thue' => '0315456782',
                'nganh' => 'Y tế / Sức khoẻ',
                'quy_mo' => '201-500',
                'dia_chi' => '210 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh',
                'dien_thoai' => '02838990012',
                'email' => 'recruitment@medilink.vn',
                'website' => 'https://medilink.vn',
                'mo_ta' => 'Mạng lưới phòng khám đa khoa vận hành hồ sơ bệnh án điện tử, chăm sóc khách hàng y tế và quy trình điều dưỡng theo tiêu chuẩn nội bộ.',
            ],
            [
                'owner_email' => 'hr.healcare@demo.vn',
                'ten_cong_ty' => 'HealCare Pharmacy',
                'ma_so_thue' => '1801776543',
                'nganh' => 'Y tế / Sức khoẻ',
                'quy_mo' => '51-200',
                'dia_chi' => '35 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ',
                'dien_thoai' => '02923994455',
                'email' => 'jobs@healcare.vn',
                'website' => 'https://healcare.vn',
                'mo_ta' => 'Chuỗi nhà thuốc và tư vấn dược phẩm cộng đồng, tập trung vào chăm sóc khách hàng, hồ sơ thuốc và chuẩn hóa quy trình bán lẻ dược.',
            ],
            [
                'owner_email' => 'hr.skylinebuild@demo.vn',
                'ten_cong_ty' => 'Skyline Build Design',
                'ma_so_thue' => '0316234789',
                'nganh' => 'Xây dựng / Bất động sản',
                'quy_mo' => '51-200',
                'dia_chi' => '6A Nguyễn Hữu Thọ, Quận 7, TP. Hồ Chí Minh',
                'dien_thoai' => '02838887766',
                'email' => 'hr@skylinebuild.vn',
                'website' => 'https://skylinebuild.vn',
                'mo_ta' => 'Công ty thiết kế và thi công công trình dân dụng, văn phòng, nhà phố; sử dụng AutoCAD, Revit, BIM và quản lý dự án xây dựng.',
            ],
            [
                'owner_email' => 'hr.greenhome@demo.vn',
                'ten_cong_ty' => 'GreenHome Real Estate',
                'ma_so_thue' => '0109345681',
                'nganh' => 'Xây dựng / Bất động sản',
                'quy_mo' => '201-500',
                'dia_chi' => '12 Tố Hữu, Nam Từ Liêm, Hà Nội',
                'dien_thoai' => '02435557788',
                'email' => 'tuyendung@greenhome.vn',
                'website' => 'https://greenhome.vn',
                'mo_ta' => 'Doanh nghiệp phát triển và phân phối dự án nhà ở xanh, có đội ngũ kinh doanh bất động sản, thiết kế, pháp lý và chăm sóc khách hàng.',
            ],
            [
                'owner_email' => 'hr.vietlogix@demo.vn',
                'ten_cong_ty' => 'VietLogix Supply Chain',
                'ma_so_thue' => '0317559312',
                'nganh' => 'Kinh doanh / Bán hàng',
                'quy_mo' => '201-500',
                'dia_chi' => '128 Xa Lộ Hà Nội, TP. Thủ Đức, TP. Hồ Chí Minh',
                'dien_thoai' => '02837220011',
                'email' => 'people@vietlogix.vn',
                'website' => 'https://vietlogix.vn',
                'mo_ta' => 'Doanh nghiệp vận hành kho, giao nhận nội địa, quản lý tồn kho, procurement và tối ưu chuỗi cung ứng cho thương mại điện tử.',
            ],
            [
                'owner_email' => 'hr.lumieretravel@demo.vn',
                'ten_cong_ty' => 'Lumiere Travel & Hospitality',
                'ma_so_thue' => '4201897654',
                'nganh' => 'Kinh doanh / Bán hàng',
                'quy_mo' => '51-200',
                'dia_chi' => '76 Trần Phú, Nha Trang, Khánh Hòa',
                'dien_thoai' => '02583889900',
                'email' => 'jobs@lumieretravel.vn',
                'website' => 'https://lumieretravel.vn',
                'mo_ta' => 'Đơn vị vận hành tour, khách sạn boutique, sự kiện doanh nghiệp và dịch vụ front office cho khách du lịch nội địa/quốc tế.',
            ],
        ];

        $now = now();
        $count = 0;

        foreach ($companies as $data) {
            $owner = NguoiDung::where('email', $data['owner_email'])->first();

            if (!$owner) {
                continue;
            }

            $nganh = NganhNghe::where('ten_nganh', $data['nganh'])->first()
                ?? NganhNghe::whereNull('danh_muc_cha_id')->first();

            $company = CongTy::updateOrCreate(
                ['email' => $data['email']],
                [
                    'nguoi_dung_id' => $owner->id,
                    'ten_cong_ty' => $data['ten_cong_ty'],
                    'ma_so_thue' => $data['ma_so_thue'],
                    'mo_ta' => $data['mo_ta'],
                    'dia_chi' => $data['dia_chi'],
                    'dien_thoai' => $data['dien_thoai'],
                    'website' => $data['website'],
                    'nganh_nghe_id' => $nganh?->id,
                    'quy_mo' => $data['quy_mo'],
                    'trang_thai' => CongTy::TRANG_THAI_HOAT_DONG,
                ]
            );

            DB::table('cong_ty_nguoi_dungs')->updateOrInsert(
                ['nguoi_dung_id' => $owner->id],
                [
                    'cong_ty_id' => $company->id,
                    'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
                    'quyen_noi_bo' => json_encode(CongTy::defaultHrPermissions(), JSON_UNESCAPED_UNICODE),
                    'duoc_tao_boi' => $owner->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $count++;
        }

        $this->command->info("✅ CongTySeeder: Đã tạo {$count} công ty demo chi tiết và membership owner đầy đủ.");
    }
}
