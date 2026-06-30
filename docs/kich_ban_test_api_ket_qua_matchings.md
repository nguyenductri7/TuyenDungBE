# Kịch bản Test API Việc Làm Gợi Ý AI (`ket_qua_matchings`)

Tài liệu này xác minh khả năng hoạt động của hệ thống trả kết quả Gợi ý Việc làm từ Thuật toán AI cho Ứng viên và màn hình Monitor (Tracking performance) của Admin.

---

## 1️⃣ ỨNG VIÊN (Yêu cầu đăng nhập, `vai_tro = 0`)

*   **Tính năng:** Ứng viên truy cập vào Tab "Việc làm AI gợi ý cho bạn" (Smart Match). Hệ thống tự động truy vấn các job có điểm số phù hợp cao nhất gửi đến màn hình của ứng viên.

### SC1.1: Ứng viên lấy danh sách Việc làm Gợi ý
*   **Request:** `GET /api/v1/ung-vien/ket-qua-matchings`
*   **Điều kiện cần:** Header `Authorization: Bearer <Token_Ung_Vien>`.
*   **Expect:** `200 OK`. 
    * Trả về mảng JSON các `ket_qua_matchings` chứa `tinTuyenDung` và `hoSo`. 
    * Chỉ trả về các job thuộc quyền sở hữu của chính ứng viên đăng nhập (Bảo mật).
    * Sắp xếp ưu tiên: `diem_phu_hop` giảm dần (Từ 100 điểm rớt xuống).
    * Bắt buộc phải có object JSON `chi_tiet_diem` (Giải thích tại sao AI cho điểm này) và chuỗi `danh_sach_ky_nang_thieu`.

### SC1.2: Ứng viên lọc Gợi ý theo 1 CV nằm trong hệ thống
*   **Request:** `GET /api/v1/ung-vien/ket-qua-matchings?ho_so_id=1`
*   **Điều kiện cần:** Ứng viên đang có nhiều CV (CV Code Backend, CV BA). Ứng viên bấm Lọc kết quả cho CV BA.
*   **Expect:** `200 OK`. Chỉ trả danh sách các AI Match Record thuộc về `ho_so_id = 1`.

---

## 2️⃣ ADMIN / AI ENGINEER (Yêu cầu đăng nhập, `vai_tro = 2`)

### SC2.1: Kỹ sư xem toàn bộ lịch sử điểm số do AI chấm trên toàn hệ thống
*   **Request:** `GET /api/v1/admin/ket-qua-matchings`
*   **Expect:** `200 OK`. Nhìn thấy tất cả hồ sơ match với tất cả công việc. Dùng để Debug xem thuật toán chấm đúng hay sai.

### SC2.2: Lọc các kết quả bị "Failed" (Match lỏng lẻo < 40 điểm)
*   **Request:** `GET /api/v1/admin/ket-qua-matchings?max_score=40`
*   **Expect:** `200 OK`. Chỉ lấy các bản ghi có `diem_phu_hop <= 40`. Giúp Kỹ sư kiểm tra liệu AI có đang soi quá gắt hay không.

### SC2.3: Bộ lọc Version AI
*   **Request:** `GET /api/v1/admin/ket-qua-matchings?model_version=v1.0-tfidf`
*   **Expect:** `200 OK`.

### SC2.4: Admin lấy Thống Kê tổng quan Hiệu suất AI
*   **Request:** `GET /api/v1/admin/ket-qua-matchings/thong-ke`
*   **Expect:** `200 OK`. 
    * Trả về mảng gộp (Group By `model_version`).
    * Có các chỉ số đo lường hiệu suất: `total_matches` (Tổng lượng bản ghi đã sinh), `average_score` (Điểm match trung bình toàn sàn), `max_score`, `min_score`.
