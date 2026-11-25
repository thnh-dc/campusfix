# QNU CampusFix 2.0 – Hệ thống Hỗ trợ Tiện ích và An ninh trong Trường Đại học

Đồ án môn học **Phân tích & Thiết kế Hệ thống Thông tin** (Nhóm 4 – Khoa CNTT – Trường Đại học Quy Nhơn).

## 1. Điểm mới trong phiên bản 2.0
- **Giao diện hiện đại, trẻ trung (Smart Campus UI v2.0):** Thiết kế lại toàn diện hệ thống màu sắc, typography (`Plus Jakarta Sans` / `Be Vietnam Pro`), thẻ card bo góc đổ bóng đa tầng, avatar người dùng, badge trạng thái có chấm chỉ báo, nút điền nhanh tài khoản kiểm thử và hộp thoại Modal xác nhận đăng xuất chuẩn UC0-03.
- **Bản đồ khuôn viên trường 3D chuyên nghiệp (Three.js Architectural Visualization):**
  - Bầu trời gradient 3D (Sky Dome) hỗ trợ **3 chế độ ánh sáng: Sáng / Hoàng hôn / Ban đêm** (cửa sổ tòa nhà và hệ thống đèn đường tự động phát sáng vào ban đêm).
  - Cảnh quan khuôn viên hoàn chỉnh: nền cỏ xanh, tường rào, sân quảng trường lát gạch, đài phun nước, cột cờ QNU, sân thể thao ngoài trời, đường nhựa có vỉa hè, vạch kẻ đường đứt nét, vạch qua đường zebra và cây xanh tán tròn low-poly nhiều tầng.
  - Kiến trúc 3D đặc trưng theo từng loại công trình (Tòa nhà 15T có khối đế + tháp kính + ăng-ten đèn chớp; Tòa nhà Thể chất mái vòm cong; Thư viện mái giếng trời kính; Hội trường có sảnh đón; KTX có bồn nước sân thượng; Khu thực hành có cụm HVAC; Cổng chính QNU kiến trúc hiện đại có dải đèn LED).
  - Hiệu ứng **sóng Radar 3D (Live Incident Pulse)** trực quan trên các tòa nhà đang có sự cố/yêu cầu camera.
  - Bộ công cụ HUD 3D chuyên nghiệp: Thanh lọc nhanh loại công trình ngay trên bản đồ, La bàn 3D xoay đồng bộ camera, nút chuyển góc nhìn nhanh (`Tổng quan`, `Tòa 15T`, `Cổng QNU`, `2D/3D`), ẩn/hiện nhãn và Slide-over Panel chi tiết tòa nhà.

## 2. Kiến trúc 5 Phân hệ (Bám sát 100% Báo cáo)
- **SUB-00 (Truy cập chung):** Bản đồ 3D khuôn viên trường, Đăng nhập (khóa tạm 30 phút nếu sai quá 5 lần), Đăng ký, Cập nhật thông tin cá nhân, Đăng xuất (Modal xác nhận).
- **SUB-01 (Quản lý & Phản ánh sự cố tiện ích):** Gửi phản ánh sự cố, Tiếp nhận & phân loại, Chuyển xử lý, Cập nhật kết quả khắc phục, Theo dõi & Tra cứu lịch sử.
- **SUB-02 (Tìm đồ & Trả đồ thất lạc):** Đăng tin báo mất / nhặt được tài sản, Tra cứu tin, Hệ thống nhắn tin hội thoại trao trả đồ, Quản lý trạng thái tin cá nhân.
- **SUB-03 (Yêu cầu kiểm tra camera & Báo cáo an ninh):** Gửi yêu cầu trích xuất camera theo khung giờ/khu vực, Tiếp nhận & chuyển bộ phận an ninh, Cập nhật kết quả kiểm tra camera, Theo dõi & tra cứu lịch sử.
- **SUB-04 (Quản trị hệ thống):** Quản lý tài khoản & phân quyền (6 vai trò), Quản lý danh mục sự cố/an ninh, Quản lý tòa nhà & phòng học, Tra cứu & kiểm duyệt tin vi phạm, Thống kê biểu đồ & xuất báo cáo CSV.

## 3. Hướng dẫn cài đặt trên XAMPP
1. Giải nén và copy thư mục `campusfix2.0` vào `C:\xampp\htdocs\campusfix2.0`.
2. Mở **XAMPP Control Panel**, khởi động **Apache** và **MySQL**.
3. Truy cập `http://localhost/phpmyadmin`, Import trực tiếp tệp `database/database.sql` (gồm 17 bảng chuẩn hóa và dữ liệu mẫu).
4. Truy cập hệ thống tại: `http://localhost/campusfix2.0/index.php`.

## 4. Tài khoản mẫu kiểm thử (Mật khẩu chung: `123456`)
- **Sinh viên:** `sinhvien` / `123456`
- **Giảng viên:** `giangvien` / `123456`
- **Nhân viên tiếp nhận:** `tiepnhan` / `123456`
- **Nhân viên xử lý sự cố:** `xuly` / `123456`
- **Nhân viên an ninh:** `anninh` / `123456`
- **Quản trị viên:** `admin` / `123456`
