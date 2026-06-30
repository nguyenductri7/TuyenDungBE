# 📋 Kịch Bản Kiểm Thử API — Module Người Dùng (nguoi_dungs)

**Dự án:** Khóa luận tốt nghiệp (KLTN)  
**Module:** Quản lý Người dùng  
**Base URL:** `http://localhost:8000/api/v1`  
**Công nghệ:** Laravel 12 + Laravel Sanctum (Token-based Auth)  
**Ngày lập:** 09/03/2026  

---

## 1. Thông tin chung

### 1.1 Phân quyền vai trò

| Giá trị `vai_tro` | Tên vai trò | Ký hiệu |
|:-----------------:|-------------|---------|
| `0` | Ứng viên | UV |
| `1` | Nhà tuyển dụng | NTD |
| `2` | Quản trị viên | Admin |

### 1.2 Dữ liệu tài khoản test

| STT | Vai trò | Email | Mật khẩu | Trạng thái |
|-----|---------|-------|----------|-----------|
| 1 | Admin | `admin@kltn.com` | `Admin@123` | Active |
| 2 | NTD | `tuyen.dung1@kltn.com` | `NTD@123456` | Active |
| 3 | NTD | `tuyen.dung2@kltn.com` | `NTD@123456` | Active |
| 4 | UV | `ung.vien1@kltn.com` | `UV@123456` | Active |
| 5 | UV | `ung.vien2@kltn.com` | `UV@123456` | Active |
| 6 | UV | `ung.vien.khoa@kltn.com` | `UV@123456` | **Bị khoá** |

### 1.3 Header chung

| Header | Giá trị |
|--------|--------|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |
| `Authorization` | `Bearer {access_token}` *(chỉ route cần xác thực)* |

### 1.4 Cấu trúc Response chuẩn

```json
{
  "success": true | false,
  "message": "Mô tả kết quả",
  "data": { }
}
```

---

## 2. Nhóm chức năng: Đăng ký tài khoản

**Endpoint:** `POST /api/v1/dang-ky`  
**Quyền truy cập:** Public (không cần token)

---

### TC-DK-01 — Đăng ký ứng viên thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DK-01 |
| **Mục tiêu** | Đăng ký tài khoản ứng viên với đầy đủ thông tin hợp lệ |
| **Điều kiện tiên quyết** | Email chưa tồn tại trong hệ thống |
| **Phương thức** | `POST` |
| **URL** | `/api/v1/dang-ky` |

**Request Body:**
```json
{
    "ho_ten": "Nguyễn Văn Test",
    "email": "test.ungvien@kltn.com",
    "mat_khau": "password123",
    "mat_khau_confirmation": "password123",
    "so_dien_thoai": "0987654321",
    "ngay_sinh": "2000-06-15",
    "gioi_tinh": "nam",
    "dia_chi": "123 Đường ABC, Hà Nội",
    "vai_tro": 0
}
```

**Kết quả mong đợi:**
```json
HTTP 201 Created
{
    "success": true,
    "message": "Đăng ký tài khoản thành công.",
    "data": {
        "id": 18,
        "ho_ten": "Nguyễn Văn Test",
        "email": "test.ungvien@kltn.com",
        "vai_tro": 0
    }
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `201 Created` |
| `success` | `true` |
| Tài khoản được tạo | ✅ Có trong database |
| Mật khẩu | Được mã hoá (bcrypt), không trả về trong response |

---

### TC-DK-02 — Đăng ký nhà tuyển dụng thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DK-02 |
| **Mục tiêu** | Đăng ký tài khoản nhà tuyển dụng (`vai_tro = 1`) |

**Request Body:**
```json
{
    "ho_ten": "Trần Thị NTD",
    "email": "test.ntd@kltn.com",
    "mat_khau": "password123",
    "mat_khau_confirmation": "password123",
    "vai_tro": 1
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `201 Created` |
| `data.vai_tro` | `1` |

---

### TC-DK-03 — Đăng ký thất bại: Email đã tồn tại

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DK-03 |
| **Mục tiêu** | Hệ thống từ chối khi email đã được sử dụng |

**Request Body:**
```json
{
    "ho_ten": "Người Dùng Mới",
    "email": "ung.vien1@kltn.com",
    "mat_khau": "password123",
    "mat_khau_confirmation": "password123"
}
```

**Kết quả mong đợi:**
```json
HTTP 422 Unprocessable Content
{
    "success": false,
    "message": "Dữ liệu không hợp lệ.",
    "errors": {
        "email": ["Email này đã được sử dụng."]
    }
}
```

---

### TC-DK-04 — Đăng ký thất bại: Thiếu trường bắt buộc

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DK-04 |
| **Mục tiêu** | Hệ thống trả lỗi khi thiếu `ho_ten`, `email`, `mat_khau` |

**Request Body:**
```json
{
    "email": "test@kltn.com"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.ho_ten` | `["Họ tên không được để trống."]` |
| `errors.mat_khau` | `["Mật khẩu không được để trống."]` |

---

### TC-DK-05 — Đăng ký thất bại: Mật khẩu không khớp

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DK-05 |
| **Mục tiêu** | Hệ thống từ chối khi `mat_khau` ≠ `mat_khau_confirmation` |

**Request Body:**
```json
{
    "ho_ten": "Test User",
    "email": "test2@kltn.com",
    "mat_khau": "password123",
    "mat_khau_confirmation": "differentpass"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.mat_khau` | `["Xác nhận mật khẩu không khớp."]` |

---

### TC-DK-06 — Đăng ký thất bại: Số điện thoại sai định dạng

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DK-06 |
| **Mục tiêu** | Kiểm tra validation số điện thoại (phải bắt đầu bằng 0, đủ 10 số) |

**Request Body:**
```json
{
    "ho_ten": "Test User",
    "email": "test3@kltn.com",
    "mat_khau": "password123",
    "mat_khau_confirmation": "password123",
    "so_dien_thoai": "123456"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.so_dien_thoai` | `["Số điện thoại phải bắt đầu bằng 0 và gồm 10 chữ số."]` |

---

## 3. Nhóm chức năng: Đăng nhập

**Endpoint:** `POST /api/v1/dang-nhap`  
**Quyền truy cập:** Public

---

### TC-DN-01 — Đăng nhập Admin thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DN-01 |
| **Mục tiêu** | Đăng nhập thành công và nhận Bearer Token |

**Request Body:**
```json
{
    "email": "admin@kltn.com",
    "mat_khau": "Admin@123"
}
```

**Kết quả mong đợi:**
```json
HTTP 200 OK
{
    "success": true,
    "message": "Đăng nhập thành công.",
    "data": {
        "nguoi_dung": {
            "id": 1,
            "ho_ten": "Super Admin",
            "email": "admin@kltn.com",
            "vai_tro": 2
        },
        "access_token": "1|abc...xyz",
        "token_type": "Bearer",
        "vai_tro": "Admin"
    }
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.access_token` | Chuỗi token không rỗng |
| `data.token_type` | `"Bearer"` |
| `data.vai_tro` | `"Admin"` |
| `data.nguoi_dung.mat_khau` | **Không xuất hiện** trong response |

---

### TC-DN-02 — Đăng nhập Nhà tuyển dụng thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DN-02 |

**Request Body:**
```json
{
    "email": "tuyen.dung1@kltn.com",
    "mat_khau": "NTD@123456"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.vai_tro` | `"Nhà tuyển dụng"` |

---

### TC-DN-03 — Đăng nhập Ứng viên thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DN-03 |

**Request Body:**
```json
{
    "email": "ung.vien1@kltn.com",
    "mat_khau": "UV@123456"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.vai_tro` | `"Ứng viên"` |

---

### TC-DN-04 — Đăng nhập thất bại: Sai mật khẩu

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DN-04 |
| **Mục tiêu** | Hệ thống từ chối khi mật khẩu không đúng |

**Request Body:**
```json
{
    "email": "admin@kltn.com",
    "mat_khau": "SaiMatKhau999"
}
```

**Kết quả mong đợi:**
```json
HTTP 401 Unauthorized
{
    "success": false,
    "message": "Email hoặc mật khẩu không đúng."
}
```

---

### TC-DN-05 — Đăng nhập thất bại: Tài khoản bị khoá

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DN-05 |
| **Mục tiêu** | Hệ thống từ chối khi tài khoản bị khoá (`trang_thai = 0`) |

**Request Body:**
```json
{
    "email": "ung.vien.khoa@kltn.com",
    "mat_khau": "UV@123456"
}
```

**Kết quả mong đợi:**
```json
HTTP 403 Forbidden
{
    "success": false,
    "message": "Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên."
}
```

---

### TC-DN-06 — Đăng nhập thất bại: Email không tồn tại

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DN-06 |

**Request Body:**
```json
{
    "email": "khong.ton.tai@kltn.com",
    "mat_khau": "password123"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `401 Unauthorized` |
| `message` | `"Email hoặc mật khẩu không đúng."` |

---

## 4. Nhóm chức năng: Hồ sơ cá nhân

> **Điều kiện:** Cần `Authorization: Bearer {token}` (đăng nhập trước)

---

### TC-HS-01 — Xem hồ sơ cá nhân thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-HS-01 |
| **Mục tiêu** | Lấy thông tin người dùng đang đăng nhập |
| **Phương thức** | `GET` |
| **URL** | `/api/v1/ho-so` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.email` | Email của người dùng đang đăng nhập |
| `data.mat_khau` | **Không xuất hiện** |

---

### TC-HS-02 — Xem hồ sơ khi chưa đăng nhập

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-HS-02 |
| **Mục tiêu** | Từ chối khi không có token |

**Kết quả mong đợi:**
```json
HTTP 401 Unauthorized
{
    "success": false,
    "message": "Chưa xác thực. Vui lòng đăng nhập."
}
```

---

### TC-HS-03 — Cập nhật hồ sơ thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-HS-03 |
| **Mục tiêu** | Cập nhật thông tin cá nhân |
| **Phương thức** | `PUT` |
| **URL** | `/api/v1/ho-so` |

**Request Body:**
```json
{
    "ho_ten": "Tên Mới Cập Nhật",
    "so_dien_thoai": "0909090909",
    "ngay_sinh": "1995-08-20",
    "gioi_tinh": "nu",
    "dia_chi": "99 Nguyễn Trãi, Quận 5, TP. HCM"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.ho_ten` | `"Tên Mới Cập Nhật"` |
| `data.so_dien_thoai` | `"0909090909"` |

---

### TC-HS-04 — Cập nhật hồ sơ: Email trùng với người khác

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-HS-04 |
| **Mục tiêu** | Không cho phép đổi sang email của người khác |

**Request Body:**
```json
{
    "email": "ung.vien2@kltn.com"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.email` | `["Email này đã được sử dụng."]` |

---

## 5. Nhóm chức năng: Đổi mật khẩu

**Endpoint:** `POST /api/v1/doi-mat-khau`  
**Quyền truy cập:** Cần token

---

### TC-MK-01 — Đổi mật khẩu thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-MK-01 |
| **Mục tiêu** | Đổi mật khẩu thành công và thu hồi toàn bộ token |

**Request Body:**
```json
{
    "mat_khau_cu": "UV@123456",
    "mat_khau_moi": "NewPass@789",
    "mat_khau_moi_confirmation": "NewPass@789"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `message` | `"Đổi mật khẩu thành công. Vui lòng đăng nhập lại."` |
| Token cũ | **Bị vô hiệu hóa** — gọi lại API sẽ nhận 401 |

---

### TC-MK-02 — Đổi mật khẩu thất bại: Sai mật khẩu cũ

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-MK-02 |

**Request Body:**
```json
{
    "mat_khau_cu": "SaiMatKhau",
    "mat_khau_moi": "NewPass@789",
    "mat_khau_moi_confirmation": "NewPass@789"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `message` | `"Mật khẩu cũ không đúng."` |

---

### TC-MK-03 — Đổi mật khẩu thất bại: Mật khẩu mới trùng mật khẩu cũ

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-MK-03 |

**Request Body:**
```json
{
    "mat_khau_cu": "UV@123456",
    "mat_khau_moi": "UV@123456",
    "mat_khau_moi_confirmation": "UV@123456"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.mat_khau_moi` | `["Mật khẩu mới phải khác mật khẩu cũ."]` |

---

### TC-MK-04 — Đổi mật khẩu thất bại: Xác nhận không khớp

**Request Body:**
```json
{
    "mat_khau_cu": "UV@123456",
    "mat_khau_moi": "NewPass@789",
    "mat_khau_moi_confirmation": "DifferentPass"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.mat_khau_moi` | `["Xác nhận mật khẩu mới không khớp."]` |

---

## 6. Nhóm chức năng: Đăng xuất

**Endpoint:** `POST /api/v1/dang-xuat`  
**Quyền truy cập:** Cần token

---

### TC-DX-01 — Đăng xuất thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-DX-01 |
| **Mục tiêu** | Thu hồi token hiện tại |

**Kết quả mong đợi:**
```json
HTTP 200 OK
{
    "success": true,
    "message": "Đăng xuất thành công."
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| Token sau đăng xuất | Gọi lại `/ho-so` với token cũ → `401` |

---

## 7. Nhóm chức năng: Admin — Danh sách & Tìm kiếm

**Endpoint:** `GET /api/v1/admin/nguoi-dungs`  
**Quyền truy cập:** Chỉ Admin (`vai_tro = 2`)

---

### TC-AD-DS-01 — Lấy danh sách tất cả người dùng

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-DS-01 |
| **URL** | `/api/v1/admin/nguoi-dungs?per_page=10` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.total` | `17` |
| `data.per_page` | `10` |

---

### TC-AD-DS-02 — Lọc ứng viên

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-DS-02 |
| **URL** | `/api/v1/admin/nguoi-dungs?vai_tro=0` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| Tất cả bản ghi | `vai_tro = 0` |

---

### TC-AD-DS-03 — Lọc tài khoản bị khoá

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-DS-03 |
| **URL** | `/api/v1/admin/nguoi-dungs?trang_thai=0` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| Tất cả bản ghi | `trang_thai = 0` |
| Số lượng | `3` bản ghi bị khoá |

---

### TC-AD-DS-04 — Tìm kiếm theo tên/email

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-DS-04 |
| **URL** | `/api/v1/admin/nguoi-dungs?search=admin` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| Kết quả | Bản ghi chứa `"admin"` trong tên hoặc email |

---

### TC-AD-DS-05 — Danh sách bằng token NTD (không có quyền)

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-DS-05 |
| **Mục tiêu** | Từ chối khi token không phải Admin |
| **Điều kiện** | Đăng nhập bằng `tuyen.dung1@kltn.com` rồi gọi Admin API |

**Kết quả mong đợi:**
```json
HTTP 403 Forbidden
{
    "success": false,
    "message": "Bạn không có quyền thực hiện hành động này."
}
```

---

## 8. Nhóm chức năng: Admin — Tạo tài khoản

**Endpoint:** `POST /api/v1/admin/nguoi-dungs`

---

### TC-AD-TK-01 — Tạo tài khoản Admin mới thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-TK-01 |

**Request Body:**
```json
{
    "ho_ten": "Admin Mới",
    "email": "admin.moi@kltn.com",
    "mat_khau": "Admin@999",
    "vai_tro": 2,
    "trang_thai": 1
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `201 Created` |
| `data.vai_tro` | `2` |

---

### TC-AD-TK-02 — Tạo tài khoản: Email trùng

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-TK-02 |

**Request Body:**
```json
{
    "ho_ten": "Test Trùng",
    "email": "admin@kltn.com",
    "mat_khau": "abc123",
    "vai_tro": 0
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `errors.email` | `["Email này đã được sử dụng."]` |

---

## 9. Nhóm chức năng: Admin — Xem & Cập nhật

---

### TC-AD-CT-01 — Xem chi tiết người dùng tồn tại

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-CT-01 |
| **Phương thức** | `GET` |
| **URL** | `/api/v1/admin/nguoi-dungs/1` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.id` | `1` |
| `data.email` | `admin@kltn.com` |

---

### TC-AD-CT-02 — Xem chi tiết ID không tồn tại

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-CT-02 |
| **URL** | `/api/v1/admin/nguoi-dungs/9999` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `404 Not Found` |
| `message` | `"Không tìm thấy dữ liệu yêu cầu."` |

---

### TC-AD-CP-01 — Cập nhật thông tin người dùng

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-CP-01 |
| **Phương thức** | `PUT` |
| **URL** | `/api/v1/admin/nguoi-dungs/4` |

**Request Body:**
```json
{
    "ho_ten": "Phạm Văn An (Đã cập nhật)",
    "dia_chi": "Đường mới, TP. HCM",
    "trang_thai": 1
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.ho_ten` | `"Phạm Văn An (Đã cập nhật)"` |

---

### TC-AD-CP-02 — Admin đổi vai trò người dùng

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-CP-02 |
| **Mục tiêu** | Nâng ứng viên (ID 5) thành nhà tuyển dụng |

**Request Body:**
```json
{
    "vai_tro": 1
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `data.vai_tro` | `1` |

---

## 10. Nhóm chức năng: Admin — Khoá/Mở khoá tài khoản

**Endpoint:** `PATCH /api/v1/admin/nguoi-dungs/{id}/khoa`

---

### TC-AD-KH-01 — Khoá tài khoản đang active

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-KH-01 |
| **URL** | `/api/v1/admin/nguoi-dungs/4/khoa` |
| **Điều kiện** | ID 4 đang `trang_thai = 1` |

**Kết quả mong đợi:**
```json
HTTP 200 OK
{
    "success": true,
    "message": "Khoá tài khoản thành công.",
    "data": {
        "id": 4,
        "trang_thai": 0
    }
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| `data.trang_thai` | `0` (đã bị khoá) |
| Token của ID 4 | Bị thu hồi |

---

### TC-AD-KH-02 — Mở khoá tài khoản (gọi lại lần 2)

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-KH-02 |
| **URL** | `/api/v1/admin/nguoi-dungs/4/khoa` |
| **Điều kiện** | ID 4 đang `trang_thai = 0` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `message` | `"Mở khoá tài khoản thành công."` |
| `data.trang_thai` | `1` |

---

### TC-AD-KH-03 — Khoá tài khoản đang đăng nhập (chính mình)

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-KH-03 |
| **Mục tiêu** | Hệ thống không cho Admin tự khoá mình |
| **URL** | `/api/v1/admin/nguoi-dungs/1/khoa` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `message` | `"Không thể khoá tài khoản đang đăng nhập."` |

---

## 11. Nhóm chức năng: Admin — Xoá tài khoản

**Endpoint:** `DELETE /api/v1/admin/nguoi-dungs/{id}`

---

### TC-AD-XD-01 — Xoá tài khoản thành công

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-XD-01 |
| **URL** | `/api/v1/admin/nguoi-dungs/10` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `200 OK` |
| `message` | `"Xoá tài khoản thành công."` |
| DB | Bản ghi ID 10 không còn tồn tại |

---

### TC-AD-XD-02 — Xoá tài khoản chính mình

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-XD-02 |
| **Mục tiêu** | Không cho phép Admin tự xoá tài khoản đang đăng nhập |
| **URL** | `/api/v1/admin/nguoi-dungs/1` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `422 Unprocessable Content` |
| `message` | `"Không thể xoá tài khoản đang đăng nhập."` |

---

### TC-AD-XD-03 — Xoá tài khoản không tồn tại

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-XD-03 |
| **URL** | `/api/v1/admin/nguoi-dungs/9999` |

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------|
| HTTP Status | `404 Not Found` |

---

## 12. Nhóm chức năng: Admin — Thống kê

**Endpoint:** `GET /api/v1/admin/nguoi-dungs/thong-ke`

---

### TC-AD-TK-03 — Xem thống kê người dùng

| Mục | Nội dung |
|-----|---------|
| **Mã kịch bản** | TC-AD-TK-03 |

**Kết quả mong đợi:**
```json
HTTP 200 OK
{
    "success": true,
    "data": {
        "tong": 17,
        "admin": 1,
        "nha_tuyen_dung": 5,
        "ung_vien": 11,
        "dang_hoat_dong": 14,
        "bi_khoa": 3
    }
}
```

---

## 13. Tổng hợp kịch bản kiểm thử

| STT | Mã kịch bản | Chức năng | Method | Kết quả mong đợi |
|----:|------------|-----------|:------:|:----------------:|
| 1 | TC-DK-01 | Đăng ký UV thành công | POST | `201` ✅ |
| 2 | TC-DK-02 | Đăng ký NTD thành công | POST | `201` ✅ |
| 3 | TC-DK-03 | Đăng ký — email trùng | POST | `422` ❌ |
| 4 | TC-DK-04 | Đăng ký — thiếu field | POST | `422` ❌ |
| 5 | TC-DK-05 | Đăng ký — mật khẩu không khớp | POST | `422` ❌ |
| 6 | TC-DK-06 | Đăng ký — SĐT sai định dạng | POST | `422` ❌ |
| 7 | TC-DN-01 | Đăng nhập Admin | POST | `200` ✅ |
| 8 | TC-DN-02 | Đăng nhập NTD | POST | `200` ✅ |
| 9 | TC-DN-03 | Đăng nhập UV | POST | `200` ✅ |
| 10 | TC-DN-04 | Đăng nhập — sai mật khẩu | POST | `401` ❌ |
| 11 | TC-DN-05 | Đăng nhập — tài khoản bị khoá | POST | `403` ❌ |
| 12 | TC-DN-06 | Đăng nhập — email không tồn tại | POST | `401` ❌ |
| 13 | TC-HS-01 | Xem hồ sơ cá nhân | GET | `200` ✅ |
| 14 | TC-HS-02 | Xem hồ sơ — chưa đăng nhập | GET | `401` ❌ |
| 15 | TC-HS-03 | Cập nhật hồ sơ thành công | PUT | `200` ✅ |
| 16 | TC-HS-04 | Cập nhật — email trùng | PUT | `422` ❌ |
| 17 | TC-MK-01 | Đổi mật khẩu thành công | POST | `200` ✅ |
| 18 | TC-MK-02 | Đổi mật khẩu — sai MK cũ | POST | `422` ❌ |
| 19 | TC-MK-03 | Đổi mật khẩu — MK mới = MK cũ | POST | `422` ❌ |
| 20 | TC-MK-04 | Đổi mật khẩu — xác nhận không khớp | POST | `422` ❌ |
| 21 | TC-DX-01 | Đăng xuất thành công | POST | `200` ✅ |
| 22 | TC-AD-DS-01 | Admin — danh sách người dùng | GET | `200` ✅ |
| 23 | TC-AD-DS-05 | Admin — NTD không có quyền | GET | `403` ❌ |
| 24 | TC-AD-TK-01 | Admin — tạo tài khoản Admin | POST | `201` ✅ |
| 25 | TC-AD-CT-02 | Admin — ID không tồn tại | GET | `404` ❌ |
| 26 | TC-AD-KH-01 | Admin — khoá tài khoản | PATCH | `200` ✅ |
| 27 | TC-AD-KH-03 | Admin — khoá chính mình | PATCH | `422` ❌ |
| 28 | TC-AD-XD-02 | Admin — xoá chính mình | DELETE | `422` ❌ |

---

**Tổng cộng: 28 kịch bản**

| Loại | Số lượng | Tỉ lệ |
|------|:--------:|:-----:|
| ✅ Happy Path (thành công) | 14 | 50% |
| ❌ Error Case (lỗi/từ chối) | 14 | 50% |
