# 📋 Kịch Bản Kiểm Thử API — Module Kỹ Năng Người Dùng (nguoi_dung_ky_nangs)

**Dự án:** Khóa luận tốt nghiệp (KLTN)  
**Module:** Quản lý Kỹ năng cá nhân (pivot: người dùng ↔ kỹ năng)  
**Base URL:** `http://localhost:8000/api/v1`  
**Ngày lập:** 11/03/2026  

---

## 1. Thông tin chung

### 1.1 Phân quyền

| Vai trò | Quyền |
|---------|-------|
| Ứng viên | CRUD kỹ năng của chính mình (thêm/sửa/xoá) |
| NTD | Xem kỹ năng ứng viên (qua hồ sơ công khai) |
| Admin | Xem tất cả bản ghi, thống kê, xem theo user |

### 1.2 Tài khoản test

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Admin | `admin@kltn.com` | `Admin@123` |
| UV 1 | `ung.vien1@kltn.com` | `UV@123456` |
| UV 2 | `ung.vien2@kltn.com` | `UV@123456` |

### 1.3 Mức độ thành thạo

| Giá trị | Nhãn |
|:-------:|------|
| 1 | Cơ bản |
| 2 | Trung bình |
| 3 | Khá |
| 4 | Giỏi |
| 5 | Chuyên gia |

### 1.4 Dữ liệu sau seed

- **UV 1:** 9 kỹ năng (PHP, Laravel, JS, React, MySQL, Docker, Git, REST API, Tiếng Anh)
- **UV 2:** 7 kỹ năng (JS, TypeScript, React, Vue.js, Next.js, Figma, Git)
- **UV khác:** 3-6 kỹ năng ngẫu nhiên mỗi người

---

## 2. Ứng viên — Xem kỹ năng

### TC-NDKN-UV-01 — Xem kỹ năng của mình

**URL:** `GET /api/v1/ung-vien/ky-nangs`  
**Auth:** UV 1

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Số bản ghi | 9 |
| Mỗi bản ghi có | `ky_nang.ten_ky_nang`, `muc_do`, `nam_kinh_nghiem` |

### TC-NDKN-UV-02 — Chưa đăng nhập → 401 ❌

**URL:** `GET /api/v1/ung-vien/ky-nangs`  
**Auth:** Không

| HTTP Status | `401 Unauthorized` |

---

## 3. Ứng viên — Thêm kỹ năng

### TC-NDKN-UV-03 — Thêm kỹ năng đầy đủ

**URL:** `POST /api/v1/ung-vien/ky-nangs`

```json
{
    "ky_nang_id": 26,
    "muc_do": 3,
    "nam_kinh_nghiem": 2
}
```

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `201 Created` |
| `data.ky_nang.ten_ky_nang` | "AWS" |
| `data.muc_do` | `3` |

### TC-NDKN-UV-04 — Thêm trùng kỹ năng → 422 ❌

```json
{ "ky_nang_id": 1, "muc_do": 3, "nam_kinh_nghiem": 1 }
```

(UV 1 đã có PHP — ky_nang_id = 1)

| HTTP Status | `422` |
| `message` | "Bạn đã thêm kỹ năng này rồi." |

### TC-NDKN-UV-05 — Thiếu ky_nang_id → 422 ❌

```json
{ "muc_do": 3 }
```

| HTTP Status | `422` |

### TC-NDKN-UV-06 — Mức độ không hợp lệ → 422 ❌

```json
{ "ky_nang_id": 20, "muc_do": 9 }
```

| HTTP Status | `422` |
| `errors.muc_do` | "Mức độ phải từ 1 (Cơ bản) đến 5 (Chuyên gia)." |

### TC-NDKN-UV-07 — ky_nang_id không tồn tại → 422 ❌

```json
{ "ky_nang_id": 9999, "muc_do": 3 }
```

| HTTP Status | `422` |

### TC-NDKN-UV-08 — Năm kinh nghiệm âm → 422 ❌

```json
{ "ky_nang_id": 20, "muc_do": 3, "nam_kinh_nghiem": -1 }
```

| HTTP Status | `422` |

---

## 4. Ứng viên — Cập nhật & Xoá

### TC-NDKN-UV-09 — Cập nhật mức độ

**URL:** `PUT /api/v1/ung-vien/ky-nangs/1`

```json
{ "muc_do": 5, "nam_kinh_nghiem": 4 }
```

| `data.muc_do` | `5` |
| `data.nam_kinh_nghiem` | `4` |

### TC-NDKN-UV-10 — Chỉ cập nhật mức độ

```json
{ "muc_do": 4 }
```

| `data.muc_do` | `4` |

### TC-NDKN-UV-11 — Cập nhật bản ghi không phải của mình → 404 ❌

**Auth:** UV 2  
**URL:** `PUT /api/v1/ung-vien/ky-nangs/1` (bản ghi thuộc UV 1)

| HTTP Status | `404` |

### TC-NDKN-UV-12 — Xoá kỹ năng thành công

**URL:** `DELETE /api/v1/ung-vien/ky-nangs/1`

| HTTP Status | `200 OK` |
| `message` | "Xoá kỹ năng thành công." |

### TC-NDKN-UV-13 — Xoá bản ghi không phải của mình → 404 ❌

**Auth:** UV 2  
**URL:** `DELETE /api/v1/ung-vien/ky-nangs/2` (bản ghi thuộc UV 1)

| HTTP Status | `404` |

### TC-NDKN-UV-14 — NTD/Admin gọi API UV → 403 ❌

**Auth:** Admin  
**URL:** `GET /api/v1/ung-vien/ky-nangs`

| HTTP Status | `403 Forbidden` |

---

## 5. Admin — Xem & Thống kê

### TC-NDKN-AD-01 — Danh sách tất cả

**URL:** `GET /api/v1/admin/nguoi-dung-ky-nangs`

| HTTP Status | `200 OK` |
| Mỗi bản ghi có | `nguoi_dung.ho_ten`, `ky_nang.ten_ky_nang` |

### TC-NDKN-AD-02 — Lọc theo user ID

**URL:** `GET /api/v1/admin/nguoi-dung-ky-nangs?nguoi_dung_id=2`

| Tất cả bản ghi | `nguoi_dung_id = 2` |

### TC-NDKN-AD-03 — Lọc theo mức độ

**URL:** `GET /api/v1/admin/nguoi-dung-ky-nangs?muc_do=5`

| Tất cả bản ghi | `muc_do = 5` |

### TC-NDKN-AD-04 — Thống kê

**URL:** `GET /api/v1/admin/nguoi-dung-ky-nangs/thong-ke`

```json
HTTP 200
{
    "success": true,
    "data": {
        "tong_ban_ghi": ...,
        "so_nguoi_co_ky_nang": ...,
        "theo_muc_do": {...},
        "top_ky_nang": [...]
    }
}
```

### TC-NDKN-AD-05 — Kỹ năng của 1 user

**URL:** `GET /api/v1/admin/nguoi-dung-ky-nangs/nguoi-dung/2`

| Tất cả bản ghi | `nguoi_dung_id = 2` |
| Sắp xếp | `muc_do` giảm dần |

### TC-NDKN-AD-06 — UV gọi Admin API → 403 ❌

**Auth:** UV  
**URL:** `GET /api/v1/admin/nguoi-dung-ky-nangs`

| HTTP Status | `403 Forbidden` |

---

## 6. Tổng hợp

| STT | Mã | Chức năng | Method | Mong đợi |
|----:|------|-----------|:------:|:--------:|
| 1 | TC-NDKN-UV-01 | UV — Xem kỹ năng | GET | `200` ✅ |
| 2 | TC-NDKN-UV-02 | UV — Chưa đăng nhập | GET | `401` ❌ |
| 3 | TC-NDKN-UV-03 | UV — Thêm đầy đủ | POST | `201` ✅ |
| 4 | TC-NDKN-UV-04 | UV — Thêm trùng | POST | `422` ❌ |
| 5 | TC-NDKN-UV-05 | UV — Thiếu ky_nang_id | POST | `422` ❌ |
| 6 | TC-NDKN-UV-06 | UV — Mức độ sai | POST | `422` ❌ |
| 7 | TC-NDKN-UV-07 | UV — KN không tồn tại | POST | `422` ❌ |
| 8 | TC-NDKN-UV-08 | UV — Năm KN âm | POST | `422` ❌ |
| 9 | TC-NDKN-UV-09 | UV — Cập nhật đầy đủ | PUT | `200` ✅ |
| 10 | TC-NDKN-UV-10 | UV — Cập nhật 1 trường | PUT | `200` ✅ |
| 11 | TC-NDKN-UV-11 | UV — Sửa không phải mình | PUT | `404` ❌ |
| 12 | TC-NDKN-UV-12 | UV — Xoá | DELETE | `200` ✅ |
| 13 | TC-NDKN-UV-13 | UV — Xoá không phải mình | DELETE | `404` ❌ |
| 14 | TC-NDKN-UV-14 | UV — NTD/Admin gọi API UV | GET | `403` ❌ |
| 15 | TC-NDKN-AD-01 | Admin — Tất cả | GET | `200` ✅ |
| 16 | TC-NDKN-AD-02 | Admin — Lọc user | GET | `200` ✅ |
| 17 | TC-NDKN-AD-03 | Admin — Lọc mức độ | GET | `200` ✅ |
| 18 | TC-NDKN-AD-04 | Admin — Thống kê | GET | `200` ✅ |
| 19 | TC-NDKN-AD-05 | Admin — Kỹ năng 1 user | GET | `200` ✅ |
| 20 | TC-NDKN-AD-06 | Admin — UV không quyền | GET | `403` ❌ |

---

**Tổng cộng: 20 kịch bản**

| Loại | Số lượng | Tỉ lệ |
|------|:--------:|:-----:|
| ✅ Happy Path | 10 | 50% |
| ❌ Error Case | 10 | 50% |
