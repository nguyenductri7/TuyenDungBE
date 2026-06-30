<?php

namespace Database\Seeders;

use App\Models\HoSo;
use App\Models\InterviewRound;
use App\Models\OnboardingPlan;
use App\Models\OnboardingTask;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UngTuyenSeeder extends Seeder
{
    private function nowUtc(): Carbon
    {
        return Carbon::now('UTC');
    }

    public function run(): void
    {
        $profiles = HoSo::with('nguoiDung')->get();
        $jobs = TinTuyenDung::with('congTy')->get()->keyBy('tieu_de');
        $now = $this->nowUtc();

        $applications = [
            [
                'email' => 'ungvien.backend@demo.vn',
                'job' => 'Backend Developer Laravel',
                'status' => UngTuyen::TRANG_THAI_DA_XEM,
                'applied_at' => $now->copy()->subDays(3)->setTime(3, 20),
                'letter' => 'Tôi có 3 năm kinh nghiệm Laravel, REST API, MySQL và Redis. Tôi quan tâm TechViet vì sản phẩm SaaS có nhiều bài toán tối ưu hiệu năng và tích hợp API doanh nghiệp.',
                'note' => 'Hồ sơ backend tốt, chờ HR lên lịch phỏng vấn kỹ thuật.',
            ],
            [
                'email' => 'ungvien.qa@demo.vn',
                'job' => 'QA Engineer Manual/API',
                'status' => UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN,
                'applied_at' => $now->copy()->subDays(5)->setTime(2, 10),
                'letter' => 'Tôi từng kiểm thử web admin và API cho hệ thống POS, có kinh nghiệm viết test case, regression checklist và phối hợp xác nhận lỗi với developer.',
                'interview_at' => $now->copy()->addDays(2)->setTime(2, 0),
                'interview_status' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
                'interview_response_at' => $now->copy()->subDay()->setTime(4, 45),
                'interview_type' => 'Online',
                'interviewer' => 'Trần Gia Huy',
                'interview_link' => 'https://meet.google.com/demo-techviet-qa-round1',
                'note' => 'Ứng viên đã xác nhận tham gia vòng QA Lead.',
                'rounds' => [
                    ['name' => 'Vòng 1 - QA Lead', 'type' => 'technical', 'status' => InterviewRound::TRANG_THAI_DA_LEN_LICH, 'days' => 2, 'score' => null, 'note' => 'Tập trung test case, API testing và tình huống release.'],
                ],
            ],
            [
                'email' => 'ungvien.frontend@demo.vn',
                'job' => 'Frontend Developer Vue.js',
                'status' => UngTuyen::TRANG_THAI_QUA_PHONG_VAN,
                'applied_at' => $now->copy()->subDays(10)->setTime(7, 15),
                'letter' => 'Tôi có kinh nghiệm Vue.js, TypeScript, Tailwind CSS và Figma handoff, phù hợp với dashboard quản trị và hệ thống SaaS của TechViet.',
                'interview_at' => $now->copy()->subDays(2)->setTime(3, 0),
                'interview_status' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
                'interview_response_at' => $now->copy()->subDays(5)->setTime(8, 30),
                'interview_type' => 'Online',
                'interviewer' => 'Nguyễn Thu Hà',
                'interview_link' => 'https://meet.google.com/demo-techviet-fe-round2',
                'result' => 'Ứng viên trả lời tốt phần component design, state management và phối hợp backend.',
                'note' => 'Đang chờ duyệt offer nội bộ.',
                'rounds' => [
                    ['name' => 'Vòng 1 - HR Screening', 'type' => 'hr', 'status' => InterviewRound::TRANG_THAI_HOAN_THANH, 'days' => -7, 'score' => 8.0, 'note' => 'Giao tiếp tốt, mục tiêu nghề nghiệp rõ.'],
                    ['name' => 'Vòng 2 - Frontend Technical', 'type' => 'technical', 'status' => InterviewRound::TRANG_THAI_HOAN_THANH, 'days' => -2, 'score' => 8.3, 'note' => 'Nắm tốt Vue.js, cần bổ sung thêm unit test.'],
                ],
            ],
            [
                'email' => 'ungvien.data@demo.vn',
                'job' => 'Data Analyst',
                'status' => UngTuyen::TRANG_THAI_TRUNG_TUYEN,
                'applied_at' => $now->copy()->subDays(14)->setTime(1, 50),
                'letter' => 'Tôi có hơn 4 năm kinh nghiệm SQL, Power BI và phân tích dữ liệu bán lẻ. Tôi mong muốn tham gia các dự án BI có dữ liệu thực tế và tác động rõ tới vận hành.',
                'interview_at' => $now->copy()->subDays(6)->setTime(7, 30),
                'interview_status' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
                'interview_response_at' => $now->copy()->subDays(8)->setTime(2, 20),
                'interview_type' => 'Offline',
                'interviewer' => 'Phan Quốc Thịnh',
                'result' => 'Ứng viên đạt yêu cầu về SQL, dashboard BI và khả năng trình bày insight.',
                'offer_status' => UngTuyen::OFFER_DA_CHAP_NHAN,
                'offer_sent_at' => $now->copy()->subDays(4)->setTime(4, 0),
                'offer_responded_at' => $now->copy()->subDays(3)->setTime(6, 10),
                'offer_deadline' => $now->copy()->addDays(4)->setTime(16, 59),
                'offer_note' => 'Offer vị trí Data Analyst, lương 28.000.000 VND/tháng, thử việc 2 tháng, ngày bắt đầu dự kiến sau 2 tuần.',
                'offer_response_note' => 'Ứng viên đã xác nhận nhận việc và đồng ý ngày bắt đầu dự kiến.',
                'note' => 'Đã chuyển sang onboarding.',
                'rounds' => [
                    ['name' => 'Vòng 1 - HR Screening', 'type' => 'hr', 'status' => InterviewRound::TRANG_THAI_HOAN_THANH, 'days' => -10, 'score' => 8.2, 'note' => 'Kinh nghiệm phù hợp, giao tiếp tốt.'],
                    ['name' => 'Vòng 2 - Case Study BI', 'type' => 'technical', 'status' => InterviewRound::TRANG_THAI_HOAN_THANH, 'days' => -6, 'score' => 8.7, 'note' => 'Case SQL/Power BI tốt, giải thích insight rõ.'],
                ],
                'onboarding' => true,
            ],
            [
                'email' => 'ungvien.marketing@demo.vn',
                'job' => 'Digital Marketing Executive',
                'status' => UngTuyen::TRANG_THAI_DA_XEM,
                'applied_at' => $now->copy()->subDays(4)->setTime(8, 25),
                'letter' => 'Tôi từng triển khai Meta Ads và Google Ads cho ngành làm đẹp/giáo dục, có kinh nghiệm theo dõi ROAS và phối hợp creative để tối ưu chuyển đổi.',
                'note' => 'Performance Lead đánh giá hồ sơ tốt, chờ shortlist.',
            ],
            [
                'email' => 'ungvien.sales@demo.vn',
                'job' => 'Sales Supervisor FMCG',
                'status' => UngTuyen::TRANG_THAI_TU_CHOI,
                'applied_at' => $now->copy()->subDays(9)->setTime(5, 30),
                'letter' => 'Tôi có kinh nghiệm sales B2C, CRM và chăm sóc đại lý nhỏ. Tôi muốn thử sức ở vai trò giám sát bán hàng trong chuỗi bán lẻ.',
                'result' => 'Ứng viên có kinh nghiệm sales tốt nhưng chưa đủ kinh nghiệm quản lý đội nhóm quy mô khu vực.',
                'note' => 'Gợi ý ứng viên ứng tuyển vị trí Sales Executive khi mở đợt sau.',
            ],
            [
                'email' => 'ungvien.accounting@demo.vn',
                'job' => 'Kế toán tổng hợp',
                'status' => UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN,
                'applied_at' => $now->copy()->subDays(6)->setTime(2, 45),
                'letter' => 'Tôi có 4 năm kinh nghiệm kế toán tổng hợp, báo cáo thuế, MISA và Excel nâng cao, phù hợp với mô hình dịch vụ kế toán nhiều khách hàng.',
                'interview_at' => $now->copy()->addDays(1)->setTime(8, 0),
                'interview_status' => UngTuyen::PHONG_VAN_CHO_XAC_NHAN,
                'interview_type' => 'Offline',
                'interviewer' => 'Đặng Khánh Linh',
                'note' => 'Chờ ứng viên xác nhận lịch phỏng vấn.',
                'rounds' => [
                    ['name' => 'Vòng 1 - Kế toán trưởng', 'type' => 'technical', 'status' => InterviewRound::TRANG_THAI_DA_LEN_LICH, 'days' => 1, 'score' => null, 'note' => 'Trao đổi nghiệp vụ thuế, MISA và đối soát công nợ.'],
                ],
            ],
            [
                'email' => 'ungvien.hr@demo.vn',
                'job' => 'IT Recruiter',
                'status' => UngTuyen::TRANG_THAI_DA_XEM,
                'applied_at' => $now->copy()->subDays(2)->setTime(4, 5),
                'letter' => 'Tôi có kinh nghiệm sourcing, screening và điều phối phỏng vấn các vị trí developer, QA, sales. Tôi mong muốn phát triển sâu ở thị trường nhân sự IT.',
                'note' => 'Hồ sơ phù hợp, cần kiểm tra kinh nghiệm tuyển IT senior.',
            ],
            [
                'email' => 'ungvien.teacher@demo.vn',
                'job' => 'Giáo viên tiếng Anh online',
                'status' => UngTuyen::TRANG_THAI_CHO_DUYET,
                'applied_at' => $now->copy()->subDay()->setTime(9, 0),
                'letter' => 'Tôi có kinh nghiệm dạy tiếng Anh online, xây lesson plan và theo dõi tiến độ học viên trên LMS. Tôi muốn tham gia lớp giao tiếp cho người đi làm.',
                'note' => null,
            ],
            [
                'email' => 'ungvien.nurse@demo.vn',
                'job' => 'Điều dưỡng phòng khám',
                'status' => UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN,
                'applied_at' => $now->copy()->subDays(5)->setTime(10, 10),
                'letter' => 'Tôi có kinh nghiệm chăm sóc bệnh nhân, hỗ trợ bác sĩ và cập nhật hồ sơ bệnh án điện tử tại phòng khám đa khoa.',
                'interview_at' => $now->copy()->addDays(3)->setTime(3, 30),
                'interview_status' => UngTuyen::PHONG_VAN_CHO_XAC_NHAN,
                'interview_type' => 'Offline',
                'interviewer' => 'Đinh Gia Phúc',
                'note' => 'Mời phỏng vấn trực tiếp tại phòng khám trung tâm.',
                'rounds' => [
                    ['name' => 'Vòng 1 - Điều dưỡng trưởng', 'type' => 'technical', 'status' => InterviewRound::TRANG_THAI_DA_LEN_LICH, 'days' => 3, 'score' => null, 'note' => 'Trao đổi quy trình chăm sóc bệnh nhân và hồ sơ bệnh án.'],
                ],
            ],
            [
                'email' => 'ungvien.construction@demo.vn',
                'job' => 'Kỹ sư thiết kế Revit/AutoCAD',
                'status' => UngTuyen::TRANG_THAI_QUA_PHONG_VAN,
                'applied_at' => $now->copy()->subDays(11)->setTime(6, 0),
                'letter' => 'Tôi có 5 năm kinh nghiệm AutoCAD, Revit và triển khai hồ sơ kỹ thuật công trình dân dụng, có thể phối hợp tốt với công trường.',
                'interview_at' => $now->copy()->subDays(3)->setTime(7, 0),
                'interview_status' => UngTuyen::PHONG_VAN_DA_XAC_NHAN,
                'interview_response_at' => $now->copy()->subDays(6)->setTime(2, 45),
                'interview_type' => 'Offline',
                'interviewer' => 'Nguyễn Việt Dũng',
                'result' => 'Ứng viên phù hợp kỹ thuật, đang chờ kiểm tra portfolio công trình.',
                'note' => 'Chờ trưởng phòng thiết kế duyệt offer.',
                'rounds' => [
                    ['name' => 'Vòng 1 - Portfolio Review', 'type' => 'technical', 'status' => InterviewRound::TRANG_THAI_HOAN_THANH, 'days' => -3, 'score' => 8.1, 'note' => 'Bản vẽ rõ, kinh nghiệm phối hợp công trường tốt.'],
                ],
            ],
            [
                'email' => 'ungvien.logistics@demo.vn',
                'job' => 'Logistics Coordinator',
                'status' => UngTuyen::TRANG_THAI_DA_XEM,
                'applied_at' => $now->copy()->subDays(3)->setTime(11, 20),
                'letter' => 'Tôi có kinh nghiệm điều phối kho vận, theo dõi SLA giao hàng, xử lý lệch tồn và phối hợp nhà vận chuyển cho đơn hàng thương mại điện tử.',
                'note' => 'Operations Manager cần xem thêm kinh nghiệm WMS/TMS.',
            ],
        ];

        $count = 0;

        foreach ($applications as $item) {
            $profile = $this->findProfileByEmail($profiles, $item['email']);
            $job = $jobs->get($item['job']);

            if (!$profile || !$job) {
                continue;
            }

            $application = UngTuyen::updateOrCreate(
                [
                    'tin_tuyen_dung_id' => $job->id,
                    'ho_so_id' => $profile->id,
                ],
                [
                    'trang_thai' => $item['status'],
                    'da_rut_don' => false,
                    'thu_xin_viec' => $item['letter'],
                    'thu_xin_viec_ai' => null,
                    'thoi_gian_gui_offer' => $item['offer_sent_at'] ?? null,
                    'trang_thai_offer' => $item['offer_status'] ?? UngTuyen::OFFER_CHUA_GUI,
                    'thoi_gian_phan_hoi_offer' => $item['offer_responded_at'] ?? null,
                    'han_phan_hoi_offer' => $item['offer_deadline'] ?? null,
                    'ghi_chu_offer' => $item['offer_note'] ?? null,
                    'ghi_chu_phan_hoi_offer' => $item['offer_response_note'] ?? null,
                    'link_offer' => null,
                    'ghi_chu' => $item['note'] ?? null,
                    'thoi_gian_ung_tuyen' => $item['applied_at'],
                ]
            );

            $this->seedInterviewRounds($application, $item['rounds'] ?? [], $job);

            if (($item['onboarding'] ?? false) === true) {
                $this->seedOnboarding($application, $job);
            }

            $count++;
        }

        $this->command->info("✅ UngTuyenSeeder: Đã tạo {$count} đơn ứng tuyển demo theo nhiều trạng thái pipeline.");
    }

    private function findProfileByEmail($profiles, string $email): ?HoSo
    {
        return $profiles->first(fn (HoSo $profile) => $profile->nguoiDung?->email === $email);
    }

    private function seedInterviewRounds(UngTuyen $application, array $rounds, TinTuyenDung $job): void
    {
        foreach ($rounds as $index => $round) {
            $scheduledAt = $this->nowUtc()->copy()->addDays($round['days'])->setTime($round['days'] >= 0 ? 2 + $index : 3 + $index, 0);

            InterviewRound::updateOrCreate(
                [
                    'ung_tuyen_id' => $application->id,
                    'thu_tu' => $index + 1,
                ],
                [
                    'ten_vong' => $round['name'],
                    'loai_vong' => $round['type'],
                    'trang_thai' => $round['status'],
                    'ngay_hen_phong_van' => $scheduledAt,
                    'hinh_thuc_phong_van' => 'online',
                    'interviewer_user_id' => $job->hr_phu_trach_id ?: $job->congTy?->nguoi_dung_id,
                    'link_phong_van' => null,
                    'trang_thai_tham_gia' => null,
                    'thoi_gian_phan_hoi' => null,
                    'ket_qua' => $round['status'] === InterviewRound::TRANG_THAI_HOAN_THANH ? InterviewRound::KET_QUA_DAT : null,
                    'diem_so' => $round['score'],
                    'ghi_chu' => $round['note'],
                    'rubric_danh_gia_json' => null,
                    'created_by' => $job->hr_phu_trach_id ?: $job->congTy?->nguoi_dung_id,
                    'updated_by' => $job->hr_phu_trach_id ?: $job->congTy?->nguoi_dung_id,
                ]
            );
        }
    }

    private function seedOnboarding(UngTuyen $application, TinTuyenDung $job): void
    {
        $profile = $application->hoSo;
        $candidate = $profile?->nguoiDung;
        $hrId = $job->hr_phu_trach_id ?: $job->congTy?->nguoi_dung_id;

        if (!$candidate || !$job->congTy) {
            return;
        }

        $plan = OnboardingPlan::updateOrCreate(
            ['ung_tuyen_id' => $application->id],
            [
                'cong_ty_id' => $job->congTy->id,
                'nguoi_dung_id' => $candidate->id,
                'hr_phu_trach_id' => $hrId,
                'ngay_bat_dau' => now()->addDays(12)->toDateString(),
                'dia_diem_lam_viec' => $job->dia_diem_lam_viec,
                'trang_thai' => OnboardingPlan::TRANG_THAI_DANG_CHUAN_BI,
                'loi_chao_mung' => 'Chào mừng bạn đến với ' . $job->congTy->ten_cong_ty . '. HR sẽ đồng hành cùng bạn trong tuần làm việc đầu tiên.',
                'ghi_chu_noi_bo' => 'Chuẩn bị laptop, email công ty và lịch training sản phẩm trước ngày onboard.',
                'ghi_chu_ung_vien' => 'Vui lòng hoàn tất giấy tờ cá nhân trước ngày nhận việc.',
                'tai_lieu_can_chuan_bi_json' => ['CMND/CCCD', 'Bằng cấp/chứng chỉ', 'Thông tin tài khoản ngân hàng', 'Mã số thuế cá nhân nếu có'],
                'created_by' => $hrId,
                'updated_by' => $hrId,
            ]
        );

        $tasks = [
            ['Hoàn thiện hồ sơ cá nhân', 'Ứng viên bổ sung CCCD, bằng cấp và thông tin ngân hàng.', 1, OnboardingTask::NGUOI_PHU_TRACH_UNG_VIEN, OnboardingTask::TRANG_THAI_DANG_LAM],
            ['Tạo email và tài khoản nội bộ', 'HR/IT tạo email công ty, tài khoản dashboard và quyền truy cập dự án.', 2, OnboardingTask::NGUOI_PHU_TRACH_HR, OnboardingTask::TRANG_THAI_CHO_LAM],
            ['Chuẩn bị thiết bị làm việc', 'Chuẩn bị laptop, tài khoản VPN và hướng dẫn bảo mật.', 3, OnboardingTask::NGUOI_PHU_TRACH_HR, OnboardingTask::TRANG_THAI_CHO_LAM],
            ['Lịch orientation tuần đầu', 'Gửi lịch giới thiệu công ty, sản phẩm, quy trình làm việc và người hướng dẫn.', 4, OnboardingTask::NGUOI_PHU_TRACH_HR, OnboardingTask::TRANG_THAI_CHO_LAM],
        ];

        foreach ($tasks as [$title, $description, $order, $owner, $status]) {
            OnboardingTask::updateOrCreate(
                [
                    'onboarding_plan_id' => $plan->id,
                    'thu_tu' => $order,
                ],
                [
                    'tieu_de' => $title,
                    'mo_ta' => $description,
                    'han_hoan_tat' => now()->addDays($order + 2)->toDateString(),
                    'nguoi_phu_trach' => $owner,
                    'trang_thai' => $status,
                    'hoan_tat_luc' => null,
                    'completed_by' => null,
                    'metadata_json' => [],
                ]
            );
        }
    }
}
