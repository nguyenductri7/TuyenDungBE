# SmartJob AI Backend

Backend của SmartJob AI là API nghiệp vụ viết bằng Laravel 12. Service này là nguồn dữ liệu chính cho hệ thống tuyển dụng: xác thực, phân quyền, hồ sơ/CV, công ty, tin tuyển dụng, ứng tuyển, phỏng vấn, offer, onboarding, ví/thanh toán, notification, audit log và tích hợp AI service.

## Công Nghệ

- PHP 8.2+
- Laravel 12
- Laravel Sanctum cho Bearer token API
- Laravel Socialite cho Google OAuth
- Laravel Reverb/Echo cho realtime notification
- Pest/PHPUnit cho kiểm thử
- MySQL cho database
- DomPDF cho export tài liệu server-side

## Cấu Trúc Chính

```text
BE/
├── app/Http/Controllers/Api      # REST API theo public/candidate/employer/admin
├── app/Http/Middleware           # role, admin permission, company permission
├── app/Http/Requests             # validation request
├── app/Models                    # Eloquent models
├── app/Services                  # nghiệp vụ AI, billing, audit, notification, export
├── database/migrations           # schema database
├── database/seeders              # dữ liệu demo
├── routes/api.php                # toàn bộ API chính
└── tests                         # unit/feature tests
```

## Cài Đặt Local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Các biến môi trường quan trọng nằm trong `.env.example`, gồm:

- `DB_*`
- `FRONTEND_URL`
- `AI_SERVICE_URL`
- `GOOGLE_*`
- `MAIL_*`
- `REVERB_*`
- `MOMO_*`
- `VNPAY_*`

Không commit file `.env` thật vì có thể chứa secret.

## Chạy Backend

```bash
php artisan serve
```

Nếu demo đầy đủ realtime/queue:

```bash
php artisan queue:work --tries=3 --timeout=90
php artisan reverb:start
```

Backend mặc định chạy tại:

```text
http://127.0.0.1:8000
```

## Deploy Và Email Xác Thực

Khi deploy lên Render hoặc môi trường production, đăng ký/đăng nhập phụ thuộc các biến sau:

- `APP_KEY`: bắt buộc để tạo signed URL xác thực email.
- `APP_URL`: URL backend public, ví dụ `https://your-backend.onrender.com`.
- `FRONTEND_URL`: URL frontend public, ví dụ `https://your-frontend.vercel.app`.
- `MAIL_*`: SMTP thật, không dùng placeholder từ `.env.example`.
- `QUEUE_CONNECTION=sync`: phù hợp khi chỉ có một web service và chưa chạy queue worker riêng.

Nếu người dùng đăng ký thành công nhưng không nhận email, tài khoản sẽ chưa có `email_verified_at` và đăng nhập sẽ bị chặn với mã `EMAIL_NOT_VERIFIED`. Kiểm tra log deploy để tìm lỗi SMTP như sai app password, sai host/port, hoặc thiếu `APP_KEY`/`APP_URL`.

## Kết Nối AI Service

Backend gọi FastAPI service qua biến:

```env
AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_TIMEOUT=120
```

Các controller AI chính gồm parse CV/JD, matching, cover letter, career report, chatbot, mock interview và interview copilot.

## Kiểm Thử

```bash
php artisan test
```

Ở lần rà soát gần nhất, test backend chạy thành công với 59 tests và 443 assertions.

## Tài Khoản Demo Từ Seeder

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Super Admin | `admin@kltn.com` | `Admin@123` |
| Nhà tuyển dụng | `hr.techviet@demo.vn` | `NTD@123456` |
| Ứng viên | `ungvien.backend@demo.vn` | `UV@123456` |

Các tài khoản này chỉ dành cho môi trường demo/local.

## Tài Liệu Liên Quan

- `../README.md`: tổng quan toàn hệ thống.
- `docs/TAI_LIEU_TONG_QUAN_HE_THONG_VA_KICH_BAN_DEMO.md`: tài liệu tổng quan và kịch bản bảo vệ.
- `docs/FEATURE_IMPLEMENTATION_STATUS_SUMMARY.md`: trạng thái tính năng so với roadmap.
