<?php
require_once __DIR__ . '/../config/functions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập tài khoản trường để gửi phản ánh!'], JSON_UNESCAPED_UNICODE);
    exit;
}

$zoneId    = trim($_POST['zone_id'] ?? '');
$catKey    = trim($_POST['category'] ?? '');
$title     = mb_substr(trim($_POST['title'] ?? ''), 0, 200);
$locDetail = mb_substr(trim($_POST['location_detail'] ?? ''), 0, 255);
$desc      = trim($_POST['description'] ?? '');
$sevMap    = ['low' => 'THAP', 'medium' => 'TRUNG_BINH', 'high' => 'KHAN_CAP'];
$priority  = $sevMap[$_POST['severity'] ?? 'medium'] ?? 'TRUNG_BINH';

if ($zoneId === '' || $title === '' || $desc === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ tiêu đề và mô tả chi tiết'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();
    $stRoom = $pdo->prepare("SELECT p.MaPhong FROM PhongHoc p JOIN ToaNha t ON p.MaToaNha = t.MaToaNha WHERE t.KyHieu = ? LIMIT 1");
    $stRoom->execute([$zoneId]);
    $maPhong = (int)($stRoom->fetchColumn() ?: 1);
    $maUser  = (int)currentUser()['MaUser'];

    if (in_array($catKey, ['security', 'camera'], true)) {
        $st = $pdo->prepare("
            INSERT INTO SUB3_request (MaNguoiGui, MaDanhMuc, MaPhong, ViTriChiTiet, TieuDe, KhungGioBatDau, KhungGioKetThuc, LyDoYeuCau)
            VALUES (?, 6, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW(), ?)
        ");
        $st->execute([$maUser, $maPhong, $locDetail, $title, $desc]);
        $newId = (int)$pdo->lastInsertId();
        $code  = 'AN-' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);
        guiThongBaoTheoVaiTro('NV_TIEP_NHAN', "Yêu cầu an ninh mới #$code", $title, 'AN_NINH', 'SUB3_request', $newId);
    } else {
        $catMap = ['equipment' => 1, 'lab' => 1, 'electric' => 2, 'water' => 3, 'facility' => 4, 'roof' => 4, 'room' => 4, 'wifi' => 5];
        $maDanhMuc = $catMap[$catKey] ?? 1;

        $st = $pdo->prepare("
            INSERT INTO SUB1_reflect (MaNguoiGui, MaDanhMuc, MaPhong, ViTriChiTiet, TieuDe, NoiDung, MucDoUuTien)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$maUser, $maDanhMuc, $maPhong, $locDetail, $title, $desc, $priority]);
        $newId = (int)$pdo->lastInsertId();
        $code  = 'SC-' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);

        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'suco', false);
            if ($up['ok'] && $up['data']) {
                $d = $up['data'];
                $pdo->prepare("INSERT INTO Attachment (MaPhanAnh, LoaiTep, TenTep, DuongDan, MimeType, KichThuoc) VALUES (?,?,?,?,?,?)")
                    ->execute([$newId, $d['LoaiTep'], $d['TenTep'], $d['DuongDan'], $d['MimeType'], $d['KichThuoc']]);
            }
        }
        guiThongBaoTheoVaiTro('NV_TIEP_NHAN', "Phản ánh sự cố mới #$code", $title, 'SU_CO', 'SUB1_reflect', $newId);
    }

    echo json_encode(['success' => true, 'code' => $code], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu vào hệ thống'], JSON_UNESCAPED_UNICODE);
}
