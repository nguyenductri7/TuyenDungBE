# Kịch bản Test API Tư Vấn Nghề Nghiệp (AI Báo cáo) (`tu_van_nghe_nghieps`)

Tài liệu này xác minh khả năng hoạt động của hệ thống sinh Báo Cáo Phân Tích Nghề Nghiệp do Thuật toán định hướng gửi thẳng cho tài khoản sinh viên.

---

## 1️⃣ ỨNG VIÊN (Yêu cầu đăng nhập, `vai_tro = 0`)

*   **Tính năng:** Ứng viên truy cập vào Tab "Góc Phân Tích Định Hướng". Server gọi tự động toàn bộ báo cáo từ bảng `tu_van_nghe_nghieps` xuống UI.

### SC1.1: Ứng viên lấy danh sách Lời khuyên định hướng
*   **Request:** `GET /api/v1/ung-vien/tu-van-nghe-nghieps`
*   **Điều kiện cần:** Header `Authorization: Bearer <Token_Ung_Vien>`.
*   **Expect:** `200 OK`. 
    * Trả về mảng JSON chỉ chứa báo cáo của chính ứng viên đăng nhập (Bảo mật data `nguoi_dung_id`).
    * Có thuộc tính `nghe_de_xuat` và `goi_y_ky_nang_bo_sung`.
    * Được sắp xếp theo độ tự tin giảm dần (`muc_do_phu_hop` desc).

### SC1.2: Ứng viên lọc Báo cáo theo 1 CV cũ
*   **Request:** `GET /api/v1/ung-vien/tu-van-nghe-nghieps?ho_so_id=1`
*   **Expect:** `200 OK`. Lọc ráo riết theo CV số 1.

---

## 2️⃣ ADMIN / CHUYÊN GIA HỆ THỐNG (Yêu cầu đăng nhập, `vai_tro = 2`)

### SC2.1: Quản trị viên theo dõi hoạt động Bot Tư Vấn
*   **Request:** `GET /api/v1/admin/tu-van-nghe-nghieps`
*   **Expect:** `200 OK`. Nhìn thấy tất cả lời khuyên AI đã ban hành cho toàn bộ User.

### SC2.2: Lọc các kết quả có tính chính xác (Confidence) siêu cao
*   **Request:** `GET /api/v1/admin/tu-van-nghe-nghieps?min_score=90`
*   **Expect:** `200 OK`. Trả về những lời nhận định mà AI tin chắc tới mức 90%.

### SC2.3: Lọc xem có bao nhiêu người bị kéo vào Job Backend
*   **Request:** `GET /api/v1/admin/tu-van-nghe-nghieps?nghe_de_xuat=Backend`
*   **Expect:** `200 OK`. Lọc theo chuỗi Pattern Matching của Nghề nghiệp.

### SC2.4: Admin lấy Thống Kê Tổng Quan Phễu Nghề Nghiệp
*   **Request:** `GET /api/v1/admin/tu-van-nghe-nghieps/thong-ke`
*   **Expect:** `200 OK`. 
    * Trả về mảng JSON Group By theo Tên Nghề (VD: Backend - Bao nhiêu người, Data Analytic - Bao nhiêu người).
    * Bảng Dashboard đo lường được "Xu hướng nghề nghiệp hot" trên toàn app hiện tại (Trending Market Analysis).
