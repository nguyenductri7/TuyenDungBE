<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HoSoController;
use App\Http\Controllers\Api\NhaTuyenDungHoSoController;
use App\Http\Controllers\Api\NganhNgheController;
use App\Http\Controllers\Api\KyNangController;
use App\Http\Controllers\Api\NguoiDungKyNangController;
use App\Http\Controllers\Api\CongTyController;
use App\Http\Controllers\Api\TinTuyenDungController;
use App\Http\Controllers\Api\NhaTuyenDungCongTyController;
use App\Http\Controllers\Api\NhaTuyenDungAuditLogController;
use App\Http\Controllers\Api\NhaTuyenDungShortlistController;
use App\Http\Controllers\Api\NhaTuyenDungTinTuyenDungController;
use App\Http\Controllers\Api\NhaTuyenDungUngTuyenController;
use App\Http\Controllers\Api\UngVienKetQuaMatchingController;
use App\Http\Controllers\Api\UngVienTuVanNgheNghiepController;
use App\Http\Controllers\Api\UngVienLuuTinController;
use App\Http\Controllers\Api\UngVienTheoDoiCongTyController;
use App\Http\Controllers\Api\ReEngagementController;
use App\Http\Controllers\Api\UngVienUngTuyenController;
use App\Http\Controllers\Api\CvParsingController;
use App\Http\Controllers\Api\JdParsingController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\MomoTopUpController;
use App\Http\Controllers\Api\VnpayController;
use App\Http\Controllers\Api\CoverLetterController;
use App\Http\Controllers\Api\CareerReportController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\CvTemplateController;
use App\Http\Controllers\Api\CvBuilderWritingController;
use App\Http\Controllers\Api\AiChatSessionController;
use App\Http\Controllers\Api\AiChatMessageController;
use App\Http\Controllers\Api\MockInterviewController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\ApplicationExportController;
use App\Http\Controllers\Api\Admin\AdminNguoiDungController;
use App\Http\Controllers\Api\Admin\AdminQuanTriVienController;
use App\Http\Controllers\Api\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\Admin\AdminAiUsageController;
use App\Http\Controllers\Api\Admin\AdminHoSoController;
use App\Http\Controllers\Api\Admin\AdminNganhNgheController;
use App\Http\Controllers\Api\Admin\AdminKyNangController;
use App\Http\Controllers\Api\Admin\AdminNguoiDungKyNangController;
use App\Http\Controllers\Api\Admin\AdminCongTyController;
use App\Http\Controllers\Api\Admin\AdminTinTuyenDungController;
use App\Http\Controllers\Api\Admin\AdminBillingController;
use App\Http\Controllers\Api\Admin\AdminLuuTinController;
use App\Http\Controllers\Api\Admin\AdminUngTuyenController;
use App\Http\Controllers\Api\Admin\AdminKetQuaMatchingController;
use App\Http\Controllers\Api\Admin\AdminTuVanNgheNghiepController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Module: Người dùng (nguoi_dungs) + Hồ sơ (ho_sos)
|--------------------------------------------------------------------------
|
| Mô hình: API → Methods → Request → Controller → Model
|
| Vai trò:
|   - vai_tro = 0 : ung_vien       (Ứng viên)
|   - vai_tro = 1 : nha_tuyen_dung (Nhà tuyển dụng)
|   - vai_tro = 2 : admin          (Quản trị viên)
|
*/


// ============================================================
// NHÓM 1: PUBLIC — Không cần xác thực
// ============================================================

// Đăng ký tài khoản mới (ứng viên hoặc nhà tuyển dụng)
Route::post('v1/dang-ky', [AuthController::class, 'dangKy'])
    ->name('auth.dang-ky');

// Đăng nhập — trả về Bearer Token
Route::post('v1/dang-nhap', [AuthController::class, 'dangNhap'])
    ->name('auth.dang-nhap');

// Đăng nhập bằng Google (Socialite)
Route::get('v1/auth/google/redirect', [AuthController::class, 'redirectGoogle'])
    ->name('auth.google.redirect');
Route::get('v1/auth/google/callback', [AuthController::class, 'callbackGoogle'])
    ->name('auth.google.callback');

// Quên mật khẩu — tạo token đặt lại mật khẩu
Route::post('v1/quen-mat-khau', [AuthController::class, 'quenMatKhau'])
    ->name('auth.quen-mat-khau');

// Đặt lại mật khẩu bằng token
Route::post('v1/dat-lai-mat-khau', [AuthController::class, 'datLaiMatKhau'])
    ->name('auth.dat-lai-mat-khau');

// Gửi lại email xác thực tài khoản
Route::post('v1/gui-lai-email-xac-thuc', [AuthController::class, 'guiLaiEmailXacThuc'])
    ->name('auth.gui-lai-email-xac-thuc');

// Xác thực email qua liên kết đã ký
Route::get('v1/xac-thuc-email/{id}/{hash}', [AuthController::class, 'xacThucEmail'])
    ->middleware('signed')
    ->name('verification.verify');

// Ảnh đại diện public từ storage hiện tại
Route::get('v1/anh-dai-dien', [AuthController::class, 'avatar'])
    ->name('auth.avatar');

// Tải ảnh chứng chỉ kỹ năng
Route::get('v1/chung-chi-ky-nang', [NguoiDungKyNangController::class, 'hinhAnh'])
    ->name('ung-vien.ky-nangs.hinh-anh');


// ============================================================
// NHÓM 2: AUTH — Cần Bearer Token (tất cả vai trò)
// ============================================================

// Đăng xuất — thu hồi token hiện tại
Route::post('v1/dang-xuat', [AuthController::class, 'dangXuat'])
    ->middleware('auth:sanctum')
    ->name('auth.dang-xuat');

// Xem hồ sơ cá nhân
Route::get('v1/ho-so', [AuthController::class, 'hoSo'])
    ->middleware('auth:sanctum')
    ->name('auth.ho-so');

// Cập nhật hồ sơ cá nhân (hỗ trợ upload ảnh đại diện)
Route::put('v1/ho-so', [AuthController::class, 'capNhatHoSo'])
    ->middleware('auth:sanctum')
    ->name('auth.cap-nhat-ho-so');

// Đổi mật khẩu — thu hồi tất cả token sau khi đổi thành công
Route::post('v1/doi-mat-khau', [AuthController::class, 'doiMatKhau'])
    ->middleware('auth:sanctum')
    ->name('auth.doi-mat-khau');

Route::get('v1/notifications', [NotificationController::class, 'index'])
    ->middleware('auth:sanctum')
    ->name('notifications.index');

Route::get('v1/notifications/unread-count', [NotificationController::class, 'unreadCount'])
    ->middleware('auth:sanctum')
    ->name('notifications.unread-count');

Route::patch('v1/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
    ->middleware('auth:sanctum')
    ->name('notifications.read');

Route::patch('v1/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
    ->middleware('auth:sanctum')
    ->name('notifications.read-all');

Route::get('v1/ung-vien/vi', [WalletController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.vi.show');

Route::get('v1/ung-vien/vi/bien-dong', [WalletController::class, 'transactions'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.vi.transactions');

Route::get('v1/ung-vien/payments', [WalletController::class, 'payments'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.payments.index');

Route::get('v1/ung-vien/payments/{maGiaoDichNoiBo}', [WalletController::class, 'paymentDetail'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.payments.show');

Route::get('v1/ung-vien/ai-pricing', [WalletController::class, 'pricing'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ai-pricing.index');

Route::get('v1/ung-vien/billing/entitlements', [WalletController::class, 'entitlements'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.billing.entitlements');

Route::get('v1/ung-vien/goi-dich-vus', [SubscriptionController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.goi-dich-vus.index');

Route::get('v1/ung-vien/goi-dich-vu-hien-tai', [SubscriptionController::class, 'current'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.goi-dich-vus.current');

Route::post('v1/ung-vien/goi-dich-vus/mua/momo', [SubscriptionController::class, 'purchaseMomo'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.goi-dich-vus.purchase.momo');

Route::post('v1/ung-vien/goi-dich-vus/mua/vi', [SubscriptionController::class, 'purchaseWallet'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.goi-dich-vus.purchase.wallet');

Route::post('v1/ung-vien/goi-dich-vus/mua/vnpay', [VnpayController::class, 'purchasePlan'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.goi-dich-vus.purchase.vnpay');

Route::post('v1/ung-vien/vi/nap-tien/momo', [MomoTopUpController::class, 'create'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.vi.topup.momo.create');

Route::post('v1/ung-vien/vi/nap-tien/vnpay', [VnpayController::class, 'createTopUp'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.vi.topup.vnpay.create');

Route::get('v1/ung-vien/vi/nap-tien/{maGiaoDichNoiBo}', [MomoTopUpController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.vi.topup.show');

Route::get('v1/nha-tuyen-dung/vi', [WalletController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.vi.show');

Route::get('v1/nha-tuyen-dung/vi/bien-dong', [WalletController::class, 'transactions'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.vi.transactions');

Route::get('v1/nha-tuyen-dung/ai-pricing', [WalletController::class, 'pricing'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.ai-pricing.index');

Route::get('v1/nha-tuyen-dung/billing/entitlements', [WalletController::class, 'entitlements'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.billing.entitlements');

Route::post('v1/nha-tuyen-dung/vi/nap-tien/momo', [MomoTopUpController::class, 'create'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.vi.topup.momo.create');

Route::post('v1/nha-tuyen-dung/vi/nap-tien/vnpay', [VnpayController::class, 'createTopUp'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.vi.topup.vnpay.create');

Route::get('v1/nha-tuyen-dung/vi/nap-tien/{maGiaoDichNoiBo}', [MomoTopUpController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.vi.topup.show');

Route::get('v1/nha-tuyen-dung/payments', [WalletController::class, 'payments'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.payments.index');

Route::get('v1/nha-tuyen-dung/payments/{maGiaoDichNoiBo}', [WalletController::class, 'paymentDetail'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:billing'])
    ->name('nha-tuyen-dung.payments.show');

Route::get('v1/payments/momo/return', [MomoTopUpController::class, 'handleReturn'])
    ->name('payments.momo.return');

Route::post('v1/payments/momo/ipn', [MomoTopUpController::class, 'handleIpn'])
    ->name('payments.momo.ipn');

Route::get('v1/payments/vnpay/return', [VnpayController::class, 'handleReturn'])
    ->name('payments.vnpay.return');

Route::get('v1/payments/vnpay/ipn', [VnpayController::class, 'handleIpn'])
    ->name('payments.vnpay.ipn');


// ============================================================
// NHÓM 3: ADMIN — Quản lý người dùng (vai_tro = 2)
// ============================================================

Route::get('v1/admin/audit-logs', [AdminAuditLogController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:audit_logs'])
    ->name('admin.audit-logs.index');

Route::get('v1/admin/ai-usage/overview', [AdminAiUsageController::class, 'overview'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:ai_usage'])
    ->name('admin.ai-usage.overview');

Route::get('v1/admin/ai-usage/logs', [AdminAiUsageController::class, 'logs'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:ai_usage'])
    ->name('admin.ai-usage.logs');

Route::get('v1/admin/ai-usage/features', [AdminAiUsageController::class, 'features'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:ai_usage'])
    ->name('admin.ai-usage.features');

Route::get('v1/admin/billing/overview', [AdminBillingController::class, 'overview'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.overview');

Route::get('v1/admin/billing/payments', [AdminBillingController::class, 'payments'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.payments.index');

Route::get('v1/admin/billing/payments/{maGiaoDichNoiBo}', [AdminBillingController::class, 'paymentDetail'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.payments.show');

Route::post('v1/admin/billing/payments/{maGiaoDichNoiBo}/reconcile', [AdminBillingController::class, 'reconcilePayment'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.payments.reconcile');

Route::get('v1/admin/billing/plans', [AdminBillingController::class, 'plans'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.plans.index');

Route::post('v1/admin/billing/plans', [AdminBillingController::class, 'storePlan'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.plans.store');

Route::put('v1/admin/billing/plans/{plan}', [AdminBillingController::class, 'updatePlan'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.plans.update');

Route::get('v1/admin/billing/prices', [AdminBillingController::class, 'prices'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.prices.index');

Route::post('v1/admin/billing/prices', [AdminBillingController::class, 'storePrice'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.prices.store');

Route::put('v1/admin/billing/prices/{price}', [AdminBillingController::class, 'updatePrice'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.prices.update');

Route::get('v1/admin/billing/subscriptions', [AdminBillingController::class, 'subscriptions'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:billing'])
    ->name('admin.billing.subscriptions.index');

// Thống kê tổng quan người dùng (⚠️ đặt trước /{id} để tránh conflict)
Route::get('v1/admin/nguoi-dungs/thong-ke', [AdminNguoiDungController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.thong-ke');

Route::get('v1/admin/admins/thong-ke', [AdminQuanTriVienController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.thong-ke');

Route::get('v1/admin/admins', [AdminQuanTriVienController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.index');

Route::post('v1/admin/admins', [AdminQuanTriVienController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.store');

Route::get('v1/admin/admins/{id}', [AdminQuanTriVienController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.show');

Route::get('v1/admin/admins/{id}/permissions', [AdminQuanTriVienController::class, 'permissions'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.permissions.show');

Route::put('v1/admin/admins/{id}', [AdminQuanTriVienController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.update');

Route::put('v1/admin/admins/{id}/permissions', [AdminQuanTriVienController::class, 'updatePermissions'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.permissions.update');

Route::post('v1/admin/admins/permissions/definitions', [AdminQuanTriVienController::class, 'storePermissionDefinition'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.permissions.definitions.store');

Route::patch('v1/admin/admins/{id}/khoa', [AdminQuanTriVienController::class, 'khoaTaiKhoan'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.khoa');

Route::delete('v1/admin/admins/{id}', [AdminQuanTriVienController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'super_admin'])
    ->name('admin.admins.destroy');

// Danh sách tất cả người dùng (có lọc, tìm kiếm, phân trang)
Route::get('v1/admin/nguoi-dungs', [AdminNguoiDungController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.index');

// Tạo tài khoản mới (admin có thể tạo bất kỳ vai trò nào)
Route::post('v1/admin/nguoi-dungs', [AdminNguoiDungController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.store');

// Xem chi tiết một người dùng theo ID
Route::get('v1/admin/nguoi-dungs/{id}', [AdminNguoiDungController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.show');

// Cập nhật thông tin người dùng theo ID
Route::put('v1/admin/nguoi-dungs/{id}', [AdminNguoiDungController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.update');

// Khoá hoặc mở khoá tài khoản (toggle trạng thái)
Route::patch('v1/admin/nguoi-dungs/{id}/khoa', [AdminNguoiDungController::class, 'khoaTaiKhoan'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.khoa');

// Xoá tài khoản người dùng theo ID
Route::delete('v1/admin/nguoi-dungs/{id}', [AdminNguoiDungController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:users'])
    ->name('admin.nguoi-dungs.destroy');


// ============================================================
// NHÓM 4: ỨNG VIÊN — Quản lý hồ sơ (vai_tro = 0)
// ============================================================

// Danh sách hồ sơ của ứng viên đang đăng nhập
Route::get('v1/ung-vien/ho-sos', [HoSoController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.index');

// Tạo hồ sơ mới (hỗ trợ upload file CV)
Route::post('v1/ung-vien/ho-sos', [HoSoController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.store');

// Xem chi tiết hồ sơ (chỉ xem được của mình)
Route::get('v1/ung-vien/ho-sos/{id}', [HoSoController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.show');

// Xem file CV đã tải lên của chính mình
Route::get('v1/ung-vien/ho-sos/{id}/cv', [HoSoController::class, 'viewCv'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.cv');

// Cập nhật hồ sơ (chỉ sửa được của mình, hỗ trợ upload file CV)
Route::put('v1/ung-vien/ho-sos/{id}', [HoSoController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.update');

// Xoá hồ sơ (chỉ xoá được của mình)
Route::delete('v1/ung-vien/ho-sos/{id}', [HoSoController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.destroy');

// Đổi trạng thái hồ sơ (công khai/ẩn - toggle)
Route::patch('v1/ung-vien/ho-sos/{id}/trang-thai', [HoSoController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.doi-trang-thai');

// Parse CV bằng AI
Route::post('v1/ung-vien/ho-sos/{id}/parse', [CvParsingController::class, 'parse'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.parse');

// Sinh kết quả matching theo hồ sơ
Route::post('v1/ung-vien/ho-sos/{id}/matching', [MatchingController::class, 'generate'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.matching');
Route::post('v1/ung-vien/ho-sos/{id}/matching/batch', [MatchingController::class, 'generateBatch'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.matching.batch');

// Sinh báo cáo tư vấn nghề nghiệp
Route::post('v1/ung-vien/ho-sos/{id}/career-report', [CareerReportController::class, 'generate'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.career-report');

Route::post('v1/ung-vien/cv-builder/ai-writing', [CvBuilderWritingController::class, 'generate'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.cv-builder.ai-writing');

Route::get('v1/ai-chat/sessions', [AiChatSessionController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.sessions.index');

Route::post('v1/ai-chat/sessions', [AiChatSessionController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.sessions.store');

Route::patch('v1/ai-chat/sessions/{id}/status', [AiChatSessionController::class, 'updateStatus'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.sessions.update-status');

Route::get('v1/ai-chat/sessions/{id}/messages', [AiChatSessionController::class, 'messages'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.sessions.messages');

Route::delete('v1/ai-chat/sessions/{id}/messages', [AiChatSessionController::class, 'clearMessages'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.sessions.clear-messages');

Route::post('v1/ai-chat/messages', [AiChatMessageController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.messages.store');

Route::post('v1/ai-chat/messages/stream', [AiChatMessageController::class, 'stream'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ai-chat.messages.stream');

Route::get('v1/mock-interview/sessions', [MockInterviewController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.index');

Route::get('v1/mock-interview/dashboard', [MockInterviewController::class, 'dashboard'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.dashboard');

Route::post('v1/mock-interview/sessions', [MockInterviewController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.store');

Route::get('v1/mock-interview/sessions/{id}/messages', [MockInterviewController::class, 'messages'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.messages');

Route::patch('v1/mock-interview/sessions/{id}/status', [MockInterviewController::class, 'updateStatus'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.update-status');

Route::delete('v1/mock-interview/sessions/{id}', [MockInterviewController::class, 'clearSession'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.destroy');

Route::post('v1/mock-interview/messages', [MockInterviewController::class, 'answer'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.messages.answer');

Route::post('v1/mock-interview/messages/stream', [MockInterviewController::class, 'stream'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.messages.stream');

Route::post('v1/mock-interview/sessions/{id}/report', [MockInterviewController::class, 'generateReport'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.report.generate');

Route::post('v1/mock-interview/sessions/{id}/report/stream', [MockInterviewController::class, 'streamReport'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.report.stream');

Route::get('v1/mock-interview/sessions/{id}/report', [MockInterviewController::class, 'showReport'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('mock-interview.sessions.report.show');

// ============================================================
// NHÓM 5: NHÀ TUYỂN DỤNG — Xem hồ sơ ứng viên (vai_tro = 1)
// ============================================================

// Danh sách hồ sơ công khai (có lọc, tìm kiếm, phân trang)
Route::get('v1/nha-tuyen-dung/ho-sos', [NhaTuyenDungHoSoController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:applications'])
    ->name('nha-tuyen-dung.ho-sos.index');

// Xem chi tiết hồ sơ công khai
Route::get('v1/nha-tuyen-dung/ho-sos/{id}', [NhaTuyenDungHoSoController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:applications'])
    ->name('nha-tuyen-dung.ho-sos.show');

// Xem file CV công khai của ứng viên
Route::get('v1/nha-tuyen-dung/ho-sos/{id}/cv', [NhaTuyenDungHoSoController::class, 'downloadCv'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:applications'])
    ->name('nha-tuyen-dung.ho-sos.cv');


// ============================================================
// NHÓM 6: ADMIN — Quản lý hồ sơ ứng viên (vai_tro = 2)
// ============================================================

// Thống kê hồ sơ (⚠️ đặt trước /{id} để tránh conflict)
Route::get('v1/admin/ho-sos/thong-ke', [AdminHoSoController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.thong-ke');

// Danh sách hồ sơ lưu trữ
Route::get('v1/admin/ho-sos/da-xoa', [AdminHoSoController::class, 'danhSachDaXoa'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.da-xoa');

// Danh sách tất cả hồ sơ (có lọc, tìm kiếm, phân trang)
Route::get('v1/admin/ho-sos', [AdminHoSoController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.index');

// Xem chi tiết hồ sơ theo ID
Route::get('v1/admin/ho-sos/{id}', [AdminHoSoController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.show');

// Đổi trạng thái hồ sơ (công khai/ẩn)
Route::patch('v1/admin/ho-sos/{id}/trang-thai', [AdminHoSoController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.doi-trang-thai');

// Lưu trữ hồ sơ (soft delete — có thể khôi phục)
Route::delete('v1/admin/ho-sos/{id}', [AdminHoSoController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.destroy');

// Khôi phục hồ sơ đã lưu trữ
Route::patch('v1/admin/ho-sos/{id}/khoi-phuc', [AdminHoSoController::class, 'khoiPhuc'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.khoi-phuc');

// Xóa vĩnh viễn hồ sơ đã lưu trữ
Route::delete('v1/admin/ho-sos/{id}/xoa-vinh-vien', [AdminHoSoController::class, 'xoaVinhVien'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:profiles'])
    ->name('admin.ho-sos.xoa-vinh-vien');


// ============================================================
// NHÓM 7: PUBLIC — Danh mục ngành nghề (không cần xác thực)
// ============================================================

// Danh sách ngành nghề hiển thị (dạng phẳng)
Route::get('v1/nganh-nghes', [NganhNgheController::class, 'index'])
    ->name('nganh-nghes.index');

// Danh sách ngành nghề dạng cây (cha-con)
Route::get('v1/nganh-nghes/cay', [NganhNgheController::class, 'cay'])
    ->name('nganh-nghes.cay');

// Chi tiết ngành nghề
Route::get('v1/nganh-nghes/{id}', [NganhNgheController::class, 'show'])
    ->name('nganh-nghes.show');


// ============================================================
// NHÓM 8: ADMIN — Quản lý ngành nghề (vai_tro = 2)
// ============================================================

// Thống kê ngành nghề (⚠️ đặt trước /{id})
Route::get('v1/admin/nganh-nghes/thong-ke', [AdminNganhNgheController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.thong-ke');

// Danh sách tất cả ngành nghề (kể cả ẩn)
Route::get('v1/admin/nganh-nghes', [AdminNganhNgheController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.index');

// Tạo ngành nghề mới
Route::post('v1/admin/nganh-nghes', [AdminNganhNgheController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.store');

// Chi tiết ngành nghề theo ID
Route::get('v1/admin/nganh-nghes/{id}', [AdminNganhNgheController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.show');

// Cập nhật ngành nghề
Route::put('v1/admin/nganh-nghes/{id}', [AdminNganhNgheController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.update');

// Đổi trạng thái (hiển thị/ẩn)
Route::patch('v1/admin/nganh-nghes/{id}/trang-thai', [AdminNganhNgheController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.doi-trang-thai');

// Xoá ngành nghề
Route::delete('v1/admin/nganh-nghes/{id}', [AdminNganhNgheController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:industries'])
    ->name('admin.nganh-nghes.destroy');


// ============================================================
// NHÓM 9: PUBLIC — Danh mục kỹ năng (không cần xác thực)
// ============================================================

// Danh sách kỹ năng
Route::get('v1/ky-nangs', [KyNangController::class, 'index'])
    ->name('ky-nangs.index');

// Chi tiết kỹ năng
Route::get('v1/ky-nangs/{id}', [KyNangController::class, 'show'])
    ->name('ky-nangs.show');

Route::get('v1/cv-templates', [CvTemplateController::class, 'publicIndex'])
    ->name('cv-templates.index');


// ============================================================
// NHÓM 10: ADMIN — Quản lý kỹ năng (vai_tro = 2)
// ============================================================

// Thống kê kỹ năng (⚠️ đặt trước /{id})
Route::get('v1/admin/ky-nangs/thong-ke', [AdminKyNangController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:skills'])
    ->name('admin.ky-nangs.thong-ke');

// Danh sách tất cả kỹ năng
Route::get('v1/admin/ky-nangs', [AdminKyNangController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:skills,user_skills'])
    ->name('admin.ky-nangs.index');

// Tạo kỹ năng mới
Route::post('v1/admin/ky-nangs', [AdminKyNangController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:skills'])
    ->name('admin.ky-nangs.store');

// Chi tiết kỹ năng theo ID
Route::get('v1/admin/ky-nangs/{id}', [AdminKyNangController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:skills'])
    ->name('admin.ky-nangs.show');

// Cập nhật kỹ năng
Route::put('v1/admin/ky-nangs/{id}', [AdminKyNangController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:skills'])
    ->name('admin.ky-nangs.update');

// Xoá kỹ năng
Route::delete('v1/admin/ky-nangs/{id}', [AdminKyNangController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:skills'])
    ->name('admin.ky-nangs.destroy');


// ============================================================
// NHÓM 11: ỨNG VIÊN — Kỹ năng cá nhân (vai_tro = 0)
// ============================================================

// Danh sách kỹ năng của mình
Route::get('v1/ung-vien/ky-nangs', [NguoiDungKyNangController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.index');

// Thêm kỹ năng vào hồ sơ
Route::post('v1/ung-vien/ky-nangs', [NguoiDungKyNangController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.store');

// Cập nhật mức độ / kinh nghiệm / chứng chỉ
Route::put('v1/ung-vien/ky-nangs/{id}', [NguoiDungKyNangController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.update');

// Cập nhật kèm upload ảnh chứng chỉ (dùng POST + _method=PUT cho multipart/form-data)
Route::post('v1/ung-vien/ky-nangs/{id}', [NguoiDungKyNangController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.update-multipart');

// Xoá kỹ năng khỏi hồ sơ
Route::delete('v1/ung-vien/ky-nangs/{id}', [NguoiDungKyNangController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.destroy');


// ============================================================
// NHÓM 19: ỨNG VIÊN — Quản lý tin lưu trữ (vai_tro = 0)
// ============================================================

Route::get('v1/ung-vien/tin-da-luu', [UngVienLuuTinController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.luu-tins.index');

Route::post('v1/ung-vien/tin-da-luu/{tin_id}/toggle', [UngVienLuuTinController::class, 'toggle'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.luu-tins.toggle');

Route::get('v1/ung-vien/re-engagement/insights', [ReEngagementController::class, 'insights'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.re-engagement.insights');

Route::get('v1/ung-vien/cong-ty-theo-doi', [UngVienTheoDoiCongTyController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.theo-doi-cong-tys.index');

Route::post('v1/ung-vien/cong-ty-theo-doi/{cong_ty_id}/toggle', [UngVienTheoDoiCongTyController::class, 'toggle'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.theo-doi-cong-tys.toggle');


// ============================================================
// NHÓM 21: ỨNG VIÊN — Nộp hồ sơ (Ứng tuyển) (vai_tro = 0)
// ============================================================

Route::get('v1/ung-vien/ung-tuyens', [UngVienUngTuyenController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.index');

Route::post('v1/ung-vien/ung-tuyens', [UngVienUngTuyenController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.store');

Route::patch('v1/ung-vien/ung-tuyens/{id}', [UngVienUngTuyenController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.update');

Route::patch('v1/ung-vien/ung-tuyens/{id}/xac-nhan-phong-van', [UngVienUngTuyenController::class, 'xacNhanPhongVan'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.confirm-interview');

Route::patch('v1/ung-vien/ung-tuyens/{id}/interview-rounds/{roundId}/xac-nhan', [UngVienUngTuyenController::class, 'xacNhanVongPhongVan'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.interview-rounds.confirm');

Route::patch('v1/ung-vien/ung-tuyens/{id}/phan-hoi-offer', [UngVienUngTuyenController::class, 'phanHoiOffer'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.respond-offer');

Route::get('v1/ung-vien/ung-tuyens/{id}/onboarding', [OnboardingController::class, 'showForCandidate'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.onboarding.show');

Route::get('v1/ung-vien/ung-tuyens/{id}/export/{document}', [ApplicationExportController::class, 'candidate'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.export');

Route::patch('v1/ung-vien/ung-tuyens/{id}/onboarding/tasks/{taskId}', [OnboardingController::class, 'updateCandidateTask'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.onboarding.tasks.update');

Route::patch('v1/ung-vien/ung-tuyens/{id}/rut-don', [UngVienUngTuyenController::class, 'rutDon'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.withdraw');

Route::get('v1/ung-vien/ung-tuyens/{id}/xac-nhan-phong-van/email/{action}', [UngVienUngTuyenController::class, 'xacNhanPhongVanQuaEmail'])
    ->middleware('signed')
    ->name('ung-vien.ung-tuyens.confirm-interview-email');

Route::get('v1/ung-vien/ung-tuyens/{id}/interview-rounds/{roundId}/xac-nhan/email/{action}', [UngVienUngTuyenController::class, 'xacNhanVongPhongVanQuaEmail'])
    ->middleware('signed')
    ->name('ung-vien.ung-tuyens.interview-rounds.confirm-email');

Route::get('v1/ung-vien/ung-tuyens/{id}/phan-hoi-offer/email/{action}', [UngVienUngTuyenController::class, 'phanHoiOfferQuaEmail'])
    ->middleware('signed')
    ->name('ung-vien.ung-tuyens.confirm-offer-email');

Route::post('v1/ung-vien/ung-tuyens/generate-cover-letter', [CoverLetterController::class, 'generate'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.generate-cover-letter');

Route::patch('v1/ung-vien/ung-tuyens/{id}/confirm-cover-letter', [CoverLetterController::class, 'confirm'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.confirm-cover-letter');


// ============================================================
// NHÓM 24: ỨNG VIÊN — Việc Làm Gợi Ý (AI Matching)
// ============================================================

Route::get('v1/ung-vien/ket-qua-matchings', [UngVienKetQuaMatchingController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ket-qua-matchings.index');



// ============================================================
// NHÓM 26: ỨNG VIÊN — BÁO CÁO ĐỊNH HƯỚNG NGHỀ (AI Tư vấn)
// ============================================================

Route::get('v1/ung-vien/tu-van-nghe-nghieps', [UngVienTuVanNgheNghiepController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.tu-van-nghe-nghieps.index');

Route::delete('v1/ung-vien/tu-van-nghe-nghieps/{reportId}', [CareerReportController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.tu-van-nghe-nghieps.destroy');

// ============================================================
// NHÓM 12: ADMIN — Quản lý kỹ năng người dùng (vai_tro = 2)
// ============================================================

// Thống kê (⚠️ đặt trước route có param)
Route::get('v1/admin/nguoi-dung-ky-nangs/thong-ke', [AdminNguoiDungKyNangController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:user_skills'])
    ->name('admin.nguoi-dung-ky-nangs.thong-ke');

// Danh sách tất cả bản ghi người dùng — kỹ năng
Route::get('v1/admin/nguoi-dung-ky-nangs', [AdminNguoiDungKyNangController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:user_skills'])
    ->name('admin.nguoi-dung-ky-nangs.index');

// Kỹ năng của 1 người dùng cụ thể
Route::get('v1/admin/nguoi-dung-ky-nangs/nguoi-dung/{nguoiDungId}', [AdminNguoiDungKyNangController::class, 'kyNangCuaNguoiDung'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:user_skills'])
    ->name('admin.nguoi-dung-ky-nangs.nguoi-dung');


// ============================================================
// NHÓM 13: PUBLIC — Công ty (không cần xác thực)
// ============================================================

// Danh sách công ty (đang hoạt động)
Route::get('v1/cong-tys', [CongTyController::class, 'index'])
    ->name('cong-tys.index');

// Chi tiết công ty
Route::get('v1/cong-tys/{id}', [CongTyController::class, 'show'])
    ->name('cong-tys.show');

// Logo công ty public
Route::get('v1/cong-ty-logo', [CongTyController::class, 'logo'])
    ->name('cong-tys.logo');

// ============================================================
// NHÓM 16: PUBLIC — Tin tuyển dụng (không cần xác thực)
// ============================================================

Route::get('v1/tin-tuyen-dungs', [TinTuyenDungController::class, 'index'])
    ->name('tin-tuyen-dungs.index');

Route::get('v1/tin-tuyen-dungs/{id}', [TinTuyenDungController::class, 'show'])
    ->name('tin-tuyen-dungs.show');


// ============================================================
// NHÓM 14: NHÀ TUYỂN DỤNG — Quản lý công ty (vai_tro = 1)
// ============================================================

// Xem công ty của mình
Route::get('v1/nha-tuyen-dung/cong-ty', [NhaTuyenDungCongTyController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.cong-ty.show');

// Tạo công ty
Route::post('v1/nha-tuyen-dung/cong-ty', [NhaTuyenDungCongTyController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.cong-ty.store');

// Cập nhật công ty
Route::put('v1/nha-tuyen-dung/cong-ty', [NhaTuyenDungCongTyController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:company_profile'])
    ->name('nha-tuyen-dung.cong-ty.update');

Route::get('v1/nha-tuyen-dung/cong-ty/thanh-viens', [NhaTuyenDungCongTyController::class, 'members'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members');

Route::post('v1/nha-tuyen-dung/cong-ty/thanh-viens', [NhaTuyenDungCongTyController::class, 'addMember'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.store');

Route::patch('v1/nha-tuyen-dung/cong-ty/thanh-viens/{memberId}/vai-tro', [NhaTuyenDungCongTyController::class, 'updateMemberRole'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.update-role');

Route::put('v1/nha-tuyen-dung/cong-ty/thanh-viens/{memberId}', [NhaTuyenDungCongTyController::class, 'updateMember'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.update');

Route::patch('v1/nha-tuyen-dung/cong-ty/thanh-viens/{memberId}/khoa', [NhaTuyenDungCongTyController::class, 'toggleMemberStatus'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.toggle-status');

Route::get('v1/nha-tuyen-dung/cong-ty/thanh-viens/{memberId}/permissions', [NhaTuyenDungCongTyController::class, 'memberPermissions'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.permissions');

Route::put('v1/nha-tuyen-dung/cong-ty/thanh-viens/{memberId}/permissions', [NhaTuyenDungCongTyController::class, 'updateMemberPermissions'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.permissions.update');

Route::post('v1/nha-tuyen-dung/cong-ty/thanh-viens/permissions/definitions', [NhaTuyenDungCongTyController::class, 'createHrPermissionDefinition'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.permissions.definitions.store');

Route::delete('v1/nha-tuyen-dung/cong-ty/thanh-viens/{memberId}', [NhaTuyenDungCongTyController::class, 'removeMember'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.members.destroy');

Route::get('v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo', [NhaTuyenDungCongTyController::class, 'internalRoles'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.internal-roles.index');

Route::post('v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo', [NhaTuyenDungCongTyController::class, 'createInternalRole'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.internal-roles.store');

Route::patch('v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo/{roleId}', [NhaTuyenDungCongTyController::class, 'updateInternalRole'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.internal-roles.update');

Route::delete('v1/nha-tuyen-dung/cong-ty/vai-tro-noi-bo/{roleId}', [NhaTuyenDungCongTyController::class, 'deleteInternalRole'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:members'])
    ->name('nha-tuyen-dung.cong-ty.internal-roles.destroy');

Route::get('v1/nha-tuyen-dung/cong-ty/hr-audit-logs', [NhaTuyenDungCongTyController::class, 'hrAuditLogs'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:audit_logs'])
    ->name('nha-tuyen-dung.cong-ty.hr-audit-logs.index');

Route::get('v1/nha-tuyen-dung/audit-logs', [NhaTuyenDungAuditLogController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:audit_logs'])
    ->name('nha-tuyen-dung.audit-logs.index');

// ============================================================
// NHÓM 17: NHÀ TUYỂN DỤNG — Quản lý tin tuyển dụng (vai_tro = 1)
// ============================================================

Route::get('v1/nha-tuyen-dung/tin-tuyen-dungs', [NhaTuyenDungTinTuyenDungController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.index');

Route::post('v1/nha-tuyen-dung/tin-tuyen-dungs', [NhaTuyenDungTinTuyenDungController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.store');

Route::get('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}', [NhaTuyenDungTinTuyenDungController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.show');

Route::post('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/sponsor', [NhaTuyenDungTinTuyenDungController::class, 'sponsor'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.sponsor');

Route::put('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}', [NhaTuyenDungTinTuyenDungController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.update');

Route::patch('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/trang-thai', [NhaTuyenDungTinTuyenDungController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.doi-trang-thai');

Route::delete('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}', [NhaTuyenDungTinTuyenDungController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.destroy');

Route::post('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/parse', [JdParsingController::class, 'parse'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.parse');

Route::get('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/shortlist', [NhaTuyenDungShortlistController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.shortlist');

Route::post('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/shortlist/compare', [NhaTuyenDungShortlistController::class, 'compare'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:jobs'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.shortlist.compare');


// ============================================================
// NHÓM 22: NHÀ TUYỂN DỤNG — Duyệt hồ sơ ứng tuyển (vai_tro = 1)
// ============================================================

Route::get('v1/nha-tuyen-dung/ung-tuyens', [NhaTuyenDungUngTuyenController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:applications'])
    ->name('nha-tuyen-dung.ung-tuyens.index');

Route::get('v1/nha-tuyen-dung/ung-tuyens/notification-templates', [NhaTuyenDungUngTuyenController::class, 'notificationTemplates'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:applications'])
    ->name('nha-tuyen-dung.ung-tuyens.notification-templates');

Route::get('v1/nha-tuyen-dung/ung-tuyens/{id}/interview-rounds', [NhaTuyenDungUngTuyenController::class, 'interviewRounds'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.interview-rounds.index');

Route::post('v1/nha-tuyen-dung/ung-tuyens/{id}/interview-rounds', [NhaTuyenDungUngTuyenController::class, 'storeInterviewRound'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.interview-rounds.store');

Route::put('v1/nha-tuyen-dung/ung-tuyens/{id}/interview-rounds/{roundId}', [NhaTuyenDungUngTuyenController::class, 'updateInterviewRound'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.interview-rounds.update');

Route::delete('v1/nha-tuyen-dung/ung-tuyens/{id}/interview-rounds/{roundId}', [NhaTuyenDungUngTuyenController::class, 'destroyInterviewRound'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.interview-rounds.destroy');

Route::post('v1/nha-tuyen-dung/ung-tuyens/{id}/interview-copilot/generate', [NhaTuyenDungUngTuyenController::class, 'generateInterviewCopilot'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.interview-copilot.generate');

Route::post('v1/nha-tuyen-dung/ung-tuyens/{id}/interview-copilot/evaluate', [NhaTuyenDungUngTuyenController::class, 'evaluateInterviewCopilot'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.interview-copilot.evaluate');

Route::post('v1/nha-tuyen-dung/ung-tuyens/{id}/gui-offer', [NhaTuyenDungUngTuyenController::class, 'guiOffer'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:offers'])
    ->name('nha-tuyen-dung.ung-tuyens.gui-offer');

Route::get('v1/nha-tuyen-dung/ung-tuyens/{id}/onboarding', [OnboardingController::class, 'showForEmployer'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:onboarding'])
    ->name('nha-tuyen-dung.ung-tuyens.onboarding.show');

Route::get('v1/nha-tuyen-dung/ung-tuyens/{id}/export/{document}', [ApplicationExportController::class, 'employer'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:exports'])
    ->name('nha-tuyen-dung.ung-tuyens.export');

Route::put('v1/nha-tuyen-dung/ung-tuyens/{id}/onboarding', [OnboardingController::class, 'updateForEmployer'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:onboarding'])
    ->name('nha-tuyen-dung.ung-tuyens.onboarding.update');

Route::post('v1/nha-tuyen-dung/ung-tuyens/{id}/onboarding/tasks', [OnboardingController::class, 'storeTask'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:onboarding'])
    ->name('nha-tuyen-dung.ung-tuyens.onboarding.tasks.store');

Route::put('v1/nha-tuyen-dung/ung-tuyens/{id}/onboarding/tasks/{taskId}', [OnboardingController::class, 'updateTask'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:onboarding'])
    ->name('nha-tuyen-dung.ung-tuyens.onboarding.tasks.update');

Route::delete('v1/nha-tuyen-dung/ung-tuyens/{id}/onboarding/tasks/{taskId}', [OnboardingController::class, 'destroyTask'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:onboarding'])
    ->name('nha-tuyen-dung.ung-tuyens.onboarding.tasks.destroy');

Route::patch('v1/nha-tuyen-dung/ung-tuyens/{id}/trang-thai', [NhaTuyenDungUngTuyenController::class, 'updateTrangThai'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:applications'])
    ->name('nha-tuyen-dung.ung-tuyens.update-trang-thai');

Route::post('v1/nha-tuyen-dung/ung-tuyens/{id}/gui-lai-email-phong-van', [NhaTuyenDungUngTuyenController::class, 'guiLaiEmailPhongVan'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung', 'company_role:permission:interviews'])
    ->name('nha-tuyen-dung.ung-tuyens.gui-lai-email-phong-van');


// ============================================================
// NHÓM 15: ADMIN — Quản lý công ty (vai_tro = 2)
// ============================================================

// Thống kê (⚠️ đặt trước /{id})
Route::get('v1/admin/cong-tys/thong-ke', [AdminCongTyController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies'])
    ->name('admin.cong-tys.thong-ke');

// Danh sách tất cả
Route::get('v1/admin/cong-tys', [AdminCongTyController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies,applications,jobs'])
    ->name('admin.cong-tys.index');

// Tạo công ty (Admin)
Route::post('v1/admin/cong-tys', [AdminCongTyController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies'])
    ->name('admin.cong-tys.store');

// Chi tiết
Route::get('v1/admin/cong-tys/{id}', [AdminCongTyController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies'])
    ->name('admin.cong-tys.show');

// Cập nhật
Route::put('v1/admin/cong-tys/{id}', [AdminCongTyController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies'])
    ->name('admin.cong-tys.update');

// Đổi trạng thái
Route::patch('v1/admin/cong-tys/{id}/trang-thai', [AdminCongTyController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies'])
    ->name('admin.cong-tys.doi-trang-thai');

// Xoá
Route::delete('v1/admin/cong-tys/{id}', [AdminCongTyController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:companies'])
    ->name('admin.cong-tys.destroy');


// ============================================================
// NHÓM 18: ADMIN — Quản lý tin tuyển dụng (vai_tro = 2)
// ============================================================

Route::get('v1/admin/tin-tuyen-dungs/thong-ke', [AdminTinTuyenDungController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.thong-ke');

Route::get('v1/admin/tin-tuyen-dungs', [AdminTinTuyenDungController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.index');

Route::post('v1/admin/tin-tuyen-dungs', [AdminTinTuyenDungController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.store');

Route::get('v1/admin/tin-tuyen-dungs/{id}', [AdminTinTuyenDungController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.show');

Route::put('v1/admin/tin-tuyen-dungs/{id}', [AdminTinTuyenDungController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.update');

Route::patch('v1/admin/tin-tuyen-dungs/{id}/trang-thai', [AdminTinTuyenDungController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.doi-trang-thai');

Route::delete('v1/admin/tin-tuyen-dungs/{id}', [AdminTinTuyenDungController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:jobs'])
    ->name('admin.tin-tuyen-dungs.destroy');


// ============================================================
// NHÓM 19: ADMIN — Quản lý template CV
// ============================================================

Route::get('v1/admin/cv-templates', [CvTemplateController::class, 'adminIndex'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:cv_templates'])
    ->name('admin.cv-templates.index');

Route::post('v1/admin/cv-templates', [CvTemplateController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:cv_templates'])
    ->name('admin.cv-templates.store');

Route::put('v1/admin/cv-templates/{id}', [CvTemplateController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:cv_templates'])
    ->name('admin.cv-templates.update');

Route::patch('v1/admin/cv-templates/{id}/trang-thai', [CvTemplateController::class, 'toggleStatus'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:cv_templates'])
    ->name('admin.cv-templates.toggle-status');

Route::delete('v1/admin/cv-templates/{id}', [CvTemplateController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:cv_templates'])
    ->name('admin.cv-templates.destroy');


// ============================================================
// NHÓM 20: ADMIN — Thống kê lưu tin (vai_tro = 2)
// ============================================================

Route::get('v1/admin/luu-tins/thong-ke', [AdminLuuTinController::class, 'topLuuTin'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:stats'])
    ->name('admin.luu-tins.thong-ke');


// ============================================================
// NHÓM 23: ADMIN — Quản lý ứng tuyển (vai_tro = 2)
// ============================================================

Route::get('v1/admin/ung-tuyens/thong-ke', [AdminUngTuyenController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:applications,stats'])
    ->name('admin.ung-tuyens.thong-ke');

Route::get('v1/admin/ung-tuyens', [AdminUngTuyenController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:applications'])
    ->name('admin.ung-tuyens.index');

// ============================================================
// NHÓM 25: ADMIN — Quản lý lịch sử AI Matching
// ============================================================

Route::get('v1/admin/ket-qua-matchings/thong-ke', [AdminKetQuaMatchingController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:matchings,stats'])
    ->name('admin.ket-qua-matchings.thong-ke');

Route::get('v1/admin/ket-qua-matchings', [AdminKetQuaMatchingController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:matchings'])
    ->name('admin.ket-qua-matchings.index');

// ============================================================
// NHÓM 27: ADMIN — Quản lý Hồ sơ Phân Tích (AI Advising)
// ============================================================

Route::get('v1/admin/tu-van-nghe-nghieps/thong-ke', [AdminTuVanNgheNghiepController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:career_advising,stats'])
    ->name('admin.tu-van-nghe-nghieps.thong-ke');

Route::get('v1/admin/tu-van-nghe-nghieps', [AdminTuVanNgheNghiepController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin', 'admin_permission:career_advising'])
    ->name('admin.tu-van-nghe-nghieps.index');
