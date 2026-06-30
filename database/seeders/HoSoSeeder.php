<?php

namespace Database\Seeders;

use App\Models\HoSo;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class HoSoSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            'ungvien.backend@demo.vn' => [
                'tieu_de_ho_so' => 'Backend Developer Laravel/PHP',
                'vi_tri' => 'Backend Developer Laravel',
                'nganh' => 'Lập trình Backend',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 3,
                'muc_tieu' => 'Tìm kiếm vị trí Backend Developer trong môi trường product hoặc SaaS, tập trung xây dựng REST API ổn định, tối ưu hiệu năng và phát triển lên Senior Backend trong 2 năm tới.',
                'mo_ta' => 'Có 3 năm kinh nghiệm phát triển hệ thống CRM và quản trị bán hàng bằng Laravel, MySQL, Redis. Quen làm việc với Git, Docker, viết tài liệu API, tối ưu truy vấn và phối hợp với frontend Vue.js.',
                'skills' => ['PHP', 'Laravel', 'REST API', 'MySQL', 'Redis', 'Docker', 'Git', 'JavaScript', 'Vue.js'],
                'experiences' => [
                    ['title' => 'Backend Developer', 'company' => 'Nova Retail SaaS', 'period' => '2022 - 2025', 'description' => 'Phát triển module đơn hàng, phân quyền, báo cáo doanh thu và API mobile app bằng Laravel/MySQL. Tối ưu truy vấn dashboard từ 4 giây xuống dưới 1 giây.'],
                    ['title' => 'PHP Developer Intern', 'company' => 'SunTech Lab', 'period' => '2021 - 2022', 'description' => 'Xây CRUD nội bộ, tích hợp gửi email, import Excel và viết tài liệu API cho frontend.'],
                ],
                'education' => [['school' => 'Đại học Công nghệ Thông tin', 'major' => 'Kỹ thuật phần mềm', 'period' => '2017 - 2021']],
                'projects' => [['name' => 'CRM bán lẻ đa chi nhánh', 'description' => 'Thiết kế API quản lý khách hàng, phân nhóm khách hàng, lịch sử mua hàng và báo cáo doanh thu theo chi nhánh.']],
            ],
            'ungvien.frontend@demo.vn' => [
                'tieu_de_ho_so' => 'Frontend Developer Vue/React',
                'vi_tri' => 'Frontend Developer',
                'nganh' => 'Lập trình Frontend',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 2,
                'muc_tieu' => 'Phát triển theo hướng frontend engineer có tư duy UX tốt, ưu tiên sản phẩm SaaS, dashboard quản trị và thương mại điện tử.',
                'mo_ta' => 'Thành thạo Vue.js, React, TypeScript, Tailwind CSS và Figma handoff. Có kinh nghiệm xây giao diện responsive, tích hợp API, xử lý state và phối hợp kiểm thử UI.',
                'skills' => ['JavaScript', 'TypeScript', 'Vue.js', 'React', 'Tailwind CSS', 'HTML/CSS', 'Figma', 'Git'],
                'experiences' => [
                    ['title' => 'Frontend Developer', 'company' => 'Bright UI Studio', 'period' => '2023 - 2025', 'description' => 'Xây dashboard quản trị bán hàng bằng Vue.js, tối ưu component table, filter, form validation và responsive layout.'],
                    ['title' => 'UI Developer', 'company' => 'PixelCraft', 'period' => '2022 - 2023', 'description' => 'Cắt giao diện landing page, tích hợp animation nhẹ và phối hợp designer chuẩn hóa design token.'],
                ],
                'education' => [['school' => 'Đại học Khoa học Tự nhiên', 'major' => 'Công nghệ thông tin', 'period' => '2018 - 2022']],
                'projects' => [['name' => 'Recruitment Dashboard UI', 'description' => 'Xây bộ component form, modal, pagination và realtime notification cho dashboard tuyển dụng.']],
            ],
            'ungvien.data@demo.vn' => [
                'tieu_de_ho_so' => 'Data Analyst BI',
                'vi_tri' => 'Data Analyst',
                'nganh' => 'Phân tích dữ liệu',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 4,
                'muc_tieu' => 'Tìm vị trí Data Analyst/BI Analyst nơi có thể khai thác dữ liệu kinh doanh, xây dashboard và hỗ trợ quyết định dựa trên số liệu.',
                'mo_ta' => 'Có kinh nghiệm SQL, Power BI, Microsoft Excel, Python và ETL cơ bản. Thường xuyên làm sạch dữ liệu, chuẩn hóa chỉ số, phân tích doanh thu và trình bày insight cho phòng kinh doanh.',
                'skills' => ['SQL', 'Power BI', 'Microsoft Excel', 'Python', 'Data Analysis', 'Data Visualization', 'ETL', 'Presentation'],
                'experiences' => [
                    ['title' => 'Data Analyst', 'company' => 'Retail Insight', 'period' => '2021 - 2025', 'description' => 'Xây dashboard doanh thu, tồn kho, tỷ lệ hoàn đơn và cohort khách hàng. Tự động hóa báo cáo tuần bằng SQL và Power BI.'],
                ],
                'education' => [['school' => 'Đại học Kinh tế Quốc dân', 'major' => 'Hệ thống thông tin quản lý', 'period' => '2017 - 2021']],
                'projects' => [['name' => 'Dashboard hiệu quả bán hàng', 'description' => 'Thiết kế data model và dashboard Power BI cho 35 cửa hàng bán lẻ.']],
            ],
            'ungvien.marketing@demo.vn' => [
                'tieu_de_ho_so' => 'Digital Marketing Executive',
                'vi_tri' => 'Digital Marketing Executive',
                'nganh' => 'Digital Marketing',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 2,
                'muc_tieu' => 'Phát triển theo hướng performance marketing, quản lý ngân sách quảng cáo hiệu quả và tối ưu phễu chuyển đổi cho doanh nghiệp thương mại điện tử.',
                'mo_ta' => 'Có kinh nghiệm chạy Facebook Ads, Google Ads, TikTok Ads, phối hợp content/design để triển khai chiến dịch, theo dõi ROAS và báo cáo hàng tuần bằng Google Analytics.',
                'skills' => ['Facebook Ads', 'Google Ads', 'TikTok Ads', 'Content Marketing', 'SEO', 'Google Analytics', 'Social Media Marketing'],
                'experiences' => [
                    ['title' => 'Digital Marketing Executive', 'company' => 'BeautyPlus Vietnam', 'period' => '2022 - 2025', 'description' => 'Triển khai campaign Meta/Google cho ngành làm đẹp, tối ưu CPL giảm 22% và tăng ROAS trung bình 1.8 lần sau 6 tháng.'],
                ],
                'education' => [['school' => 'Đại học Kinh tế TP. Hồ Chí Minh', 'major' => 'Marketing', 'period' => '2018 - 2022']],
                'projects' => [['name' => 'Launch campaign sản phẩm skincare', 'description' => 'Lên media plan, tracking UTM, phối hợp landing page và báo cáo hiệu quả theo tuần.']],
            ],
            'ungvien.qa@demo.vn' => [
                'tieu_de_ho_so' => 'QA Engineer Manual/API',
                'vi_tri' => 'QA Engineer',
                'nganh' => 'Kiểm thử phần mềm (QA)',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 5,
                'muc_tieu' => 'Tìm cơ hội kiểm thử phần mềm trong môi trường agile, từng bước mở rộng sang automation test cho web app và API.',
                'mo_ta' => 'Có kinh nghiệm viết test case, test plan, regression test, API testing bằng Postman và phối hợp BA/developer để xác minh lỗi trên Jira/TestRail.',
                'skills' => ['Manual Testing', 'Postman', 'API Testing', 'TestRail', 'Jira', 'Automation Testing', 'Selenium'],
                'experiences' => [
                    ['title' => 'QA Engineer', 'company' => 'CloudPOS Vietnam', 'period' => '2020 - 2025', 'description' => 'Phụ trách test web admin, mobile web và API cho hệ thống POS. Xây test checklist release và chuẩn hóa bug report.'],
                ],
                'education' => [['school' => 'Đại học FPT', 'major' => 'Kỹ thuật phần mềm', 'period' => '2016 - 2020']],
                'projects' => [['name' => 'Regression suite cho POS admin', 'description' => 'Thiết kế bộ test hồi quy cho đơn hàng, kho, thanh toán và phân quyền.']],
            ],
            'ungvien.sales@demo.vn' => [
                'tieu_de_ho_so' => 'Sales Executive B2B/B2C',
                'vi_tri' => 'Nhân viên kinh doanh',
                'nganh' => 'Nhân viên kinh doanh',
                'trinh_do' => 'Cao đẳng',
                'kinh_nghiem_nam' => 3,
                'muc_tieu' => 'Tìm vị trí sales có quy trình CRM rõ ràng, sản phẩm ổn định và cơ hội phát triển lên Key Account Executive.',
                'mo_ta' => 'Có kinh nghiệm telesales, tư vấn trực tiếp, chăm sóc khách hàng sau bán, quản lý pipeline trên CRM và đàm phán hợp đồng B2B cơ bản.',
                'skills' => ['Sales B2B', 'Sales B2C', 'Telesales', 'CRM', 'Negotiation', 'Customer Service', 'Lead Generation'],
                'experiences' => [['title' => 'Sales Executive', 'company' => 'HomeCare Retail', 'period' => '2021 - 2025', 'description' => 'Tư vấn khách hàng B2C, chăm sóc đại lý nhỏ và duy trì tỷ lệ chốt đơn trung bình 18%.']],
                'education' => [['school' => 'Cao đẳng Kinh tế Đối ngoại', 'major' => 'Quản trị kinh doanh', 'period' => '2018 - 2021']],
                'projects' => [['name' => 'CRM pipeline bán hàng', 'description' => 'Chuẩn hóa dữ liệu khách hàng tiềm năng và quy trình follow-up sau báo giá.']],
            ],
            'ungvien.accounting@demo.vn' => [
                'tieu_de_ho_so' => 'Kế toán tổng hợp',
                'vi_tri' => 'Kế toán tổng hợp',
                'nganh' => 'Kế toán tổng hợp',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 4,
                'muc_tieu' => 'Tìm vị trí kế toán tổng hợp trong doanh nghiệp dịch vụ hoặc bán lẻ, có cơ hội tham gia chuẩn hóa báo cáo tài chính nội bộ.',
                'mo_ta' => 'Thành thạo hạch toán chi phí, đối soát công nợ, kê khai thuế, payroll, MISA và Microsoft Excel nâng cao.',
                'skills' => ['Accounting', 'Bookkeeping', 'Tax Declaration', 'Payroll', 'MISA', 'Microsoft Excel', 'Financial Analysis'],
                'experiences' => [['title' => 'General Accountant', 'company' => 'Minh Tâm Services', 'period' => '2020 - 2025', 'description' => 'Phụ trách sổ sách kế toán, báo cáo thuế tháng/quý, đối soát công nợ và báo cáo chi phí vận hành.']],
                'education' => [['school' => 'Đại học Tài chính - Marketing', 'major' => 'Kế toán', 'period' => '2016 - 2020']],
                'projects' => [['name' => 'Chuẩn hóa báo cáo chi phí', 'description' => 'Thiết lập file Excel theo dõi chi phí phòng ban và đối chiếu với MISA.']],
            ],
            'ungvien.hr@demo.vn' => [
                'tieu_de_ho_so' => 'Recruiter / Talent Acquisition',
                'vi_tri' => 'Recruiter',
                'nganh' => 'Tuyển dụng (Recruiter)',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 3,
                'muc_tieu' => 'Phát triển theo hướng Talent Acquisition trong lĩnh vực công nghệ và dịch vụ, tối ưu trải nghiệm ứng viên và quy trình phỏng vấn.',
                'mo_ta' => 'Có kinh nghiệm sourcing, screening, phỏng vấn sơ bộ, phối hợp hiring manager, quản lý pipeline tuyển dụng và onboarding nhân sự mới.',
                'skills' => ['Recruitment', 'Talent Acquisition', 'Interviewing', 'Onboarding', 'Employee Relations', 'Communication'],
                'experiences' => [['title' => 'Recruiter', 'company' => 'PeopleFirst HR', 'period' => '2021 - 2025', 'description' => 'Tuyển dụng vị trí sales, marketing, developer; quản lý pipeline hơn 30 requisition mỗi quý.']],
                'education' => [['school' => 'Đại học Lao động - Xã hội', 'major' => 'Quản trị nhân lực', 'period' => '2017 - 2021']],
                'projects' => [['name' => 'Candidate experience checklist', 'description' => 'Xây checklist email, lịch phỏng vấn và feedback sau vòng phỏng vấn.']],
            ],
            'ungvien.teacher@demo.vn' => [
                'tieu_de_ho_so' => 'Giáo viên tiếng Anh online',
                'vi_tri' => 'Giáo viên tiếng Anh',
                'nganh' => 'Giảng viên / Giáo viên',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 3,
                'muc_tieu' => 'Tìm vị trí giảng dạy tiếng Anh kết hợp LMS và lớp online, chú trọng cá nhân hóa lộ trình học.',
                'mo_ta' => 'Có kinh nghiệm lesson planning, classroom management, online teaching, giao tiếp tiếng Anh tốt và quen dùng LMS để theo dõi tiến độ học viên.',
                'skills' => ['Lesson Planning', 'Classroom Management', 'Online Teaching', 'LMS', 'Tiếng Anh', 'Presentation'],
                'experiences' => [['title' => 'English Teacher', 'company' => 'Bright English Center', 'period' => '2021 - 2025', 'description' => 'Giảng dạy lớp giao tiếp, luyện phát âm và lớp online cho học sinh cấp 2/cấp 3.']],
                'education' => [['school' => 'Đại học Sư phạm TP. Hồ Chí Minh', 'major' => 'Sư phạm tiếng Anh', 'period' => '2017 - 2021']],
                'projects' => [['name' => 'Lộ trình học online 12 tuần', 'description' => 'Thiết kế lesson plan, bài tập LMS và rubric đánh giá speaking.']],
            ],
            'ungvien.nurse@demo.vn' => [
                'tieu_de_ho_so' => 'Điều dưỡng phòng khám',
                'vi_tri' => 'Điều dưỡng',
                'nganh' => 'Y tế / Sức khoẻ',
                'trinh_do' => 'Cao đẳng',
                'kinh_nghiem_nam' => 4,
                'muc_tieu' => 'Tìm vị trí điều dưỡng tại phòng khám đa khoa có quy trình chăm sóc bệnh nhân rõ ràng và hồ sơ bệnh án điện tử.',
                'mo_ta' => 'Có kinh nghiệm patient care, clinical assistance, ghi nhận medical records, hướng dẫn bệnh nhân và phối hợp bác sĩ trong quy trình khám.',
                'skills' => ['Patient Care', 'Medical Records', 'Nursing Care', 'Clinical Assistance', 'Customer Service', 'Communication'],
                'experiences' => [['title' => 'Clinic Nurse', 'company' => 'An Tâm Clinic', 'period' => '2020 - 2025', 'description' => 'Hỗ trợ khám, theo dõi hồ sơ bệnh nhân, nhắc lịch tái khám và hướng dẫn sử dụng thuốc theo chỉ định.']],
                'education' => [['school' => 'Cao đẳng Y tế Cần Thơ', 'major' => 'Điều dưỡng', 'period' => '2016 - 2020']],
                'projects' => [['name' => 'Chuẩn hóa hồ sơ bệnh án', 'description' => 'Tham gia nhập liệu và kiểm tra hồ sơ bệnh án điện tử cho phòng khám.']],
            ],
            'ungvien.construction@demo.vn' => [
                'tieu_de_ho_so' => 'Kỹ sư thiết kế xây dựng',
                'vi_tri' => 'Kỹ sư xây dựng',
                'nganh' => 'Xây dựng / Bất động sản',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 5,
                'muc_tieu' => 'Tìm vị trí kỹ sư thiết kế/triển khai bản vẽ trong công ty xây dựng dân dụng, có cơ hội làm việc với BIM.',
                'mo_ta' => 'Có kinh nghiệm AutoCAD, Revit, SketchUp, bóc tách khối lượng, phối hợp công trường và kiểm soát hồ sơ kỹ thuật.',
                'skills' => ['AutoCAD', 'Revit', 'SketchUp', 'Project Management', 'Document Control', 'Problem Solving'],
                'experiences' => [['title' => 'Construction Engineer', 'company' => 'Delta Build', 'period' => '2019 - 2025', 'description' => 'Triển khai bản vẽ, phối hợp kiến trúc/kết cấu, kiểm tra hồ sơ hoàn công và hỗ trợ giám sát công trường.']],
                'education' => [['school' => 'Đại học Xây dựng Hà Nội', 'major' => 'Kỹ thuật xây dựng', 'period' => '2014 - 2019']],
                'projects' => [['name' => 'Văn phòng 12 tầng quận 7', 'description' => 'Tham gia triển khai bản vẽ kỹ thuật, phối hợp Revit và kiểm soát thay đổi thiết kế.']],
            ],
            'ungvien.logistics@demo.vn' => [
                'tieu_de_ho_so' => 'Logistics Coordinator',
                'vi_tri' => 'Điều phối logistics',
                'nganh' => 'Thương mại điện tử',
                'trinh_do' => 'Đại học',
                'kinh_nghiem_nam' => 3,
                'muc_tieu' => 'Tìm vị trí điều phối logistics/kho vận cho doanh nghiệp thương mại điện tử hoặc chuỗi cung ứng nội địa.',
                'mo_ta' => 'Có kinh nghiệm warehouse management, inventory management, procurement, transportation management và xử lý chứng từ giao nhận.',
                'skills' => ['Warehouse Management', 'Inventory Management', 'Procurement', 'Transportation Management', 'Supply Chain', 'Microsoft Excel'],
                'experiences' => [['title' => 'Logistics Coordinator', 'company' => 'FastShip Hub', 'period' => '2021 - 2025', 'description' => 'Điều phối tuyến giao, theo dõi tồn kho, xử lý lệch tồn và phối hợp nhà vận chuyển giảm tỷ lệ giao trễ.']],
                'education' => [['school' => 'Đại học Giao thông Vận tải TP. Hồ Chí Minh', 'major' => 'Logistics và quản lý chuỗi cung ứng', 'period' => '2017 - 2021']],
                'projects' => [['name' => 'Tối ưu quy trình nhập kho', 'description' => 'Chuẩn hóa checklist nhập kho, giảm sai lệch tồn kho sau kiểm kê tháng.']],
            ],
        ];

        $count = 0;

        foreach ($profiles as $email => $profile) {
            $user = NguoiDung::where('email', $email)->first();

            if (!$user) {
                continue;
            }

            HoSo::updateOrCreate(
                [
                    'nguoi_dung_id' => $user->id,
                    'tieu_de_ho_so' => $profile['tieu_de_ho_so'],
                ],
                [
                    'muc_tieu_nghe_nghiep' => $profile['muc_tieu'],
                    'trinh_do' => $profile['trinh_do'],
                    'kinh_nghiem_nam' => $profile['kinh_nghiem_nam'],
                    'mo_ta_ban_than' => $profile['mo_ta'],
                    'file_cv' => null,
                    'nguon_ho_so' => 'builder',
                    'mau_cv' => 'modern',
                    'bo_cuc_cv' => 'classic',
                    'ten_template_cv' => 'Modern Professional',
                    'che_do_mau_cv' => 'blue',
                    'vi_tri_ung_tuyen_muc_tieu' => $profile['vi_tri'],
                    'ten_nganh_nghe_muc_tieu' => $profile['nganh'],
                    'che_do_anh_cv' => 'profile',
                    'ky_nang_json' => array_map(fn (string $skill) => ['ten' => $skill, 'muc_do' => 4], $profile['skills']),
                    'kinh_nghiem_json' => $profile['experiences'],
                    'hoc_van_json' => $profile['education'],
                    'du_an_json' => $profile['projects'],
                    'chung_chi_json' => [],
                    'trang_thai' => HoSo::TRANG_THAI_CONG_KHAI,
                ]
            );

            $count++;
        }

        $this->command->info("✅ HoSoSeeder: Đã tạo {$count} hồ sơ persona công khai, không dùng dữ liệu ngẫu nhiên.");
    }
}
