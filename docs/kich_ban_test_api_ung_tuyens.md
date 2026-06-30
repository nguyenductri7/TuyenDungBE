# Kịch bản Test API Ứng Tuyển (`ung_tuyens`)

Tài liệu này quy chiếu các Kịch bản kiểm thử cho toàn bộ luồng Ứng Viên nộp Hồ Sơ vào một Tin Tuyển Dụng, và quá trình NTD/Admin kiểm duyệt.

---

## 1️⃣ ỨNG VIÊN (Yêu cầu đăng nhập, `vai_tro = 0`)

### SC1.1: Ứng viên xem lịch sử ứng tuyển của chính mình
*   **Request:** `GET /api/v1/ung-vien/ung-tuyens`
*   **Expect:** `200 OK`. Trả về danh sách công việc đã nộp (gồm thông tin Công Ty, tên Tin Tuyển Dụng, loại CV đã dùng, và trạng thái duyệt). Pagination hợp lệ.

### SC1.2: Ứng viên nộp CV thành công
*   **Request:** `POST /api/v1/ung-vien/ung-tuyens`
    *   Body JSON: `{"tin_tuyen_dung_id": 1, "ho_so_id": 1}`
*   **Điều kiện cần:** `ho_so_id` thuộc về người này, `tin_tuyen_dung_id` đang mở cửa.
*   **Expect:** `201 Created`. Nhận được thông báo "Nộp hồ sơ thành công". Record được lưu trữ vào DB với `trang_thai = 0` (Chờ duyệt).

### SC1.3: Cố tình nộp tiếp cùng 1 tin (Dù dùng CV khác)
*   **Request:** `POST /api/v1/ung-vien/ung-tuyens`
    *   Body: `{"tin_tuyen_dung_id": 1, "ho_so_id": 2}`
*   **Expect:** `400 Bad Request`. Báo lỗi "Bạn đã nộp hồ sơ vào tin này rồi, không thể nộp thêm."

### SC1.4: Cố tình nộp bằng Hồ sơ của người khác (Hack)
*   **Request:** `POST /api/v1/ung-vien/ung-tuyens`
    *   Body: `{"tin_tuyen_dung_id": 2, "ho_so_id": 999}` *(Giả sử 999 là Của UV khác)*
*   **Expect:** `422 Unprocessable Entity`. Fail qua tầng `FormRequest` vì `exists:ho_sos...nguoi_dung_id`.

### SC1.5: Nộp vào 1 Tin tuyển dụng Đã Tạm Ngưng
*   **Expect:** `400 Bad Request`. Trả về "Tin tuyển dụng đã hết hạn hoặc tạm ngưng."

---

## 2️⃣ NHÀ TUYỂN DỤNG (`vai_tro = 1`)

### SC2.1: NTD xem danh sách đơn ứng tuyển vào Công Ty Của Họ
*   **Request:** `GET /api/v1/nha-tuyen-dung/ung-tuyens`
*   **Expect:** `200 OK`. Chỉ trả về các đơn (`ung_tuyens.ho_so.file_cv`) được nộp vào các tin do NTD sở hữu.
*   **Bộ lọc test:** Thử gắn thêm params: `?tin_tuyen_dung_id=1` -> Chỉ show đơn của Tin 1. Gắn thêm `?trang_thai=0` -> Chỉ show đơn "Chờ duyệt".

### SC2.2: NTD chuyển trạng thái ứng viên (Đậu / Rớt)
*   **Request:** `PATCH /api/v1/nha-tuyen-dung/ung-tuyens/{id}/trang-thai`
    *   Body JSON: `{"trang_thai": 2}` (2 là Chấp nhận/Đậu)
*   **Expect:** `200 OK`. Lịch sử ứng tuyển chuyển thành Chấp nhận.

### SC2.3: NTD cố sửa trạng thái đơn của công ty Đối Thủ
*   **Request:** `PATCH /api/v1/nha-tuyen-dung/ung-tuyens/{id_cua_cty_khac}/trang-thai`
*   **Expect:** `404 Not Found` (Vì Scope Role khóa cứng chỉ quét id thuộc công ty mình).

### SC2.4: Nhập trạng thái ảo ma không có trong hệ thống
*   **Request:** `PATCH /api/v1/nha-tuyen-dung/ung-tuyens/1/trang-thai`
    *   Body JSON: `{"trang_thai": 99}`
*   **Expect:** `422 Unprocessable Entity` - Lỗi trạng thái không hợp lệ.

---

## 3️⃣ ADMIN (`vai_tro = 2`)

### SC3.1: Admin xem Tổng quan toàn bộ đơn ứng tuyển (Dashboard)
*   **Request:** `GET /api/v1/admin/ung-tuyens`
*   **Expect:** `200 OK`. Xem được tất cả mọi đơn ứng tuyển từ mọi ứng viên và mọi công ty.

### SC3.2: Admin xem Thống Kê trạng thái phễu tuyển dụng
*   **Request:** `GET /api/v1/admin/ung-tuyens/thong-ke`
*   **Expect:** `200 OK`. Trả về JSON chứa `tong_don_ung_tuyen`, và biến `chi_tiet` chứa số lượng Đang chờ, Đã xem, Chấp nhận, Từ chối.
