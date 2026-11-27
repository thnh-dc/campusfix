# QNU CampusFix – Hệ thống Hỗ trợ Tiện ích và An ninh trong Trường Đại học

Đồ án môn học **Thực hành làm việc nhóm**
Nhóm 4

## 1. Kiến trúc hệ thống gồm 5 Phân hệ:
- **SUB-00 (Truy cập chung):** Bản đồ 3D khuôn viên trường, Đăng nhập (khóa tạm 30 phút nếu sai quá 5 lần), Đăng ký, Cập nhật thông tin cá nhân, Đăng xuất (Modal xác nhận).
- **SUB-01 (Quản lý & Phản ánh sự cố tiện ích):** Gửi phản ánh sự cố, Tiếp nhận & phân loại, Chuyển xử lý, Cập nhật kết quả khắc phục, Theo dõi & Tra cứu lịch sử.
- **SUB-02 (Tìm đồ & Trả đồ thất lạc):** Đăng tin báo mất / nhặt được tài sản, Tra cứu tin, Hệ thống nhắn tin hội thoại trao trả đồ, Quản lý trạng thái tin cá nhân.
- **SUB-03 (Yêu cầu kiểm tra camera & Báo cáo an ninh):** Gửi yêu cầu trích xuất camera theo khung giờ/khu vực, Tiếp nhận & chuyển bộ phận an ninh, Cập nhật kết quả kiểm tra camera, Theo dõi & tra cứu lịch sử.
- **SUB-04 (Quản trị hệ thống):** Quản lý tài khoản & phân quyền (6 vai trò), Quản lý danh mục sự cố/an ninh, Quản lý tòa nhà & phòng học, Tra cứu & kiểm duyệt tin vi phạm, Thống kê biểu đồ & xuất báo cáo CSV.

## 2. Hướng dẫn cài đặt trên XAMPP
1. Giải nén và copy thư mục `campusfix` vào `C:\xampp\htdocs\campusfix`.
2. Mở **XAMPP Control Panel**, khởi động **Apache** và **MySQL**.
3. Truy cập `http://localhost/phpmyadmin`, Import trực tiếp tệp `database/database.sql` (gồm 17 bảng chuẩn hóa và dữ liệu mẫu).
4. Truy cập hệ thống tại: `http://localhost/campusfix2.0/index.php`.

## 3. Tài khoản mẫu kiểm thử (Mật khẩu chung: `123456`)
- **Sinh viên:** `sinhvien` / `123456`
- **Giảng viên:** `giangvien` / `123456`
- **Nhân viên tiếp nhận:** `tiepnhan` / `123456`
- **Nhân viên xử lý sự cố:** `xuly` / `123456`
- **Nhân viên an ninh:** `anninh` / `123456`
- **Quản trị viên:** `admin` / `123456`
