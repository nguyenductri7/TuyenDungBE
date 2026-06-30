# 📋 Kịch Bản Kiểm Thử API — Module Công Ty (cong_tys)

**Dự án:** Khóa luận tốt nghiệp (KLTN)  
**Module:** Quản lý Công ty  
**Base URL:** `http://localhost:8000/api/v1`  
**Ngày lập:** 11/03/2026  

---

## 1. Thông tin chung

### 1.1 Phân quyền

| Vai trò | Quyền trên công ty |
|---------|-------------------|
| Public | Xem danh sách & chi tiết (chỉ công ty đang hoạt động) |
| NTD | Tạo/xem/cập nhật công ty **của chính mình** (tối đa 1) |
| Admin | Full CRUD + thống kê + đổi trạng thái |

### 1.2 Tài khoản test

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Admin | `admin@kltn.com` | `Admin@123` |
| NTD 1 | `tuyen.dung1@kltn.com` | `NTD@123456` |
| NTD 2 | `tuyen.dung2@kltn.com` | `NTD@123456` |
| UV | `ung.vien1@kltn.com` | `UV@123456` |

### 1.3 Dữ liệu sau seed

- **NTD 1:** TechViet Solutions (CNTT, 51-200 nhân viên)
- **NTD 2:** DigiGrowth Agency (Marketing, 11-50 nhân viên)

---

## 2. Public — Danh sách & Chi tiết

### TC-CT-PUB-01 — Tất cả công ty

**URL:** `GET /api/v1/cong-tys`

| Tiêu chí | Kết quả mong đợi |
|----------|-----------------| 
| HTTP Status | `200 OK` |
| Chỉ hiện | `trang_thai = 1` (hoạt động) |

### TC-CT-PUB-02 — Tìm kiếm

**URL:** `GET /api/v1/cong-tys?search=TechViet`

### TC-CT-PUB-03 — Lọc theo ngành nghề

**URL:** `GET /api/v1/cong-tys?nganh_nghe_id=1`

### TC-CT-PUB-04 — Chi tiết

**URL:** `GET /api/v1/cong-tys/1`

| `data.ten_cong_ty` | "TechViet Solutions" |

### TC-CT-PUB-05 — ID không tồn tại → 404 ❌

**URL:** `GET /api/v1/cong-tys/9999`

---

## 3. NTD — Quản lý công ty

### TC-CT-NTD-01 — Xem công ty của mình

**URL:** `GET /api/v1/nha-tuyen-dung/cong-ty`  
**Auth:** NTD 1

| `data.ten_cong_ty` | "TechViet Solutions" |

### TC-CT-NTD-02 — Tạo công ty (đã có → 422) ❌

**Auth:** NTD 1 (đã có công ty)

| HTTP Status | `422` |
| `message` | "Bạn đã tạo công ty rồi..." |

### TC-CT-NTD-03 — Cập nhật tên

```json
{ "ten_cong_ty": "TechViet Pro" }
```

| `data.ten_cong_ty` | "TechViet Pro" |
| `data.slug` | tự cập nhật |

### TC-CT-NTD-04 — Cập nhật nhiều trường

```json
{ "dia_chi": "Địa chỉ mới", "quy_mo": "201-500" }
```

### TC-CT-NTD-05 — Quy mô không hợp lệ → 422 ❌

```json
{ "quy_mo": "abc" }
```

### TC-CT-NTD-06 — UV gọi API NTD → 403 ❌

**Auth:** UV

### TC-CT-NTD-07 — Thiếu tên khi tạo → 422 ❌

```json
{ "mo_ta": "test" }
```

---

## 4. Admin — Full CRUD + Thống kê

### TC-CT-AD-01 — Danh sách tất cả

| Hiện cả hoạt động và tạm ngưng |

### TC-CT-AD-02 — Lọc trạng thái

**URL:** `GET /api/v1/admin/cong-tys?trang_thai=1`

### TC-CT-AD-03 — Thống kê

```json
HTTP 200
{
    "data": {
        "tong": 2,
        "hoat_dong": 2,
        "tam_ngung": 0,
        "theo_quy_mo": {...}
    }
}
```

### TC-CT-AD-04 — Chi tiết (Admin)

**URL:** `GET /api/v1/admin/cong-tys/1`

### TC-CT-AD-05 — Cập nhật (Admin)

```json
{ "ten_cong_ty": "Admin Edit Corp" }
```

### TC-CT-AD-06 — Đổi trạng thái

**URL:** `PATCH /api/v1/admin/cong-tys/1/trang-thai`

| `data.trang_thai` | Đổi từ 1 → 0 hoặc 0 → 1 |

### TC-CT-AD-07 — Xoá

**URL:** `DELETE /api/v1/admin/cong-tys/2`

### TC-CT-AD-08 — UV gọi Admin API → 403 ❌

---

## 5. Tổng hợp

| STT | Mã | Chức năng | Method | Mong đợi |
|----:|------|-----------|:------:|:--------:|
| 1 | TC-CT-PUB-01 | Public — Tất cả | GET | `200` ✅ |
| 2 | TC-CT-PUB-02 | Public — Tìm kiếm | GET | `200` ✅ |
| 3 | TC-CT-PUB-03 | Public — Lọc ngành | GET | `200` ✅ |
| 4 | TC-CT-PUB-04 | Public — Chi tiết | GET | `200` ✅ |
| 5 | TC-CT-PUB-05 | Public — 404 | GET | `404` ❌ |
| 6 | TC-CT-NTD-01 | NTD — Xem | GET | `200` ✅ |
| 7 | TC-CT-NTD-02 | NTD — Tạo trùng | POST | `422` ❌ |
| 8 | TC-CT-NTD-03 | NTD — Cập nhật tên | PUT | `200` ✅ |
| 9 | TC-CT-NTD-04 | NTD — Cập nhật nhiều | PUT | `200` ✅ |
| 10 | TC-CT-NTD-05 | NTD — Quy mô sai | PUT | `422` ❌ |
| 11 | TC-CT-NTD-06 | NTD — UV không quyền | GET | `403` ❌ |
| 12 | TC-CT-NTD-07 | NTD — Thiếu tên | POST | `422` ❌ |
| 13 | TC-CT-AD-01 | Admin — Tất cả | GET | `200` ✅ |
| 14 | TC-CT-AD-02 | Admin — Lọc TT | GET | `200` ✅ |
| 15 | TC-CT-AD-03 | Admin — Thống kê | GET | `200` ✅ |
| 16 | TC-CT-AD-04 | Admin — Chi tiết | GET | `200` ✅ |
| 17 | TC-CT-AD-05 | Admin — Cập nhật | PUT | `200` ✅ |
| 18 | TC-CT-AD-06 | Admin — Đổi TT | PATCH | `200` ✅ |
| 19 | TC-CT-AD-07 | Admin — Xoá | DELETE | `200` ✅ |
| 20 | TC-CT-AD-08 | Admin — UV 403 | GET | `403` ❌ |

---

**Tổng cộng: 20 kịch bản**

| Loại | Số lượng | Tỉ lệ |
|------|:--------:|:-----:|
| ✅ Happy Path | 14 | 70% |
| ❌ Error Case | 6 | 30% |
