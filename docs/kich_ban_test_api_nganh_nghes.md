# 📋 Kịch Bản Kiểm Thử API — Module Ngành Nghề (nganh_nghes)

**Dự án:** Khóa luận tốt nghiệp (KLTN)  
**Module:** Quản lý Ngành nghề  
**Base URL:** `http://localhost:8000/api/v1`  
**Ngày lập:** 10/03/2026  

---

## 1. Thông tin chung

### 1.1 Phân quyền

| Vai trò | Quyền trên ngành nghề |
|---------|----------------------|
| Public (không đăng nhập) | Xem danh sách & chi tiết ngành nghề hiển thị |
| Ứng viên / NTD | Xem (giống public, dùng để chọn ngành) |
| Admin | Full CRUD (tạo, sửa, xoá, đổi trạng thái, thống kê) |

### 1.2 Tài khoản test

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Admin | `admin@kltn.com` | `Admin@123` |
| UV | `ung.vien1@kltn.com` | `UV@123456` |
| NTD | `tuyen.dung1@kltn.com` | `NTD@123456` |

### 1.3 Dữ liệu sau seed

| STT | ID | Tên ngành | Loại | Trạng thái |
|-----|---:|-----------|------|:----------:|
| 1 | 1 | Công nghệ thông tin | Gốc | Hiển thị |
| 2 | 2 | Lập trình Backend | Con (→1) | Hiển thị |
| ... | ... | ... | ... | ... |
| 8 | 21 | Xây dựng / Bất động sản | Gốc | Hiển thị |
| 9 | 28 | Ngành test (ẩn) | Gốc | **Ẩn** |

> **Tổng: ~28 bản ghi** (8 gốc hiển thị + 1 gốc ẩn + 19 con)

---

## 2. Public — Danh sách ngành nghề

### TC-NN-PUB-01 — Lấy tất cả ngành nghề hiển thị

**URL:** `GET /api/v1/nganh-nghes`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Tất cả bản ghi | `trang_thai = 1` |
| Ngành "test (ẩn)" | ❌ Không xuất hiện |

### TC-NN-PUB-02 — Chỉ lấy ngành gốc

**URL:** `GET /api/v1/nganh-nghes?goc=1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Tất cả bản ghi | `danh_muc_cha_id = null` |
| Số lượng | 8 (không tính ngành ẩn) |

### TC-NN-PUB-03 — Lọc ngành con theo cha

**URL:** `GET /api/v1/nganh-nghes?danh_muc_cha_id=1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| Tất cả bản ghi | `danh_muc_cha_id = 1` |
| Kết quả | Backend, Frontend, Mobile, DevOps, QA, Phân tích dữ liệu |

### TC-NN-PUB-04 — Tìm kiếm

**URL:** `GET /api/v1/nganh-nghes?search=Backend`

| Kết quả | Chứa "Backend" trong tên hoặc mô tả |

### TC-NN-PUB-05 — Dạng cây (cha-con)

**URL:** `GET /api/v1/nganh-nghes/cay`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Cấu trúc | Mỗi ngành gốc có mảng `danh_muc_con` |
| Chỉ hiển thị | `trang_thai = 1` |

### TC-NN-PUB-06 — Chi tiết ngành nghề

**URL:** `GET /api/v1/nganh-nghes/1`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| `data.ten_nganh` | "Công nghệ thông tin" |
| `data.danh_muc_con` | Danh sách con |
| `data.danh_muc_cha` | `null` (vì là gốc) |

### TC-NN-PUB-07 — Chi tiết ngành ẩn → 404 ❌

**URL:** `GET /api/v1/nganh-nghes/28` (ngành ẩn)

| HTTP Status | `404 Not Found` |

### TC-NN-PUB-08 — ID không tồn tại → 404 ❌

**URL:** `GET /api/v1/nganh-nghes/9999`

| HTTP Status | `404 Not Found` |

---

## 3. Admin — Danh sách & Thống kê

### TC-NN-AD-01 — Danh sách tất cả (kể cả ẩn)

**URL:** `GET /api/v1/admin/nganh-nghes`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Số lượng | ≥ 28 (bao gồm cả ẩn) |

### TC-NN-AD-02 — Lọc chỉ ngành ẩn

**URL:** `GET /api/v1/admin/nganh-nghes?trang_thai=0`

| Tất cả bản ghi | `trang_thai = 0` |

### TC-NN-AD-03 — Lọc ngành gốc

**URL:** `GET /api/v1/admin/nganh-nghes?danh_muc_cha_id=null`

| Tất cả bản ghi | `danh_muc_cha_id = null` |

### TC-NN-AD-04 — Tìm kiếm

**URL:** `GET /api/v1/admin/nganh-nghes?search=Marketing`

### TC-NN-AD-05 — Thống kê

**URL:** `GET /api/v1/admin/nganh-nghes/thong-ke`

```json
HTTP 200
{
    "success": true,
    "data": {
        "tong": 28,
        "hien_thi": 27,
        "an": 1,
        "nganh_goc": 9,
        "nganh_con": 19
    }
}
```

### TC-NN-AD-06 — UV/NTD gọi Admin API → 403 ❌

| Điều kiện | Đăng nhập bằng UV/NTD |
| HTTP Status | `403 Forbidden` |

---

## 4. Admin — Tạo ngành nghề

### TC-NN-AD-07 — Tạo ngành gốc thành công

**URL:** `POST /api/v1/admin/nganh-nghes`

```json
{
    "ten_nganh": "Logistics / Kho vận",
    "mo_ta": "Lĩnh vực logistics, vận tải, kho bãi.",
    "icon": "🚚",
    "trang_thai": 1
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `201 Created` |
| `data.slug` | `"logistics-kho-van"` (tự động tạo) |
| `data.danh_muc_cha_id` | `null` |

### TC-NN-AD-08 — Tạo ngành con thành công

```json
{
    "ten_nganh": "An ninh mạng",
    "mo_ta": "Cybersecurity, SIEM, SOC...",
    "icon": "🔒",
    "danh_muc_cha_id": 1,
    "trang_thai": 1
}
```

| `data.danh_muc_cha_id` | `1` |

### TC-NN-AD-09 — Thiếu tên → 422 ❌

```json
{ "mo_ta": "Test thiếu tên" }
```

| HTTP Status | `422` |
| `errors.ten_nganh` | Có thông báo lỗi |

### TC-NN-AD-10 — danh_muc_cha_id không tồn tại → 422 ❌

```json
{ "ten_nganh": "Test", "danh_muc_cha_id": 9999 }
```

| HTTP Status | `422` |

---

## 5. Admin — Chi tiết & Cập nhật

### TC-NN-AD-11 — Xem chi tiết (Admin xem được cả ẩn)

**URL:** `GET /api/v1/admin/nganh-nghes/28`

| HTTP Status | `200 OK` |
| `data.trang_thai` | `0` (ẩn) |

### TC-NN-AD-12 — Cập nhật tên (slug tự động cập nhật)

**URL:** `PUT /api/v1/admin/nganh-nghes/1`

```json
{ "ten_nganh": "Công nghệ thông tin (CNTT)" }
```

| `data.slug` | `"cong-nghe-thong-tin-cntt"` |

### TC-NN-AD-13 — Đặt cha = chính nó → 422 ❌

**URL:** `PUT /api/v1/admin/nganh-nghes/1`

```json
{ "danh_muc_cha_id": 1 }
```

| HTTP Status | `422` |
| `message` | "Không thể đặt ngành nghề làm danh mục cha của chính nó." |

---

## 6. Admin — Đổi trạng thái & Xoá

### TC-NN-AD-14 — Ẩn ngành nghề

**URL:** `PATCH /api/v1/admin/nganh-nghes/8/trang-thai`

| `data.trang_thai` | `0` |

### TC-NN-AD-15 — Hiển thị lại (toggle)

| `data.trang_thai` | `1` |

### TC-NN-AD-16 — Xoá ngành lá (không có con) ✅

**URL:** `DELETE /api/v1/admin/nganh-nghes/28` (ngành ẩn, không có con)

| HTTP Status | `200 OK` |
| `message` | "Xoá ngành nghề thành công." |

### TC-NN-AD-17 — Xoá ngành có con → 422 ❌

**URL:** `DELETE /api/v1/admin/nganh-nghes/1` (CNTT — có 6 con)

| HTTP Status | `422` |
| `message` | "Không thể xoá. Ngành nghề này có 6 danh mục con..." |

### TC-NN-AD-18 — Xoá ID không tồn tại → 404 ❌

**URL:** `DELETE /api/v1/admin/nganh-nghes/9999`

| HTTP Status | `404 Not Found` |

---

## 7. Tổng hợp

| STT | Mã | Chức năng | Method | Mong đợi |
|----:|------|-----------|:------:|:--------:|
| 1 | TC-NN-PUB-01 | Public — Tất cả | GET | `200` ✅ |
| 2 | TC-NN-PUB-02 | Public — Ngành gốc | GET | `200` ✅ |
| 3 | TC-NN-PUB-03 | Public — Con theo cha | GET | `200` ✅ |
| 4 | TC-NN-PUB-04 | Public — Tìm kiếm | GET | `200` ✅ |
| 5 | TC-NN-PUB-05 | Public — Dạng cây | GET | `200` ✅ |
| 6 | TC-NN-PUB-06 | Public — Chi tiết | GET | `200` ✅ |
| 7 | TC-NN-PUB-07 | Public — Ngành ẩn | GET | `404` ❌ |
| 8 | TC-NN-PUB-08 | Public — ID không tồn tại | GET | `404` ❌ |
| 9 | TC-NN-AD-01 | Admin — Danh sách | GET | `200` ✅ |
| 10 | TC-NN-AD-02 | Admin — Lọc ẩn | GET | `200` ✅ |
| 11 | TC-NN-AD-03 | Admin — Lọc gốc | GET | `200` ✅ |
| 12 | TC-NN-AD-04 | Admin — Tìm kiếm | GET | `200` ✅ |
| 13 | TC-NN-AD-05 | Admin — Thống kê | GET | `200` ✅ |
| 14 | TC-NN-AD-06 | Admin — UV/NTD không quyền | GET | `403` ❌ |
| 15 | TC-NN-AD-07 | Admin — Tạo gốc | POST | `201` ✅ |
| 16 | TC-NN-AD-08 | Admin — Tạo con | POST | `201` ✅ |
| 17 | TC-NN-AD-09 | Admin — Thiếu tên | POST | `422` ❌ |
| 18 | TC-NN-AD-10 | Admin — Cha không tồn tại | POST | `422` ❌ |
| 19 | TC-NN-AD-11 | Admin — Chi tiết (cả ẩn) | GET | `200` ✅ |
| 20 | TC-NN-AD-12 | Admin — Đổi tên + slug | PUT | `200` ✅ |
| 21 | TC-NN-AD-13 | Admin — Cha = chính nó | PUT | `422` ❌ |
| 22 | TC-NN-AD-14 | Admin — Ẩn | PATCH | `200` ✅ |
| 23 | TC-NN-AD-15 | Admin — Hiển thị lại | PATCH | `200` ✅ |
| 24 | TC-NN-AD-16 | Admin — Xoá lá | DELETE | `200` ✅ |
| 25 | TC-NN-AD-17 | Admin — Xoá có con | DELETE | `422` ❌ |
| 26 | TC-NN-AD-18 | Admin — Xoá không tồn tại | DELETE | `404` ❌ |

---

**Tổng cộng: 26 kịch bản**

| Loại | Số lượng | Tỉ lệ |
|------|:--------:|:-----:|
| ✅ Happy Path | 18 | 69% |
| ❌ Error Case | 8 | 31% |
