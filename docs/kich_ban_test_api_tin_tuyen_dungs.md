# Kịch bản Test API Tin Tuyển Dụng (`tin_tuyen_dungs`)

Tài liệu này mô tả các kịch bản kiểm thử (Test Cases) cho hệ thống API Tin Tuyển Dụng.

---

## 1️⃣ PUBLIC (Không yêu cầu đăng nhập)

*   **Tính năng:** Mọi người dùng đều có thể xem danh sách và chi tiết tin tuyển dụng đang hoạt động.
*   **Điều kiện lọc mặc định:** `trang_thai = 1` (Hoạt động) VÀ `ngay_het_han >= hôm nay` VÀ `Công ty đang hoạt động`.

### SC1.1: Xem danh sách tin tuyển dụng
*   **Request:** `GET /api/v1/tin-tuyen-dungs`
*   **Expect:** `200 OK`. Trả về mảng `data` chứa các tin hợp lệ. Có thông tin `cong_ty` và mảng `nganh_nghes`. Pagination meta (per_page mặc định 15).

### SC1.2: Lọc tin tuyển dụng theo từ khoá tìm kiếm
*   **Request:** `GET /api/v1/tin-tuyen-dungs?search=Laravel`
*   **Expect:** `200 OK`. Chỉ trả về các tin có từ khoá "Laravel" ở tiêu đề, mô tả, hoặc địa điểm, hoặc tên công ty.

### SC1.3: Xem chi tiết 1 tin tuyển dụng
*   **Request:** `GET /api/v1/tin-tuyen-dungs/{id_hop_le}`
*   **Expect:** `200 OK`. Trả về chi tiết tin. Lượt xem (`luot_xem`) đã được tự động cộng thêm 1 trong DB.

### SC1.4: Xem chi tiết tin tạm ngưng / hết hạn / công ty bị khóa
*   **Request:** `GET /api/v1/tin-tuyen-dungs/{id_tin_tam_ngung}`
*   **Expect:** `404 Not Found`.

---

## 2️⃣ NHÀ TUYỂN DỤNG (Yêu cầu đăng nhập, `vai_tro = 1`)

*   **Lưu ý:** NTD chỉ thao tác được trên các tin thuộc `cong_ty_id` của họ. Cần thiết lập công ty trước (`POST /nha-tuyen-dung/cong-ty`).

### SC2.1: NTD xem danh sách tin của chính mình
*   **Request:** `GET /api/v1/nha-tuyen-dung/tin-tuyen-dungs`
*   **Expect:** `200 OK`. Trả về tất cả các tin của công ty này (cả tạm ngưng, cả hết hạn). Cấu trúc pagination.

### SC2.2: NTD tạo tin tuyển dụng MỚI (Hợp lệ)
*   **Request:** `POST /api/v1/nha-tuyen-dung/tin-tuyen-dungs`
    *   Body:
        ```json
        {
            "tieu_de": "Tuyển dụng Dev",
            "mo_ta_cong_viec": "Làm web",
            "dia_diem_lam_viec": "Hà Nội",
            "so_luong_tuyen": 3,
            "nganh_nghes": [1, 2]
        }
        ```
*   **Expect:** `201 Created`. Trả về tin vừa tạo. Mối quan hệ N-N với `nganh_nghes` được lưu vào bảng `chi_tiet_nganh_nghes`.

### SC2.3: NTD tạo tin nhưng quên gắn Lĩnh Vực/Ngành Nghề
*   **Request:** `POST /api/v1/nha-tuyen-dung/tin-tuyen-dungs`
    *   Body: (thiếu mảng `nganh_nghes`)
*   **Expect:** `422 Unprocessable Entity`. Lỗi "Vui lòng chọn ít nhất 1 ngành nghề."

### SC2.4: NTD cập nhật tin
*   **Request:** `PUT /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id_tin_cua_NTD}`
    *   Body: `{"tieu_de": "Đổi tiêu đề", "nganh_nghes": [5]}`
*   **Expect:** `200 OK`. `tieu_de` thay đổi. Các ngành nghề cũ bị xoá và thay bằng ngành có ID = 5.

### SC2.5: NTD đổi trạng thái (Pause / Resume tin)
*   **Request:** `PATCH /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id}`
*   **Expect:** `200 OK`. Giá trị `trang_thai` đảo ngược (1 -> 0, hoặc 0 -> 1).

### SC2.6: NTD cố gắng xoá tin của CÔNG TY KHÁC
*   **Request:** `DELETE /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id_tin_cong_ty_khac}`
*   **Expect:** `404 Not Found` (Do scope model where(cong_ty_id) => FailOrFail => 404).

---

## 3️⃣ ADMIN (Yêu cầu đăng nhập, `vai_tro = 2`)

### SC3.1: Admin xem thống kê tin
*   **Request:** `GET /api/v1/admin/tin-tuyen-dungs/thong-ke`
*   **Expect:** `200 OK`. Trả về `tong_tin`, `hoat_dong`, `tam_ngung`.

### SC3.2: Admin xem danh sách toàn bộ tin
*   **Request:** `GET /api/v1/admin/tin-tuyen-dungs`
*   **Expect:** `200 OK`. Pagination, có filter truyền vào `?cong_ty_id=1&trang_thai=0`.

### SC3.3: Admin đổi trạng thái tin duyệt sai quy định
*   **Request:** `PATCH /api/v1/admin/tin-tuyen-dungs/{id}/trang-thai`
*   **Expect:** `200 OK`. Trạng thái đổi từ 1 -> 0.

### SC3.4: Admin tạo tin giúp đối tác
*   **Request:** `POST /api/v1/admin/tin-tuyen-dungs`
    *   Body: Giống NTD nhưng **phải có thêm** `"cong_ty_id": 1`
*   **Expect:** `201 Created`. Tạo vào DB với `cong_ty_id` chỉ định.

### SC3.5: Admin xoá cứng tin spam
*   **Request:** `DELETE /api/v1/admin/tin-tuyen-dungs/{id_bat_ky}`
*   **Expect:** `200 OK`. Bản tin biến mất khỏi DB hoàn toàn (Cùng bản ghi khóa ngoại trên bảng chi tiết ngành nghề biến mất theo - cascade).
