# Tài Liệu Tổng Quan Hệ Thống Và Kịch Bản Demo Bảo Vệ Khóa Luận

Ngày rà soát codebase: 09/05/2026  
Phạm vi rà soát: `BE`, `FE`, `AI`, các route API, controller, model, migration, seeder, tài liệu hiện có trong `BE/docs`, router frontend và service tích hợp AI/thanh toán.

## 1. Tóm Tắt Hệ Thống

Hệ thống là nền tảng tuyển dụng và tư vấn việc làm tích hợp trí tuệ nhân tạo. Sản phẩm phục vụ bốn nhóm người dùng chính:

- Khách vãng lai: tra cứu việc làm, công ty, ngành nghề, kỹ năng; đăng ký tài khoản.
- Ứng viên: tạo hồ sơ/CV, quản lý kỹ năng, tìm việc, lưu tin, theo dõi công ty, ứng tuyển, theo dõi pipeline tuyển dụng, dùng các tính năng AI hỗ trợ nghề nghiệp.
- Nhà tuyển dụng: quản lý công ty, thành viên HR, phân quyền nội bộ, đăng tin, lọc ứng viên, quản lý phỏng vấn, offer, onboarding và thanh toán.
- Quản trị viên: quản trị dữ liệu nền, tài khoản, công ty, hồ sơ, tin tuyển dụng, ứng tuyển, billing, AI usage, audit log và phân quyền admin.

Điểm nổi bật của hệ thống không chỉ nằm ở CRUD tuyển dụng, mà ở việc kết nối toàn bộ vòng đời tuyển dụng:

1. Ứng viên xây dựng hồ sơ và được AI phân tích.
2. Hệ thống kiểm tra chất lượng dữ liệu CV/JD trước khi đưa vào matching.
3. Hệ thống so khớp hồ sơ với tin tuyển dụng, có giải thích theo kỹ năng, kinh nghiệm, học vấn, lương, địa điểm và hình thức làm việc.
4. Ứng viên ứng tuyển, sinh thư xin việc bằng AI và được kiểm tra tránh bịa kỹ năng.
5. Nhà tuyển dụng dùng AI để parse JD, shortlist, so sánh ứng viên và hỗ trợ phỏng vấn.
6. Sau phỏng vấn, hệ thống hỗ trợ offer, ứng viên phản hồi offer, onboarding và xuất tài liệu.
7. Admin giám sát dữ liệu, hoạt động AI, thanh toán và audit log.

## 2. Cấu Trúc Codebase

```text
KLTN_Final3/
├── BE/      Backend Laravel API, database, queue, realtime, billing, notifications
├── FE/      Frontend Vue 3/Vite cho guest, ứng viên, nhà tuyển dụng, admin
├── AI/      FastAPI service cho parsing, matching, generation, chatbot, interview AI
├── Momo/    Mẫu/tài nguyên tham khảo tích hợp MoMo sandbox
└── BE/docs/ Tài liệu API, database, use case, trạng thái tính năng
```

### 2.1. Backend `BE`

Backend là Laravel API, đóng vai trò trung tâm nghiệp vụ.

Các nhóm thư mục chính:

- `BE/routes/api.php`: khai báo toàn bộ REST API theo nhóm public/auth/candidate/employer/admin/payment.
- `BE/app/Http/Controllers/Api`: controller nghiệp vụ chính.
- `BE/app/Http/Controllers/Api/Admin`: controller quản trị hệ thống.
- `BE/app/Http/Middleware`: middleware kiểm tra vai trò hệ thống, quyền admin, quyền nội bộ công ty.
- `BE/app/Models`: Eloquent model cho người dùng, hồ sơ, công ty, tin tuyển dụng, ứng tuyển, AI, billing, notification.
- `BE/app/Services`: service tầng nghiệp vụ như AI client, billing, ví, audit log, notification, onboarding, export.
- `BE/app/Events`, `BE/app/Listeners`, `BE/app/Notifications`: realtime event, listener và email/in-app notification.
- `BE/database/migrations`: schema database.
- `BE/database/seeders`: dữ liệu demo/tự động khởi tạo.
- `BE/resources/js`, `BE/resources/css`: asset Laravel/Vite phụ trợ cho backend.

### 2.2. Frontend `FE`

Frontend là SPA Vue 3 dùng Vue Router, tách layout theo nhóm người dùng.

Các nhóm chính:

- `FE/src/router/index.js`: định nghĩa route và guard theo vai trò.
- `FE/src/services/api.js`: lớp gọi API tập trung, xử lý token, lỗi, stream, blob download.
- `FE/src/services/realtime.js`: kết nối realtime qua Laravel Echo/Pusher/Reverb.
- `FE/src/components/Guest`: landing, tìm việc, chi tiết việc, công ty, ngành nghề, kỹ năng, đăng nhập/đăng ký/reset password.
- `FE/src/components/Dashboard`: màn ứng viên như hồ sơ, CV builder, kỹ năng, ứng tuyển, AI Center, ví, gói dịch vụ.
- `FE/src/components/Employer`: dashboard nhà tuyển dụng, công ty, HR, tin tuyển dụng, ứng viên, phỏng vấn, billing, audit log.
- `FE/src/components/Admin`: dashboard admin, quản lý user, admin, company, profile, skill, industry, job, application, AI usage, billing, audit.
- `FE/src/layouts`: layout guest, auth, dashboard ứng viên, employer, admin, CV builder, plain.
- Các vùng văn bản dài như mục tiêu nghề nghiệp, cover letter, ghi chú ứng tuyển/phỏng vấn ở `/my-cv`, `/admin/profiles` và `/admin/applications` dùng vùng scroll nội bộ để không cắt mất nội dung.

### 2.3. AI Service `AI`

AI service là FastAPI tách riêng khỏi Laravel để xử lý các tác vụ AI.

Các nhóm chính:

- `AI/app/main.py`: đăng ký app và router.
- `AI/app/routers`: endpoint AI như parse CV/JD, matching, generation, chat, interview.
- `AI/app/services`: logic xử lý CV parser, JD parser, matching, chatbot, mock interview, interview copilot, career report.
- `AI/app/providers`: provider template, Ollama local và OpenAI-compatible.
- `AI/app/schemas`: schema Pydantic cho request/response.
- `AI/tests`: regression test cho chatbot, mock interview, CV builder writing, career path simulator, skill alias và matching.
- `AI/data/skill_aliases.json`: catalog kỹ năng/alias dùng để chuẩn hóa kỹ năng khi parse CV, parse JD, matching, shortlist/compare, chatbot và các seed demo.

## 3. Công Nghệ Sử Dụng

### 3.1. Backend

- PHP `^8.2`.
- Laravel Framework `^12.0`.
- Laravel Sanctum `^4.3` cho Bearer token/API auth.
- Laravel Socialite `^5.26` cho đăng nhập Google.
- Laravel Reverb `^1.10`, Laravel Echo, Pusher PHP Server cho realtime/broadcasting.
- DomPDF `barryvdh/laravel-dompdf` cho xuất tài liệu PDF server-side.
- L5 Swagger có trong dependency để hỗ trợ tài liệu API.
- Queue/job, event/listener, notification và scheduler của Laravel.
- Database quan hệ qua Eloquent ORM, schema nằm trong `BE/database/migrations`.

### 3.2. Frontend

- Vue `^3.5`.
- Vue Router `^4.6`.
- Vite `^7`.
- Tailwind CSS `^4`.
- Laravel Echo `^2.3` và Pusher JS `^8.5` cho realtime.
- Vue Toastification cho thông báo giao diện.
- Fetch API tập trung trong `FE/src/services/api.js`, có hỗ trợ JSON, blob download và streaming response.

### 3.3. AI Service

- Python FastAPI `0.116`.
- Uvicorn `0.35`.
- Pydantic `2.11`.
- pdfplumber cho trích xuất nội dung PDF.
- python-dotenv cho cấu hình môi trường.
- Provider AI linh hoạt: template fallback, Ollama local và OpenAI legacy; demo hiện ưu tiên Ollama local.

### 3.4. Tích Hợp Bên Ngoài

- Google OAuth cho đăng nhập.
- MoMo sandbox cho nạp ví/mua gói.
- VNPay sandbox cho nạp ví/mua gói.
- Email notification cho xác thực email, reset password, lịch phỏng vấn, offer.
- Realtime notification cho thay đổi ứng tuyển, job mới từ công ty đang theo dõi, billing.

## 4. Kiến Trúc Tổng Quan

Luồng kiến trúc chính:

```text
Người dùng
  ↓
Vue 3 SPA (FE)
  ↓ REST API / Stream / Blob download / Realtime channel
Laravel Backend (BE)
  ├── Auth, role, permission, validation
  ├── Business logic: profile, job, application, interview, offer, onboarding
  ├── Billing: wallet, subscription, payment gateway
  ├── Notification, event, audit log, export PDF
  └── AiClientService
        ↓ HTTP JSON
     FastAPI AI Service (AI)
        ├── Parse CV/JD kèm quality guard
        ├── Matching CV-JD theo kỹ năng/alias, kinh nghiệm, học vấn, ngữ cảnh CV-JD, lương, địa điểm, hình thức làm việc
        ├── Career report
        ├── Cover letter kèm audit kỹ năng
        ├── CV builder writing
        ├── Career chatbot có RAG nhẹ từ database hiện có
        └── Mock interview / Interview copilot có rubric và cảnh báo câu trả lời yếu
```

Backend là nguồn sự thật của dữ liệu nghiệp vụ. AI service chỉ nhận dữ liệu đã được backend chuẩn bị, trả kết quả có cấu trúc, sau đó backend lưu kết quả vào database và ghi log sử dụng AI.
Các nâng cấp AI mới ưu tiên mở rộng response và service logic, tận dụng các bảng hiện có như `ho_so_parsings`, `tin_tuyen_dung_parsings`, `ket_qua_matchings`, `ai_chat_sessions`, `ai_chat_messages`, `ai_usage_logs`, hạn chế tạo thêm schema mới.

## 5. Mô Hình Vai Trò Và Phân Quyền

### 5.1. Vai Trò Cấp Hệ Thống

Trong bảng `nguoi_dungs`, hệ thống dùng `vai_tro`:

- `0`: ứng viên.
- `1`: nhà tuyển dụng.
- `2`: quản trị viên.

Frontend guard tự điều hướng theo vai trò:

- Ứng viên vào `/dashboard`.
- Nhà tuyển dụng vào `/employer`.
- Admin vào `/admin`.

Backend dùng middleware:

- `auth:sanctum`: yêu cầu token.
- `role:ung_vien`, `role:nha_tuyen_dung`, `role:admin`: kiểm tra vai trò.
- `admin_permission:*`: kiểm tra quyền admin.
- `company_role:permission:*`: kiểm tra quyền nội bộ công ty của HR.

### 5.2. Phân Quyền Admin

Admin có hai cấp:

- `super_admin`: có quyền quản lý admin khác và quyền hệ thống cao nhất.
- `admin`: admin thường, quyền chi tiết nằm trong `quyen_admin`.

Các nhóm quyền admin chính:

- `users`: quản lý người dùng.
- `companies`: quản lý công ty.
- `profiles`: quản lý hồ sơ ứng viên.
- `jobs`: quản lý tin tuyển dụng.
- `applications`: quản lý ứng tuyển.
- `industries`, `skills`, `user_skills`: quản lý danh mục và kỹ năng.
- `matchings`, `career_advising`, `cv_templates`: quản lý dữ liệu AI/CV.
- `billing`, `ai_usage`, `audit_logs`, `stats`: vận hành hệ thống.

### 5.3. Phân Quyền Nội Bộ Công Ty

Nhà tuyển dụng thuộc công ty qua `cong_ty_nguoi_dungs`.

Vai trò nội bộ hiện tại:

- `owner`: chủ sở hữu công ty, thường có đầy đủ quyền.
- `member`: HR thường, quyền chi tiết nằm trong `quyen_noi_bo`.

Các quyền nội bộ quan trọng:

- `company_profile`: cập nhật hồ sơ công ty.
- `members`: quản lý thành viên HR.
- `jobs`: quản lý tin tuyển dụng.
- `applications`: xem/xử lý ứng tuyển.
- `interviews`: quản lý vòng phỏng vấn.
- `offers`: gửi offer.
- `onboarding`: quản lý onboarding.
- `exports`: xuất tài liệu.
- `billing`: ví, nạp tiền, gói dịch vụ, featured listing.
- `audit_logs`: xem audit log công ty.

## 6. Database Và Các Entity Chính

Trung tâm dữ liệu là bảng `nguoi_dungs`.

Các nhóm entity chính:

- Tài khoản/phân quyền: `nguoi_dungs`, `personal_access_tokens`, `permission_definitions`, `cong_ty_nguoi_dungs`.
- Hồ sơ ứng viên: `ho_sos`, `ho_so_parsings`, `nguoi_dung_ky_nangs`, `ky_nangs`.
- Danh mục: `nganh_nghes`, `ky_nangs`, `cv_templates`.
- Công ty/tuyển dụng: `cong_tys`, `tin_tuyen_dungs`, `tin_tuyen_dung_ky_nangs`, `luu_tins`, `theo_doi_cong_tys`.
- Ứng tuyển: `ung_tuyens`, `interview_rounds`, `onboarding_plans`, `onboarding_tasks`.
- AI: `ket_qua_matchings`, `tu_van_nghe_nghieps`, `ai_chat_sessions`, `ai_chat_messages`, `ai_interview_reports`, `ai_usage_logs`.
- Billing: `vi_nguoi_dungs`, `bien_dong_vi`, `giao_dich_thanh_toans`, `goi_dich_vus`, `goi_dich_vu_tinh_nangs`, `nguoi_dung_goi_dich_vus`, `bang_gia_tinh_nang_ai`, `su_dung_tinh_nang_ais`.
- Vận hành: `app_notifications`, `audit_logs`, queue/cache/session tables của Laravel.

Quan hệ nghiệp vụ quan trọng:

- Một ứng viên có nhiều hồ sơ, kỹ năng cá nhân, đơn ứng tuyển, kết quả matching và báo cáo nghề nghiệp.
- Một công ty có nhiều HR, nhiều tin tuyển dụng, nhiều ứng tuyển.
- Một tin tuyển dụng có nhiều kỹ năng yêu cầu và nhiều ứng tuyển.
- Một đơn ứng tuyển có nhiều vòng phỏng vấn, có thể có offer và onboarding plan.
- Một lần dùng AI được backend ghi nhận vào `ai_usage_logs` và có thể bị kiểm soát bằng billing/entitlement.

## 7. Tính Năng Theo Nhóm Người Dùng

### 7.1. Khách Vãng Lai

Khách chưa đăng nhập có thể:

- Xem landing page.
- Tìm kiếm và lọc việc làm.
- Xem chi tiết việc làm.
- Xem danh sách/chi tiết công ty.
- Xem danh sách/chi tiết ngành nghề.
- Xem danh sách/chi tiết kỹ năng.
- Đăng ký ứng viên hoặc nhà tuyển dụng.
- Đăng nhập bằng email/mật khẩu hoặc Google.
- Quên/đặt lại mật khẩu.
- Xác thực email qua link đã ký.

Các file đại diện:

- `FE/src/components/Guest/*`
- `BE/app/Http/Controllers/Api/AuthController.php`
- `BE/app/Http/Controllers/Api/TinTuyenDungController.php`
- `BE/app/Http/Controllers/Api/CongTyController.php`
- `BE/app/Http/Controllers/Api/NganhNgheController.php`
- `BE/app/Http/Controllers/Api/KyNangController.php`

### 7.2. Ứng Viên

Ứng viên có thể:

- Cập nhật hồ sơ cá nhân và avatar.
- Quản lý nhiều hồ sơ/CV.
- Upload CV file và xem file CV.
- Parse CV bằng AI để trích xuất thông tin, phát hiện CV layout phức tạp/2 cột và cảnh báo kết quả cần xác nhận.
- Xem modal kết quả parse CV, chỉnh nhanh họ tên/email/số điện thoại trước khi áp dụng vào hồ sơ cá nhân.
- Tạo CV bằng CV Builder, chọn template, màu sắc, layout, ảnh CV, mục tiêu nghề nghiệp; phần kinh nghiệm hỗ trợ nhập theo năm hoặc tháng.
- Dùng AI Writing để viết summary, objective, kinh nghiệm, dự án/thành tựu, kỹ năng.
- Preview/in CV từ trình duyệt.
- Quản lý kỹ năng cá nhân, mức độ, kinh nghiệm, chứng chỉ.
- Tìm việc, lưu/bỏ lưu tin.
- Theo dõi/bỏ theo dõi công ty.
- Nhận realtime khi công ty đang theo dõi có job mới hoặc mở lại job.
- Sinh kết quả matching theo hồ sơ, có chuẩn hóa skill alias và điểm ngữ cảnh CV-JD từ raw text.
- Xem danh sách job phù hợp.
- Sinh báo cáo định hướng nghề nghiệp.
- Chatbot tư vấn nghề nghiệp.
- Mock interview: tạo phiên, trả lời câu hỏi, nhận đánh giá, bị trừ điểm nếu câu trả lời quá ngắn/chung chung/lạc đề, sinh báo cáo.
- Sinh cover letter khi ứng tuyển, kèm kiểm tra thư có nhắc kỹ năng chưa có trong CV/matching hay không.
- Nộp hồ sơ, cập nhật thư xin việc, rút đơn.
- Xác nhận lịch phỏng vấn qua UI hoặc email.
- Phản hồi offer qua UI hoặc email.
- Xem onboarding và cập nhật task được giao.
- Xem ví, lịch sử giao dịch, gói dịch vụ, mua gói, nạp tiền.
- Xem notification và đánh dấu đã đọc.
- Xuất tài liệu ứng tuyển theo quyền.

Các màn hình frontend chính:

- `/dashboard`
- `/profile`
- `/my-cv`
- `/cv-builder`
- `/my-skills`
- `/applications`
- `/saved-jobs`
- `/followed-companies`
- `/matched-jobs`
- `/career-report`
- `/ai-center/chatbot`
- `/ai-center/mock-interview`
- `/wallet`
- `/plans`
- `/payments`

### 7.3. Nhà Tuyển Dụng

Nhà tuyển dụng có thể:

- Tạo/cập nhật hồ sơ công ty.
- Quản lý thành viên HR nội bộ.
- Gán quyền chi tiết cho HR.
- Xem audit log công ty.
- Tạo, sửa, tắt/mở, xóa tin tuyển dụng; yêu cầu kinh nghiệm hỗ trợ nhập theo năm hoặc tháng để đồng bộ với CV ứng viên.
- Gán HR phụ trách tin tuyển dụng.
- Parse JD bằng AI, có cảnh báo JD quá ngắn/thiếu yêu cầu/thiếu lương/thiếu địa điểm/thiếu hình thức làm việc và gợi ý kỹ năng để HR xác nhận.
- Tài trợ/featured tin tuyển dụng bằng ví/billing.
- Xem hồ sơ công khai của ứng viên.
- Xem danh sách ứng tuyển theo công ty/job/status/HR.
- Duyệt trạng thái ứng tuyển.
- Quản lý nhiều vòng phỏng vấn.
- Gửi email lịch phỏng vấn và gửi lại email.
- Dùng Interview Copilot để sinh câu hỏi/rubric/red flags.
- Đánh giá sau phỏng vấn bằng AI.
- Dùng AI Shortlist để chấm điểm ứng viên theo JD, có giải thích điểm bằng Ollama local khi cấu hình provider cho phép, đồng thời vẫn giữ điểm số deterministic từ dữ liệu hệ thống.
- So sánh 2-5 ứng viên theo dạng ma trận, có điểm mạnh/yếu, kỹ năng khớp/thiếu, độ tin cậy và gợi ý câu hỏi phỏng vấn.
- Gửi offer cho ứng viên.
- Theo dõi ứng viên chấp nhận/từ chối offer.
- Quản lý onboarding plan và onboarding task.
- Xuất tài liệu: hồ sơ ứng tuyển, offer, báo cáo phỏng vấn, checklist onboarding.
- Quản lý ví, nạp tiền MoMo/VNPay, xem lịch sử thanh toán.

Các màn hình frontend chính:

- `/employer`
- `/employer/company`
- `/employer/hr-management`
- `/employer/jobs`
- `/employer/jobs/:id`
- `/employer/candidates`
- `/employer/interviews`
- `/employer/billing`
- `/employer/payments`
- `/employer/audit-logs`
- `/employer/profile`

### 7.4. Quản Trị Viên

Admin có thể:

- Xem dashboard tổng quan.
- Quản lý người dùng: tạo, sửa, khóa/mở khóa, xóa, thống kê.
- Super admin quản lý admin khác và phân quyền admin.
- Quản lý công ty.
- Quản lý hồ sơ ứng viên, bao gồm ẩn/công khai, xóa mềm, khôi phục, xóa vĩnh viễn.
- Quản lý ngành nghề, kỹ năng, kỹ năng người dùng.
- Quản lý tin tuyển dụng.
- Quản lý ứng tuyển.
- Quản lý lịch sử matching.
- Quản lý báo cáo/tư vấn nghề nghiệp.
- Quản lý CV template.
- Quản lý billing: giao dịch, gói dịch vụ, bảng giá tính năng AI, subscription, đối soát payment pending.
- Theo dõi AI usage: request, feature, success/error/fallback, latency.
- Xem audit log toàn hệ thống.
- Xem thống kê hệ thống.

Các màn hình frontend chính:

- `/admin`
- `/admin/users`
- `/admin/admins`
- `/admin/companies`
- `/admin/profiles`
- `/admin/user-skills`
- `/admin/matchings`
- `/admin/career-advising`
- `/admin/applications`
- `/admin/industries`
- `/admin/skills`
- `/admin/jobs`
- `/admin/cv-templates`
- `/admin/billing`
- `/admin/ai-usage`
- `/admin/audit-logs`
- `/admin/stats`

## 8. Các Tính Năng AI Và Công Nghệ Áp Dụng

Backend gọi AI service qua `App\Services\Ai\AiClientService`. Mỗi request AI có log thành công/lỗi/fallback qua `AiUsageLogger`.

### 8.1. Parse CV

- Endpoint AI: `POST /parse/cv`.
- Backend: `CvParsingController`, `AiClientService::parseCv`, `AiClientService::parseCvFromRawText`.
- Công nghệ: FastAPI, pdfplumber, parser nội bộ, Pydantic schema.
- Phiên bản hiện tại: `cv_parser_v2_layout_guarded`.
- Kết quả chính: raw text, tên, email, phone, skills, experience, education, confidence score.
- Nâng cấp mới:
  - phát hiện CV layout phức tạp/2 cột qua phân tích PDF page/word position,
  - trả `layout_analysis_json`, `quality_warnings_json`, `review_required`, `suggested_actions`,
  - cảnh báo CV scan/ảnh hoặc text trích xuất quá ngắn,
  - modal frontend cho ứng viên xem cảnh báo và chỉnh nhanh họ tên/email/số điện thoại trước khi áp dụng.
- Dùng cho: matching, career report, chatbot, mock interview, shortlist.
- Ý nghĩa demo: AI không tự động tin tuyệt đối vào kết quả parse, mà có cơ chế cảnh báo và bắt người dùng xác nhận các trường dễ sai.

### 8.2. Parse JD

- Endpoint AI: `POST /parse/jd`.
- Backend: `JdParsingController`, `AiClientService::parseJd`.
- Phiên bản hiện tại: `jd_parser_v2_quality_skills`.
- Kết quả chính: skills, requirements, benefits, salary, location, work mode.
- Nâng cấp mới:
  - tự gợi ý kỹ năng từ JD qua `suggested_skills_json`,
  - đánh dấu kỹ năng bắt buộc nếu JD có tín hiệu `yêu cầu`, `bắt buộc`, `required`, `must have`,
  - cảnh báo JD quá ngắn, thiếu requirements, thiếu benefits, thiếu salary, thiếu location, thiếu work mode, thiếu yêu cầu kinh nghiệm,
  - frontend job detail hiển thị nhóm cảnh báo chất lượng JD và kỹ năng AI gợi ý để HR xác nhận.
- Dùng cho: matching, AI shortlist, AI compare, interview copilot.
- Ý nghĩa demo: dữ liệu JD được kiểm tra chất lượng trước khi dùng cho AI, tránh matching sai do JD mơ hồ hoặc thiếu thông tin.

### 8.3. Matching CV-JD

- Endpoint AI: `POST /match/cv-jd`.
- Backend: `MatchingController`, `NhaTuyenDungShortlistController`.
- Phiên bản hiện tại: `matching_v4_salary_location_workmode`.
- Kết quả: điểm phù hợp, điểm kỹ năng, điểm kinh nghiệm, điểm học vấn, điểm lương, điểm địa điểm, điểm hình thức làm việc, skill khớp/thiếu, kỹ năng gần nghĩa, giải thích.
- Nâng cấp mới:
  - breakdown điểm trong `chi_tiet_diem`,
  - `score_explanation_items` giải thích từng tiêu chí,
  - chuẩn hóa kỹ năng bằng `AI/data/skill_aliases.json`, ví dụ `JS`/`JavaScript`, `reactjs`/`React`, `postgres`/`PostgreSQL`, `Firestore`/`Firebase`,
  - điểm ngữ cảnh CV-JD dùng `raw_text` từ `ho_so_parsings` và `tin_tuyen_dung_parsings` thay vì chỉ dựa vào danh sách skill,
  - `text_similarity_score` dùng hướng lai `BM25 + TF-IDF + lexical cosine/jaccard + skill alias context`, có metadata `text_similarity_method` trong `chi_tiet_diem`,
  - kinh nghiệm hỗ trợ cả năm và tháng, ví dụ `6 tháng`, `0.5`, `1 năm`, và UI hiển thị các giá trị nhỏ hơn 1 năm theo tháng,
  - scoring salary/location/work mode theo hướng trung lập nếu thiếu dữ liệu để tránh phạt sai,
  - trọng số thay đổi theo cấp bậc job: intern, fresher, junior, mid, senior, lead/manager,
  - phần giải thích dùng Ollama theo cấu hình `MATCH_EXPLANATION_PROVIDER=ollama`; Ollama chỉ diễn giải từ dữ liệu/điểm đã tính, không tự tính lại điểm.
- Dùng cho: Matched Jobs của ứng viên và shortlist của nhà tuyển dụng.
- Ý nghĩa demo: điểm matching không chỉ dựa vào keyword kỹ năng, mà xét thêm bối cảnh thực tế khi ứng tuyển.

### 8.4. Career Report

- Endpoint AI: `POST /generate/career-report`.
- Backend: `CareerReportController`.
- Kết quả: nghề đề xuất, mức độ phù hợp, skill gap, báo cáo chi tiết.
- Dùng cho: định hướng nghề nghiệp và ngữ cảnh chatbot.

### 8.5. Cover Letter AI

- Endpoint AI: `POST /generate/cover-letter`.
- Backend: `CoverLetterController`.
- Kết quả: thư xin việc cá nhân hóa theo CV, JD và matching.
- Nâng cấp mới:
  - kiểm tra kỹ năng được nhắc trong thư so với CV/matching/JD,
  - trả `skill_audit`, `quality_warnings`, `skill_audit_passed`,
  - cảnh báo nếu thư có nhắc kỹ năng thuộc JD nhưng chưa thấy trong CV/matching,
  - cảnh báo thư quá chung chung, thiếu dự án/số liệu/minh chứng.
- Dùng cho: bước ứng tuyển của ứng viên.
- Ý nghĩa demo: AI hỗ trợ viết thư nhưng hệ thống vẫn kiểm soát rủi ro bịa kỹ năng, phù hợp bối cảnh tuyển dụng.

### 8.6. CV Builder AI Writing

- Endpoint AI: `POST /generate/cv-builder-writing`.
- Backend: `CvBuilderWritingController`.
- Kết quả: gợi ý nội dung từng phần CV.
- Dùng cho: CV Builder.

### 8.7. AI Career Chatbot

- Endpoint AI: `POST /chat/career-consultant`, `POST /chat/career-consultant/stream`.
- Backend: `AiChatSessionController`, `AiChatMessageController`.
- Công nghệ: session lịch sử chat, intent engine, guardrail, template/model fallback, streaming response.
- Nâng cấp mới:
  - RAG nhẹ từ database hiện có, không cần vector database,
  - backend đưa vào context ngắn gồm hồ sơ ứng viên, kỹ năng, báo cáo nghề nghiệp, matching gần nhất, job liên quan, ngành nghề, skill catalog hints và job snippets,
  - chatbot dùng context này để gợi ý job, giải thích skill gap và trả lời theo hồ sơ cụ thể hơn.
- Dùng cho: hỏi đáp nghề nghiệp, CV, kỹ năng, job, lộ trình 30/60/90 ngày.
- Ý nghĩa demo: chatbot không chỉ trả lời chung chung, mà có dữ liệu thật từ hệ thống.

### 8.8. Mock Interview

- Endpoint AI:
  - `POST /interview/mock/question`
  - `POST /interview/mock/evaluate`
  - `POST /interview/mock/report`
- Backend: `MockInterviewController`.
- Kết quả: câu hỏi, đánh giá câu trả lời, follow-up, báo cáo tổng kết.
- Nâng cấp mới:
  - phát hiện câu trả lời quá ngắn, mỏng nội dung, chung chung, lạc đề, thiếu kỹ năng trọng tâm, thiếu minh chứng,
  - trả `answer_quality` và `score_penalties`,
  - trừ điểm theo từng tiêu chí: kỹ thuật, giao tiếp, phù hợp JD, rõ ý, cụ thể, cấu trúc,
  - điểm thấp sẽ kích hoạt câu hỏi follow-up yêu cầu trả lời cụ thể hơn theo bối cảnh/hành động/kết quả.
- Dùng cho: ứng viên luyện phỏng vấn.
- Ý nghĩa demo: AI đánh giá có rubric rõ ràng, không chỉ khen/chê chung chung.

### 8.9. Interview Copilot Cho Nhà Tuyển Dụng

- Endpoint AI:
  - `POST /interview/copilot/generate`
  - `POST /interview/copilot/evaluate`
- Backend: `NhaTuyenDungUngTuyenController`.
- Kết quả: tóm tắt ứng viên, focus area, câu hỏi, rubric, red flags, đánh giá sau phỏng vấn.
- Dùng cho: HR chuẩn bị và đánh giá phỏng vấn.

### 8.10. AI Usage Và Billing

- Backend ghi log qua `AiUsageLogger`.
- Admin xem dashboard qua `AdminAiUsageController`.
- Billing/entitlement nằm trong các service:
  - `FeatureAccessService`
  - `BillingEntitlementService`
  - `AiFeatureBillingService`
  - `WalletService`
  - `SubscriptionService`

Ý nghĩa khi demo: hệ thống không chỉ gọi AI, mà còn kiểm soát lượt dùng, ghi nhận chi phí/quota/fallback và cho admin giám sát.

### 8.11. AI Shortlist Và AI Compare

- Backend: `NhaTuyenDungShortlistController`, `AiClientService::matchCvJd`, `AiClientService::matchCvJdParallel`.
- Frontend: job detail của nhà tuyển dụng.
- Shortlist mặc định có rule-based scoring để không phụ thuộc hoàn toàn vào AI service.
- Khi bật AI explanation, hệ thống gọi AI matching cho top ứng viên, sau đó blend rule score và AI score.
- Các request chấm lại top ứng viên được gọi song song bằng HTTP pool để giảm thời gian chờ khi HR bấm chấm lại/so sánh.
- Dữ liệu gửi sang AI gồm `raw_text`, skill/experience/education đã parse của CV và `raw_text`, skill/requirements đã parse của JD; nhờ vậy điểm ngữ cảnh không bị 0 chỉ vì thiếu text trong payload.
- Skill trong shortlist/compare được chuẩn hóa bằng cùng catalog alias để tránh lệch do cách viết khác nhau giữa CV upload, CV tạo trong hệ thống và JD.
- Phần giải thích ngắn và giải thích chi tiết có thể do Ollama local viết lại từ payload điểm, kỹ năng khớp/thiếu, kinh nghiệm, học vấn, lương, địa điểm và hình thức làm việc; điểm số vẫn lấy từ matcher deterministic.
- Compare hỗ trợ chọn 2-5 hồ sơ, trả về `matrix` gồm:
  - điểm tổng,
  - nguồn CV,
  - độ tin cậy,
  - điểm mạnh nổi bật từ structured explanation nếu có,
  - điểm cần cải thiện từ structured explanation nếu có,
  - kỹ năng khớp,
  - kỹ năng/yêu cầu còn thiếu,
  - giải thích AI,
  - câu hỏi phỏng vấn gợi ý theo điểm yếu.
- Ý nghĩa demo: HR có bảng so sánh trực quan, không phải đọc từng CV rời rạc.

## 9. Luồng Hoạt Động Chính Của Hệ Thống

### 9.1. Luồng Đăng Ký Và Xác Thực

1. Người dùng chọn đăng ký ứng viên hoặc nhà tuyển dụng.
2. Frontend gửi request `/dang-ky`.
3. Backend tạo tài khoản `nguoi_dungs`, hash mật khẩu, gán `vai_tro`.
4. Nếu là nhà tuyển dụng, hệ thống có thể khởi tạo/nhận thông tin công ty.
5. Backend gửi email xác thực.
6. Người dùng xác thực email qua link đã ký.
7. Đăng nhập qua `/dang-nhap`, nhận Bearer token Sanctum.
8. Frontend lưu token và chuyển hướng theo vai trò.

### 9.2. Luồng Ứng Viên Tạo Hồ Sơ Và Dùng AI

1. Ứng viên đăng nhập vào dashboard.
2. Tạo hồ sơ/CV hoặc dùng CV Builder.
3. Upload CV hoặc nhập dữ liệu có cấu trúc; kinh nghiệm có thể nhập theo năm hoặc tháng.
4. Gọi parse CV bằng AI.
5. Backend gửi file/text sang AI service.
6. AI service trả dữ liệu đã trích xuất.
7. AI service kèm cảnh báo layout phức tạp, thiếu thông tin, độ tin cậy và các hành động cần kiểm tra.
8. Backend lưu kết quả parse, đồng bộ một phần dữ liệu chắc chắn như trình độ, kinh nghiệm, kỹ năng.
9. Ứng viên xem modal xác nhận, chỉnh nhanh họ tên/email/số điện thoại trước khi áp dụng vào tài khoản.
10. Ứng viên dùng dữ liệu đó để:
   - matching việc làm,
   - sinh career report,
   - hỏi chatbot,
   - luyện mock interview,
   - sinh cover letter khi ứng tuyển.

### 9.3. Luồng Tìm Việc Và Ứng Tuyển

1. Ứng viên tìm việc ở `/jobs`.
2. Xem chi tiết job.
3. Lưu tin hoặc theo dõi công ty nếu quan tâm.
4. Chọn hồ sơ để ứng tuyển.
5. Có thể sinh cover letter bằng AI.
6. AI kiểm tra thư xin việc có nhắc kỹ năng chưa có trong CV/matching hay không.
7. Ứng viên chỉnh lại thư nếu có cảnh báo.
8. Nộp hồ sơ.
9. Backend tạo `ung_tuyens`, tính/lưu trạng thái ban đầu, tạo timeline/audit/notification.
10. Ứng viên theo dõi đơn ở `/applications`.

### 9.4. Luồng Nhà Tuyển Dụng Đăng Tin Và Lọc Ứng Viên

1. Nhà tuyển dụng đăng nhập vào `/employer`.
2. Cập nhật thông tin công ty.
3. Tạo tin tuyển dụng.
4. Nhập yêu cầu kinh nghiệm theo năm hoặc tháng, sau đó parse JD bằng AI để chuẩn hóa yêu cầu.
5. Hệ thống hiển thị cảnh báo chất lượng JD và kỹ năng AI gợi ý để HR xác nhận.
6. Mở tin để public.
7. Khi có ứng viên nộp hồ sơ, HR xem danh sách ứng tuyển.
8. HR dùng AI Shortlist để chấm điểm ứng viên theo JD; top ứng viên được gọi AI song song để tối ưu tốc độ.
9. HR xem breakdown điểm theo kỹ năng, kinh nghiệm, học vấn, ngữ cảnh CV-JD, lương, địa điểm, hình thức làm việc và độ tin cậy.
10. HR có thể so sánh nhiều ứng viên bằng ma trận.
11. HR cập nhật trạng thái ứng tuyển hoặc chuyển sang phỏng vấn.

### 9.5. Luồng Phỏng Vấn, Offer Và Onboarding

1. HR tạo vòng phỏng vấn cho ứng viên.
2. Hệ thống gửi notification/email lịch phỏng vấn.
3. Ứng viên xác nhận tham gia qua UI hoặc email.
4. HR dùng Interview Copilot để sinh bộ câu hỏi, rubric và điểm cần khai thác.
5. Sau phỏng vấn, HR nhập ghi chú/scores và dùng AI đánh giá.
6. Nếu đạt, HR gửi offer.
7. Ứng viên chấp nhận/từ chối offer qua UI/email.
8. Nếu chấp nhận, hệ thống chuyển sang onboarding.
9. HR tạo task onboarding, ứng viên cập nhật tiến độ task.
10. Hai bên có thể xuất tài liệu liên quan.

### 9.6. Luồng Billing Và Gói Dịch Vụ

1. Người dùng mở ví/gói dịch vụ.
2. Xem số dư, lịch sử giao dịch, entitlement và bảng giá AI.
3. Nạp tiền qua MoMo/VNPay hoặc mua gói Pro.
4. Backend tạo `giao_dich_thanh_toans`, gọi payment gateway.
5. Người dùng quay lại từ trang thanh toán.
6. Backend xử lý return/IPN, cập nhật giao dịch, ví, subscription.
7. Khi dùng tính năng AI/featured listing, hệ thống kiểm tra quota/gói/ví.
8. Admin có thể xem, đối soát và quản lý billing.

### 9.7. Luồng Admin Vận Hành

1. Admin đăng nhập vào `/admin`.
2. Xem dashboard tổng quan.
3. Quản trị dữ liệu nền: ngành nghề, kỹ năng, CV template.
4. Quản trị nghiệp vụ: user, company, profile, job, application.
5. Theo dõi AI usage: request, lỗi, fallback, latency.
6. Theo dõi billing: giao dịch, gói, bảng giá, subscription.
7. Kiểm tra audit log để chứng minh hệ thống có truy vết thao tác.

## 10. Dữ Liệu Demo Có Sẵn Từ Seeder

Seeder đã tạo sẵn các tài khoản có thể dùng demo:

| Nhóm | Email | Mật khẩu | Ghi chú |
|---|---|---|---|
| Super Admin | `admin@kltn.com` | `Admin@123` | Quản trị cao nhất |
| Nhà tuyển dụng | `hr.techviet@demo.vn` | `NTD@123456` | TechViet Solutions |
| Nhà tuyển dụng | `hr.digigrowth@demo.vn` | `NTD@123456` | DigiGrowth Agency |
| Nhà tuyển dụng | `hr.northstar@demo.vn` | `NTD@123456` | NorthStar Analytics |
| Ứng viên | `ungvien.backend@demo.vn` | `UV@123456` | Backend Laravel persona |
| Ứng viên | `ungvien.frontend@demo.vn` | `UV@123456` | Frontend Vue/React persona |
| Ứng viên | `ungvien.data@demo.vn` | `UV@123456` | Data Analyst persona |

Công ty demo nổi bật:

- TechViet Solutions: công nghệ thông tin, có job Backend Developer Laravel, QA Engineer Manual/API, Frontend Developer Vue.js.
- DigiGrowth Agency: marketing/truyền thông, có job Digital Marketing, Content Intern, Designer, Account.
- NorthStar Analytics: data/analytics, có job Data Analyst, BI Developer, Data Engineer.

Nên dùng cặp demo chính:

- Ứng viên: `ungvien.backend@demo.vn`.
- Nhà tuyển dụng: `hr.techviet@demo.vn` với công ty TechViet Solutions.
- Admin: `admin@kltn.com`.

Lý do: TechViet có nhiều job IT, phù hợp để trình diễn CV, matching, AI shortlist, phỏng vấn và offer một cách logic.

## 11. Kịch Bản Demo Hoàn Hảo Cho Hội Đồng Bảo Vệ

### 11.1. Mục Tiêu Của Buổi Demo

Thông điệp nên nhấn mạnh:

> Đây không chỉ là website đăng tin tuyển dụng. Hệ thống mô phỏng một quy trình tuyển dụng hoàn chỉnh, có AI hỗ trợ cả ứng viên và nhà tuyển dụng, có phân quyền, realtime, thanh toán, audit log và quản trị vận hành.

Demo nên đi theo một câu chuyện xuyên suốt:

1. Khách tìm việc.
2. Ứng viên chuẩn bị hồ sơ bằng AI.
3. Ứng viên tìm việc và ứng tuyển.
4. Nhà tuyển dụng xử lý ứng viên bằng AI.
5. Hai bên đi qua phỏng vấn, offer, onboarding.
6. Admin chứng minh hệ thống có quản trị, giám sát và kiểm soát vận hành.

### 11.2. Chuẩn Bị Trước Khi Demo

Trước khi vào phòng bảo vệ, nên chuẩn bị:

- Backend Laravel đang chạy.
- Frontend Vue đang chạy.
- AI service FastAPI đang chạy.
- Queue worker/scheduler/reverb nếu muốn demo realtime đầy đủ.
- Database đã migrate và seed.
- Tài khoản `ungvien.backend@demo.vn`, `hr.techviet@demo.vn`, `admin@kltn.com` đăng nhập được.
- Có ít nhất một CV hoặc hồ sơ ứng viên đủ thông tin kỹ năng IT.
- Có job `Backend Developer Laravel` hoặc `Frontend Developer Vue.js` đang hoạt động.
- Trình duyệt chuẩn bị 3 profile/tab riêng:
  - Tab 1: ứng viên.
  - Tab 2: nhà tuyển dụng.
  - Tab 3: admin.

Các lệnh thường dùng khi chạy local:

```bash
# AI
AI/.venv/bin/python -m uvicorn app.main:app --app-dir AI --reload --reload-dir AI --host 127.0.0.1 --port 8001

# Backend
cd BE
php artisan serve
php artisan queue:work --tries=3 --timeout=90
php artisan reverb:start

# Frontend
cd FE
npm run dev
```

Nếu chỉ có ít thời gian, ưu tiên chạy backend, frontend và AI service. Queue/realtime có thể giải thích nếu môi trường demo không bật đầy đủ.

### 11.3. Phần 1 - Public Flow: Giới Thiệu Cổng Tìm Việc

Thời lượng đề xuất: 2-3 phút.

Các bước:

1. Mở trang landing `/`.
2. Vào `/jobs`.
3. Tìm từ khóa `Laravel` hoặc `Frontend`.
4. Mở chi tiết job `Backend Developer Laravel` của TechViet Solutions.
5. Chỉ nhanh các thông tin:
   - mô tả công việc,
   - yêu cầu,
   - công ty,
   - mức lương,
   - hạn tuyển,
   - nút lưu tin/ứng tuyển.
6. Mở `/companies`, xem TechViet Solutions.
7. Chỉ tính năng theo dõi công ty.

Lời dẫn gợi ý:

> Ở lớp public, hệ thống cho phép người dùng tra cứu việc làm, công ty, ngành nghề và kỹ năng. Các dữ liệu này là đầu vào cho các luồng sau: ứng viên lưu tin/theo dõi công ty, còn nhà tuyển dụng nhận hồ sơ ứng tuyển.

### 11.4. Phần 2 - Ứng Viên: Hồ Sơ, CV Builder Và AI

Thời lượng đề xuất: 7-9 phút.

Tài khoản: `ungvien.backend@demo.vn` / `UV@123456`.

Các bước:

1. Đăng nhập ứng viên.
2. Vào `/dashboard` để giới thiệu dashboard.
3. Vào `/profile`, cập nhật nhanh hồ sơ cá nhân nếu cần.
4. Vào `/my-cv`, giới thiệu danh sách hồ sơ/CV.
5. Mở hoặc tạo một hồ sơ.
6. Nếu có file CV: demo upload và parse CV bằng AI.
7. Trong modal kết quả parse, chỉ các phần mới:
   - cảnh báo chất lượng parse,
   - phát hiện layout phức tạp/2 cột nếu có,
   - danh sách kỹ năng/học vấn/kinh nghiệm trích xuất,
   - ô chỉnh nhanh họ tên/email/số điện thoại trước khi áp dụng.
8. Nếu muốn đẹp hơn: vào `/cv-builder`.
9. Chọn template CV, nhập/chỉnh các phần chính:
   - thông tin cá nhân,
   - mục tiêu nghề nghiệp,
   - kinh nghiệm,
   - kỹ năng,
   - học vấn,
   - dự án.
10. Dùng AI Writing để sinh mô tả bản thân hoặc mô tả kinh nghiệm.
11. Mở preview/print CV.
12. Vào `/my-skills`, chỉ kỹ năng cá nhân và chứng chỉ.

Điểm cần nói:

- CV không chỉ lưu file, mà còn có dữ liệu có cấu trúc.
- Parse CV giúp các tính năng matching và tư vấn chính xác hơn.
- Hệ thống không áp dụng mù kết quả AI: nếu CV ngắn, thiếu trường hoặc layout phức tạp, UI bắt người dùng kiểm tra lại.
- AI Writing hỗ trợ ứng viên trình bày CV chuyên nghiệp hơn.

Lời dẫn gợi ý:

> Phần này thể hiện giá trị cho ứng viên: hệ thống giúp họ tạo một hồ sơ hoàn chỉnh, sau đó biến hồ sơ thành dữ liệu có thể phân tích. Đây là nền tảng để AI matching, career report, chatbot và mock interview hoạt động.

### 11.5. Phần 3 - Ứng Viên: Matching, Career Report, Chatbot Và Mock Interview

Thời lượng đề xuất: 8-10 phút.

Các bước:

1. Vào `/matched-jobs`.
2. Chọn hồ sơ và sinh hoặc xem kết quả matching.
3. Mở một job phù hợp, giải thích:
   - điểm phù hợp,
   - kỹ năng khớp,
   - kỹ năng thiếu,
   - điểm kinh nghiệm/học vấn,
   - điểm ngữ cảnh CV-JD dựa trên raw text, BM25/TF-IDF và skill alias,
   - điểm lương/địa điểm/hình thức làm việc nếu dữ liệu có,
   - lý do gợi ý.
4. Vào `/career-report`.
5. Sinh hoặc xem báo cáo nghề nghiệp:
   - vai trò đề xuất,
   - mức độ phù hợp,
   - kỹ năng cần bổ sung,
   - hướng phát triển.
6. Vào `/ai-center/chatbot`.
7. Hỏi một câu có tính demo:
   - `Với hồ sơ hiện tại, em nên ứng tuyển Backend Laravel hay Frontend Vue.js?`
   - `Hãy gợi ý lộ trình 30/60/90 ngày để em đạt vị trí Backend Developer Laravel.`
   - `Job nào trong hệ thống hiện tại gần nhất với hồ sơ của em?`
8. Vào `/ai-center/mock-interview`.
9. Tạo phiên phỏng vấn cho vị trí Backend Developer Laravel.
10. Trả lời 1 câu quá ngắn hoặc chung chung, ví dụ: `Em sẽ cố gắng học hỏi thêm`.
11. Cho hội đồng xem AI trừ điểm theo từng tiêu chí và cảnh báo câu trả lời quá ngắn/chung chung/lạc đề.
12. Trả lời lại bằng câu có bối cảnh, hành động, kết quả để so sánh điểm.
13. Sinh báo cáo phiên phỏng vấn.

Điểm cần nói:

- Matching có giải thích, không chỉ hiển thị điểm; điểm ngữ cảnh dùng raw text đã chuẩn hóa và skill alias nên ít phụ thuộc vào việc CV/JD viết giống hệt từng từ.
- Career report giúp ứng viên định hướng dài hạn.
- Chatbot dùng ngữ cảnh hồ sơ/báo cáo/job và RAG nhẹ từ dữ liệu hiện có trong database.
- Mock interview giúp ứng viên luyện tập trước khi phỏng vấn thật, có rubric và cơ chế phạt câu trả lời yếu rõ ràng.

Lời dẫn gợi ý:

> Nhóm AI cho ứng viên bao phủ trước, trong và sau quá trình tìm việc: phân tích hồ sơ, gợi ý việc làm, định hướng nghề nghiệp, hỏi đáp cá nhân hóa và luyện phỏng vấn.

### 11.6. Phần 4 - Ứng Viên: Ứng Tuyển Và Theo Dõi Pipeline

Thời lượng đề xuất: 4-5 phút.

Các bước:

1. Quay lại chi tiết job `Backend Developer Laravel`.
2. Chọn hồ sơ vừa chuẩn bị.
3. Bấm sinh cover letter bằng AI.
4. Chỉ phần cảnh báo chất lượng nếu thư có nhắc kỹ năng chưa có trong CV/matching hoặc còn quá chung chung.
5. Chỉnh nhẹ nội dung nếu cần.
6. Nộp hồ sơ.
7. Vào `/applications`.
8. Chỉ trạng thái đơn, timeline, thư xin việc, nút rút đơn nếu có.

Điểm cần nói:

- Cover letter được cá nhân hóa theo CV và JD.
- Hệ thống kiểm tra thư xin việc để hạn chế AI bịa kỹ năng hoặc viết quá chung chung.
- Ứng viên có thể theo dõi toàn bộ tiến trình tuyển dụng.
- Timeline giúp minh bạch trạng thái: nộp hồ sơ, phỏng vấn, offer, onboarding.

### 11.7. Phần 5 - Nhà Tuyển Dụng: Công Ty, HR, Tin Tuyển Dụng

Thời lượng đề xuất: 6-8 phút.

Tài khoản: `hr.techviet@demo.vn` / `NTD@123456`.

Các bước:

1. Đăng nhập nhà tuyển dụng ở tab khác.
2. Vào `/employer`.
3. Giới thiệu dashboard nhà tuyển dụng.
4. Vào `/employer/company`, xem/cập nhật hồ sơ TechViet Solutions.
5. Vào `/employer/hr-management`.
6. Chỉ danh sách HR, vai trò owner/member, quyền chi tiết.
7. Vào `/employer/jobs`.
8. Mở job `Backend Developer Laravel`.
9. Demo parse JD bằng AI.
10. Chỉ phần cảnh báo chất lượng JD:
    - JD quá ngắn hoặc thiếu yêu cầu rõ ràng,
    - thiếu lương/địa điểm/hình thức làm việc,
    - thiếu kỹ năng bắt buộc,
    - kỹ năng AI gợi ý để HR xác nhận.
11. Chỉ chức năng bật/tắt job, gán HR phụ trách, sponsor/featured listing nếu có.

Điểm cần nói:

- Employer flow có phân quyền nội bộ, không phải mọi HR đều có toàn quyền.
- JD parsing giúp chuẩn hóa yêu cầu tuyển dụng để AI shortlist, AI compare, cover letter và interview copilot dùng được.
- Quality warning giúp HR cải thiện dữ liệu đầu vào, vì AI chỉ chính xác khi JD đủ rõ.

### 11.8. Phần 6 - Nhà Tuyển Dụng: AI Shortlist, So Sánh Ứng Viên, Phỏng Vấn

Thời lượng đề xuất: 10-12 phút.

Các bước:

1. Từ job detail, mở danh sách ứng viên/shortlist.
2. Chạy hoặc xem AI Shortlist.
3. Giải thích điểm phù hợp của ứng viên vừa nộp:
   - kỹ năng khớp với Laravel/PHP/API/database,
   - kinh nghiệm phù hợp,
   - điểm tương đồng ngữ cảnh CV-JD từ raw text đã parse,
   - lương/địa điểm/hình thức làm việc,
   - độ tin cậy của dữ liệu CV/JD,
   - kỹ năng còn thiếu.
4. Chọn 2-3 ứng viên và dùng chức năng compare.
5. Trong bảng compare, chỉ các cột:
   - điểm tổng,
   - nguồn CV,
   - độ tin cậy,
   - điểm mạnh nổi bật,
   - điểm cần cải thiện,
   - kỹ năng khớp/thiếu,
   - giải thích AI,
   - câu hỏi phỏng vấn gợi ý.
6. Vào `/employer/candidates` hoặc `/employer/interviews`.
7. Mở đơn ứng tuyển của ứng viên.
8. Cập nhật trạng thái sang phỏng vấn.
9. Tạo vòng phỏng vấn:
   - vòng kỹ thuật,
   - thời gian,
   - hình thức online/offline,
   - interviewer,
   - link phỏng vấn.
10. Dùng Interview Copilot generate:
   - câu hỏi,
   - rubric,
   - focus area,
   - red flags.
11. Nhập ghi chú phỏng vấn mẫu.
12. Dùng Interview Copilot evaluate để nhận đánh giá sau phỏng vấn.

Điểm cần nói:

- AI cho nhà tuyển dụng giúp giảm thời gian lọc hồ sơ.
- Shortlist và compare có giải thích, giúp HR ra quyết định minh bạch hơn.
- Matching/compare không chỉ dựa vào kỹ năng, mà bổ sung ngữ cảnh CV-JD, kinh nghiệm theo tháng/năm, lương, địa điểm, hình thức làm việc và độ tin cậy dữ liệu.
- Ollama local chỉ viết phần giải thích từ dữ liệu đã tính; không thay thế công thức chấm điểm để tránh kết quả khó kiểm soát.
- Interview Copilot hỗ trợ cả trước và sau phỏng vấn.
- Nhiều vòng phỏng vấn được quản lý bằng bảng riêng `interview_rounds`.

Lời dẫn gợi ý:

> Ở phía nhà tuyển dụng, AI không thay HR quyết định, mà đóng vai trò trợ lý phân tích: gợi ý ứng viên tiềm năng, chỉ ra điểm mạnh/yếu, tạo bộ câu hỏi và hỗ trợ đánh giá có cấu trúc.

### 11.9. Phần 7 - Offer, Ứng Viên Phản Hồi Và Onboarding

Thời lượng đề xuất: 6-8 phút.

Các bước phía nhà tuyển dụng:

1. Trong chi tiết ứng tuyển, chuyển ứng viên sang trạng thái đạt/chờ offer nếu cần.
2. Gửi offer:
   - vị trí,
   - mức lương,
   - ngày bắt đầu,
   - hạn phản hồi,
   - ghi chú.
3. Chỉ notification/email offer.

Các bước phía ứng viên:

4. Quay lại tab ứng viên `/applications`.
5. Mở đơn ứng tuyển.
6. Xem offer.
7. Chấp nhận offer.

Các bước phía nhà tuyển dụng:

8. Quay lại tab employer.
9. Xem trạng thái offer đã được chấp nhận.
10. Mở onboarding.
11. Tạo hoặc xem checklist onboarding:
    - bổ sung giấy tờ,
    - nhận thiết bị,
    - ký hợp đồng,
    - tham gia buổi orientation.
12. Xuất tài liệu nếu muốn demo PDF/export.

Điểm cần nói:

- Hệ thống đi đến cuối vòng đời tuyển dụng, không dừng ở nộp CV.
- Offer có phản hồi qua UI/email.
- Onboarding giúp chuyển từ tuyển dụng sang nhận việc.
- Export tài liệu phục vụ lưu trữ/hành chính.

### 11.10. Phần 8 - Billing: Ví, Gói Pro, Thanh Toán

Thời lượng đề xuất: 4-6 phút.

Nên demo ngắn, vì thanh toán sandbox có thể phụ thuộc môi trường.

Các bước:

1. Ở ứng viên hoặc nhà tuyển dụng, vào ví/billing.
2. Chỉ số dư, lịch sử biến động ví, lịch sử payment.
3. Mở gói dịch vụ.
4. Chỉ quota/entitlement.
5. Chỉ tùy chọn mua gói/nạp tiền MoMo hoặc VNPay.
6. Nếu sandbox ổn, tạo giao dịch nhỏ.
7. Nếu không muốn mạo hiểm, giải thích return/IPN và mở admin billing để chứng minh dữ liệu giao dịch.

Điểm cần nói:

- Billing áp dụng cho AI feature và featured listing.
- Hệ thống có ví, giao dịch, subscription, bảng giá tính năng AI và đối soát.
- Có service riêng cho MoMo/VNPay nên dễ mở rộng payment gateway.

### 11.11. Phần 9 - Admin: Quản Trị Và Giám Sát Hệ Thống

Thời lượng đề xuất: 7-9 phút.

Tài khoản: `admin@kltn.com` / `Admin@123`.

Các bước:

1. Đăng nhập admin.
2. Vào `/admin`, giới thiệu dashboard.
3. Vào `/admin/users`, lọc user theo vai trò, chỉ khóa/mở khóa.
4. Vào `/admin/admins`, chỉ super admin quản lý admin và phân quyền.
5. Vào `/admin/companies`, xem TechViet Solutions.
6. Vào `/admin/jobs`, xem tin tuyển dụng.
7. Vào `/admin/applications`, xem đơn ứng tuyển vừa demo.
8. Vào `/admin/ai-usage`, chỉ:
   - số request AI,
   - feature được dùng,
   - tỉ lệ thành công/lỗi/fallback,
   - latency/log chi tiết.
9. Vào `/admin/billing`, chỉ giao dịch/gói/bảng giá.
10. Vào `/admin/audit-logs`, chỉ thao tác hệ thống đã được ghi log.

Điểm cần nói:

- Admin có phân quyền chi tiết, không chỉ một tài khoản toàn quyền.
- AI usage dashboard chứng minh hệ thống có giám sát vận hành AI.
- Audit log chứng minh hệ thống có truy vết thao tác.
- Billing dashboard chứng minh mô hình thương mại/quota có thể vận hành.

### 11.12. Thứ Tự Demo Rút Gọn Nếu Chỉ Có 15 Phút

Nếu hội đồng chỉ cho thời gian ngắn, dùng bản rút gọn:

1. Public tìm job `Backend Developer Laravel`.
2. Ứng viên đăng nhập, mở CV/parse CV, chỉ modal cảnh báo và xác nhận dữ liệu AI.
3. Ứng viên xem matching có breakdown kỹ năng/kinh nghiệm/lương/địa điểm/hình thức.
4. Ứng viên hỏi chatbot hoặc xem career report.
5. Ứng viên sinh cover letter, chỉ audit kỹ năng rồi ứng tuyển.
6. Nhà tuyển dụng mở job, parse JD và chỉ quality warning/skill suggestion.
7. Nhà tuyển dụng xem AI Shortlist/compare matrix.
8. Nhà tuyển dụng tạo phỏng vấn, dùng Interview Copilot.
9. Nhà tuyển dụng gửi offer, ứng viên chấp nhận.
10. Admin mở AI Usage và Audit Log.

Đây là đường demo ngắn nhưng vẫn bao phủ được điểm mạnh nhất: AI hai phía, quy trình tuyển dụng end-to-end và quản trị hệ thống.

### 11.13. Kịch Bản Demo 10 Phút Và Tài Khoản Mẫu

Nếu thời gian cực ngắn, nên demo theo một câu chuyện duy nhất: ứng viên backend ứng tuyển vào TechViet, HR dùng AI để lọc và admin giám sát vận hành.

Tài khoản mẫu nên chuẩn bị sẵn trong 3 tab/profile trình duyệt:

| Vai trò | Email | Mật khẩu | Mục đích |
|---|---|---|---|
| Ứng viên | `ungvien.backend@demo.vn` | `UV@123456` | CV, matching, cover letter, ứng tuyển |
| Nhà tuyển dụng | `hr.techviet@demo.vn` | `NTD@123456` | JD, shortlist, interview, offer |
| Admin | `admin@kltn.com` | `Admin@123` | AI usage, billing, audit log |

Thứ tự thao tác 10 phút:

1. Mở `/jobs`, tìm `Backend Developer Laravel`, chỉ nhanh job và công ty TechViet Solutions.
2. Sang tab ứng viên, mở `/my-cv`, chỉ hồ sơ/CV đã parse và các cảnh báo chất lượng dữ liệu nếu có.
3. Mở `/matched-jobs`, chỉ điểm matching, kỹ năng khớp/thiếu và breakdown điểm.
4. Mở job demo, sinh cover letter bằng AI, chỉ phần kiểm tra kỹ năng trong thư rồi nộp hồ sơ.
5. Sang tab nhà tuyển dụng, mở job `Backend Developer Laravel`, chỉ JD parsing/quality warning.
6. Mở AI Shortlist hoặc compare, chỉ điểm ứng viên vừa nộp và giải thích AI.
7. Tạo hoặc mở vòng phỏng vấn, bấm Interview Copilot để sinh câu hỏi/rubric.
8. Gửi offer hoặc mở offer có sẵn, quay lại tab ứng viên chấp nhận offer nếu kịp.
9. Sang tab admin, mở `/admin/ai-usage` để chứng minh request AI được ghi log.
10. Mở `/admin/audit-logs` hoặc `/admin/billing` để kết thúc bằng phần vận hành/quản trị.

Thông điệp chốt trong 10 phút:

> Hệ thống bao phủ luồng tuyển dụng end-to-end, AI hỗ trợ cả ứng viên và HR, còn admin có công cụ giám sát usage, billing và audit log.

## 12. Luồng Test Tuần Tự End-To-End Đầy Đủ Nhất

Mục này dùng để test một luồng logic nhất cho toàn hệ thống, khác với kịch bản demo ở trên. Demo ưu tiên trình bày nhanh; luồng test này ưu tiên kiểm tra liên kết giữa các tính năng chủ chốt.

### 12.1. Mục Tiêu Test

Kiểm tra một vòng đời tuyển dụng hoàn chỉnh:

```text
Khách xem job
→ ứng viên chuẩn bị CV/hồ sơ bằng AI
→ hệ thống matching và tư vấn nghề nghiệp
→ ứng viên ứng tuyển bằng cover letter AI
→ nhà tuyển dụng parse JD, shortlist, compare ứng viên
→ nhà tuyển dụng tạo phỏng vấn và dùng Interview Copilot
→ ứng viên xác nhận phỏng vấn
→ nhà tuyển dụng gửi offer
→ ứng viên chấp nhận offer
→ hệ thống tạo onboarding
→ billing/AI usage/audit/admin ghi nhận đầy đủ
```

Tài khoản test khuyến nghị:

| Vai trò | Email | Mật khẩu | Ghi chú |
|---|---|---|---|
| Ứng viên | `ungvien.backend@demo.vn` | `UV@123456` | Hồ sơ phù hợp job Backend Laravel |
| Nhà tuyển dụng | `hr.techviet@demo.vn` | `NTD@123456` | Công ty TechViet Solutions |
| Admin | `admin@kltn.com` | `Admin@123` | Super Admin |

Dữ liệu test chính:

- Công ty: TechViet Solutions.
- Job: `Backend Developer Laravel`.
- Hồ sơ ứng viên: có kỹ năng Laravel/PHP/API/SQL/Git, kinh nghiệm backend, mục tiêu Backend Developer.

### 12.2. Điều Kiện Trước Khi Test

1. Backend chạy tại `http://127.0.0.1:8000`.
2. Frontend chạy tại `http://localhost:5173`.
3. AI service chạy tại `http://127.0.0.1:8001` và `/health` trả `success: true`.
4. Database đã `migrate` và `seed`.
5. Queue worker và Reverb đang chạy nếu muốn kiểm tra realtime/email/notification đầy đủ.
6. Ba tab hoặc ba browser profile đã sẵn sàng để tránh lẫn token giữa ứng viên, nhà tuyển dụng và admin.

### 12.3. Luồng Test Chi Tiết

#### Bước 1 - Public Job Board

1. Mở `/`.
2. Vào `/jobs`.
3. Tìm `Laravel`.
4. Mở job `Backend Developer Laravel`.
5. Mở trang công ty TechViet Solutions.
6. Nếu đang đăng nhập ứng viên, thử lưu job và theo dõi công ty.

Kết quả mong đợi:

- Danh sách job hiển thị đúng.
- Bộ lọc/tìm kiếm trả job liên quan.
- Chi tiết job có mô tả, yêu cầu, kỹ năng, công ty, trạng thái, nút lưu/ứng tuyển.
- Công ty hiển thị danh sách job đang hoạt động.
- Lưu job/theo dõi công ty thành công và có trạng thái UI tương ứng.

#### Bước 2 - Ứng Viên Đăng Nhập Và Chuẩn Bị Hồ Sơ

1. Đăng nhập `ungvien.backend@demo.vn`.
2. Vào `/dashboard`, kiểm tra dashboard ứng viên.
3. Vào `/profile`, kiểm tra thông tin cá nhân.
4. Vào `/my-cv`.
5. Mở hồ sơ demo hoặc tạo hồ sơ mới.
6. Upload CV nếu cần.
7. Chạy parse CV bằng AI.
8. Kiểm tra modal kết quả parse.
9. Áp dụng dữ liệu parse vào hồ sơ.
10. Vào `/cv-builder`, chỉnh CV, template, màu, kinh nghiệm, học vấn, kỹ năng.
11. Dùng AI Writing để sinh mô tả bản thân hoặc kinh nghiệm.
12. Mở preview/print CV.
13. Vào `/my-skills`, kiểm tra kỹ năng cá nhân/chứng chỉ.

Kết quả mong đợi:

- Đăng nhập thành công và điều hướng đúng role ứng viên.
- Hồ sơ/CV lưu được dữ liệu có cấu trúc.
- Parse CV trả tên/email/phone/skills/experience/education và cảnh báo chất lượng nếu có.
- Người dùng có thể xác nhận hoặc chỉnh nhanh dữ liệu parse trước khi áp dụng.
- CV Builder lưu và preview được.
- AI Writing trả gợi ý hợp ngữ cảnh hồ sơ.
- Kỹ năng cá nhân hiển thị và cập nhật được.

#### Bước 3 - Matching, Career Report, Chatbot Và Mock Interview

1. Vào `/matched-jobs`.
2. Chọn hồ sơ backend demo.
3. Sinh hoặc xem matching với job `Backend Developer Laravel`.
4. Kiểm tra breakdown điểm.
5. Vào `/career-report`, sinh hoặc xem báo cáo nghề nghiệp.
6. Vào `/ai-center/chatbot`.
7. Hỏi: `Với hồ sơ hiện tại, em nên ứng tuyển Backend Laravel hay Frontend Vue.js?`
8. Hỏi tiếp: `Hãy gợi ý lộ trình 30/60/90 ngày để em đạt vị trí Backend Developer Laravel.`
9. Vào `/ai-center/mock-interview`.
10. Tạo phiên mock interview cho vị trí Backend Laravel.
11. Trả lời ngắn: `Em sẽ cố gắng học hỏi thêm`.
12. Kiểm tra AI đánh giá/trừ điểm.
13. Trả lời lại một câu có bối cảnh, hành động, kết quả.
14. Sinh báo cáo mock interview.

Kết quả mong đợi:

- Matching có điểm tổng và điểm thành phần: kỹ năng, kinh nghiệm, học vấn, ngữ cảnh CV-JD, lương, địa điểm, hình thức làm việc.
- Có kỹ năng khớp, kỹ năng thiếu và giải thích điểm.
- Career report có vai trò đề xuất, skill gap và hướng phát triển.
- Chatbot trả lời dựa trên hồ sơ/job/matching hiện có, không trả lời quá chung chung.
- Mock interview tạo câu hỏi, đánh giá câu trả lời, phát hiện câu quá ngắn/chung chung và sinh báo cáo.
- AI usage được ghi nhận để admin xem ở bước cuối.

#### Bước 4 - Ứng Viên Ứng Tuyển Bằng Cover Letter AI

1. Quay lại job `Backend Developer Laravel`.
2. Chọn hồ sơ backend demo.
3. Bấm sinh cover letter bằng AI.
4. Kiểm tra `skill_audit` và cảnh báo chất lượng thư.
5. Chỉnh thư nếu cần.
6. Nộp hồ sơ.
7. Vào `/applications`.
8. Mở đơn vừa nộp.
9. Kiểm tra timeline, trạng thái, thư xin việc, matching nếu có.

Kết quả mong đợi:

- Cover letter được cá nhân hóa theo CV/JD.
- Hệ thống cảnh báo nếu thư nhắc kỹ năng chưa có trong CV/matching hoặc nội dung quá chung chung.
- Ứng tuyển tạo bản ghi mới, không bị trùng nếu đã nộp trước đó.
- Timeline ứng tuyển hiển thị bước nộp hồ sơ.
- Ứng viên nhận notification nếu hệ thống có cấu hình realtime/queue.

#### Bước 5 - Nhà Tuyển Dụng Kiểm Tra Công Ty, HR Và JD

1. Đăng nhập `hr.techviet@demo.vn` ở tab khác.
2. Vào `/employer`.
3. Vào `/employer/company`, kiểm tra hồ sơ công ty.
4. Vào `/employer/hr-management`, kiểm tra HR owner/member và quyền nội bộ.
5. Vào `/employer/jobs`.
6. Mở job `Backend Developer Laravel`.
7. Chạy parse JD bằng AI.
8. Kiểm tra kỹ năng gợi ý, kỹ năng bắt buộc và cảnh báo chất lượng JD.
9. Kiểm tra gán HR phụ trách, bật/tắt job hoặc featured listing nếu cần.

Kết quả mong đợi:

- Nhà tuyển dụng vào đúng layout employer.
- Công ty và quyền HR hiển thị đúng.
- JD parse trả skills/requirements/benefits/salary/location/work mode nếu có.
- Quality warning xuất hiện khi JD thiếu dữ liệu quan trọng.
- Các thao tác job tuân thủ quyền nội bộ công ty.

#### Bước 6 - AI Shortlist, Compare Và Cập Nhật Pipeline

1. Trong job detail, mở danh sách ứng viên/ứng tuyển.
2. Kiểm tra đơn của `ungvien.backend@demo.vn`.
3. Chạy AI Shortlist hoặc xem kết quả shortlist.
4. Chọn 2-3 ứng viên để compare.
5. Kiểm tra compare matrix.
6. Cập nhật đơn ứng tuyển sang bước phỏng vấn.

Kết quả mong đợi:

- Shortlist có điểm ứng viên, kỹ năng khớp/thiếu, độ tin cậy và giải thích.
- Compare matrix có điểm tổng, nguồn CV, điểm mạnh/yếu, kỹ năng khớp/thiếu và câu hỏi gợi ý.
- Trạng thái ứng tuyển cập nhật thành công.
- Timeline/audit/notification có bản ghi tương ứng.

#### Bước 7 - Phỏng Vấn Và Interview Copilot

1. Vào `/employer/interviews` hoặc chi tiết ứng tuyển.  
2. Tạo vòng phỏng vấn kỹ thuật:
   - tên vòng,
   - thời gian,
   - hình thức,
   - interviewer,
   - link/địa điểm.
3. Dùng Interview Copilot generate câu hỏi/rubric/focus area/red flags.
4. Sang tab ứng viên `/applications`.
5. Mở đơn ứng tuyển.
6. Xác nhận tham gia phỏng vấn.
7. Quay lại tab employer.
8. Nhập ghi chú phỏng vấn mẫu.
9. Dùng Interview Copilot evaluate.

Kết quả mong đợi:

- Vòng phỏng vấn được tạo và hiển thị trong timeline.
- Ứng viên thấy lịch phỏng vấn và xác nhận được.
- Copilot sinh câu hỏi/rubric phù hợp JD và CV.
- Copilot evaluate trả nhận xét, điểm, rủi ro và đề xuất quyết định.
- Notification/email action hoạt động nếu queue/mail được cấu hình.

#### Bước 8 - Offer, Ứng Viên Phản Hồi Và Onboarding

1. Ở tab employer, gửi offer cho ứng viên:
   - vị trí,
   - lương,
   - ngày bắt đầu,
   - hạn phản hồi,
   - ghi chú.
2. Sang tab ứng viên `/applications`.
3. Mở offer.
4. Chấp nhận offer.
5. Quay lại tab employer.
6. Kiểm tra trạng thái offer đã chấp nhận.
7. Mở onboarding.
8. Tạo hoặc xem checklist onboarding.
9. Cập nhật task onboarding từ phía ứng viên nếu task được giao cho ứng viên.
10. Export tài liệu: hồ sơ ứng tuyển, offer, phỏng vấn hoặc onboarding.

Kết quả mong đợi:

- Offer lưu đúng trạng thái và hạn phản hồi.
- Ứng viên chấp nhận/từ chối offer được.
- Khi chấp nhận, onboarding plan/checklist được tạo.
- Task onboarding cập nhật đúng quyền.
- Export PDF/tài liệu tải được.
- Timeline hiển thị offer và onboarding.

#### Bước 9 - Billing, Ví Và Gói Dịch Vụ

1. Ở tab ứng viên, vào `/wallet`.
2. Kiểm tra số dư, biến động ví, payment history.
3. Vào `/plans`.
4. Kiểm tra gói hiện tại và quota/entitlement.
5. Thử tạo giao dịch nạp tiền hoặc mua gói bằng ví/sandbox nếu môi trường ổn.
6. Ở tab employer, vào `/employer/billing`.
7. Kiểm tra ví employer, lịch sử thanh toán, AI pricing/entitlement.
8. Nếu có featured listing hoặc AI shortlist paid feature, kiểm tra ví bị trừ/ghi log đúng.

Kết quả mong đợi:

- Ví và lịch sử giao dịch hiển thị đúng theo user/company.
- Entitlement/quota cập nhật đúng.
- Giao dịch MoMo/VNPay sandbox tạo được hoặc có trạng thái pending hợp lệ.
- Payment return/IPN không tạo trùng biến động ví.
- AI feature/billing feature ghi nhận usage và giao dịch nếu cấu hình tính phí.

#### Bước 10 - Admin Kiểm Tra Quản Trị, AI Usage, Billing Và Audit

1. Đăng nhập `admin@kltn.com`.
2. Vào `/admin`.
3. Kiểm tra dashboard tổng quan.
4. Vào `/admin/users`, tìm ứng viên demo.
5. Vào `/admin/companies`, tìm TechViet Solutions.
6. Vào `/admin/jobs`, kiểm tra job demo.
7. Vào `/admin/applications`, kiểm tra đơn ứng tuyển vừa test.
8. Vào `/admin/ai-usage`, lọc theo ngày hiện tại.
9. Kiểm tra các feature vừa dùng:
   - parse CV,
   - matching,
   - cover letter,
   - chatbot,
   - mock interview,
   - JD parsing,
   - shortlist/interview copilot nếu có.
10. Vào `/admin/billing`, kiểm tra giao dịch, gói, bảng giá, subscription.
11. Vào `/admin/audit-logs`, kiểm tra thao tác của ứng viên/HR/admin.

Kết quả mong đợi:

- Admin xem được dữ liệu vận hành liên quan đến luồng vừa test.
- AI usage có request, trạng thái success/error/fallback, latency và feature code.
- Billing dashboard phản ánh giao dịch/entitlement nếu có phát sinh.
- Audit log có thao tác tạo/cập nhật job, ứng tuyển, phỏng vấn, offer, onboarding hoặc billing.
- Quyền admin hoạt động đúng: admin thường thiếu quyền sẽ bị chặn, super admin có toàn quyền.

### 12.4. Tiêu Chí Kết Luận Luồng Test Đạt

Luồng test được xem là đạt nếu:

- Một ứng viên có thể đi từ chuẩn bị CV đến ứng tuyển, phỏng vấn, nhận offer và onboarding.
- Một nhà tuyển dụng có thể đi từ JD/job đến shortlist, compare, phỏng vấn, offer và onboarding.
- AI service hỗ trợ được ít nhất các điểm chính: parse CV, parse JD, matching, cover letter, chatbot/mock interview hoặc interview copilot.
- Admin quan sát được dữ liệu phát sinh qua AI usage, billing và audit log.
- Các bước quan trọng đều có trạng thái/timeline/notification hoặc log tương ứng.
- Khi AI/payment/realtime lỗi môi trường, hệ thống có thông báo hoặc fallback rõ ràng, không làm hỏng luồng nghiệp vụ chính.

### 12.5. Kịch Bản Quay Video Demo Gửi Giảng Viên Xem Trước

Mục này là phiên bản dùng trực tiếp khi quay video. Nên quay theo một câu chuyện duy nhất để giảng viên dễ theo dõi: một ứng viên backend chuẩn bị CV, dùng AI để tìm job phù hợp, ứng tuyển vào TechViet Solutions; sau đó nhà tuyển dụng dùng AI để lọc, phỏng vấn, gửi offer; cuối cùng admin kiểm tra usage, billing và audit log.

Thời lượng khuyến nghị: 15-20 phút. Nếu cần ngắn hơn, ưu tiên các cảnh 1, 2, 3, 4, 5, 6, 8 và 10.

#### Cảnh 1 - Mở Đầu Và Giới Thiệu Mục Tiêu

Màn hình quay: landing page hoặc `/jobs`.

Thao tác:

1. Mở trang chủ.
2. Di chuyển nhanh qua thanh điều hướng, danh sách việc làm, công ty.

Lời thoại gợi ý:

> Em xin phép demo hệ thống SmartJob AI. Đây là nền tảng tuyển dụng có tích hợp trí tuệ nhân tạo, phục vụ ba nhóm chính: ứng viên, nhà tuyển dụng và quản trị viên. Điểm em muốn thể hiện trong video này là hệ thống không chỉ đăng tin và nộp CV, mà bao phủ một quy trình tuyển dụng gần như đầy đủ: từ chuẩn bị hồ sơ, matching việc làm, ứng tuyển, lọc ứng viên bằng AI, phỏng vấn, offer, onboarding cho đến phần admin giám sát AI usage, billing và audit log.

> Trong video này em sẽ đi theo một luồng thống nhất: ứng viên backend ứng tuyển vào vị trí Backend Developer Laravel của công ty TechViet Solutions.

Kết quả cần thấy trên video:

- Người xem hiểu mục tiêu demo.
- Người xem biết demo sẽ đi theo một câu chuyện end-to-end, không phải thao tác rời rạc.

#### Cảnh 2 - Public Job Board Và Thông Tin Job

Màn hình quay: `/jobs`, chi tiết job `Backend Developer Laravel`, trang công ty TechViet Solutions.

Thao tác:

1. Vào danh sách việc làm.
2. Tìm `Laravel`.
3. Mở job `Backend Developer Laravel`.
4. Mở nhanh thông tin công ty TechViet Solutions.

Lời thoại gợi ý:

> Ở phía khách vãng lai, người dùng có thể tìm kiếm việc làm, xem chi tiết tin tuyển dụng và xem thông tin công ty. Ví dụ em tìm vị trí Laravel, hệ thống trả về job Backend Developer Laravel của TechViet Solutions. Ở trang chi tiết, người dùng thấy mô tả công việc, yêu cầu, kỹ năng, lương, địa điểm, hình thức làm việc và nút ứng tuyển.

> Các dữ liệu này không chỉ dùng để hiển thị, mà còn là đầu vào cho các chức năng AI phía sau như parse JD, matching CV với JD, sinh cover letter và AI shortlist cho nhà tuyển dụng.

Kết quả cần thấy trên video:

- Tìm kiếm job hoạt động.
- Chi tiết job có dữ liệu đủ rõ để dùng trong luồng AI.

#### Cảnh 3 - Ứng Viên Đăng Nhập, Quản Lý Hồ Sơ Và Parse CV Bằng AI

Màn hình quay: `/dashboard`, `/profile`, `/my-cv`.

Thao tác:

1. Đăng nhập `ungvien.backend@demo.vn`.
2. Mở dashboard ứng viên.
3. Vào hồ sơ/CV.
4. Mở hồ sơ demo hoặc upload CV.
5. Chạy parse CV bằng AI nếu môi trường đã sẵn sàng.
6. Mở modal kết quả parse.

Lời thoại gợi ý:

> Em đăng nhập với vai trò ứng viên. Ở dashboard, ứng viên có thể quản lý thông tin cá nhân, hồ sơ, CV, kỹ năng, đơn ứng tuyển và các tính năng AI.

> Tại màn hình hồ sơ/CV, hệ thống không chỉ lưu file CV. Khi ứng viên upload CV hoặc tạo CV trong hệ thống, AI có thể parse nội dung để trích xuất họ tên, email, số điện thoại, kỹ năng, kinh nghiệm và học vấn. Kết quả này được lưu dưới dạng dữ liệu có cấu trúc để phục vụ matching, career report, chatbot và mock interview.

> Điểm quan trọng là hệ thống không áp dụng mù kết quả AI. Nếu CV có layout phức tạp, thiếu nội dung hoặc độ tin cậy thấp, hệ thống hiển thị cảnh báo và cho ứng viên kiểm tra lại các trường quan trọng trước khi áp dụng.

Kết quả cần thấy trên video:

- Hồ sơ/CV có dữ liệu.
- Kết quả parse CV có kỹ năng, kinh nghiệm, học vấn hoặc cảnh báo chất lượng.
- Người xem hiểu CV parse là nền tảng cho các tính năng AI tiếp theo.

#### Cảnh 4 - CV Builder Và AI Writing

Màn hình quay: `/cv-builder`.

Thao tác:

1. Mở CV Builder.
2. Chọn template/màu/layout nếu có.
3. Mở một phần như summary, objective hoặc kinh nghiệm.
4. Dùng AI Writing để sinh gợi ý.
5. Mở preview CV.

Lời thoại gợi ý:

> Ngoài upload CV, ứng viên có thể tạo CV trực tiếp bằng CV Builder. Ở đây có các phần thông tin cá nhân, mục tiêu nghề nghiệp, kinh nghiệm, kỹ năng, học vấn và dự án.

> Với những phần ứng viên thường khó viết như mô tả bản thân hoặc mô tả kinh nghiệm, hệ thống có AI Writing để gợi ý nội dung chuyên nghiệp hơn. Như vậy AI không chỉ dùng ở bước tuyển dụng, mà hỗ trợ ứng viên ngay từ lúc chuẩn bị hồ sơ.

Kết quả cần thấy trên video:

- CV Builder hoạt động.
- AI Writing sinh được nội dung hoặc có dữ liệu gợi ý sẵn.
- Preview CV hiển thị được.

#### Cảnh 5 - Matching Jobs, Career Report Và Chatbot

Màn hình quay: `/matched-jobs`, `/career-report`, `/ai-center/chatbot`.

Thao tác:

1. Vào matched jobs.
2. Chọn hồ sơ backend demo.
3. Mở kết quả matching với job Backend Developer Laravel.
4. Mở career report.
5. Mở chatbot và hỏi một câu demo.

Câu hỏi chatbot nên dùng:

```text
Với hồ sơ hiện tại, em nên ứng tuyển Backend Laravel hay Frontend Vue.js?
```

Hoặc:

```text
Hãy gợi ý lộ trình 30/60/90 ngày để em đạt vị trí Backend Developer Laravel.
```

Lời thoại gợi ý:

> Sau khi hồ sơ đã có dữ liệu có cấu trúc, ứng viên có thể dùng chức năng matched jobs. Hệ thống so khớp CV với JD và trả về điểm phù hợp. Điểm này không chỉ dựa vào keyword, mà có breakdown theo kỹ năng, kinh nghiệm, học vấn, ngữ cảnh CV-JD, lương, địa điểm và hình thức làm việc.

> Ở đây hệ thống cũng chuẩn hóa các cách viết kỹ năng khác nhau qua skill alias. Ví dụ JavaScript và JS, PostgreSQL và postgres có thể được hiểu là cùng một nhóm kỹ năng. Điều này giúp kết quả matching ổn định hơn.

> Career report giúp ứng viên hiểu mình phù hợp với hướng nghề nào, còn thiếu kỹ năng gì và nên phát triển theo lộ trình nào. Chatbot thì dùng ngữ cảnh hồ sơ, matching, career report và dữ liệu job trong hệ thống để trả lời cá nhân hóa hơn, thay vì trả lời chung chung.

Kết quả cần thấy trên video:

- Matching có điểm và giải thích.
- Career report hoặc chatbot trả lời dựa trên hồ sơ/job.
- Có thể chỉ rõ kỹ năng khớp và kỹ năng thiếu.

#### Cảnh 6 - Mock Interview Cho Ứng Viên

Màn hình quay: `/ai-center/mock-interview`.

Thao tác:

1. Tạo phiên mock interview cho Backend Developer Laravel.
2. Nhận câu hỏi đầu tiên.
3. Trả lời ngắn:

```text
Em sẽ cố gắng học hỏi thêm.
```

4. Cho xem AI đánh giá/trừ điểm.
5. Trả lời lại câu có cấu trúc hơn nếu muốn.
6. Sinh báo cáo phiên phỏng vấn.

Lời thoại gợi ý:

> Phần mock interview giúp ứng viên luyện phỏng vấn trước khi gặp nhà tuyển dụng thật. AI sinh câu hỏi dựa trên CV, JD và vai trò ứng tuyển.

> Em cố tình trả lời một câu rất ngắn là "Em sẽ cố gắng học hỏi thêm" để kiểm tra hệ thống đánh giá. AI sẽ không chỉ khen chung chung, mà có rubric như kỹ thuật, giao tiếp, độ phù hợp JD, độ rõ ràng, tính cụ thể và cấu trúc câu trả lời. Nếu câu trả lời quá ngắn hoặc thiếu minh chứng, hệ thống sẽ trừ điểm và gợi ý cải thiện.

> Tính năng này giúp ứng viên biết mình cần bổ sung ví dụ, hành động cụ thể và kết quả đạt được khi trả lời phỏng vấn.

Kết quả cần thấy trên video:

- Mock interview sinh câu hỏi.
- Câu trả lời yếu bị cảnh báo/trừ điểm.
- Có feedback hoặc báo cáo tổng kết.

#### Cảnh 7 - Ứng Viên Sinh Cover Letter Và Nộp Hồ Sơ

Màn hình quay: chi tiết job, form ứng tuyển, `/applications`.

Thao tác:

1. Quay lại job Backend Developer Laravel.
2. Chọn hồ sơ ứng viên.
3. Bấm sinh cover letter bằng AI.
4. Kiểm tra cảnh báo/audit kỹ năng nếu có.
5. Nộp hồ sơ.
6. Vào danh sách đơn ứng tuyển.

Lời thoại gợi ý:

> Khi ứng tuyển, ứng viên có thể dùng AI để sinh cover letter theo CV và JD. Điểm khác biệt là hệ thống có kiểm tra chất lượng thư. Nếu thư nhắc đến kỹ năng mà CV hoặc matching chưa chứng minh được, hệ thống có thể cảnh báo để hạn chế việc AI bịa kỹ năng.

> Sau khi nộp hồ sơ, ứng viên theo dõi trạng thái trong màn hình applications. Timeline giúp ứng viên biết đơn đang ở bước nào: đã nộp, đang xét, phỏng vấn, offer hoặc onboarding.

Kết quả cần thấy trên video:

- Cover letter được sinh hoặc hiển thị.
- Đơn ứng tuyển được tạo.
- Timeline/trạng thái đơn hiển thị.

#### Cảnh 8 - Nhà Tuyển Dụng: Công Ty, Tin Tuyển Dụng Và Parse JD

Màn hình quay: `/employer`, `/employer/company`, `/employer/jobs`, job detail.

Thao tác:

1. Đăng nhập `hr.techviet@demo.vn`.
2. Mở dashboard employer.
3. Mở hồ sơ công ty.
4. Mở job Backend Developer Laravel.
5. Chạy parse JD bằng AI hoặc mở kết quả đã có.
6. Chỉ quality warning và skill suggestion.

Lời thoại gợi ý:

> Bây giờ em chuyển sang vai trò nhà tuyển dụng. Nhà tuyển dụng có dashboard riêng để quản lý công ty, thành viên HR, tin tuyển dụng, ứng viên, phỏng vấn, billing và audit log.

> Với tin tuyển dụng, hệ thống có parse JD bằng AI. AI trích xuất kỹ năng, yêu cầu, quyền lợi, lương, địa điểm và hình thức làm việc. Nếu JD quá ngắn, thiếu yêu cầu, thiếu lương hoặc thiếu hình thức làm việc, hệ thống hiển thị cảnh báo chất lượng để HR bổ sung.

> Việc kiểm soát chất lượng JD rất quan trọng, vì JD là đầu vào cho matching, shortlist, compare và interview copilot.

Kết quả cần thấy trên video:

- Employer dashboard mở đúng vai trò.
- Job detail có parse JD/quality warning/skill suggestion.

#### Cảnh 9 - AI Shortlist, Compare Và Interview Copilot

Màn hình quay: job detail, danh sách ứng viên, shortlist/compare, interview detail.

Thao tác:

1. Mở danh sách ứng viên của job.
2. Chạy hoặc xem AI Shortlist.
3. Chọn 2-3 ứng viên để compare nếu có dữ liệu.
4. Cập nhật ứng viên demo sang vòng phỏng vấn.
5. Tạo hoặc mở vòng phỏng vấn.
6. Dùng Interview Copilot generate.
7. Nhập ghi chú mẫu và dùng evaluate nếu kịp.

Lời thoại gợi ý:

> Sau khi có ứng viên nộp hồ sơ, nhà tuyển dụng có thể dùng AI Shortlist để xếp hạng ứng viên theo mức độ phù hợp với JD. Kết quả có điểm tổng, kỹ năng khớp, kỹ năng thiếu, độ tin cậy và giải thích.

> Nếu cần so sánh nhiều ứng viên, hệ thống có compare matrix. Bảng này giúp HR nhìn nhanh điểm mạnh, điểm cần cải thiện, kỹ năng còn thiếu và câu hỏi phỏng vấn gợi ý cho từng ứng viên.

> Khi chuyển sang phỏng vấn, Interview Copilot hỗ trợ HR sinh bộ câu hỏi, rubric, focus area và red flags dựa trên CV, JD và kết quả matching. Sau phỏng vấn, HR có thể nhập ghi chú để AI hỗ trợ đánh giá có cấu trúc. AI ở đây không thay HR ra quyết định, mà giúp quá trình lọc và phỏng vấn minh bạch hơn.

Kết quả cần thấy trên video:

- Shortlist/compare có điểm và giải thích.
- Có thể tạo/mở vòng phỏng vấn.
- Interview Copilot sinh câu hỏi/rubric hoặc đánh giá.

#### Cảnh 10 - Offer, Ứng Viên Phản Hồi Và Onboarding

Màn hình quay: chi tiết ứng tuyển employer, tab ứng viên `/applications`, onboarding.

Thao tác:

1. Ở employer, gửi offer hoặc mở offer đã có.
2. Chuyển sang tab ứng viên.
3. Mở đơn ứng tuyển.
4. Chấp nhận offer.
5. Quay lại employer để xem trạng thái.
6. Mở onboarding checklist nếu có.

Lời thoại gợi ý:

> Hệ thống không dừng ở bước nộp CV. Sau phỏng vấn, nhà tuyển dụng có thể gửi offer với vị trí, mức lương, ngày bắt đầu và hạn phản hồi.

> Ứng viên có thể chấp nhận hoặc từ chối offer ngay trên hệ thống. Khi offer được chấp nhận, luồng chuyển sang onboarding với các task như bổ sung giấy tờ, nhận thiết bị, ký hợp đồng hoặc tham gia orientation.

> Phần này cho thấy hệ thống bao phủ toàn bộ vòng đời tuyển dụng, từ tìm việc đến nhận việc.

Kết quả cần thấy trên video:

- Offer hiển thị được.
- Ứng viên phản hồi được.
- Onboarding hoặc timeline được cập nhật.

#### Cảnh 11 - Billing, AI Usage Và Admin Giám Sát

Màn hình quay: `/wallet` hoặc `/employer/billing`, `/admin/ai-usage`, `/admin/billing`, `/admin/audit-logs`.

Thao tác:

1. Mở nhanh ví/gói dịch vụ ở ứng viên hoặc employer.
2. Đăng nhập admin `admin@kltn.com`.
3. Mở dashboard admin.
4. Vào AI Usage.
5. Vào Billing.
6. Vào Audit Logs.

Lời thoại gợi ý:

> Cuối cùng là vai trò admin. Admin dùng để quản trị dữ liệu nền, người dùng, công ty, tin tuyển dụng, ứng tuyển, billing và các log vận hành.

> Ở màn hình AI Usage, admin có thể xem các request AI vừa phát sinh như parse CV, matching, cover letter, chatbot, mock interview, parse JD, shortlist hoặc interview copilot. Hệ thống ghi nhận feature, trạng thái thành công hoặc lỗi, fallback và latency.

> Ở phần billing, hệ thống quản lý ví, giao dịch, gói dịch vụ, quota và bảng giá tính năng AI. Ở audit log, admin có thể truy vết các thao tác quan trọng của ứng viên, nhà tuyển dụng và quản trị viên. Điều này giúp hệ thống có khả năng vận hành thực tế, không chỉ là giao diện demo.

Kết quả cần thấy trên video:

- Admin xem được AI usage.
- Billing có dữ liệu ví/gói/giao dịch nếu đã phát sinh.
- Audit log có thao tác trong luồng demo.

#### Cảnh 12 - Kết Luận Video

Màn hình quay: admin dashboard, AI usage hoặc quay lại job/application vừa demo.

Lời thoại gợi ý:

> Như vậy, video đã đi qua một luồng tuyển dụng end-to-end: khách xem job, ứng viên chuẩn bị CV và dùng AI để matching, định hướng, luyện phỏng vấn, sinh cover letter và ứng tuyển; nhà tuyển dụng dùng AI để parse JD, shortlist, compare và hỗ trợ phỏng vấn; sau đó hệ thống xử lý offer, onboarding, billing và admin giám sát usage/audit.

> Điểm chính của hệ thống là AI được tích hợp vào đúng các bước nghiệp vụ, có giải thích và có kiểm soát chất lượng đầu vào/đầu ra, thay vì chỉ gọi AI để sinh văn bản. Em xin kết thúc phần demo tại đây.

Kết quả cần thấy trên video:

- Người xem nắm được toàn bộ giá trị hệ thống.
- Kết thúc bằng thông điệp rõ ràng: end-to-end, AI hai phía, có quản trị vận hành.

#### Checklist Quay Video Trước Khi Bấm Record

- Đã đăng nhập sẵn 3 tab/profile: ứng viên, nhà tuyển dụng, admin.
- Job `Backend Developer Laravel` đang hoạt động.
- Hồ sơ ứng viên backend có kỹ năng Laravel/PHP/API/SQL/Git.
- CV đã parse trước ít nhất một lần.
- JD đã parse trước ít nhất một lần.
- Matching có kết quả đẹp để tránh chờ lâu khi quay.
- Có ít nhất một đơn ứng tuyển hoặc sẵn sàng nộp mới.
- Shortlist/compare có dữ liệu.
- Có thể tạo hoặc mở sẵn một vòng phỏng vấn.
- Có offer/onboarding sẵn nếu môi trường không ổn định.
- Admin mở được AI usage, billing và audit logs.
- Nếu AI service, payment sandbox hoặc realtime bị lỗi, dùng dữ liệu đã chạy trước và nói rõ đây là phần đã có log/kết quả trong hệ thống.

## 13. Những Điểm Nên Nhấn Mạnh Khi Bảo Vệ

### 13.1. Điểm Kỹ Thuật

- Hệ thống tách 3 khối rõ ràng: frontend, backend, AI service.
- Backend Laravel quản lý nghiệp vụ, xác thực, phân quyền, dữ liệu và audit.
- AI service tách riêng giúp dễ thay đổi provider/model mà không phá nghiệp vụ backend.
- Có fallback/logging khi AI lỗi.
- Các nâng cấp AI mới chủ yếu mở rộng response/service logic, hạn chế tạo thêm bảng/cột không cần thiết.
- Parse CV/JD có quality guard để giảm lỗi dữ liệu đầu vào.
- Matching có breakdown đa tiêu chí: kỹ năng, kinh nghiệm, học vấn, ngữ cảnh CV-JD, lương, địa điểm, hình thức làm việc.
- Điểm kỹ năng dùng skill alias catalog để chuẩn hóa các cách viết khác nhau; điểm ngữ cảnh dùng BM25/TF-IDF kết hợp lexical similarity và skill alias context, dễ giải thích hơn embedding đen hộp.
- Ollama local được dùng ở lớp sinh giải thích khi cấu hình cho phép, còn dữ liệu và điểm số vẫn do matcher deterministic tính.
- AI shortlist/compare tối ưu tốc độ bằng gọi song song top ứng viên qua `Http::pool`.
- Có realtime notification và event broadcasting.
- Có billing/entitlement để kiểm soát tính năng AI.
- Có phân quyền nhiều tầng: vai trò hệ thống, quyền admin, quyền HR nội bộ.
- Có export PDF/tài liệu cho quy trình tuyển dụng.

### 13.2. Điểm Nghiệp Vụ

- Hệ thống bao phủ đủ vòng đời tuyển dụng: tìm việc, CV, ứng tuyển, lọc hồ sơ, phỏng vấn, offer, onboarding.
- AI hỗ trợ cả ứng viên và nhà tuyển dụng.
- AI có giải thích điểm matching/shortlist/compare, giúp tăng tính minh bạch.
- Kinh nghiệm ứng viên và yêu cầu tuyển dụng được đồng bộ theo năm/tháng, tránh hiển thị khó hiểu như `0.25 năm`.
- Hệ thống có cơ chế kiểm soát chất lượng output AI: cảnh báo parse CV/JD, audit cover letter, mock interview phạt câu trả lời yếu.
- Admin có công cụ vận hành, giám sát và truy vết.
- Billing/gói dịch vụ cho thấy hướng phát triển sản phẩm thực tế.

### 13.3. Điểm Khác Biệt So Với Website Tuyển Dụng Thông Thường

- Không chỉ đăng tin và nộp CV.
- Có CV Builder và AI Writing.
- Có parse CV/JD để biến dữ liệu văn bản thành dữ liệu có cấu trúc, kèm cảnh báo chất lượng.
- Có matching dùng skill alias, raw text và phương pháp BM25/TF-IDF dễ diễn giải, cùng career report.
- Có chatbot nghề nghiệp dùng RAG nhẹ từ database hiện có và mock interview có rubric.
- Có AI shortlist, compare ứng viên dạng ma trận và interview copilot cho HR.
- Có offer/onboarding/timeline/export sau khi phỏng vấn.
- Có AI usage dashboard, billing và audit log.

## 14. Rủi Ro Demo Và Cách Xử Lý

### 14.1. AI Service Không Chạy

Dấu hiệu:

- Parse CV/JD, chatbot, mock interview báo lỗi kết nối AI service.

Cách xử lý:

- Kiểm tra `AI_SERVICE_URL` trong backend.
- Chạy:

```bash
AI/.venv/bin/python -m uvicorn app.main:app --app-dir AI --reload --reload-dir AI --host 127.0.0.1 --port 8001
```

- Nếu vẫn lỗi, dùng các kết quả parse/matching/career report đã chạy thử trước buổi demo và giải thích hệ thống có fallback/log AI usage. Seeder demo hiện không tạo dữ liệu AI giả để tránh số liệu ảo.
- Nếu thư mục `AI/.venv` chưa được tạo, tạo lại virtual environment và cài dependency bằng `AI/.venv/bin/python -m pip install -r AI/requirements.txt`.

### 14.2. Realtime Không Hoạt Động

Dấu hiệu:

- Notification không bật tức thì.

Cách xử lý:

- Chạy Reverb/queue nếu môi trường cần:

```bash
cd BE
php artisan reverb:start
php artisan queue:work --tries=3 --timeout=90
```

- Nếu không có thời gian, refresh trang và giải thích realtime dùng Laravel Echo/Reverb, có fallback polling ở frontend.

### 14.3. Thanh Toán Sandbox Không Ổn Định

Dấu hiệu:

- MoMo/VNPay redirect hoặc IPN không phản hồi như mong muốn.

Cách xử lý:

- Không demo giao dịch live nếu mạng/phòng bảo vệ không ổn.
- Chỉ demo màn tạo giao dịch, lịch sử giao dịch, admin billing và giải thích luồng return/IPN.
- Dùng dữ liệu giao dịch đã có trong DB nếu đã seed/tạo trước.

### 14.4. Tài Khoản Demo Không Có Dữ Liệu Đúng

Cách xử lý:

- Ưu tiên dùng `ungvien.backend@demo.vn`, `hr.techviet@demo.vn`, `admin@kltn.com`.
- Kiểm tra trước job TechViet Solutions còn hoạt động.
- Chuẩn bị sẵn một hồ sơ có kỹ năng Laravel/PHP/Vue/API/SQL để matching đẹp.
- Chạy trước parse CV/JD và matching cho job demo để trong trường hợp AI service gặp sự cố vẫn có dữ liệu đã lưu để trình bày.

## 15. Checklist Trước Khi Vào Phòng Bảo Vệ

- Backend chạy được.
- Frontend chạy được.
- AI service `/health` trả `success: true`.
- Đăng nhập được 3 tài khoản: ứng viên, nhà tuyển dụng, admin.
- Job demo đang hoạt động.
- Ứng viên demo có CV/hồ sơ đủ dữ liệu.
- CV demo đã parse và modal kết quả có dữ liệu kỹ năng/học vấn/kinh nghiệm.
- JD demo đã parse và có kỹ năng yêu cầu/cảnh báo chất lượng để trình bày.
- AI Matching có kết quả.
- Matching demo có breakdown điểm đa tiêu chí, bao gồm ngữ cảnh CV-JD và metadata phương pháp `bm25_tfidf_skill_alias_context`.
- Kết quả demo hiển thị kinh nghiệm dưới 1 năm theo tháng, ví dụ `3 tháng`, `6 tháng`.
- Career report hoặc chatbot trả lời được.
- Employer xem được ứng tuyển/shortlist.
- Compare matrix có ít nhất 2 ứng viên để so sánh, có điểm mạnh/yếu lấy từ structured explanation khi có.
- Có thể tạo phỏng vấn hoặc có sẵn phỏng vấn.
- Có thể gửi offer hoặc có sẵn offer.
- Admin mở được AI usage, billing, audit log.
- Trình duyệt đã tách tab/profile để không lẫn token giữa các vai trò.

## 16. Kết Luận

Codebase hiện tại thể hiện một hệ thống tuyển dụng tương đối đầy đủ theo hướng sản phẩm thực tế: có public job board, dashboard ứng viên, dashboard nhà tuyển dụng, admin console, AI service độc lập, realtime notification, billing và audit log.

Kịch bản demo tốt nhất là trình diễn theo một hồ sơ ứng viên cụ thể ứng tuyển vào một job cụ thể của TechViet Solutions. Cách này giúp hội đồng thấy rõ logic end-to-end: từ chuẩn bị CV, AI phân tích, matching, ứng tuyển, HR shortlist, phỏng vấn, offer, onboarding đến admin giám sát vận hành.
