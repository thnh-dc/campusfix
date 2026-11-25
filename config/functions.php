<?php
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLE_LABELS = [
    'SINH_VIEN'    => 'Sinh viên',
    'GIANG_VIEN'   => 'Giảng viên',
    'NV_TIEP_NHAN' => 'Nhân viên tiếp nhận',
    'NV_SU_CO'     => 'Nhân viên xử lý sự cố',
    'NV_AN_NINH'   => 'Nhân viên an ninh',
    'QUAN_TRI'     => 'Quản trị viên',
];

function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user']);
}

function hasRole(array $roles): bool {
    $u = currentUser();
    if (!$u) return false;
    return in_array($u['VaiTro'], $roles, true);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $req = basename($_SERVER['REQUEST_URI'] ?? 'index.php');
        setFlash('err', 'Vui lòng đăng nhập hoặc đăng ký tài khoản để sử dụng chức năng này.');
        redirect('index.php?c=auth&a=login&redirect=' . urlencode($req));
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        setFlash('err', 'Bạn không có quyền truy cập chức năng này.');
        redirect('index.php');
    }
}

function guiThongBao(int $maNguoiNhan, string $tieuDe, string $noiDung, string $loai = 'HE_THONG', ?string $loaiThamChieu = null, ?int $maThamChieu = null): void {
    try {
        $st = db()->prepare(
            'INSERT INTO Notify (MaNguoiNhan, TieuDe, NoiDung, LoaiThongBao, LoaiThamChieu, MaThamChieu)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$maNguoiNhan, $tieuDe, $noiDung, $loai, $loaiThamChieu, $maThamChieu]);
    } catch (Throwable $e) {
        // Không làm gián đoạn luồng chính nếu lỗi ghi thông báo
    }
}

function guiThongBaoTheoVaiTro(string $vaiTro, string $tieuDe, string $noiDung, string $loai = 'HE_THONG', ?string $loaiThamChieu = null, ?int $maThamChieu = null): void {
    $st = db()->prepare("SELECT MaUser FROM TaiKhoan WHERE VaiTro = ? AND TrangThai = 'HOAT_DONG'");
    $st->execute([$vaiTro]);
    foreach ($st->fetchAll() as $row) {
        guiThongBao((int)$row['MaUser'], $tieuDe, $noiDung, $loai, $loaiThamChieu, $maThamChieu);
    }
}

function uploadFile(array $file, string $subFolder, bool $allowVideo = false): array {
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'data' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Lỗi tải tệp lên máy chủ.'];
    }
    $maxSize = $allowVideo ? 20 * 1024 * 1024 : 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'Dung lượng tệp vượt quá giới hạn cho phép (' . ($allowVideo ? '20MB' : '5MB') . ').'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $imageMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $videoMap = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    $loaiTep = null;
    $ext = null;
    if (isset($imageMap[$mime])) {
        $loaiTep = 'HINH_ANH';
        $ext = $imageMap[$mime];
    } elseif ($allowVideo && isset($videoMap[$mime])) {
        $loaiTep = 'VIDEO';
        $ext = $videoMap[$mime];
    } else {
        return ['ok' => false, 'error' => 'Định dạng tệp không hợp lệ. Chỉ chấp nhận ' . ($allowVideo ? 'JPG, PNG, WEBP, MP4, WEBM.' : 'JPG, PNG, WEBP.')];
    }

    $targetDir = __DIR__ . '/../uploads/' . $subFolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $safeName = date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    $destPath = $targetDir . '/' . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['ok' => false, 'error' => 'Không thể lưu tệp vào thư mục uploads.'];
    }

    return [
        'ok'   => true,
        'data' => [
            'LoaiTep'   => $loaiTep,
            'TenTep'    => basename($file['name']),
            'DuongDan'  => 'uploads/' . $subFolder . '/' . $safeName,
            'MimeType'  => $mime,
            'KichThuoc' => (int)$file['size'],
        ]
    ];
}

function statusBadge(string $status): string {
    $map = [
        'CHO_TIEP_NHAN' => ['Chờ tiếp nhận', 'badge-warn'],
        'DA_TIEP_NHAN'  => ['Đã tiếp nhận',  'badge-info'],
        'DA_PHAN_CONG'  => ['Đã phân công',  'badge-info'],
        'DANG_XU_LY'    => ['Đang xử lý',    'badge-primary'],
        'DA_XU_LY'      => ['Đã giải quyết', 'badge-success'],
        'CHO_DUYET'     => ['Chờ tiếp nhận', 'badge-warn'],
        'DA_DUYET'      => ['Đã tiếp nhận',  'badge-info'],
        'DANG_KIEM_TRA' => ['Đang kiểm tra', 'badge-primary'],
        'HOAN_THANH'    => ['Hoàn thành',    'badge-success'],
        'TU_CHOI'       => ['Từ chối',       'badge-danger'],
        'DONG'          => ['Đã đóng',       'badge-muted'],
        'DANG_TIM'      => ['Đang đăng',     'badge-primary'],
        'DA_TRA'        => ['Đã hoàn tất',   'badge-success'],
        'HOAT_DONG'     => ['Hoạt động',     'badge-success'],
        'KHOA'          => ['Đã khóa',       'badge-danger'],
        'NGUNG'         => ['Ngừng áp dụng', 'badge-muted'],
    ];
    [$label, $cls] = $map[$status] ?? [$status, 'badge-muted'];
    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
}

function priorityBadge(string $priority): string {
    $map = [
        'THAP'       => ['Thấp',       'sev low'],
        'TRUNG_BINH' => ['Trung bình', 'sev medium'],
        'CAO'        => ['Cao',        'sev high'],
        'KHAN_CAP'   => ['Khẩn cấp',   'sev high'],
    ];
    [$label, $cls] = $map[$priority] ?? [$priority, 'sev low'];
    return '<span class="' . $cls . '">' . e($label) . '</span>';
}

