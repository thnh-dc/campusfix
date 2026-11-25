-- ============================================================================
-- CƠ SỞ DỮ LIỆU: HỆ THỐNG HỖ TRỢ TIỆN ÍCH VÀ AN NINH TRONG TRƯỜNG ĐẠI HỌC
-- Chuẩn hóa theo Báo cáo Nhóm 4 - Khoa CNTT - Trường Đại học Quy Nhơn (17 bảng)
-- Mật khẩu mặc định cho tất cả tài khoản mẫu: 123456
-- ============================================================================

CREATE DATABASE IF NOT EXISTS campus_utility_security
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE campus_utility_security;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Attachment;
DROP TABLE IF EXISTS SUB3_result;
DROP TABLE IF EXISTS SUB3_divison;
DROP TABLE IF EXISTS SUB3_request;
DROP TABLE IF EXISTS SUB2_message;
DROP TABLE IF EXISTS SUB2_conversation;
DROP TABLE IF EXISTS SUB2_post;
DROP TABLE IF EXISTS SUB1_result;
DROP TABLE IF EXISTS SUB1_division;
DROP TABLE IF EXISTS SUB1_reflect;
DROP TABLE IF EXISTS PhongHoc;
DROP TABLE IF EXISTS ToaNha;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS Notify;
DROP TABLE IF EXISTS HeThongSession;
DROP TABLE IF EXISTS TaiKhoan;
DROP TABLE IF EXISTS `User`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Bảng User
CREATE TABLE `User` (
  MaUser        INT AUTO_INCREMENT PRIMARY KEY,
  MaSo          VARCHAR(20)  NOT NULL UNIQUE,
  HoTen         VARCHAR(100) NOT NULL,
  NgaySinh      DATE         NULL,
  GioiTinh      ENUM('Nam','Nu','Khac') NOT NULL DEFAULT 'Nam',
  Email         VARCHAR(100) NOT NULL UNIQUE,
  SoDienThoai   VARCHAR(15)  NULL,
  DonVi         VARCHAR(150) NULL,
  AnhDaiDien    VARCHAR(255) NULL,
  LoaiNguoiDung ENUM('SV','GV','NV','QTV') NOT NULL DEFAULT 'SV',
  NgayTao       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Bảng TaiKhoan
CREATE TABLE TaiKhoan (
  MaTaiKhoan       INT AUTO_INCREMENT PRIMARY KEY,
  MaUser           INT          NOT NULL UNIQUE,
  TenDangNhap      VARCHAR(50)  NOT NULL UNIQUE,
  MatKhauHash      VARCHAR(255) NOT NULL,
  VaiTro           ENUM('SINH_VIEN','GIANG_VIEN','NV_TIEP_NHAN','NV_SU_CO','NV_AN_NINH','QUAN_TRI') NOT NULL DEFAULT 'SINH_VIEN',
  TrangThai        ENUM('HOAT_DONG','KHOA') NOT NULL DEFAULT 'HOAT_DONG',
  SoLanDangNhapSai TINYINT      NOT NULL DEFAULT 0,
  KhoaDenLuc       DATETIME     NULL,
  LyDoKhoa         VARCHAR(255) NULL,
  ThoiDiemKhoa     DATETIME     NULL,
  LanDangNhapCuoi  DATETIME     NULL,
  NgayTao          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_taikhoan_user FOREIGN KEY (MaUser) REFERENCES `User`(MaUser) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Bảng HeThongSession
CREATE TABLE HeThongSession (
  MaSession      INT AUTO_INCREMENT PRIMARY KEY,
  MaTaiKhoan     INT          NOT NULL,
  SessionToken   CHAR(64)     NOT NULL UNIQUE,
  TrinhDuyet     VARCHAR(255) NULL,
  ThoiGianTao    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ThoiGianHetHan DATETIME     NOT NULL,
  TrangThai      ENUM('CON_HIEU_LUC','HET_HAN','DA_DANG_XUAT') NOT NULL DEFAULT 'CON_HIEU_LUC',
  CONSTRAINT fk_session_taikhoan FOREIGN KEY (MaTaiKhoan) REFERENCES TaiKhoan(MaTaiKhoan) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Bảng Notify
CREATE TABLE Notify (
  MaThongBao    INT AUTO_INCREMENT PRIMARY KEY,
  MaNguoiNhan   INT          NOT NULL,
  TieuDe        VARCHAR(200) NOT NULL,
  NoiDung       TEXT         NOT NULL,
  LoaiThongBao  ENUM('SU_CO','TIM_DO','AN_NINH','HE_THONG') NOT NULL DEFAULT 'HE_THONG',
  LoaiThamChieu VARCHAR(30)  NULL,
  MaThamChieu   INT          NULL,
  DaDoc         TINYINT(1)   NOT NULL DEFAULT 0,
  ThoiGianTao   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notify_user FOREIGN KEY (MaNguoiNhan) REFERENCES `User`(MaUser) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Bảng Category
CREATE TABLE Category (
  MaDanhMuc   INT AUTO_INCREMENT PRIMARY KEY,
  LoaiDanhMuc ENUM('SU_CO','AN_NINH') NOT NULL,
  TenDanhMuc  VARCHAR(100) NOT NULL,
  MoTa        VARCHAR(255) NULL,
  TrangThai   ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
  UNIQUE KEY uq_category_loai_ten (LoaiDanhMuc, TenDanhMuc)
) ENGINE=InnoDB;

-- 6. Bảng ToaNha (KyHieu khớp 100% với ZONES[].id trên bản đồ 3D)
CREATE TABLE ToaNha (
  MaToaNha  INT AUTO_INCREMENT PRIMARY KEY,
  KyHieu    VARCHAR(20)  NOT NULL UNIQUE,
  TenToaNha VARCHAR(100) NOT NULL UNIQUE,
  SoTang    TINYINT      NOT NULL DEFAULT 1,
  MoTa      VARCHAR(255) NULL,
  TrangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG'
) ENGINE=InnoDB;

-- 7. Bảng PhongHoc
CREATE TABLE PhongHoc (
  MaPhong   INT AUTO_INCREMENT PRIMARY KEY,
  MaToaNha  INT         NOT NULL,
  TenPhong  VARCHAR(50) NOT NULL,
  Tang      TINYINT     NOT NULL DEFAULT 1,
  LoaiPhong ENUM('LY_THUYET','THUC_HANH','HOI_TRUONG','KHAC') NOT NULL DEFAULT 'LY_THUYET',
  SucChua   SMALLINT    NOT NULL DEFAULT 50,
  TrangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
  CONSTRAINT fk_phong_toanha FOREIGN KEY (MaToaNha) REFERENCES ToaNha(MaToaNha) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 8. Bảng SUB1_reflect (Phản ánh sự cố tiện ích)
CREATE TABLE SUB1_reflect (
  MaPhanAnh    INT AUTO_INCREMENT PRIMARY KEY,
  MaNguoiGui   INT          NOT NULL,
  MaDanhMuc    INT          NOT NULL,
  MaPhong      INT          NOT NULL,
  ViTriChiTiet VARCHAR(255) NULL,
  TieuDe       VARCHAR(200) NOT NULL,
  NoiDung      TEXT         NOT NULL,
  MucDoUuTien  ENUM('THAP','TRUNG_BINH','CAO','KHAN_CAP') NOT NULL DEFAULT 'TRUNG_BINH',
  TrangThai    ENUM('CHO_TIEP_NHAN','DA_TIEP_NHAN','DA_PHAN_CONG','DANG_XU_LY','DA_XU_LY','TU_CHOI','DONG') NOT NULL DEFAULT 'CHO_TIEP_NHAN',
  LyDoTuChoi   VARCHAR(255) NULL,
  NgayGui      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NgayCapNhat  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub1_user    FOREIGN KEY (MaNguoiGui) REFERENCES `User`(MaUser),
  CONSTRAINT fk_sub1_cat     FOREIGN KEY (MaDanhMuc)  REFERENCES Category(MaDanhMuc),
  CONSTRAINT fk_sub1_phong   FOREIGN KEY (MaPhong)    REFERENCES PhongHoc(MaPhong)
) ENGINE=InnoDB;

-- 9. Bảng SUB1_division (Phân công xử lý sự cố)
CREATE TABLE SUB1_division (
  MaPhanCong   INT AUTO_INCREMENT PRIMARY KEY,
  MaNhanVien   INT          NOT NULL,
  MaPhanAnh    INT          NOT NULL,
  NgayPhanCong DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  HanXuLy      DATETIME     NULL,
  TrangThai    ENUM('CHO_NHAN','DANG_XU_LY','HOAN_THANH','TU_CHOI') NOT NULL DEFAULT 'CHO_NHAN',
  GhiChu       VARCHAR(255) NULL,
  CONSTRAINT fk_sub1div_nv FOREIGN KEY (MaNhanVien) REFERENCES `User`(MaUser),
  CONSTRAINT fk_sub1div_pa FOREIGN KEY (MaPhanAnh)  REFERENCES SUB1_reflect(MaPhanAnh) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Bảng SUB1_result (Kết quả xử lý sự cố)
CREATE TABLE SUB1_result (
  MaKetQua     INT AUTO_INCREMENT PRIMARY KEY,
  MaPhanAnh    INT          NOT NULL,
  TrangThaiMoi ENUM('DANG_XU_LY','DA_XU_LY','TU_CHOI','DONG') NOT NULL DEFAULT 'DA_XU_LY',
  NoiDungXuLy  TEXT         NOT NULL,
  ThoiGianXuLy DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  GhiChu       VARCHAR(255) NULL,
  CONSTRAINT fk_sub1res_pa FOREIGN KEY (MaPhanAnh) REFERENCES SUB1_reflect(MaPhanAnh) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Bảng SUB2_post (Tin đăng tìm đồ / trả đồ thất lạc)
CREATE TABLE SUB2_post (
  MaTinDang        INT AUTO_INCREMENT PRIMARY KEY,
  MaNguoiDang      INT          NOT NULL,
  MaPhong          INT          NOT NULL,
  ViTriChiTiet     VARCHAR(255) NULL,
  LoaiTin          ENUM('MAT_DO','NHAT_DUOC') NOT NULL,
  TieuDe           VARCHAR(200) NOT NULL,
  TenTaiSan        VARCHAR(150) NOT NULL,
  MoTa             TEXT         NOT NULL,
  ThoiGianPhatSinh DATETIME     NOT NULL,
  TrangThai        ENUM('DANG_TIM','DA_TRA','DONG') NOT NULL DEFAULT 'DANG_TIM',
  NgayDang         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NgayCapNhat      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub2post_user  FOREIGN KEY (MaNguoiDang) REFERENCES `User`(MaUser),
  CONSTRAINT fk_sub2post_phong FOREIGN KEY (MaPhong)     REFERENCES PhongHoc(MaPhong)
) ENGINE=InnoDB;

-- 12. Bảng SUB2_conversation (Hội thoại trao đổi đồ thất lạc)
CREATE TABLE SUB2_conversation (
  MaHoiThoai     INT AUTO_INCREMENT PRIMARY KEY,
  MaTinDang      INT      NOT NULL,
  MaNguoiLienHe  INT      NOT NULL,
  TrangThai      ENUM('MO','DONG') NOT NULL DEFAULT 'MO',
  NgayTao        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TinNhanCuoiLuc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sub2_conv (MaTinDang, MaNguoiLienHe),
  CONSTRAINT fk_sub2conv_post FOREIGN KEY (MaTinDang)     REFERENCES SUB2_post(MaTinDang) ON DELETE CASCADE,
  CONSTRAINT fk_sub2conv_user FOREIGN KEY (MaNguoiLienHe) REFERENCES `User`(MaUser)
) ENGINE=InnoDB;

-- 13. Bảng SUB2_message (Tin nhắn chi tiết)
CREATE TABLE SUB2_message (
  MaTinNhan   INT AUTO_INCREMENT PRIMARY KEY,
  MaHoiThoai  INT        NOT NULL,
  MaNguoiGui  INT        NOT NULL,
  NoiDung     TEXT       NOT NULL,
  ThoiGianGui DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  DaDoc       TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_sub2msg_conv FOREIGN KEY (MaHoiThoai) REFERENCES SUB2_conversation(MaHoiThoai) ON DELETE CASCADE,
  CONSTRAINT fk_sub2msg_user FOREIGN KEY (MaNguoiGui) REFERENCES `User`(MaUser)
) ENGINE=InnoDB;

-- 14. Bảng SUB3_request (Yêu cầu kiểm tra camera & báo cáo an ninh)
CREATE TABLE SUB3_request (
  MaYeuCau       INT AUTO_INCREMENT PRIMARY KEY,
  MaNguoiGui     INT          NOT NULL,
  MaDanhMuc      INT          NOT NULL,
  MaPhong        INT          NOT NULL,
  ViTriChiTiet   VARCHAR(255) NULL,
  TieuDe         VARCHAR(200) NOT NULL,
  KhungGioBatDau DATETIME     NOT NULL,
  KhungGioKetThuc DATETIME    NOT NULL,
  LyDoYeuCau     TEXT         NOT NULL,
  TrangThai      ENUM('CHO_DUYET','DA_DUYET','DANG_KIEM_TRA','HOAN_THANH','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
  LyDoTuChoi     VARCHAR(255) NULL,
  NgayGui        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NgayCapNhat    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub3req_user  FOREIGN KEY (MaNguoiGui) REFERENCES `User`(MaUser),
  CONSTRAINT fk_sub3req_cat   FOREIGN KEY (MaDanhMuc)  REFERENCES Category(MaDanhMuc),
  CONSTRAINT fk_sub3req_phong FOREIGN KEY (MaPhong)    REFERENCES PhongHoc(MaPhong)
) ENGINE=InnoDB;

-- 15. Bảng SUB3_divison (Giữ nguyên tên bảng theo biểu đồ lớp thực thể trong báo cáo)
CREATE TABLE SUB3_divison (
  MaPhanCong   INT AUTO_INCREMENT PRIMARY KEY,
  MaYeuCau     INT          NOT NULL,
  MaNhanVien   INT          NOT NULL,
  NgayPhanCong DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  HanXuLy      DATETIME     NULL,
  TrangThai    ENUM('CHO_NHAN','DANG_XU_LY','HOAN_THANH','TU_CHOI') NOT NULL DEFAULT 'CHO_NHAN',
  GhiChu       VARCHAR(255) NULL,
  CONSTRAINT fk_sub3div_req FOREIGN KEY (MaYeuCau)   REFERENCES SUB3_request(MaYeuCau) ON DELETE CASCADE,
  CONSTRAINT fk_sub3div_nv  FOREIGN KEY (MaNhanVien) REFERENCES `User`(MaUser)
) ENGINE=InnoDB;

-- 16. Bảng SUB3_result (Kết quả kiểm tra camera)
CREATE TABLE SUB3_result (
  MaKetQua      INT AUTO_INCREMENT PRIMARY KEY,
  MaYeuCau      INT          NOT NULL,
  KetQuaKiemTra ENUM('CO_PHAT_HIEN','KHONG_PHAT_HIEN','KHONG_CO_DU_LIEU') NOT NULL,
  TrangThaiMoi  ENUM('DANG_KIEM_TRA','HOAN_THANH','TU_CHOI','DONG') NOT NULL DEFAULT 'HOAN_THANH',
  KetLuan       TEXT         NOT NULL,
  ThoiGianXuLy  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  GhiChu        VARCHAR(255) NULL,
  CONSTRAINT fk_sub3res_req FOREIGN KEY (MaYeuCau) REFERENCES SUB3_request(MaYeuCau) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 17. Bảng Attachment (Tệp đính kèm đa thực thể)
CREATE TABLE Attachment (
  MaTepDinhKem INT AUTO_INCREMENT PRIMARY KEY,
  MaPhanAnh    INT          NULL,
  MaKetQuaSub1 INT          NULL,
  MaTinDang    INT          NULL,
  MaYeuCau     INT          NULL,
  MaKetQuaSub3 INT          NULL,
  LoaiTep      ENUM('HINH_ANH','VIDEO') NOT NULL DEFAULT 'HINH_ANH',
  TenTep       VARCHAR(255) NOT NULL,
  DuongDan     VARCHAR(255) NOT NULL,
  MimeType     VARCHAR(100) NOT NULL,
  KichThuoc    BIGINT       NOT NULL DEFAULT 0,
  MoTa         VARCHAR(255) NULL,
  NgayTai      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_sub1    FOREIGN KEY (MaPhanAnh)    REFERENCES SUB1_reflect(MaPhanAnh) ON DELETE CASCADE,
  CONSTRAINT fk_att_sub1res FOREIGN KEY (MaKetQuaSub1) REFERENCES SUB1_result(MaKetQua)   ON DELETE CASCADE,
  CONSTRAINT fk_att_sub2    FOREIGN KEY (MaTinDang)    REFERENCES SUB2_post(MaTinDang)    ON DELETE CASCADE,
  CONSTRAINT fk_att_sub3    FOREIGN KEY (MaYeuCau)     REFERENCES SUB3_request(MaYeuCau)  ON DELETE CASCADE,
  CONSTRAINT fk_att_sub3res FOREIGN KEY (MaKetQuaSub3) REFERENCES SUB3_result(MaKetQua)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- DỮ LIỆU MẪU PHỤC VỤ KIỂM THỬ & BÁO CÁO (Mật khẩu chung: 123456)
-- Hash chuẩn PHP password_hash('123456', PASSWORD_DEFAULT)
-- ============================================================================
INSERT INTO `User` (MaUser, MaSo, HoTen, NgaySinh, GioiTinh, Email, SoDienThoai, DonVi, LoaiNguoiDung) VALUES
(1, '4551050001', 'Nguyễn Thành A', '2004-05-12', 'Nam', 'a.sv@st.qnu.edu.vn',   '0912345001', 'Khoa Công nghệ thông tin - K45', 'SV'),
(2, 'GV00102',    'Nguyễn Thành B',    '1985-08-20', 'Nam', 'b.gv@qnu.edu.vn',      '0912345002', 'Khoa Công nghệ thông tin',       'GV'),
(3, 'NVTN01',     'Nguyễn Văn B',   '1996-03-15', 'Nam', 'b.tiepnhan@qnu.edu.vn','0912345003', 'Phòng Hành chính - Tiếp nhận',   'NV'),
(4, 'NVSC01',     'Nguyễn Văn C', '1994-11-02', 'Nam', 'c.kythuat@qnu.edu.vn', '0912345004', 'Tổ Quản trị thiết bị & Điện nước','NV'),
(5, 'NVAN01',     'Nguyễn Văn D',    '1992-07-19', 'Nam', 'd.anninh@qnu.edu.vn',   '0912345005', 'Ban Bảo vệ & An ninh trường',    'NV'),
(6, 'QTV001',     'Quản Trị Viên',         '1990-01-10', 'Nam', 'admin@qnu.edu.vn',        '0912345006', 'Trung tâm CNTT & Truyền thông',  'QTV');

INSERT INTO TaiKhoan (MaTaiKhoan, MaUser, TenDangNhap, MatKhauHash, VaiTro, TrangThai) VALUES
(1, 1, 'sinhvien',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SINH_VIEN',    'HOAT_DONG'),
(2, 2, 'giangvien', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GIANG_VIEN',   'HOAT_DONG'),
(3, 3, 'tiepnhan',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NV_TIEP_NHAN', 'HOAT_DONG'),
(4, 4, 'xuly',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NV_SU_CO',     'HOAT_DONG'),
(5, 5, 'anninh',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NV_AN_NINH',   'HOAT_DONG'),
(6, 6, 'admin',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'QUAN_TRI',     'HOAT_DONG');

-- Danh mục sự cố & an ninh
INSERT INTO Category (MaDanhMuc, LoaiDanhMuc, TenDanhMuc, MoTa, TrangThai) VALUES
(1, 'SU_CO',   'Thiết bị phòng học',          'Máy chiếu, màn chiếu, loa, micro, máy tính giảng đường', 'HOAT_DONG'),
(2, 'SU_CO',   'Điện / Đèn / Quạt / Điều hòa','Mất điện cục bộ, hỏng bóng đèn, quạt trần, điều hòa',     'HOAT_DONG'),
(3, 'SU_CO',   'Nước / Nhà vệ sinh',          'Rò rỉ ống nước, tắc nghẽn, hỏng thiết bị vệ sinh',      'HOAT_DONG'),
(4, 'SU_CO',   'Bàn ghế / Cơ sở vật chất',    'Bàn ghế gãy, cửa kính vỡ, khóa cửa hỏng',               'HOAT_DONG'),
(5, 'SU_CO',   'Wifi / Hạ tầng mạng',         'Mất kết nối Wifi, hỏng ổ cắm mạng LAN phòng thực hành', 'HOAT_DONG'),
(6, 'AN_NINH', 'Mất cắp / Thất lạc xe',       'Mất xe máy, xe đạp, mũ bảo hiểm tại nhà xe/khuôn viên',  'HOAT_DONG'),
(7, 'AN_NINH', 'Va quẹt / Sự cố giao thông',  'Va chạm phương tiện trong khuôn viên và bãi đỗ xe',      'HOAT_DONG'),
(8, 'AN_NINH', 'Mất tài sản giá trị lớn',     'Trích xuất camera tìm laptop, điện thoại, balo thất lạc','HOAT_DONG'),
(9, 'AN_NINH', 'Gây rối / Vi phạm nội quy',   'Người lạ xâm nhập, gây mất trật tự an ninh khuôn viên',  'HOAT_DONG');

-- 23 Tòa nhà / Khu vực khớp 100% với bản đồ 3D (js/map-data.js)
INSERT INTO ToaNha (MaToaNha, KyHieu, TenToaNha, SoTang, MoTa) VALUES
(1,  'A1',      'Khu giảng đường A1',              4,  'Giảng đường lý thuyết phía Tây Nam'),
(2,  'A2',      'Khu giảng đường A2',              4,  'Giảng đường lý thuyết trung tâm Tây'),
(3,  'A3',      'Khu giảng đường A3',              4,  'Giảng đường lý thuyết phía Bắc'),
(4,  'A5',      'Khu thực hành A5',                3,  'Phòng thí nghiệm và thực hành'),
(5,  'A6',      'Khu thực hành A6',                3,  'Xưởng thực hành kỹ thuật'),
(6,  'A7',      'Khu thực hành A7',                3,  'Phòng thực hành chuyên ngành'),
(7,  'A8',      'Khu giảng đường A8',              4,  'Giảng đường lý thuyết phía Bắc'),
(8,  '15T',     'Tòa nhà 15 tầng (15T)',           15, 'Tòa nhà điều hành, văn phòng khoa và giảng đường'),
(9,  '4T',      'Tòa nhà thực hành 4 tầng (4T)',   4,  'Trung tâm thực hành CNTT và Ngoại ngữ'),
(10, 'TC',      'Tòa nhà Giáo dục Thể chất',       3,  'Nhà thi đấu và khu thể chất'),
(11, 'KTX',     'Ký túc xá sinh viên',             5,  'Khu nội trú sinh viên phía Đông'),
(12, 'KTX-YT',  'Ký túc xá và Trạm Y tế',          5,  'Khu nội trú kết hợp trạm y tế trường'),
(13, 'CANTIN',  'Khu Căn tin trường',              2,  'Khu ăn uống và dịch vụ sinh viên'),
(14, 'HT-A',    'Hội trường A',                    2,  'Hội trường tổ chức sự kiện lớn'),
(15, 'HT-B',    'Hội trường B',                    2,  'Hội trường hội thảo phía Đông'),
(16, 'THUVIEN', 'Trung tâm Thông tin - Thư viện',  4,  'Thư viện trung tâm và phòng tự học'),
(17, 'TH-NVLN', 'Khu thực hành NV LN & KS',        3,  'Khu thực hành Nông lâm & Khách sạn'),
(18, 'TX-A1',   'Khu vực dựng xe A1',              1,  'Bãi giữ xe sinh viên khu A1'),
(19, 'TX-A2a',  'Khu vực dựng xe A2 (Tây)',        1,  'Bãi giữ xe sinh viên phía Tây A2'),
(20, 'TX-A2b',  'Khu vực dựng xe A2 (Bắc)',        1,  'Bãi giữ xe sinh viên phía Bắc A2'),
(21, 'TX-A2c',  'Khu vực dựng xe A2 (Nam)',        1,  'Bãi giữ xe lớn gần cổng giảng đường'),
(22, 'TX-A3',   'Trại xe A3 (Sinh viên)',          1,  'Bãi giữ xe sinh viên khu A3'),
(23, 'TX-A3GV', 'Trại xe A3 (Giảng viên)',         1,  'Bãi giữ xe dành cho giảng viên khu A3');

-- Phòng học / Khu vực con cho từng tòa nhà
INSERT INTO PhongHoc (MaPhong, MaToaNha, TenPhong, Tang, LoaiPhong, SucChua) VALUES
(1,  1,  'Phòng A1.101', 1, 'LY_THUYET',  80),
(2,  1,  'Phòng A1.203', 2, 'LY_THUYET',  80),
(3,  2,  'Phòng A2.301', 3, 'LY_THUYET',  100),
(4,  3,  'Phòng A3.102', 1, 'LY_THUYET',  70),
(5,  4,  'Phòng TN A5.201', 2, 'THUC_HANH', 45),
(6,  5,  'Xưởng A6.101', 1, 'THUC_HANH',  50),
(7,  6,  'Phòng TH A7.105', 1, 'THUC_HANH', 45),
(8,  7,  'Phòng A8.202', 2, 'LY_THUYET',  90),
(9,  8,  'Phòng 15T.502', 5, 'LY_THUYET', 60),
(10, 8,  'Sảnh tầng 1 - 15T', 1, 'KHAC', 150),
(11, 9,  'Phòng Lab 4T.304', 3, 'THUC_HANH', 50),
(12, 10, 'Sân bóng rổ / Nhà thể chất', 1, 'KHAC', 300),
(13, 11, 'Khu B - Tầng 2 KTX', 2, 'KHAC', 100),
(14, 12, 'Phòng Y tế tầng 1', 1, 'KHAC', 30),
(15, 13, 'Khu vực quầy ăn Căn tin', 1, 'KHAC', 200),
(16, 14, 'Khán phòng Hội trường A', 1, 'HOI_TRUONG', 400),
(17, 15, 'Khán phòng Hội trường B', 1, 'HOI_TRUONG', 250),
(18, 16, 'Phòng đọc tầng 2 Thư viện', 2, 'KHAC', 120),
(19, 17, 'Phòng TH Du lịch 102', 1, 'THUC_HANH', 40),
(20, 18, 'Khu vực dãy B - Trại xe A1', 1, 'KHAC', 200),
(21, 19, 'Khu vực dãy 1 - Trại xe A2 Tây', 1, 'KHAC', 150),
(22, 20, 'Khu vực mái che - Trại xe A2 Bắc', 1, 'KHAC', 150),
(23, 21, 'Khu vực cổng ra vào - Trại xe A2 Nam', 1, 'KHAC', 350),
(24, 22, 'Khu vực dãy A - Trại xe A3', 1, 'KHAC', 250),
(25, 23, 'Khu vực mái che - Trại xe GV', 1, 'KHAC', 100);

-- Dữ liệu mẫu SUB-01: Phản ánh sự cố tiện ích
INSERT INTO SUB1_reflect (MaPhanAnh, MaNguoiGui, MaDanhMuc, MaPhong, ViTriChiTiet, TieuDe, NoiDung, MucDoUuTien, TrangThai, NgayGui) VALUES
(1, 1, 1, 2, 'Bục giảng phòng A1.203', 'Máy chiếu phòng A1.203 mất tín hiệu HDMI', 'Đèn nguồn máy chiếu vẫn sáng nhưng không nhận tín hiệu từ cáp HDMI trên bục giảng.', 'CAO', 'DA_PHAN_CONG', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 2, 9, 'Trần nhà góc phải phòng 15T.502', 'Điều hòa số 2 chảy nước và không lạnh', 'Máy lạnh phía cuối lớp chảy nước xuống bàn sinh viên khi bật chế độ làm mát.', 'TRUNG_BINH', 'CHO_TIEP_NHAN', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 1, 5, 11, 'Dãy máy số 3 - Lab 4T.304', 'Mất kết nối mạng LAN toàn bộ dãy 3', '10 máy tính dãy bên trái không truy cập được Internet phục vụ giờ thực hành.', 'KHAN_CAP', 'DA_XU_LY', DATE_SUB(NOW(), INTERVAL 4 DAY));

INSERT INTO SUB1_division (MaPhanCong, MaNhanVien, MaPhanAnh, NgayPhanCong, HanXuLy, TrangThai, GhiChu) VALUES
(1, 4, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY), 'DANG_XU_LY', 'Kiểm tra bộ chuyển đổi HDMI và dây tín hiệu âm tường.'),
(2, 4, 3, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 'HOAN_THANH', 'Đã thay switch mạng tầng 3.');

INSERT INTO SUB1_result (MaKetQua, MaPhanAnh, TrangThaiMoi, NoiDungXuLy, ThoiGianXuLy, GhiChu) VALUES
(1, 3, 'DA_XU_LY', 'Đã thay thế dây cáp mạng chính và khởi động lại bộ chia mạng Switch tại phòng Lab 4T.304. Tất cả máy hoạt động ổn định.', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Đã nghiệm thu cùng giảng viên bộ môn.');

-- Dữ liệu mẫu SUB-02: Tìm đồ & Trả đồ thất lạc
INSERT INTO SUB2_post (MaTinDang, MaNguoiDang, MaPhong, ViTriChiTiet, LoaiTin, TieuDe, TenTaiSan, MoTa, ThoiGianPhatSinh, TrangThai) VALUES
(1, 1, 18, 'Bàn tự học gần cửa sổ tầng 2 Thư viện', 'MAT_DO', 'Rơi ví da màu nâu kèm thẻ sinh viên K45', 'Ví da màu nâu + Thẻ SV', 'Trong ví có thẻ sinh viên mang tên Nguyễn Thành Được, CCCD và một ít tiền mặt.', DATE_SUB(NOW(), INTERVAL 1 DAY), 'DANG_TIM'),
(2, 2, 2, 'Hộc bàn hàng thứ 3 phòng A1.203', 'NHAT_DUOC', 'Nhặt được bình giữ nhiệt Lock&Lock màu xanh đen', 'Bình giữ nhiệt Lock&Lock', 'Bình dung tích 500ml có dán sticker khoa CNTT, nhặt được sau ca học sáng.', DATE_SUB(NOW(), INTERVAL 6 HOUR), 'DANG_TIM');

INSERT INTO SUB2_conversation (MaHoiThoai, MaTinDang, MaNguoiLienHe, TrangThai, NgayTao, TinNhanCuoiLuc) VALUES
(1, 2, 1, 'MO', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR));

INSERT INTO SUB2_message (MaTinNhan, MaHoiThoai, MaNguoiGui, NoiDung, ThoiGianGui, DaDoc) VALUES
(1, 1, 1, 'Chào thầy ạ, em là sinh viên lớp K45 CNTT. Sáng nay em có học tiết 1-3 ở phòng A1.203 và để quên bình nước màu xanh đen có dán sticker QNU ạ.', DATE_SUB(NOW(), INTERVAL 3 HOUR), 1),
(2, 1, 2, 'Chào em, đúng đặc điểm rồi. Chiều nay 14h00 em ghé văn phòng Khoa CNTT tầng 4 nhà 15T nhận lại nhé.', DATE_SUB(NOW(), INTERVAL 2 HOUR), 0);

-- Dữ liệu mẫu SUB-03: Yêu cầu kiểm tra camera & Báo cáo an ninh
INSERT INTO SUB3_request (MaYeuCau, MaNguoiGui, MaDanhMuc, MaPhong, ViTriChiTiet, TieuDe, KhungGioBatDau, KhungGioKetThuc, LyDoYeuCau, TrangThai, NgayGui) VALUES
(1, 1, 6, 23, 'Cổng ra vào bãi giữ xe A2 Nam', 'Kiểm tra camera tìm mũ bảo hiểm bị lấy nhầm', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 22 HOUR), 'Em dựng xe Wave xanh biển số 77L1-xxxx tại dãy gần cổng ra vào từ 13h30 đến 15h30, khi ra lấy xe thì bị mất mũ bảo hiểm.', 'DANG_KIEM_TRA', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(2, 2, 7, 25, 'Lối rẽ vào trại xe giảng viên A3', 'Trích xuất camera xác minh va quẹt xe ô tô', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 70 HOUR), 'Xe đỗ trong khu vực mái che bị trầy xước phần cản sau trong khoảng thời gian họp hội đồng.', 'CHO_DUYET', DATE_SUB(NOW(), INTERVAL 4 HOUR));

INSERT INTO SUB3_divison (MaPhanCong, MaYeuCau, MaNhanVien, NgayPhanCong, HanXuLy, TrangThai, GhiChu) VALUES
(1, 1, 5, DATE_SUB(NOW(), INTERVAL 18 HOUR), DATE_ADD(NOW(), INTERVAL 6 HOUR), 'DANG_XU_LY', 'Kiểm tra góc camera số 04 hướng cổng bãi xe A2.');

-- Thông báo mẫu
INSERT INTO Notify (MaNguoiNhan, TieuDe, NoiDung, LoaiThongBao, LoaiThamChieu, MaThamChieu, DaDoc) VALUES
(1, 'Phản ánh #1 đã được phân công xử lý', 'Phản ánh "Máy chiếu phòng A1.203 mất tín hiệu HDMI" đã được chuyển cho nhân viên kỹ thuật Hứa Phan Tấn Dũng.', 'SU_CO', 'SUB1_reflect', 1, 0),
(1, 'Có phản hồi mới về tin nhặt được đồ', 'Giảng viên Phan Đình Sinh đã trả lời tin nhắn của bạn về bình giữ nhiệt Lock&Lock.', 'TIM_DO', 'SUB2_post', 2, 0),
(3, 'Có yêu cầu kiểm tra camera mới #2', 'Giảng viên Phan Đình Sinh vừa gửi yêu cầu trích xuất camera tại Trại xe A3 (Giảng viên).', 'AN_NINH', 'SUB3_request', 2, 0);

ALTER TABLE TaiKhoan MODIFY MatKhauHash VARCHAR(255) NOT NULL;

-- Bổ sung dữ liệu phòng học demo (tầng 1: 101-110, tầng 2: 201-210, tầng 3: 301-310) cho tất cả tòa nhà
INSERT INTO PhongHoc (MaToaNha, TenPhong, Tang, LoaiPhong, SucChua, TrangThai)
SELECT t.MaToaNha,
       CONCAT(fl.tang, LPAD(rm.so, 2, '0')) AS TenPhong,
       fl.tang AS Tang,
       CASE WHEN fl.tang = 3 THEN 'THUC_HANH' ELSE 'LY_THUYET' END AS LoaiPhong,
       60 AS SucChua,
       'HOAT_DONG' AS TrangThai
FROM ToaNha t
CROSS JOIN (SELECT 1 AS tang UNION ALL SELECT 2 UNION ALL SELECT 3) fl
CROSS JOIN (
    SELECT 1 AS so UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) rm
WHERE NOT EXISTS (
    SELECT 1 FROM PhongHoc p
    WHERE p.MaToaNha = t.MaToaNha
      AND p.TenPhong = CONCAT(fl.tang, LPAD(rm.so, 2, '0'))
);