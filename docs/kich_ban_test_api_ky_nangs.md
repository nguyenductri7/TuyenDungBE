# 📋 Kịch Bản Kiểm Thử API — Module Kỹ Năng (ky_nangs)

**Dự án:** Khóa luận tốt nghiệp (KLTN)  
**Module:** Quản lý Kỹ năng  
**Base URL:** `http://localhost:8000/api/v1`  
**Ngày lập:** 11/03/2026  

---

## 1. Thông tin chung

### 1.1 Phân quyền

| Vai trò | Quyền trên kỹ năng |
|---------|-------------------|
| Public (không đăng nhập) | Xem danh sách & chi tiết |
| Ứng viên / NTD | Xem (giống public — dùng để chọn kỹ năng cho hồ sơ) |
| Admin | Full CRUD (tạo, sửa, xoá, thống kê) |

### 1.2 Tài khoản test

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Admin | `admin@kltn.com` | `Admin@123` |
| UV | `ung.vien1@kltn.com` | `UV@123456` |

### 1.3 Dữ liệu sau seed

> **Tổng: 40 kỹ năng** — bao gồm PHP, JavaScript, Python, Laravel, React, Docker, AWS, Tiếng Anh, ...

---

## 2. Public — Danh sách & Chi tiết

### TC-KN-PUB-01 — Lấy tất cả kỹ năng

**URL:** `GET /api/v1/ky-nangs`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Số bản ghi | 40 |
| Sắp xếp | A → Z theo `ten_ky_nang` |

### TC-KN-PUB-02 — Tìm kiếm

**URL:** `GET /api/v1/ky-nangs?search=PHP`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Kết quả | Chứa "PHP" trong `ten_ky_nang` |

### TC-KN-PUB-03 — Phân trang

**URL:** `GET /api/v1/ky-nangs?per_page=5`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| `data.per_page` | `5` |
| `data.total` | `40` |

### TC-KN-PUB-04 — Chi tiết kỹ năng

**URL:** `GET /api/v1/ky-nangs/1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `data.ten_ky_nang` | `"PHP"` |
| `data.so_chung_chi` | `2` |

### TC-KN-PUB-05 — ID không tồn tại → 404 ❌

**URL:** `GET /api/v1/ky-nangs/9999`

| HTTP Status | `404 Not Found` |

---

## 3. Admin — Danh sách & Thống kê

### TC-KN-AD-01 — Danh sách (Admin)

**URL:** `GET /api/v1/admin/ky-nangs`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Số bản ghi | 40 |

### TC-KN-AD-02 — Tìm kiếm

**URL:** `GET /api/v1/admin/ky-nangs?search=React`

### TC-KN-AD-03 — Sắp xếp theo số chứng chỉ giảm dần

**URL:** `GET /api/v1/admin/ky-nangs?sort_by=so_chung_chi&sort_dir=desc`

| Bản ghi đầu | `so_chung_chi` cao nhất (AWS = 5 hoặc Tiếng Anh = 5) |

### TC-KN-AD-04 — Thống kê

**URL:** `GET /api/v1/admin/ky-nangs/thong-ke`

```json
HTTP 200
{
    "success": true,
    "data": {
        "tong": 40,
        "co_chung_chi": 32,
        "khong_chung_chi": 8,
        "co_hinh_anh": 0
    }
}
```

### TC-KN-AD-05 — UV/NTD gọi Admin API → 403 ❌

| Điều kiện | Đăng nhập bằng UV |
| HTTP Status | `403 Forbidden` |

### TC-KN-AD-06 — Chưa đăng nhập → 401 ❌

| HTTP Status | `401 Unauthorized` |

---

## 4. Admin — Tạo kỹ năng

### TC-KN-AD-07 — Tạo đầy đủ thông tin

**URL:** `POST /api/v1/admin/ky-nangs`

```json
{
    "ten_ky_nang": "GraphQL",
    "so_chung_chi": 1,
    "hinh_anh": "graphql.png"
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `201 Created` |
| `data.ten_ky_nang` | `"GraphQL"` |

### TC-KN-AD-08 — Tạo tối thiểu (chỉ tên)

```json
{ "ten_ky_nang": "Rust" }
```

| HTTP Status | `201` |
| `data.so_chung_chi` | `0` (mặc định) |

### TC-KN-AD-09 — Thiếu tên → 422 ❌

```json
{ "so_chung_chi": 1 }
```

| HTTP Status | `422` |
| `errors.ten_ky_nang` | Có thông báo lỗi |

### TC-KN-AD-10 — Trùng tên → 422 ❌

```json
{ "ten_ky_nang": "PHP" }
```

| HTTP Status | `422` |
| `errors.ten_ky_nang` | "Tên kỹ năng đã tồn tại." |

### TC-KN-AD-11 — Số chứng chỉ âm → 422 ❌

```json
{ "ten_ky_nang": "Test", "so_chung_chi": -1 }
```

| HTTP Status | `422` |

---

## 5. Admin — Chi tiết & Cập nhật

### TC-KN-AD-12 — Xem chi tiết

**URL:** `GET /api/v1/admin/ky-nangs/1`

| HTTP Status | `200 OK` |

### TC-KN-AD-13 — Cập nhật tên

**URL:** `PUT /api/v1/admin/ky-nangs/1`

```json
{ "ten_ky_nang": "PHP 8.x" }
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `data.ten_ky_nang` | `"PHP 8.x"` |

### TC-KN-AD-14 — Cập nhật số chứng chỉ

```json
{ "so_chung_chi": 5 }
```

| `data.so_chung_chi` | `5` |

### TC-KN-AD-15 — Trùng tên khi cập nhật → 422 ❌

**URL:** `PUT /api/v1/admin/ky-nangs/1`

```json
{ "ten_ky_nang": "JavaScript" }
```

| HTTP Status | `422` |

### TC-KN-AD-16 — Cập nhật ID không tồn tại → 404 ❌

**URL:** `PUT /api/v1/admin/ky-nangs/9999`

| HTTP Status | `404` |

---

## 6. Admin — Xoá

### TC-KN-AD-17 — Xoá thành công

**URL:** `DELETE /api/v1/admin/ky-nangs/40`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| `message` | "Xoá kỹ năng thành công." |

### TC-KN-AD-18 — Xoá ID không tồn tại → 404 ❌

**URL:** `DELETE /api/v1/admin/ky-nangs/9999`

| HTTP Status | `404` |

---

## 7. Tổng hợp

| STT | Mã | Chức năng | Method | Mong đợi |
|----:|------|-----------|:------:|:--------:|
| 1 | TC-KN-PUB-01 | Public — Tất cả | GET | `200` ✅ |
| 2 | TC-KN-PUB-02 | Public — Tìm kiếm | GET | `200` ✅ |
| 3 | TC-KN-PUB-03 | Public — Phân trang | GET | `200` ✅ |
| 4 | TC-KN-PUB-04 | Public — Chi tiết | GET | `200` ✅ |
| 5 | TC-KN-PUB-05 | Public — ID không tồn tại | GET | `404` ❌ |
| 6 | TC-KN-AD-01 | Admin — Danh sách | GET | `200` ✅ |
| 7 | TC-KN-AD-02 | Admin — Tìm kiếm | GET | `200` ✅ |
| 8 | TC-KN-AD-03 | Admin — Sắp xếp | GET | `200` ✅ |
| 9 | TC-KN-AD-04 | Admin — Thống kê | GET | `200` ✅ |
| 10 | TC-KN-AD-05 | Admin — UV không quyền | GET | `403` ❌ |
| 11 | TC-KN-AD-06 | Admin — Chưa đăng nhập | GET | `401` ❌ |
| 12 | TC-KN-AD-07 | Admin — Tạo đầy đủ | POST | `201` ✅ |
| 13 | TC-KN-AD-08 | Admin — Tạo tối thiểu | POST | `201` ✅ |
| 14 | TC-KN-AD-09 | Admin — Thiếu tên | POST | `422` ❌ |
| 15 | TC-KN-AD-10 | Admin — Trùng tên | POST | `422` ❌ |
| 16 | TC-KN-AD-11 | Admin — Số CC âm | POST | `422` ❌ |
| 17 | TC-KN-AD-12 | Admin — Chi tiết | GET | `200` ✅ |
| 18 | TC-KN-AD-13 | Admin — Đổi tên | PUT | `200` ✅ |
| 19 | TC-KN-AD-14 | Admin — Đổi số CC | PUT | `200` ✅ |
| 20 | TC-KN-AD-15 | Admin — Trùng tên (update) | PUT | `422` ❌ |
| 21 | TC-KN-AD-16 | Admin — Update 404 | PUT | `404` ❌ |
| 22 | TC-KN-AD-17 | Admin — Xoá | DELETE | `200` ✅ |
| 23 | TC-KN-AD-18 | Admin — Xoá 404 | DELETE | `404` ❌ |

---

**Tổng cộng: 23 kịch bản**

| Loại | Số lượng | Tỉ lệ |
|------|:--------:|:-----:|
| ✅ Happy Path | 14 | 61% |
| ❌ Error Case | 9 | 39% |
