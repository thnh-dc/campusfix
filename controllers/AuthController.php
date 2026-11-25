<?php
require_once __DIR__ . '/../config/functions.php';

class AuthController {
    public function index(): void {
        if (isset($_GET['read_notify'])) {
            $id = (int)$_GET['read_notify'];
            if (isLoggedIn()) {
                db()->prepare("UPDATE Notify SET DaDoc = 1 WHERE MaThongBao = ? AND MaNguoiNhan = ?")
                    ->execute([$id, currentUser()['MaUser']]);
            }
            redirect('index.php');
        }
        $activeMenu = 'home';
        require __DIR__ . '/../views/sub00_auth/trang_chu.php';
    }

    public function login(): void {
        if (isLoggedIn()) redirect('index.php');

        $redirectTo = $_GET['redirect'] ?? ($_POST['redirect'] ?? 'index.php');
        // Chỉ cho phép đường dẫn nội bộ an toàn
        if (strpos($redirectTo, 'index.php') !== 0) {
            $redirectTo = 'index.php';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            $st = db()->prepare("
                SELECT tk.*, u.HoTen, u.MaSo, u.Email, u.SoDienThoai, u.DonVi, u.AnhDaiDien, u.LoaiNguoiDung
                FROM TaiKhoan tk JOIN `User` u ON tk.MaUser = u.MaUser
                WHERE tk.TenDangNhap = ? OR u.MaSo = ? OR u.Email = ?
                LIMIT 1
            ");
            $st->execute([$username, $username, $username]);
            $acc = $st->fetch();

            if (!$acc) {
                setFlash('err', 'Sai mã số/email đăng nhập hoặc mật khẩu.');
                redirect('index.php?c=auth&a=login&redirect=' . urlencode($redirectTo));
            }
            if ($acc['TrangThai'] === 'KHOA') {
                setFlash('err', 'Tài khoản đã bị khóa. Lý do: ' . ($acc['LyDoKhoa'] ?: 'Vi phạm quy định hệ thống'));
                redirect('index.php?c=auth&a=login&redirect=' . urlencode($redirectTo));
            }
            if (!empty($acc['KhoaDenLuc']) && strtotime($acc['KhoaDenLuc']) > time()) {
                setFlash('err', 'Tài khoản tạm thời bị khóa đến ' . date('H:i d/m/Y', strtotime($acc['KhoaDenLuc'])) . ' do nhập sai quá 5 lần!');
                redirect('index.php?c=auth&a=login&redirect=' . urlencode($redirectTo));
            }
            if (!password_verify($password, $acc['MatKhauHash'])) {
                $fails = (int)$acc['SoLanDangNhapSai'] + 1;
                if ($fails >= 5) {
                    db()->prepare("UPDATE TaiKhoan SET SoLanDangNhapSai = ?, KhoaDenLuc = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE MaTaiKhoan = ?")
                        ->execute([$fails, $acc['MaTaiKhoan']]);
                    setFlash('err', 'Tài khoản tạm thời bị khóa 30 phút do nhập sai mật khẩu quá 5 lần!');
                } else {
                    db()->prepare("UPDATE TaiKhoan SET SoLanDangNhapSai = ? WHERE MaTaiKhoan = ?")
                        ->execute([$fails, $acc['MaTaiKhoan']]);
                    setFlash('err', "Sai thông tin đăng nhập (Lần $fails/5).");
                }
                redirect('index.php?c=auth&a=login&redirect=' . urlencode($redirectTo));
            }

            $token = bin2hex(random_bytes(32));
            db()->prepare("UPDATE TaiKhoan SET SoLanDangNhapSai = 0, KhoaDenLuc = NULL, LanDangNhapCuoi = NOW() WHERE MaTaiKhoan = ?")
                ->execute([$acc['MaTaiKhoan']]);
            db()->prepare("INSERT INTO HeThongSession (MaTaiKhoan, SessionToken, TrinhDuyet, ThoiGianHetHan) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 8 HOUR))")
                ->execute([$acc['MaTaiKhoan'], $token, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Browser', 0, 250)]);

            unset($acc['MatKhauHash']);
            $acc['SessionToken'] = $token;
            $_SESSION['user'] = $acc;

            setFlash('ok', 'Đăng nhập thành công! Xin chào ' . $acc['HoTen'] . '.');
            redirect($redirectTo);
        }
        require __DIR__ . '/../views/sub00_auth/dang_nhap.php';
    }

    public function register(): void {
        if (isLoggedIn()) redirect('index.php');

        $redirectTo = $_GET['redirect'] ?? ($_POST['redirect'] ?? 'index.php');
        if (strpos($redirectTo, 'index.php') !== 0) {
            $redirectTo = 'index.php';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maSo     = trim($_POST['MaSo'] ?? '');
            $hoTen    = trim($_POST['HoTen'] ?? '');
            $email    = trim($_POST['Email'] ?? '');
            $vaiTro   = ($_POST['VaiTro'] ?? '') === 'GIANG_VIEN' ? 'GIANG_VIEN' : 'SINH_VIEN';
            $donVi    = trim($_POST['DonVi'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if ($maSo === '' || $hoTen === '' || $email === '' || $password === '') {
                setFlash('err', 'Vui lòng nhập đầy đủ Mã số SV/GV, Họ tên, Gmail và Mật khẩu.');
                redirect('index.php?c=auth&a=register&redirect=' . urlencode($redirectTo));
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                setFlash('err', 'Địa chỉ Gmail / Email không đúng định dạng.');
                redirect('index.php?c=auth&a=register&redirect=' . urlencode($redirectTo));
            }
            if (strlen($password) < 6) {
                setFlash('err', 'Mật khẩu phải có ít nhất 6 ký tự.');
                redirect('index.php?c=auth&a=register&redirect=' . urlencode($redirectTo));
            }
            if ($password !== $confirm) {
                setFlash('err', 'Mật khẩu xác nhận không khớp.');
                redirect('index.php?c=auth&a=register&redirect=' . urlencode($redirectTo));
            }

            $pdo = db();
            $stCheck = $pdo->prepare("SELECT COUNT(*) FROM `User` WHERE MaSo = ? OR Email = ?");
            $stCheck->execute([$maSo, $email]);
            if ((int)$stCheck->fetchColumn() > 0) {
                setFlash('err', 'Mã số sinh viên/giảng viên hoặc Gmail này đã được đăng ký.');
                redirect('index.php?c=auth&a=register&redirect=' . urlencode($redirectTo));
            }

            try {
                $pdo->beginTransaction();
                $loaiND = ($vaiTro === 'GIANG_VIEN') ? 'GV' : 'SV';
                $pdo->prepare("INSERT INTO `User` (MaSo, HoTen, Email, DonVi, LoaiNguoiDung) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$maSo, $hoTen, $email, $donVi, $loaiND]);
                $maUser = (int)$pdo->lastInsertId();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO TaiKhoan (MaUser, TenDangNhap, MatKhauHash, VaiTro, TrangThai) VALUES (?, ?, ?, ?, 'HOAT_DONG')")
                    ->execute([$maUser, $maSo, $hash, $vaiTro]);
                $pdo->commit();

                setFlash('ok', 'Đăng ký tài khoản thành công! Vui lòng đăng nhập bằng Mã số (' . $maSo . ') hoặc Gmail.');
                redirect('index.php?c=auth&a=login&redirect=' . urlencode($redirectTo));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                setFlash('err', 'Không thể tạo tài khoản, vui lòng thử lại.');
                redirect('index.php?c=auth&a=register&redirect=' . urlencode($redirectTo));
            }
        }
        require __DIR__ . '/../views/sub00_auth/dang_ky.php';
    }

    public function logout(): void {
        if (!empty($_SESSION['user']['SessionToken'])) {
            db()->prepare("UPDATE HeThongSession SET TrangThai = 'DA_DANG_XUAT' WHERE SessionToken = ?")
                ->execute([$_SESSION['user']['SessionToken']]);
        }
        session_destroy();
        session_start();
        setFlash('ok', 'Đã đăng xuất khỏi hệ thống.');
        redirect('index.php');
    }
}