# Kịch bản Test API Lưu Tin Tuyển Dụng (`luu_tins`)

Tài liệu này mô tả các kịch bản kiểm thử (Test Cases) cho hệ thống API "Ứng viên Lưu Tin Tuyển Dụng" (Tính năng tương tự Bookmark / Yêu thích).

---

## 1️⃣ ỨNG VIÊN (Yêu cầu đăng nhập, `vai_tro = 0`)

*   **Tính năng:** Ứng viên lưu các tin tuyển dụng mà họ quan tâm để nộp hồ sơ sau, hoặc hiển thị trang Quản lý tin đã lưu.
*   **Điểm mạnh Backend:** Ứng dụng "Toggle API" của Laravel (Chạm lần 1 = Lưu, Chạm lần 2 = Hủy lưu) thông qua 1 đường dẫn API duy nhất.

### SC1.1: Ứng viên xem danh sách các tin đã lưu
*   **Request:** `GET /api/v1/ung-vien/tin-da-luu`
*   **Điều kiện cần:** Truyền Header `Authorization: Bearer <Token_Ung_Vien>`
*   **Expect:** `200 OK`. Trả về biến `data` chứa các bản ghi `TinTuyenDung` mà ứng viên đã bấm lưu, kèm thông tin công ty và ngành nghề. Kết quả sort theo "Thời gian mới lưu nhất".

### SC1.2: Ứng viên BẤM LƯU MỘT TIN (Chưa từng lưu)
*   **Request:** `POST /api/v1/ung-vien/tin-da-luu/1/toggle` (tin_id = 1)
*   **Điều kiện cần:** Giả sử tin ID=1 tồn tại và ứng viên chưa từng lưu tin này.
*   **Expect:** `201 Created`. JSON response: `message: "Đã lưu tin tuyển dụng"`, kèm `trang_thai_luu: true`. Tin đã được nhét vào bảng trung gian `luu_tins`.

### SC1.3: Ứng viên BẤM BỎ LƯU CHÍNH TIN ĐÓ (Chạm lần 2)
*   **Request:** `POST /api/v1/ung-vien/tin-da-luu/1/toggle`
*   **Điều kiện cần:** Ở SC1.2 ứng viên đã lưu tin số 1, giờ bấm lại vào ID=1 một lần nữa.
*   **Expect:** `200 OK`. JSON response: `message: "Đã bỏ lưu tin tuyển dụng"`, kèm `trang_thai_luu: false`. Bản ghi đã tự động bị xóa khỏi bảng trung gian `luu_tins`.

### SC1.4: Cố bấm lưu tin KHÔNG TỒN TẠI
*   **Request:** `POST /api/v1/ung-vien/tin-da-luu/99999/toggle` (tin_id = 99999 không có trong Databse)
*   **Expect:** `404 Not Found`. Báo lỗi không tìm thấy tin tuyển dụng.

---

## 2️⃣ ADMIN (Yêu cầu đăng nhập, `vai_tro = 2`)

### SC2.1: Admin xem thống kê "Tin được lưu nhiều nhất" (Top Bookmarked)
*   **Request:** `GET /api/v1/admin/luu-tins/thong-ke`
*   **Điều kiện cần:** Header `Authorization: Bearer <Token_Admin>`
*   **Expect:** `200 OK`. Trả về mảng 10 tin tuyển dụng (kèm tên công ty) được ứng viên lưu nhiều nhất. Sắp xếp `nguoi_dung_luus_count` giảm dần.

---

## 3️⃣ NHÀ TUYỂN DỤNG
*   **Tính năng:** Không có quyền truy cập module `luu_tins`. Họ chỉ lên đăng tin. Danh tính ứng viên lưu tin thuộc về quyền riêng tư của ứng viên, nên NTD không được tự ý thấy (Luật bảo mật thông tin ứng viên chuẩn quốc tế).
