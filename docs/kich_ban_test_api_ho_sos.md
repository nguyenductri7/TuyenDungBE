# 📋 Kịch Bản Kiểm Thử API — Module Hồ Sơ (ho_sos) v2

**Dự án:** Khóa luận tốt nghiệp (KLTN)  
**Module:** Quản lý Hồ sơ ứng viên  
**Base URL:** `http://localhost:8000/api/v1`  
**Công nghệ:** Laravel 12 + Laravel Sanctum (Token-based Auth)  
**Ngày lập:** 10/03/2026  
**Phiên bản:** v2 — Thêm NTD xem hồ sơ, Admin xoá mềm, bỏ Admin cập nhật

---

## 1. Thông tin chung

### 1.1 Phân quyền vai trò

| Giá trị `vai_tro` | Tên vai trò | Quyền trên hồ sơ |
|:-----------------:|-------------|-------------------|
| `0` | Ứng viên (UV) | CRUD hồ sơ của mình |
| `1` | Nhà tuyển dụng (NTD) | **Xem** hồ sơ công khai |
| `2` | Quản trị viên (Admin) | Xem, đổi trạng thái, xoá mềm, khôi phục |

### 1.2 Dữ liệu tài khoản test

| STT | Vai trò | Email | Mật khẩu | Trạng thái |
|-----|---------|-------|----------|-----------| 
| 1 | Admin | `admin@kltn.com` | `Admin@123` | Active |
| 2 | NTD | `tuyen.dung1@kltn.com` | `NTD@123456` | Active |
| 3 | UV | `ung.vien1@kltn.com` | `UV@123456` | Active |
| 4 | UV | `ung.vien2@kltn.com` | `UV@123456` | Active |

### 1.3 Header chung

| Header | Giá trị |
|--------|--------|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |
| `Authorization` | `Bearer {access_token}` *(chỉ route cần xác thực)* |

### 1.4 Giá trị hợp lệ cho `trinh_do`

| Giá trị | Nhãn hiển thị |
|---------|--------------|
| `trung_hoc` | Trung học |
| `trung_cap` | Trung cấp |
| `cao_dang` | Cao đẳng |
| `dai_hoc` | Đại học |
| `thac_si` | Thạc sĩ |
| `tien_si` | Tiến sĩ |
| `khac` | Khác |

---

## 2. Ứng viên — Tạo hồ sơ

**Endpoint:** `POST /api/v1/ung-vien/ho-sos`

### TC-HS-UV-01 — Tạo hồ sơ thành công (đầy đủ thông tin)

| Mục | Nội dung |
|-----|---------|
| **Điều kiện** | Đăng nhập bằng `ung.vien1@kltn.com` |

**Request Body:**
```json
{
    "tieu_de_ho_so": "Hồ sơ DevOps Engineer",
    "muc_tieu_nghe_nghiep": "Mong muốn ứng tuyển vị trí DevOps tại công ty công nghệ.",
    "trinh_do": "dai_hoc",
    "kinh_nghiem_nam": 3,
    "mo_ta_ban_than": "Thành thạo Docker, Kubernetes, CI/CD.",
    "trang_thai": 1
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `201 Created` |
| `success` | `true` |
| `data.nguoi_dung_id` | ID của ứng viên đang đăng nhập |

### TC-HS-UV-02 — Tạo hồ sơ tối thiểu (chỉ tiêu đề)

**Request Body:** `{ "tieu_de_ho_so": "Hồ sơ nhanh" }`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `201 Created` |
| `data.trang_thai` | `1` (mặc định) |

### TC-HS-UV-03 — Thất bại: Thiếu tiêu đề ❌

**Request Body:** `{ "muc_tieu_nghe_nghiep": "Tìm việc" }`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `422` |
| `errors.tieu_de_ho_so` | Có thông báo lỗi |

### TC-HS-UV-04 — Thất bại: Trình độ không hợp lệ ❌

**Request Body:** `{ "tieu_de_ho_so": "Test", "trinh_do": "sai_value" }`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `422` |

### TC-HS-UV-05 — NTD không có quyền tạo ❌

| Điều kiện | Đăng nhập bằng `tuyen.dung1@kltn.com` |
|-----------|----------------------------------------|
| HTTP Status | `403 Forbidden` |

### TC-HS-UV-06 — Chưa đăng nhập ❌

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `401 Unauthorized` |

---

## 3. Ứng viên — Danh sách & Chi tiết

### TC-HS-UV-07 — Xem danh sách hồ sơ của mình

**URL:** `GET /api/v1/ung-vien/ho-sos`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Kết quả | Chỉ chứa hồ sơ của ứng viên đang đăng nhập |

### TC-HS-UV-08 — Lọc theo trạng thái

**URL:** `GET /api/v1/ung-vien/ho-sos?trang_thai=1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Tất cả bản ghi | `trang_thai = 1` |

### TC-HS-UV-09 — Xem chi tiết hồ sơ của mình

**URL:** `GET /api/v1/ung-vien/ho-sos/1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |

### TC-HS-UV-10 — Xem hồ sơ người khác → 404 ❌

**URL:** `GET /api/v1/ung-vien/ho-sos/4` (ID 4 thuộc UV2)

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `404 Not Found` |

---

## 4. Ứng viên — Cập nhật hồ sơ

### TC-HS-UV-11 — Cập nhật thành công

**URL:** `PUT /api/v1/ung-vien/ho-sos/1`

```json
{ "tieu_de_ho_so": "Hồ sơ Backend (Đã cập nhật)", "trinh_do": "thac_si" }
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |

### TC-HS-UV-12 — Cập nhật hồ sơ người khác → 404 ❌

**URL:** `PUT /api/v1/ung-vien/ho-sos/4`

| HTTP Status | `404 Not Found` |

---

## 5. Ứng viên — Đổi trạng thái & Xoá

### TC-HS-UV-13 — Ẩn hồ sơ thành công

**URL:** `PATCH /api/v1/ung-vien/ho-sos/1/trang-thai`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| `message` | `"Ẩn hồ sơ thành công."` |
| `data.trang_thai` | `0` |

### TC-HS-UV-14 — Công khai lại hồ sơ (gọi lần 2)

| `data.trang_thai` | `1` |

### TC-HS-UV-15 — Xoá hồ sơ thành công

**URL:** `DELETE /api/v1/ung-vien/ho-sos/3`

| HTTP Status | `200 OK` |

### TC-HS-UV-16 — Xoá hồ sơ người khác → 404 ❌

**URL:** `DELETE /api/v1/ung-vien/ho-sos/4`

| HTTP Status | `404 Not Found` |

---

## 6. NHÀ TUYỂN DỤNG — Xem hồ sơ ứng viên

**Quyền truy cập:** NTD (`vai_tro = 1`)  
**Ghi chú:** NTD chỉ xem được hồ sơ **công khai** (`trang_thai = 1`). Không có quyền tạo/sửa/xoá.

### TC-HS-NTD-01 — Duyệt danh sách hồ sơ công khai

**URL:** `GET /api/v1/nha-tuyen-dung/ho-sos`  
**Điều kiện:** Đăng nhập bằng `tuyen.dung1@kltn.com`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Tất cả bản ghi | `trang_thai = 1` |
| Relation | Có thông tin `nguoi_dung` (tên, email, SĐT, ảnh) |

### TC-HS-NTD-02 — Lọc theo trình độ

**URL:** `GET /api/v1/nha-tuyen-dung/ho-sos?trinh_do=dai_hoc`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Tất cả bản ghi | `trinh_do = "dai_hoc"` |

### TC-HS-NTD-03 — Lọc theo khoảng kinh nghiệm

**URL:** `GET /api/v1/nha-tuyen-dung/ho-sos?kinh_nghiem_tu=2&kinh_nghiem_den=5`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Tất cả bản ghi | `kinh_nghiem_nam >= 2` và `<= 5` |

### TC-HS-NTD-04 — Tìm kiếm hồ sơ

**URL:** `GET /api/v1/nha-tuyen-dung/ho-sos?search=Backend`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Kết quả | Chứa "Backend" trong tiêu đề/mục tiêu/mô tả |

### TC-HS-NTD-05 — Xem chi tiết hồ sơ công khai

**URL:** `GET /api/v1/nha-tuyen-dung/ho-sos/1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `data.nguoi_dung` | Có thông tin ứng viên |

### TC-HS-NTD-06 — Xem hồ sơ ẩn → 404 ❌

**URL:** `GET /api/v1/nha-tuyen-dung/ho-sos/3` (hồ sơ ẩn)

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `404 Not Found` |

### TC-HS-NTD-07 — UV không có quyền NTD ❌

| Mục tiêu | Ứng viên gọi API NTD → bị từ chối |
|----------|--------------------------------------|
| Điều kiện | Đăng nhập bằng `ung.vien1@kltn.com` |
| HTTP Status | `403 Forbidden` |

---

## 7. ADMIN — Danh sách & Tìm kiếm

### TC-HS-AD-01 — Danh sách tất cả hồ sơ

**URL:** `GET /api/v1/admin/ho-sos?per_page=10`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Có `nguoi_dung` relation | ✅ |

### TC-HS-AD-02 — Lọc hồ sơ công khai

**URL:** `GET /api/v1/admin/ho-sos?trang_thai=1`

### TC-HS-AD-03 — Lọc theo trình độ

**URL:** `GET /api/v1/admin/ho-sos?trinh_do=dai_hoc`

### TC-HS-AD-04 — Tìm kiếm

**URL:** `GET /api/v1/admin/ho-sos?search=Backend`

### TC-HS-AD-05 — Lọc theo người dùng

**URL:** `GET /api/v1/admin/ho-sos?nguoi_dung_id=4`

### TC-HS-AD-06 — UV/NTD không có quyền Admin ❌

| Điều kiện | Đăng nhập bằng UV/NTD rồi gọi Admin API |
| HTTP Status | `403 Forbidden` |

---

## 8. ADMIN — Chi tiết & Đổi trạng thái

### TC-HS-AD-07 — Xem chi tiết hồ sơ

**URL:** `GET /api/v1/admin/ho-sos/1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `data.nguoi_dung` | Có thông tin người dùng kèm theo |

### TC-HS-AD-08 — Xem chi tiết ID không tồn tại ❌

**URL:** `GET /api/v1/admin/ho-sos/9999`

| HTTP Status | `404 Not Found` |

### TC-HS-AD-09 — Ẩn hồ sơ

**URL:** `PATCH /api/v1/admin/ho-sos/2/trang-thai`

| `data.trang_thai` | `0` |

### TC-HS-AD-10 — Công khai hồ sơ (toggle lại)

| `data.trang_thai` | `1` |

---

## 9. ADMIN — Xoá mềm & Khôi phục (Soft Delete)

### TC-HS-AD-11 — Xoá mềm hồ sơ

**URL:** `DELETE /api/v1/admin/ho-sos/10`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `message` | `"Xoá mềm hồ sơ thành công. Có thể khôi phục sau."` |
| DB | `deleted_at` != null, bản ghi **vẫn tồn tại** trong DB |

### TC-HS-AD-12 — Xoá hồ sơ không tồn tại ❌

**URL:** `DELETE /api/v1/admin/ho-sos/9999`

| HTTP Status | `404 Not Found` |

### TC-HS-AD-13 — Xem danh sách hồ sơ đã xoá (thùng rác)

**URL:** `GET /api/v1/admin/ho-sos/da-xoa`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Kết quả | Chỉ chứa hồ sơ có `deleted_at` != null |
| ID 10 | Phải xuất hiện trong danh sách |

### TC-HS-AD-14 — Khôi phục hồ sơ đã xoá

**URL:** `PATCH /api/v1/admin/ho-sos/10/khoi-phuc`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `message` | `"Khôi phục hồ sơ thành công."` |
| DB | `deleted_at` = null, bản ghi trở lại danh sách bình thường |

### TC-HS-AD-15 — Khôi phục hồ sơ chưa bị xoá → 404 ❌

**URL:** `PATCH /api/v1/admin/ho-sos/1/khoi-phuc` (hồ sơ bình thường, chưa xoá)

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `404 Not Found` |

---

## 10. ADMIN — Thống kê

### TC-HS-AD-16 — Xem thống kê hồ sơ

**URL:** `GET /api/v1/admin/ho-sos/thong-ke`

**Kết quả mong đợi:**
```json
HTTP 200 OK
{
    "success": true,
    "data": {
        "tong": 11,
        "cong_khai": 9,
        "an": 2,
        "da_xoa_mem": 0,
        "theo_trinh_do": { ... }
    }
}
```

---

## 11. Tổng hợp kịch bản kiểm thử

| STT | Mã | Chức năng | Method | Mong đợi |
|----:|------|-----------|:------:|:--------:|
| 1 | TC-HS-UV-01 | UV — Tạo đầy đủ | POST | `201` ✅ |
| 2 | TC-HS-UV-02 | UV — Tạo tối thiểu | POST | `201` ✅ |
| 3 | TC-HS-UV-03 | UV — Thiếu tiêu đề | POST | `422` ❌ |
| 4 | TC-HS-UV-04 | UV — Trình độ sai | POST | `422` ❌ |
| 5 | TC-HS-UV-05 | UV — NTD tạo | POST | `403` ❌ |
| 6 | TC-HS-UV-06 | UV — Chưa đăng nhập | POST | `401` ❌ |
| 7 | TC-HS-UV-07 | UV — Danh sách | GET | `200` ✅ |
| 8 | TC-HS-UV-08 | UV — Lọc trạng thái | GET | `200` ✅ |
| 9 | TC-HS-UV-09 | UV — Chi tiết | GET | `200` ✅ |
| 10 | TC-HS-UV-10 | UV — Xem người khác | GET | `404` ❌ |
| 11 | TC-HS-UV-11 | UV — Cập nhật | PUT | `200` ✅ |
| 12 | TC-HS-UV-12 | UV — Cập nhật người khác | PUT | `404` ❌ |
| 13 | TC-HS-UV-13 | UV — Ẩn hồ sơ | PATCH | `200` ✅ |
| 14 | TC-HS-UV-14 | UV — Công khai lại | PATCH | `200` ✅ |
| 15 | TC-HS-UV-15 | UV — Xoá hồ sơ | DELETE | `200` ✅ |
| 16 | TC-HS-UV-16 | UV — Xoá người khác | DELETE | `404` ❌ |
| 17 | TC-HS-NTD-01 | **NTD — Duyệt danh sách** | GET | `200` ✅ |
| 18 | TC-HS-NTD-02 | **NTD — Lọc trình độ** | GET | `200` ✅ |
| 19 | TC-HS-NTD-03 | **NTD — Lọc kinh nghiệm** | GET | `200` ✅ |
| 20 | TC-HS-NTD-04 | **NTD — Tìm kiếm** | GET | `200` ✅ |
| 21 | TC-HS-NTD-05 | **NTD — Chi tiết** | GET | `200` ✅ |
| 22 | TC-HS-NTD-06 | **NTD — Xem hồ sơ ẩn** | GET | `404` ❌ |
| 23 | TC-HS-NTD-07 | **NTD — UV không quyền** | GET | `403` ❌ |
| 24 | TC-HS-AD-01 | Admin — Danh sách | GET | `200` ✅ |
| 25 | TC-HS-AD-02 | Admin — Lọc công khai | GET | `200` ✅ |
| 26 | TC-HS-AD-03 | Admin — Lọc trình độ | GET | `200` ✅ |
| 27 | TC-HS-AD-04 | Admin — Tìm kiếm | GET | `200` ✅ |
| 28 | TC-HS-AD-05 | Admin — Lọc theo user | GET | `200` ✅ |
| 29 | TC-HS-AD-06 | Admin — UV/NTD không quyền | GET | `403` ❌ |
| 30 | TC-HS-AD-07 | Admin — Chi tiết | GET | `200` ✅ |
| 31 | TC-HS-AD-08 | Admin — ID không tồn tại | GET | `404` ❌ |
| 32 | TC-HS-AD-09 | Admin — Ẩn hồ sơ | PATCH | `200` ✅ |
| 33 | TC-HS-AD-10 | Admin — Công khai lại | PATCH | `200` ✅ |
| 34 | TC-HS-AD-11 | **Admin — Xoá mềm** | DELETE | `200` ✅ |
| 35 | TC-HS-AD-12 | Admin — Xoá ID không tồn tại | DELETE | `404` ❌ |
| 36 | TC-HS-AD-13 | **Admin — Thùng rác** | GET | `200` ✅ |
| 37 | TC-HS-AD-14 | **Admin — Khôi phục** | PATCH | `200` ✅ |
| 38 | TC-HS-AD-15 | Admin — Khôi phục chưa xoá | PATCH | `404` ❌ |
| 39 | TC-HS-AD-16 | Admin — Thống kê | GET | `200` ✅ |

---

**Tổng cộng: 39 kịch bản** (tăng 8 so với v1)

| Loại | Số lượng | Tỉ lệ |
|------|:--------:|:-----:|
| ✅ Happy Path | 25 | 64% |
| ❌ Error Case | 14 | 36% |

### So sánh với v1

| Thay đổi | Chi tiết |
|----------|---------|
| **Thêm mới** | +7 kịch bản NTD (TC-HS-NTD-01 đến 07) |
| **Thêm mới** | +3 kịch bản Admin soft delete (AD-13, AD-14, AD-15) |
| **Xoá bỏ** | Bỏ Admin cập nhật hồ sơ (TC-HS-AD-09 cũ) |
| **Sửa đổi** | Admin xoá → xoá mềm (soft delete) |
| **Sửa đổi** | Thống kê thêm `da_xoa_mem` |
