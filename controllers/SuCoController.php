<?php
require_once __DIR__ . '/../config/functions.php';

class SuCoController {
    public function gui(): void {
        requireLogin();
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maToaNha  = (int)($_POST['MaToaNha'] ?? 0);
            $maPhong   = (int)($_POST['MaPhong'] ?? 0);
            $maDanhMuc = (int)($_POST['MaDanhMuc'] ?? 0);
            $viTri     = trim($_POST['ViTriChiTiet'] ?? '');
            $tieuDe    = trim($_POST['TieuDe'] ?? '');
            $noiDung   = trim($_POST['NoiDung'] ?? '');
            $uuTien    = in_array($_POST['MucDoUuTien'] ?? '', ['THAP','TRUNG_BINH','CAO','KHAN_CAP'], true) ? $_POST['MucDoUuTien'] : 'TRUNG_BINH';

            if ($maToaNha <= 0 || $maPhong <= 0 || $maDanhMuc <= 0 || $tieuDe === '' || $noiDung === '') {
                setFlash('err', 'Vui lòng chọn tòa nhà, phòng học và nhập đầy đủ thông tin bắt buộc.');
                redirect('index.php?c=suco&a=gui');
            }

            $stCheckRoom = $pdo->prepare("SELECT COUNT(*) FROM PhongHoc WHERE MaPhong = ? AND MaToaNha = ? AND TrangThai = 'HOAT_DONG'");
            $stCheckRoom->execute([$maPhong, $maToaNha]);
            if ((int)$stCheckRoom->fetchColumn() === 0) {
                setFlash('err', 'Phòng học không thuộc tòa nhà đã chọn.');
                redirect('index.php?c=suco&a=gui');
            }

            $up = uploadFile($_FILES['tep_dinh_kem'] ?? [], 'suco', false);
            if (!$up['ok']) {
                setFlash('err', $up['error']);
                redirect('index.php?c=suco&a=gui');
            }

            $st = $pdo->prepare("INSERT INTO SUB1_reflect (MaNguoiGui, MaDanhMuc, MaPhong, ViTriChiTiet, TieuDe, NoiDung, MucDoUuTien) VALUES (?,?,?,?,?,?,?)");
            $st->execute([currentUser()['MaUser'], $maDanhMuc, $maPhong, $viTri, $tieuDe, $noiDung, $uuTien]);
            $maPA = (int)$pdo->lastInsertId();

            if ($up['data']) {
                $d = $up['data'];
                $pdo->prepare("INSERT INTO Attachment (MaPhanAnh, LoaiTep, TenTep, DuongDan, MimeType, KichThuoc) VALUES (?,?,?,?,?,?)")
                    ->execute([$maPA, $d['LoaiTep'], $d['TenTep'], $d['DuongDan'], $d['MimeType'], $d['KichThuoc']]);
            }

            guiThongBaoTheoVaiTro('NV_TIEP_NHAN', "Phản ánh sự cố mới #$maPA", $tieuDe, 'SU_CO', 'SUB1_reflect', $maPA);
            setFlash('ok', "Đã gửi phản ánh sự cố thành công (Mã phiếu: #$maPA).");
            redirect('index.php?c=suco&a=theodoi');
        }

        $toaNhas  = $pdo->query("SELECT * FROM ToaNha WHERE TrangThai = 'HOAT_DONG' ORDER BY TenToaNha ASC")->fetchAll();
        $phongs   = $pdo->query("SELECT p.*, t.TenToaNha FROM PhongHoc p JOIN ToaNha t ON p.MaToaNha = t.MaToaNha WHERE p.TrangThai = 'HOAT_DONG' AND t.TrangThai = 'HOAT_DONG' ORDER BY t.TenToaNha ASC, p.Tang ASC, p.TenPhong ASC")->fetchAll();
        $danhMucs = $pdo->query("SELECT * FROM Category WHERE LoaiDanhMuc = 'SU_CO' AND TrangThai = 'HOAT_DONG'")->fetchAll();

        $selectedToaNha = (int)($_GET['toanha'] ?? 0);
        $selectedPhong  = (int)($_GET['phong'] ?? 0);
        if ($selectedPhong > 0) {
            $matchedRoom = null;
            foreach ($phongs as $p) {
                if ((int)$p['MaPhong'] === $selectedPhong) {
                    $matchedRoom = $p;
                    break;
                }
            }
            if ($matchedRoom) {
                if ($selectedToaNha <= 0) {
                    $selectedToaNha = (int)$matchedRoom['MaToaNha'];
                } elseif ((int)$matchedRoom['MaToaNha'] !== $selectedToaNha) {
                    $selectedPhong = 0;
                }
            } else {
                $selectedPhong = 0;
            }
        }

        $activeMenu = 'suco_gui';
        require __DIR__ . '/../views/sub01_suco/gui_su_co.php';
    }

    public function tiepnhan(): void {
        requireRole(['NV_TIEP_NHAN', 'QUAN_TRI']);
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $maPA   = (int)($_POST['MaPhanAnh'] ?? 0);
            $stPA   = $pdo->prepare("SELECT * FROM SUB1_reflect WHERE MaPhanAnh = ?");
            $stPA->execute([$maPA]);
            $pa = $stPA->fetch();

            if ($pa) {
                if ($action === 'duyet') {
                    $pdo->prepare("UPDATE SUB1_reflect SET MaDanhMuc = ?, MucDoUuTien = ?, TrangThai = 'DA_TIEP_NHAN' WHERE MaPhanAnh = ?")
                        ->execute([(int)$_POST['MaDanhMuc'], $_POST['MucDoUuTien'], $maPA]);
                    guiThongBao((int)$pa['MaNguoiGui'], "Phản ánh #$maPA đã được tiếp nhận", "Phiếu \"{$pa['TieuDe']}\" đã được tiếp nhận và phân loại.", 'SU_CO', 'SUB1_reflect', $maPA);
                    setFlash('ok', "Đã tiếp nhận & phân loại phản ánh #$maPA.");
                } elseif ($action === 'tuchoi') {
                    $lyDo = trim($_POST['LyDoTuChoi'] ?? '');
                    if ($lyDo === '') {
                        setFlash('err', 'Bắt buộc nhập lý do từ chối tiếp nhận.');
                    } else {
                        $pdo->prepare("UPDATE SUB1_reflect SET TrangThai = 'TU_CHOI', LyDoTuChoi = ? WHERE MaPhanAnh = ?")
                            ->execute([$lyDo, $maPA]);
                        guiThongBao((int)$pa['MaNguoiGui'], "Phản ánh #$maPA bị từ chối", "Lý do: $lyDo", 'SU_CO', 'SUB1_reflect', $maPA);
                        setFlash('ok', "Đã từ chối phản ánh #$maPA.");
                    }
                } elseif ($action === 'phancong') {
                    $maNV   = (int)$_POST['MaNhanVien'];
                    $hanXL  = $_POST['HanXuLy'] ?: null;
                    $ghiChu = trim($_POST['GhiChu'] ?? '');
                    $pdo->prepare("INSERT INTO SUB1_division (MaNhanVien, MaPhanAnh, HanXuLy, GhiChu) VALUES (?,?,?,?)")
                        ->execute([$maNV, $maPA, $hanXL, $ghiChu]);
                    $pdo->prepare("UPDATE SUB1_reflect SET TrangThai = 'DA_PHAN_CONG' WHERE MaPhanAnh = ?")->execute([$maPA]);
                    guiThongBao($maNV, "Nhiệm vụ xử lý sự cố mới #$maPA", "Bạn được giao xử lý: {$pa['TieuDe']}", 'SU_CO', 'SUB1_reflect', $maPA);
                    guiThongBao((int)$pa['MaNguoiGui'], "Phản ánh #$maPA đã phân công kỹ thuật", "Nhân viên kỹ thuật đã tiếp nhận nhiệm vụ xử lý.", 'SU_CO', 'SUB1_reflect', $maPA);
                    setFlash('ok', "Đã chuyển phản ánh #$maPA cho nhân viên xử lý.");
                }
            }
            redirect('index.php?c=suco&a=tiepnhan');
        }

        $danhSach = $pdo->query("
            SELECT r.*, u.HoTen AS NguoiGui, c.TenDanhMuc, p.TenPhong, t.TenToaNha,
                   (SELECT DuongDan FROM Attachment WHERE MaPhanAnh = r.MaPhanAnh LIMIT 1) AS AnhHienTruong
            FROM SUB1_reflect r
            JOIN `User` u ON r.MaNguoiGui = u.MaUser
            JOIN Category c ON r.MaDanhMuc = c.MaDanhMuc
            JOIN PhongHoc p ON r.MaPhong = p.MaPhong
            JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
            WHERE r.TrangThai IN ('CHO_TIEP_NHAN','DA_TIEP_NHAN')
            ORDER BY FIELD(r.MucDoUuTien,'KHAN_CAP','CAO','TRUNG_BINH','THAP'), r.NgayGui DESC
        ")->fetchAll();
        $danhMucs  = $pdo->query("SELECT * FROM Category WHERE LoaiDanhMuc = 'SU_CO' AND TrangThai = 'HOAT_DONG'")->fetchAll();
        $nhanViens = $pdo->query("SELECT u.MaUser, u.HoTen, u.DonVi FROM `User` u JOIN TaiKhoan tk ON u.MaUser = tk.MaUser WHERE tk.VaiTro = 'NV_SU_CO' AND tk.TrangThai = 'HOAT_DONG'")->fetchAll();
        $activeMenu = 'suco_tiepnhan';
        require __DIR__ . '/../views/sub01_suco/tiep_nhan_su_co.php';
    }

    public function xuly(): void {
        requireRole(['NV_SU_CO', 'QUAN_TRI']);
        $pdo = db();
        $maNV = (int)currentUser()['MaUser'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maPA    = (int)$_POST['MaPhanAnh'];
            $maPC    = (int)$_POST['MaPhanCong'];
            $ttMoi   = $_POST['TrangThaiMoi'] ?? 'DA_XU_LY';
            $noiDung = trim($_POST['NoiDungXuLy'] ?? '');
            $ghiChu  = trim($_POST['GhiChu'] ?? '');

            if ($noiDung === '') {
                setFlash('err', 'Vui lòng nhập nội dung kết quả xử lý.');
                redirect('index.php?c=suco&a=xuly');
            }

            $up = uploadFile($_FILES['anh_ket_qua'] ?? [], 'suco', false);
            if (!$up['ok']) {
                setFlash('err', $up['error']);
                redirect('index.php?c=suco&a=xuly');
            }

            $pdo->prepare("INSERT INTO SUB1_result (MaPhanAnh, TrangThaiMoi, NoiDungXuLy, GhiChu) VALUES (?,?,?,?)")
                ->execute([$maPA, $ttMoi, $noiDung, $ghiChu]);
            $maKQ = (int)$pdo->lastInsertId();

            if ($up['data']) {
                $d = $up['data'];
                $pdo->prepare("INSERT INTO Attachment (MaKetQuaSub1, LoaiTep, TenTep, DuongDan, MimeType, KichThuoc) VALUES (?,?,?,?,?,?)")
                    ->execute([$maKQ, $d['LoaiTep'], $d['TenTep'], $d['DuongDan'], $d['MimeType'], $d['KichThuoc']]);
            }

            $divStatus = ($ttMoi === 'DA_XU_LY') ? 'HOAN_THANH' : 'DANG_XU_LY';
            $pdo->prepare("UPDATE SUB1_division SET TrangThai = ? WHERE MaPhanCong = ?")->execute([$divStatus, $maPC]);
            $pdo->prepare("UPDATE SUB1_reflect SET TrangThai = ? WHERE MaPhanAnh = ?")->execute([$ttMoi, $maPA]);

            $stPA = $pdo->prepare("SELECT MaNguoiGui, TieuDe FROM SUB1_reflect WHERE MaPhanAnh = ?");
            $stPA->execute([$maPA]);
            if ($pa = $stPA->fetch()) {
                guiThongBao((int)$pa['MaNguoiGui'], "Cập nhật xử lý sự cố #$maPA", $noiDung, 'SU_CO', 'SUB1_reflect', $maPA);
            }
            setFlash('ok', "Đã cập nhật kết quả xử lý sự cố #$maPA.");
            redirect('index.php?c=suco&a=xuly');
        }

        $where = hasRole(['QUAN_TRI']) ? '' : "WHERE d.MaNhanVien = $maNV";
        $nhiemVus = $pdo->query("
            SELECT d.*, r.TieuDe, r.NoiDung, r.ViTriChiTiet, r.MucDoUuTien, r.TrangThai AS TrangThaiPA,
                   p.TenPhong, t.TenToaNha, c.TenDanhMuc, u.HoTen AS NguoiGui,
                   (SELECT DuongDan FROM Attachment WHERE MaPhanAnh = r.MaPhanAnh LIMIT 1) AS AnhHienTruong
            FROM SUB1_division d
            JOIN SUB1_reflect r ON d.MaPhanAnh = r.MaPhanAnh
            JOIN PhongHoc p ON r.MaPhong = p.MaPhong
            JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
            JOIN Category c ON r.MaDanhMuc = c.MaDanhMuc
            JOIN `User` u ON r.MaNguoiGui = u.MaUser
            $where
            ORDER BY d.NgayPhanCong DESC
        ")->fetchAll();
        $activeMenu = 'suco_xuly';
        require __DIR__ . '/../views/sub01_suco/xu_ly_su_co.php';
    }

    public function theodoi(): void {
        requireLogin();
        $pdo = db();
        $u = currentUser();

        $kw     = trim($_GET['kw'] ?? '');
        $status = $_GET['status'] ?? '';
        $toaNha = (int)($_GET['toanha'] ?? 0);

        $conds = [];
        $params = [];
        if (in_array($u['VaiTro'], ['SINH_VIEN', 'GIANG_VIEN'], true)) {
            $conds[] = 'r.MaNguoiGui = ?';
            $params[] = $u['MaUser'];
        }
        if ($kw !== '') {
            $conds[] = '(r.TieuDe LIKE ? OR r.NoiDung LIKE ?)';
            $params[] = "%$kw%";
            $params[] = "%$kw%";
        }
        if ($status !== '') { $conds[] = 'r.TrangThai = ?'; $params[] = $status; }
        if ($toaNha > 0)    { $conds[] = 't.MaToaNha = ?';  $params[] = $toaNha; }

        $whereSql = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $st = $pdo->prepare("
            SELECT r.*, c.TenDanhMuc, p.TenPhong, t.TenToaNha, u.HoTen AS NguoiGui,
                   res.NoiDungXuLy, res.ThoiGianXuLy,
                   (SELECT DuongDan FROM Attachment WHERE MaPhanAnh = r.MaPhanAnh LIMIT 1) AS AnhTruoc,
                   (SELECT DuongDan FROM Attachment WHERE MaKetQuaSub1 = res.MaKetQua LIMIT 1) AS AnhSau
            FROM SUB1_reflect r
            JOIN Category c ON r.MaDanhMuc = c.MaDanhMuc
            JOIN PhongHoc p ON r.MaPhong = p.MaPhong
            JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
            JOIN `User` u ON r.MaNguoiGui = u.MaUser
            LEFT JOIN SUB1_result res ON res.MaKetQua = (SELECT MAX(MaKetQua) FROM SUB1_result WHERE MaPhanAnh = r.MaPhanAnh)
            $whereSql
            ORDER BY r.NgayGui DESC
        ");
        $st->execute($params);
        $danhSach = $st->fetchAll();
        $toaNhas  = $pdo->query("SELECT * FROM ToaNha ORDER BY TenToaNha")->fetchAll();
        $activeMenu = 'suco_theodoi';
        require __DIR__ . '/../views/sub01_suco/theo_doi_su_co.php';
    }
}