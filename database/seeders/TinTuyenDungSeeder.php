<?php

namespace Database\Seeders;

use App\Models\CongTy;
use App\Models\KyNang;
use App\Models\NganhNghe;
use App\Models\TinTuyenDung;
use App\Models\TinTuyenDungKyNang;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TinTuyenDungSeeder extends Seeder
{
    public function run(): void
    {
        $jobs = [
            ['TechViet Solutions', 'Backend Developer Laravel', ['Công nghệ thông tin', 'Lập trình Backend'], 'Toàn thời gian', 'Nhân viên', 2, 18000000, 30000000, 'Từ 2 năm kinh nghiệm', 'Đại học/Cao đẳng', 35, 420, ['PHP', 'Laravel', 'REST API', 'MySQL', 'Redis', 'Docker', 'Git'], 'Phát triển API và module backend cho nền tảng SaaS quản trị bán lẻ.', ['Thiết kế REST API, service layer và database schema cho module đơn hàng, khách hàng, phân quyền.', 'Tối ưu truy vấn MySQL/Redis cho dashboard có dữ liệu lớn.', 'Phối hợp frontend Vue.js, QA và BA trong sprint Agile.'], ['Có kinh nghiệm Laravel thực tế từ 2 năm.', 'Nắm tốt OOP, REST API, MySQL, Git.', 'Biết Docker/Redis là lợi thế.'], ['Review code định kỳ, lộ trình lên Senior Backend.', 'MacBook hoặc laptop cấu hình cao, bảo hiểm đầy đủ.', 'Thưởng theo hiệu quả dự án và 13th salary.']],
            ['TechViet Solutions', 'Frontend Developer Vue.js', ['Công nghệ thông tin', 'Lập trình Frontend'], 'Toàn thời gian', 'Nhân viên', 2, 16000000, 26000000, 'Từ 2 năm kinh nghiệm', 'Đại học/Cao đẳng', 32, 360, ['JavaScript', 'TypeScript', 'Vue.js', 'Tailwind CSS', 'HTML/CSS', 'REST API', 'Git'], 'Xây dựng dashboard quản trị, cổng khách hàng và component UI cho sản phẩm SaaS.', ['Chuyển thiết kế Figma thành giao diện responsive.', 'Tích hợp API, xử lý form, table, filter, pagination.', 'Tối ưu hiệu năng render và trải nghiệm người dùng.'], ['Có kinh nghiệm Vue.js hoặc React.', 'Hiểu component-based UI, REST API, Git.', 'Có mắt thẩm mỹ và tư duy UX là lợi thế.'], ['Làm việc cùng designer và backend senior.', 'Môi trường product, ít outsource ngắn hạn.', 'Đánh giá tăng lương 2 lần/năm.']],
            ['TechViet Solutions', 'QA Engineer Manual/API', ['Công nghệ thông tin', 'Kiểm thử phần mềm (QA)'], 'Toàn thời gian', 'Nhân viên', 1, 14000000, 22000000, 'Từ 2 năm kinh nghiệm', 'Cao đẳng/Đại học', 28, 210, ['Manual Testing', 'Postman', 'TestRail', 'Automation Testing', 'Selenium'], 'Đảm bảo chất lượng web admin, API và mobile web trước mỗi đợt release.', ['Viết test case, test plan và regression checklist.', 'Kiểm thử API bằng Postman và phối hợp developer tái hiện lỗi.', 'Quản lý defect, xác nhận fix và báo cáo chất lượng release.'], ['Có kinh nghiệm manual testing web/API.', 'Biết viết tài liệu test rõ ràng.', 'Có nền tảng automation là lợi thế.'], ['Có QA Lead mentor.', 'Được tham gia cải tiến quy trình release.', 'Phụ cấp chứng chỉ kiểm thử.']],
            ['SaigonCloud Infrastructure', 'DevOps Engineer', ['Công nghệ thông tin', 'DevOps / SysAdmin'], 'Toàn thời gian', 'Senior', 1, 26000000, 42000000, 'Từ 3 năm kinh nghiệm', 'Đại học', 40, 250, ['Docker', 'Kubernetes', 'Linux', 'CI/CD', 'AWS', 'Redis', 'PostgreSQL'], 'Vận hành hạ tầng cloud, CI/CD, monitoring và backup cho khách hàng doanh nghiệp.', ['Thiết lập pipeline build/deploy cho web app và worker.', 'Giám sát hệ thống, xử lý sự cố và tối ưu chi phí cloud.', 'Chuẩn hóa tài liệu vận hành, backup, logging.'], ['Có kinh nghiệm Linux, Docker, CI/CD.', 'Biết Kubernetes hoặc cloud AWS/Azure.', 'Có khả năng trực incident theo lịch.'], ['Phụ cấp trực vận hành.', 'Ngân sách học chứng chỉ cloud.', 'Môi trường nhiều bài toán hạ tầng thực tế.']],
            ['SaigonCloud Infrastructure', 'Cloud Support Engineer', ['Công nghệ thông tin', 'DevOps / SysAdmin'], 'Remote', 'Nhân viên', 2, 15000000, 24000000, 'Từ 1-3 năm kinh nghiệm', 'Cao đẳng/Đại học', 30, 180, ['Linux', 'AWS', 'Docker', 'Customer Service', 'Problem Solving'], 'Hỗ trợ khách hàng doanh nghiệp trong quá trình vận hành server, cloud và backup.', ['Tiếp nhận ticket kỹ thuật, phân loại mức độ ưu tiên.', 'Hỗ trợ cấu hình server, domain, SSL, backup và monitoring.', 'Viết tài liệu hướng dẫn xử lý sự cố thường gặp.'], ['Có nền tảng Linux/networking cơ bản.', 'Giao tiếp tốt, có tinh thần hỗ trợ khách hàng.', 'Biết AWS/Docker là lợi thế.'], ['Remote linh hoạt.', 'Đào tạo cloud nội bộ.', 'Lộ trình lên DevOps Engineer.']],
            ['NorthStar Analytics', 'Data Analyst', ['Công nghệ thông tin', 'Phân tích dữ liệu'], 'Toàn thời gian', 'Chuyên viên', 2, 20000000, 33000000, 'Từ 2-4 năm kinh nghiệm', 'Đại học', 36, 390, ['SQL', 'Power BI', 'Microsoft Excel', 'Python', 'Data Analysis', 'Data Visualization'], 'Phân tích dữ liệu kinh doanh, xây dashboard và đề xuất insight cho khách hàng bán lẻ/tài chính.', ['Làm sạch dữ liệu, viết truy vấn SQL và xây data mart cơ bản.', 'Thiết kế dashboard Power BI theo chỉ số doanh thu, tồn kho, vận hành.', 'Trình bày insight và khuyến nghị hành động cho stakeholder.'], ['SQL tốt, Excel tốt, biết Power BI/Tableau.', 'Có tư duy phân tích và kỹ năng trình bày.', 'Biết Python/Pandas là lợi thế.'], ['Làm nhiều domain dữ liệu thực tế.', 'Được mentor bởi BI Lead.', 'Thưởng theo dự án tư vấn.']],
            ['NorthStar Analytics', 'BI Developer', ['Công nghệ thông tin', 'Phân tích dữ liệu'], 'Toàn thời gian', 'Nhân viên', 1, 18000000, 28000000, 'Từ 2 năm kinh nghiệm', 'Đại học', 25, 260, ['SQL', 'Power BI', 'ETL', 'Data Visualization', 'Microsoft Excel'], 'Xây data model và dashboard quản trị cho khối tài chính, bán hàng và vận hành.', ['Thiết kế semantic model, measure, report layout.', 'Chuẩn hóa chỉ số, kiểm tra tính nhất quán dữ liệu.', 'Phối hợp analyst và data engineer trong dự án BI.'], ['Có kinh nghiệm Power BI thực tế.', 'Nắm SQL và tư duy data model.', 'Cẩn thận với số liệu và validation.'], ['Dự án đa ngành.', 'Có lộ trình lên Solution Consultant.', 'Được hỗ trợ chứng chỉ Power BI.']],
            ['MobileWave Studio', 'Flutter Developer', ['Công nghệ thông tin', 'Lập trình Mobile'], 'Toàn thời gian', 'Nhân viên', 2, 17000000, 28000000, 'Từ 2 năm kinh nghiệm', 'Cao đẳng/Đại học', 34, 175, ['Flutter', 'Firebase', 'REST API', 'Git'], 'Phát triển ứng dụng đặt lịch, loyalty và thương mại dịch vụ trên iOS/Android bằng Flutter.', ['Xây UI, state management, tích hợp API và push notification.', 'Phối hợp backend xử lý auth, payment, booking flow.', 'Tối ưu hiệu năng và kiểm thử trên nhiều thiết bị.'], ['Có kinh nghiệm Flutter production.', 'Hiểu REST API, Firebase, Git.', 'Có app đã phát hành là lợi thế.'], ['Sản phẩm đa domain.', 'Thiết bị test đầy đủ.', 'Cơ hội học native iOS/Android.']],
            ['MobileWave Studio', 'React Native Developer', ['Công nghệ thông tin', 'Lập trình Mobile'], 'Remote', 'Nhân viên', 1, 16000000, 27000000, 'Từ 2 năm kinh nghiệm', 'Đại học/Cao đẳng', 31, 132, ['React Native', 'JavaScript', 'TypeScript', 'REST API', 'Firebase'], 'Xây mobile app cho startup giáo dục và dịch vụ đặt lịch.', ['Phát triển màn hình, navigation, form và offline cache.', 'Tích hợp Firebase, analytics, push notification.', 'Phối hợp QA xử lý crash và tối ưu release.'], ['Có kinh nghiệm React Native.', 'Biết TypeScript và REST API.', 'Có tư duy sản phẩm và chủ động debug.'], ['Remote 3 ngày/tuần.', 'Thưởng theo milestone.', 'Được tham gia quyết định kiến trúc app.']],
            ['Mekong Commerce', 'E-commerce Operations Executive', ['Kinh doanh / Bán hàng', 'Thương mại điện tử'], 'Toàn thời gian', 'Nhân viên', 3, 10000000, 16000000, 'Từ 1 năm kinh nghiệm', 'Cao đẳng/Đại học', 30, 280, ['E-commerce', 'Microsoft Excel', 'Inventory Management', 'Customer Service'], 'Vận hành gian hàng Shopee/Lazada/TikTok Shop và phối hợp kho xử lý đơn hàng.', ['Quản lý danh mục sản phẩm, tồn kho, campaign và voucher.', 'Theo dõi tỷ lệ hoàn đơn, SLA giao hàng và đánh giá khách hàng.', 'Phối hợp marketing tối ưu nội dung sản phẩm.'], ['Có kinh nghiệm sàn thương mại điện tử.', 'Excel tốt, cẩn thận với số liệu tồn kho.', 'Giao tiếp tốt với kho và CSKH.'], ['Môi trường tăng trưởng nhanh.', 'Thưởng theo doanh số gian hàng.', 'Được đào tạo livestream commerce.']],
            ['Mekong Commerce', 'Customer Service Online', ['Kinh doanh / Bán hàng', 'Nhân viên kinh doanh'], 'Toàn thời gian', 'Nhân viên', 4, 8000000, 12000000, 'Ưu tiên 6 tháng kinh nghiệm', 'Trung cấp/Cao đẳng', 22, 190, ['Customer Service', 'Complaint Handling', 'CRM', 'Communication'], 'Tư vấn khách hàng qua chat, hotline và mạng xã hội cho các đơn hàng online.', ['Trả lời thắc mắc về sản phẩm, đơn hàng, đổi trả.', 'Ghi nhận phản hồi và cập nhật tình trạng trên CRM.', 'Phối hợp kho vận xử lý khiếu nại.'], ['Giao tiếp rõ ràng, kiên nhẫn.', 'Có kinh nghiệm CSKH online là lợi thế.', 'Làm được theo ca hành chính mở rộng.'], ['Đào tạo sản phẩm đầy đủ.', 'Thưởng KPI CSKH.', 'Lộ trình lên team lead.']],
            ['An Phát Retail Group', 'Sales Supervisor FMCG', ['Kinh doanh / Bán hàng', 'Quản lý bán hàng'], 'Toàn thời gian', 'Quản lý', 2, 18000000, 30000000, 'Từ 3 năm kinh nghiệm', 'Cao đẳng/Đại học', 38, 145, ['Sales B2C', 'CRM', 'Account Management', 'Negotiation', 'Teamwork'], 'Quản lý đội bán hàng khu vực, doanh số cửa hàng và chương trình trưng bày sản phẩm.', ['Theo dõi doanh số, tồn kho và chỉ tiêu từng cửa hàng.', 'Huấn luyện nhân viên bán hàng và xử lý vấn đề khách hàng.', 'Làm việc với trade marketing về chương trình bán hàng.'], ['Có kinh nghiệm retail/FMCG.', 'Biết quản lý đội nhóm và số liệu doanh số.', 'Sẵn sàng đi thị trường.'], ['Thưởng doanh số hấp dẫn.', 'Lộ trình lên Area Sales Manager.', 'Bảo hiểm và công tác phí đầy đủ.']],
            ['An Phát Retail Group', 'CRM Specialist', ['Kinh doanh / Bán hàng', 'Thương mại điện tử'], 'Toàn thời gian', 'Chuyên viên', 1, 14000000, 22000000, 'Từ 2 năm kinh nghiệm', 'Đại học', 29, 120, ['CRM', 'Microsoft Excel', 'Google Analytics', 'Email Marketing', 'Data Analysis'], 'Phân tích dữ liệu khách hàng và triển khai chiến dịch chăm sóc thành viên.', ['Thiết lập segment khách hàng, coupon và email/SMS campaign.', 'Theo dõi retention, repeat purchase và hiệu quả ưu đãi.', 'Phối hợp IT kiểm tra dữ liệu CRM.'], ['Có kinh nghiệm CRM/loyalty.', 'Excel và phân tích số liệu tốt.', 'Biết Google Analytics hoặc BI là lợi thế.'], ['Có dữ liệu khách hàng lớn.', 'Cơ hội xây loyalty program.', 'Thưởng theo campaign.']],
            ['DigiGrowth Agency', 'Digital Marketing Executive', ['Marketing / Truyền thông', 'Digital Marketing'], 'Toàn thời gian', 'Nhân viên', 2, 12000000, 20000000, 'Từ 1-2 năm kinh nghiệm', 'Đại học', 33, 310, ['Facebook Ads', 'Google Ads', 'Google Analytics', 'Content Marketing', 'SEO'], 'Triển khai Facebook Ads, Google Ads và tối ưu landing page cho khách hàng bán lẻ/giáo dục.', ['Lên media plan, tracking UTM và báo cáo hiệu quả.', 'Theo dõi CPL, ROAS, CTR và tối ưu ngân sách.', 'Phối hợp content/design tạo creative phù hợp insight.'], ['Có kinh nghiệm chạy ads thực tế.', 'Đọc hiểu số liệu và báo cáo rõ ràng.', 'Biết SEO/content là lợi thế.'], ['Ngân sách ads thật để học nhanh.', 'Mentor bởi Performance Lead.', 'Thưởng theo hiệu quả campaign.']],
            ['DigiGrowth Agency', 'Content Marketing Intern', ['Marketing / Truyền thông', 'Content Marketing'], 'Thực tập', 'Thực tập sinh', 3, 3000000, 5000000, 'Không yêu cầu kinh nghiệm', 'Sinh viên năm 3 trở lên', 45, 160, ['Content Marketing', 'Copywriting', 'SEO', 'Canva'], 'Hỗ trợ viết social post, blog SEO và email marketing dưới sự hướng dẫn của team lead.', ['Nghiên cứu keyword, lên outline và viết bài blog.', 'Viết caption social, email và nội dung landing page.', 'Theo dõi performance nội dung cơ bản.'], ['Viết tiếng Việt tốt.', 'Có tinh thần học hỏi và đúng deadline.', 'Biết Canva/SEO cơ bản là lợi thế.'], ['Có mentor kèm cặp.', 'Được tham gia campaign thật.', 'Có hỗ trợ thực tập và cơ hội lên chính thức.']],
            ['Bloom Media House', 'Graphic Designer Marketing', ['Marketing / Truyền thông', 'Thiết kế đồ hoạ'], 'Toàn thời gian', 'Nhân viên', 2, 11000000, 18000000, 'Từ 1 năm kinh nghiệm', 'Cao đẳng/Đại học', 27, 155, ['Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'Canva'], 'Thiết kế key visual, banner, social post và landing visual cho chiến dịch truyền thông.', ['Thiết kế bộ creative theo guideline thương hiệu.', 'Phối hợp content và performance để A/B testing visual.', 'Chuẩn bị file handoff cho media và web.'], ['Thành thạo Photoshop/Illustrator/Figma.', 'Có portfolio social/landing visual.', 'Cập nhật xu hướng thiết kế số.'], ['Môi trường sáng tạo.', 'Được làm nhiều ngành hàng.', 'Phụ cấp công cụ thiết kế.']],
            ['Bloom Media House', 'Social Media Executive', ['Marketing / Truyền thông', 'Digital Marketing'], 'Toàn thời gian', 'Nhân viên', 1, 10000000, 16000000, 'Từ 1 năm kinh nghiệm', 'Đại học/Cao đẳng', 26, 130, ['Social Media Marketing', 'Content Marketing', 'Copywriting', 'TikTok Ads'], 'Quản lý lịch đăng bài, nội dung social và tương tác cộng đồng cho thương hiệu tiêu dùng.', ['Lên content calendar, phối hợp designer/video.', 'Theo dõi chỉ số reach, engagement, sentiment.', 'Đề xuất ý tưởng mini campaign theo trend.'], ['Có kinh nghiệm quản trị fanpage/TikTok.', 'Viết caption tốt, bắt trend nhanh.', 'Biết ads cơ bản là lợi thế.'], ['Được thử nghiệm ý tưởng mới.', 'Làm việc cùng creative team.', 'Thưởng campaign tốt.']],
            ['Lotus Finance Advisory', 'Financial Analyst', ['Kế toán / Tài chính', 'Tài chính doanh nghiệp'], 'Toàn thời gian', 'Chuyên viên', 1, 18000000, 28000000, 'Từ 2 năm kinh nghiệm', 'Đại học', 34, 115, ['Financial Analysis', 'Budgeting', 'Microsoft Excel', 'Accounting', 'Presentation'], 'Phân tích báo cáo tài chính, ngân sách và hiệu quả vận hành cho khách hàng SME.', ['Xây model tài chính, phân tích dòng tiền và biên lợi nhuận.', 'Chuẩn bị báo cáo tư vấn và trình bày với khách hàng.', 'Phối hợp kế toán rà soát dữ liệu đầu vào.'], ['Excel tốt, hiểu báo cáo tài chính.', 'Có tư duy phân tích và trình bày.', 'Biết Power BI là lợi thế.'], ['Làm việc với nhiều doanh nghiệp thật.', 'Được mentor bởi Finance Manager.', 'Thưởng dự án tư vấn.']],
            ['FinCore Accounting Services', 'Kế toán tổng hợp', ['Kế toán / Tài chính', 'Kế toán tổng hợp'], 'Toàn thời gian', 'Nhân viên', 2, 12000000, 18000000, 'Từ 2 năm kinh nghiệm', 'Đại học/Cao đẳng', 30, 170, ['Accounting', 'Bookkeeping', 'Tax Declaration', 'MISA', 'Microsoft Excel'], 'Phụ trách sổ sách kế toán, báo cáo thuế và đối soát công nợ cho khách hàng dịch vụ.', ['Hạch toán nghiệp vụ phát sinh, kiểm tra chứng từ.', 'Lập tờ khai thuế tháng/quý và báo cáo quản trị.', 'Đối soát công nợ, payroll và chi phí.'], ['Có kinh nghiệm kế toán tổng hợp.', 'Thành thạo MISA và Excel.', 'Cẩn thận, đúng hạn, bảo mật thông tin.'], ['Được đào tạo cập nhật chính sách thuế.', 'Lộ trình lên kế toán trưởng dịch vụ.', 'Môi trường ổn định.']],
            ['TalentBridge Vietnam', 'IT Recruiter', ['Nhân sự / Hành chính', 'Tuyển dụng (Recruiter)'], 'Toàn thời gian', 'Nhân viên', 3, 12000000, 22000000, 'Từ 1 năm kinh nghiệm', 'Đại học', 31, 185, ['Recruitment', 'Talent Acquisition', 'Interviewing', 'Communication', 'CRM'], 'Tuyển dụng vị trí developer, QA, data và DevOps cho khách hàng công nghệ.', ['Sourcing ứng viên trên nhiều kênh, screening CV.', 'Phỏng vấn sơ bộ, điều phối lịch và cập nhật pipeline.', 'Tư vấn khách hàng về thị trường nhân sự.'], ['Có kinh nghiệm tuyển dụng IT là lợi thế.', 'Giao tiếp tốt, theo sát pipeline.', 'Biết dùng ATS/CRM tuyển dụng.'], ['Hoa hồng tuyển dụng rõ ràng.', 'Được học thị trường IT.', 'Cơ hội lên Senior Recruiter.']],
            ['PeopleSphere HR Consulting', 'Learning & Development Specialist', ['Nhân sự / Hành chính', 'Đào tạo & Phát triển'], 'Toàn thời gian', 'Chuyên viên', 1, 14000000, 23000000, 'Từ 2 năm kinh nghiệm', 'Đại học', 32, 105, ['Training & Development', 'Onboarding', 'Presentation', 'LMS', 'Employee Relations'], 'Thiết kế chương trình đào tạo nội bộ, onboarding và đánh giá sau đào tạo.', ['Khảo sát nhu cầu đào tạo, xây learning path.', 'Tổ chức workshop, đo hiệu quả sau đào tạo.', 'Quản lý nội dung LMS và tài liệu nhân sự.'], ['Có kinh nghiệm L&D hoặc HRBP.', 'Trình bày tốt, biết thiết kế tài liệu.', 'Có kinh nghiệm LMS là lợi thế.'], ['Làm dự án tư vấn đa ngành.', 'Ngân sách học chuyên môn.', 'Môi trường HR chuyên sâu.']],
            ['EduSpark Learning', 'Giáo viên tiếng Anh online', ['Giáo dục / Đào tạo', 'Giảng viên / Giáo viên'], 'Bán thời gian', 'Nhân viên', 5, 8000000, 15000000, 'Từ 1 năm kinh nghiệm', 'Đại học/Cao đẳng', 42, 220, ['Lesson Planning', 'Online Teaching', 'LMS', 'Tiếng Anh', 'Classroom Management'], 'Giảng dạy tiếng Anh giao tiếp online cho học sinh và người đi làm.', ['Chuẩn bị lesson plan, bài tập và rubric speaking.', 'Dạy lớp online qua nền tảng LMS/Zoom.', 'Theo dõi tiến độ học viên và gửi feedback định kỳ.'], ['Tiếng Anh tốt, phát âm rõ.', 'Có kinh nghiệm dạy online.', 'Kiên nhẫn và biết cá nhân hóa bài học.'], ['Lịch dạy linh hoạt.', 'Có giáo trình sẵn.', 'Thưởng theo đánh giá học viên.']],
            ['Sunrise Academy', 'Chuyên viên học vụ LMS', ['Giáo dục / Đào tạo', 'Đào tạo & Phát triển'], 'Toàn thời gian', 'Nhân viên', 2, 9000000, 14000000, 'Từ 1 năm kinh nghiệm', 'Cao đẳng/Đại học', 29, 100, ['LMS', 'Administrative Support', 'Customer Service', 'Microsoft Excel', 'Scheduling'], 'Quản lý lịch học, điểm danh, tài khoản LMS và hỗ trợ học viên/người dạy.', ['Cập nhật lịch lớp, tài liệu, bài tập trên LMS.', 'Hỗ trợ học viên xử lý vấn đề tài khoản/lớp học.', 'Báo cáo tỷ lệ hoàn thành khóa học.'], ['Cẩn thận, giao tiếp tốt.', 'Biết Excel và hệ thống LMS.', 'Có kinh nghiệm học vụ là lợi thế.'], ['Môi trường giáo dục trẻ.', 'Đào tạo quy trình LMS.', 'Có thưởng chất lượng dịch vụ.']],
            ['MediLink Clinic Network', 'Điều dưỡng phòng khám', ['Y tế / Sức khoẻ'], 'Toàn thời gian', 'Nhân viên', 4, 10000000, 16000000, 'Từ 1 năm kinh nghiệm', 'Cao đẳng y tế', 37, 205, ['Patient Care', 'Medical Records', 'Nursing Care', 'Clinical Assistance', 'Customer Service'], 'Hỗ trợ bác sĩ, chăm sóc bệnh nhân và cập nhật hồ sơ bệnh án điện tử tại phòng khám.', ['Đón tiếp, hướng dẫn bệnh nhân và hỗ trợ quy trình khám.', 'Ghi nhận thông tin bệnh án, chỉ số cơ bản và lịch tái khám.', 'Phối hợp bác sĩ, dược sĩ và CSKH sau khám.'], ['Có chứng chỉ/kinh nghiệm điều dưỡng.', 'Cẩn thận, giao tiếp nhẹ nhàng.', 'Biết sử dụng phần mềm hồ sơ bệnh án là lợi thế.'], ['Môi trường y tế chuyên nghiệp.', 'Được đào tạo quy trình phòng khám.', 'Phụ cấp ca và bảo hiểm đầy đủ.']],
            ['HealCare Pharmacy', 'Dược sĩ tư vấn', ['Y tế / Sức khoẻ'], 'Toàn thời gian', 'Nhân viên', 3, 9000000, 15000000, 'Từ 1 năm kinh nghiệm', 'Trung cấp/Cao đẳng dược', 33, 160, ['Pharmacy', 'Customer Service', 'Medical Records', 'Communication'], 'Tư vấn thuốc, sản phẩm chăm sóc sức khỏe và quản lý hồ sơ khách hàng tại nhà thuốc.', ['Tư vấn sử dụng thuốc theo đơn và sản phẩm OTC.', 'Kiểm tra tồn kho, hạn dùng và nhập liệu bán hàng.', 'Ghi nhận phản hồi khách hàng và nhắc lịch mua lại.'], ['Có bằng dược phù hợp.', 'Giao tiếp tốt, cẩn thận với thông tin thuốc.', 'Có kinh nghiệm nhà thuốc là lợi thế.'], ['Đào tạo sản phẩm định kỳ.', 'Thưởng doanh số minh bạch.', 'Môi trường làm việc ổn định.']],
            ['Skyline Build Design', 'Kỹ sư thiết kế Revit/AutoCAD', ['Xây dựng / Bất động sản'], 'Toàn thời gian', 'Nhân viên', 2, 15000000, 25000000, 'Từ 2 năm kinh nghiệm', 'Đại học', 36, 150, ['AutoCAD', 'Revit', 'SketchUp', 'Document Control', 'Project Management'], 'Triển khai bản vẽ kỹ thuật, phối hợp BIM và kiểm soát hồ sơ thiết kế công trình dân dụng.', ['Triển khai bản vẽ kiến trúc/kết cấu theo yêu cầu dự án.', 'Phối hợp công trường xử lý thay đổi thiết kế.', 'Quản lý phiên bản hồ sơ và tài liệu kỹ thuật.'], ['Thành thạo AutoCAD, biết Revit.', 'Hiểu quy trình hồ sơ thiết kế.', 'Cẩn thận, phối hợp tốt với công trường.'], ['Dự án thực tế đa dạng.', 'Phụ cấp công trình.', 'Lộ trình lên chủ trì thiết kế.']],
            ['GreenHome Real Estate', 'Chuyên viên kinh doanh bất động sản', ['Xây dựng / Bất động sản', 'Nhân viên kinh doanh'], 'Toàn thời gian', 'Nhân viên', 5, 9000000, 25000000, 'Ưu tiên có kinh nghiệm sales', 'Cao đẳng/Đại học', 40, 240, ['Sales B2C', 'Negotiation', 'CRM', 'Customer Service', 'Presentation'], 'Tư vấn dự án nhà ở xanh, chăm sóc khách hàng và hỗ trợ thủ tục giao dịch.', ['Tìm kiếm khách hàng, tư vấn sản phẩm và dẫn khách tham quan dự án.', 'Cập nhật CRM, theo dõi nhu cầu và hỗ trợ hồ sơ giao dịch.', 'Phối hợp marketing trong sự kiện mở bán.'], ['Giao tiếp tốt, có tinh thần bán hàng.', 'Biết CRM và chăm sóc khách hàng.', 'Có kinh nghiệm bất động sản là lợi thế.'], ['Hoa hồng cạnh tranh.', 'Đào tạo sản phẩm và pháp lý dự án.', 'Nguồn lead từ marketing.']],
            ['VietLogix Supply Chain', 'Logistics Coordinator', ['Kinh doanh / Bán hàng', 'Thương mại điện tử'], 'Toàn thời gian', 'Nhân viên', 3, 11000000, 18000000, 'Từ 1-3 năm kinh nghiệm', 'Đại học/Cao đẳng', 35, 190, ['Warehouse Management', 'Inventory Management', 'Transportation Management', 'Supply Chain', 'Microsoft Excel'], 'Điều phối kho, giao nhận và tồn kho cho khách hàng thương mại điện tử.', ['Theo dõi đơn hàng, tuyến giao, tồn kho và sự cố vận chuyển.', 'Làm việc với nhà vận chuyển và kho để xử lý lệch tồn.', 'Báo cáo SLA giao hàng, chi phí và tỷ lệ hoàn.'], ['Có kinh nghiệm logistics/kho vận.', 'Excel tốt, chịu được áp lực thời gian.', 'Hiểu e-commerce là lợi thế.'], ['Phụ cấp điện thoại/công tác.', 'Được học hệ thống WMS/TMS.', 'Lộ trình lên Operations Supervisor.']],
            ['Lumiere Travel & Hospitality', 'Front Office Supervisor', ['Kinh doanh / Bán hàng'], 'Toàn thời gian', 'Quản lý', 1, 12000000, 20000000, 'Từ 2 năm kinh nghiệm', 'Cao đẳng/Đại học', 28, 125, ['Front Office', 'Reservation Management', 'Customer Service', 'Event Planning', 'Tiếng Anh'], 'Quản lý lễ tân, đặt phòng và trải nghiệm khách tại khách sạn boutique/tour dịch vụ.', ['Điều phối ca lễ tân, xử lý booking và yêu cầu khách.', 'Phối hợp housekeeping, sales tour và event team.', 'Theo dõi phản hồi khách và cải thiện quy trình phục vụ.'], ['Có kinh nghiệm front office/khách sạn.', 'Tiếng Anh giao tiếp tốt.', 'Biết hệ thống reservation là lợi thế.'], ['Môi trường du lịch năng động.', 'Phụ cấp ca và service charge.', 'Cơ hội lên Operations Manager.']],
        ];

        $companies = CongTy::with('nguoiDung')->get()->keyBy('ten_cong_ty');
        $industries = NganhNghe::all()->keyBy('ten_nganh');
        $skills = KyNang::all()->keyBy('ten_ky_nang');
        $count = 0;

        foreach ($jobs as [$companyName, $title, $industryNames, $workType, $level, $quantity, $salaryFrom, $salaryTo, $experience, $education, $days, $views, $skillNames, $summary, $tasks, $requirements, $benefits]) {
            $company = $companies->get($companyName);

            if (!$company) {
                continue;
            }

            $job = TinTuyenDung::updateOrCreate(
                [
                    'cong_ty_id' => $company->id,
                    'tieu_de' => $title,
                ],
                [
                    'mo_ta_cong_viec' => $this->buildJobDescription($summary, $tasks, $requirements, $benefits, $skillNames),
                    'dia_diem_lam_viec' => $this->resolveWorkLocation($company->dia_chi),
                    'hinh_thuc_lam_viec' => $workType,
                    'cap_bac' => $level,
                    'so_luong_tuyen' => $quantity,
                    'muc_luong_tu' => $salaryFrom,
                    'muc_luong_den' => $salaryTo,
                    'don_vi_luong' => 'VND/tháng',
                    'kinh_nghiem_yeu_cau' => $experience,
                    'trinh_do_yeu_cau' => $education,
                    'ngay_het_han' => Carbon::now()->addDays($days),
                    'luot_xem' => $views,
                    'hr_phu_trach_id' => $company->nguoi_dung_id,
                    'trang_thai' => TinTuyenDung::TRANG_THAI_HOAT_DONG,
                    'published_at' => Carbon::now()->subDays(10),
                    'reactivated_at' => null,
                    'featured_activated_at' => in_array($title, ['Backend Developer Laravel', 'Data Analyst', 'Digital Marketing Executive'], true)
                        ? Carbon::now()->subDays(2)
                        : null,
                    'featured_until' => in_array($title, ['Backend Developer Laravel', 'Data Analyst', 'Digital Marketing Executive'], true)
                        ? Carbon::now()->addDays(12)
                        : null,
                ]
            );

            $industryIds = collect($industryNames)
                ->map(fn (string $name) => $industries->get($name)?->id)
                ->filter()
                ->values()
                ->all();

            $job->nganhNghes()->sync($industryIds);

            foreach ($skillNames as $index => $skillName) {
                $skill = $skills->get($skillName);

                if (!$skill) {
                    continue;
                }

                TinTuyenDungKyNang::updateOrCreate(
                    [
                        'tin_tuyen_dung_id' => $job->id,
                        'ky_nang_id' => $skill->id,
                    ],
                    [
                        'muc_do_yeu_cau' => $index < 3 ? 4 : 3,
                        'bat_buoc' => $index < 4,
                        'trong_so' => $index < 3 ? 1.25 : 1.0,
                        'nguon_du_lieu' => 'manual',
                        'do_tin_cay' => 0.95,
                    ]
                );
            }

            $count++;
        }

        $this->command->info("✅ TinTuyenDungSeeder: Đã tạo {$count} tin tuyển dụng JD chi tiết, có ngành nghề và kỹ năng yêu cầu.");
    }

    private function buildJobDescription(string $summary, array $tasks, array $requirements, array $benefits, array $skills): string
    {
        return implode("\n\n", [
            'Tổng quan vị trí: ' . $summary,
            "Mô tả công việc:\n- " . implode("\n- ", $tasks),
            "Yêu cầu ứng viên:\n- " . implode("\n- ", $requirements),
            'Kỹ năng/chuyên môn cần có: ' . implode(', ', $skills) . '.',
            "Quyền lợi:\n- " . implode("\n- ", $benefits),
            'Quy trình tuyển dụng: sàng lọc hồ sơ, phỏng vấn chuyên môn, trao đổi offer và onboarding.',
        ]);
    }

    private function resolveWorkLocation(?string $address): string
    {
        if (!$address) {
            return 'Việt Nam';
        }

        $parts = array_map('trim', explode(',', $address));

        return implode(', ', array_slice($parts, -2));
    }
}
