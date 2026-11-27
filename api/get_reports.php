<?php
require_once __DIR__ . '/../config/functions.php';
header('Content-Type: application/json; charset=utf-8');

$u    = currentUser();
$role = $u['VaiTro'] ?? 'GUEST';
$uid  = (int)($u['MaUser'] ?? 0);
$pdo  = db();

function statusMeta(string $status): array {
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
    ];
    return $map[$status] ?? [$status, 'badge-muted'];
}

try {
    // 1. Chế độ xem nhanh danh sách phiếu theo tòa nhà cho Nhân viên & Quản trị viên
    $zoneKey = trim($_GET['zone'] ?? '');
    if ($zoneKey !== '') {
        if (!in_array($role, ['NV_TIEP_NHAN', 'NV_SU_CO', 'NV_AN_NINH', 'QUAN_TRI'], true)) {
            echo json_encode(['success' => true, 'role' => $role, 'items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $items = [];

        // --- Sự cố tiện ích ---
        if ($role === 'NV_TIEP_NHAN' || $role === 'QUAN_TRI') {
            $whereStatus = ($role === 'NV_TIEP_NHAN')
                ? "r.TrangThai IN ('CHO_TIEP_NHAN','DA_TIEP_NHAN','DA_PHAN_CONG','DANG_XU_LY')"
                : "1=1";
            $st = $pdo->prepare("
                SELECT r.MaPhanAnh, r.TieuDe, r.TrangThai, r.MucDoUuTien, r.NgayGui, p.TenPhong, u.HoTen AS NguoiGui
                FROM SUB1_reflect r
                JOIN PhongHoc p ON r.MaPhong = p.MaPhong
                JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
                JOIN `User` u ON r.MaNguoiGui = u.MaUser
                WHERE t.KyHieu = ? AND $whereStatus
                ORDER BY r.NgayGui DESC LIMIT 20
            ");
            $st->execute([$zoneKey]);
            foreach ($st->fetchAll() as $r) {
                [$lbl, $cls] = statusMeta($r['TrangThai']);
                if ($role === 'NV_TIEP_NHAN') {
                    $url = in_array($r['TrangThai'], ['CHO_TIEP_NHAN', 'DA_TIEP_NHAN'], true)
                        ? 'index.php?c=suco&a=tiepnhan&id=' . (int)$r['MaPhanAnh']
                        : 'index.php?c=suco&a=theodoi&id=' . (int)$r['MaPhanAnh'];
                } else {
                    $url = 'index.php?c=nghiepvu&a=index&loai=suco&id=' . (int)$r['MaPhanAnh'];
                }
                $items[] = [
                    'id'           => (int)$r['MaPhanAnh'],
                    'kind'         => 'suco',
                    'kind_label'   => 'Sự cố tiện ích',
                    'title'        => $r['TieuDe'],
                    'room'         => $r['TenPhong'],
                    'sender'       => $r['NguoiGui'],
                    'status'       => $r['TrangThai'],
                    'status_label' => $lbl,
                    'status_cls'   => $cls,
                    'created_at'   => date('d/m/Y H:i', strtotime($r['NgayGui'])),
                    'url'          => $url,
                ];
            }
        } elseif ($role === 'NV_SU_CO') {
            $st = $pdo->prepare("
                SELECT r.MaPhanAnh, r.TieuDe, r.TrangThai, r.MucDoUuTien, r.NgayGui, p.TenPhong, u.HoTen AS NguoiGui
                FROM SUB1_division d
                JOIN SUB1_reflect r ON d.MaPhanAnh = r.MaPhanAnh
                JOIN PhongHoc p ON r.MaPhong = p.MaPhong
                JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
                JOIN `User` u ON r.MaNguoiGui = u.MaUser
                WHERE t.KyHieu = ? AND d.MaNhanVien = ?
                ORDER BY d.NgayPhanCong DESC LIMIT 20
            ");
            $st->execute([$zoneKey, $uid]);
            foreach ($st->fetchAll() as $r) {
                [$lbl, $cls] = statusMeta($r['TrangThai']);
                $items[] = [
                    'id'           => (int)$r['MaPhanAnh'],
                    'kind'         => 'suco',
                    'kind_label'   => 'Nhiệm vụ sự cố',
                    'title'        => $r['TieuDe'],
                    'room'         => $r['TenPhong'],
                    'sender'       => $r['NguoiGui'],
                    'status'       => $r['TrangThai'],
                    'status_label' => $lbl,
                    'status_cls'   => $cls,
                    'created_at'   => date('d/m/Y H:i', strtotime($r['NgayGui'])),
                    'url'          => 'index.php?c=suco&a=xuly&id=' . (int)$r['MaPhanAnh'],
                ];
            }
        }

        // --- Yêu cầu kiểm tra camera & an ninh ---
        if ($role === 'NV_TIEP_NHAN' || $role === 'QUAN_TRI') {
            $whereCam = ($role === 'NV_TIEP_NHAN')
                ? "q.TrangThai IN ('CHO_DUYET','DA_DUYET','DANG_KIEM_TRA')"
                : "1=1";
            $st = $pdo->prepare("
                SELECT q.MaYeuCau, q.TieuDe, q.TrangThai, q.NgayGui, p.TenPhong, u.HoTen AS NguoiGui
                FROM SUB3_request q
                JOIN PhongHoc p ON q.MaPhong = p.MaPhong
                JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
                JOIN `User` u ON q.MaNguoiGui = u.MaUser
                WHERE t.KyHieu = ? AND $whereCam
                ORDER BY q.NgayGui DESC LIMIT 20
            ");
            $st->execute([$zoneKey]);
            foreach ($st->fetchAll() as $q) {
                [$lbl, $cls] = statusMeta($q['TrangThai']);
                if ($role === 'NV_TIEP_NHAN') {
                    $url = in_array($q['TrangThai'], ['CHO_DUYET', 'DA_DUYET'], true)
                        ? 'index.php?c=tiepnhancamera&a=index&id=' . (int)$q['MaYeuCau']
                        : 'index.php?c=yeucaucamera&a=theodoi&id=' . (int)$q['MaYeuCau'];
                } else {
                    $url = 'index.php?c=nghiepvu&a=index&loai=camera&id=' . (int)$q['MaYeuCau'];
                }
                $items[] = [
                    'id'           => (int)$q['MaYeuCau'],
                    'kind'         => 'camera',
                    'kind_label'   => 'An ninh / Camera',
                    'title'        => $q['TieuDe'],
                    'room'         => $q['TenPhong'],
                    'sender'       => $q['NguoiGui'],
                    'status'       => $q['TrangThai'],
                    'status_label' => $lbl,
                    'status_cls'   => $cls,
                    'created_at'   => date('d/m/Y H:i', strtotime($q['NgayGui'])),
                    'url'          => $url,
                ];
            }
        } elseif ($role === 'NV_AN_NINH') {
            $st = $pdo->prepare("
                SELECT q.MaYeuCau, q.TieuDe, q.TrangThai, q.NgayGui, p.TenPhong, u.HoTen AS NguoiGui
                FROM SUB3_divison d
                JOIN SUB3_request q ON d.MaYeuCau = q.MaYeuCau
                JOIN PhongHoc p ON q.MaPhong = p.MaPhong
                JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
                JOIN `User` u ON q.MaNguoiGui = u.MaUser
                WHERE t.KyHieu = ? AND d.MaNhanVien = ?
                ORDER BY d.NgayPhanCong DESC LIMIT 20
            ");
            $st->execute([$zoneKey, $uid]);
            foreach ($st->fetchAll() as $q) {
                [$lbl, $cls] = statusMeta($q['TrangThai']);
                $items[] = [
                    'id'           => (int)$q['MaYeuCau'],
                    'kind'         => 'camera',
                    'kind_label'   => 'Nhiệm vụ camera',
                    'title'        => $q['TieuDe'],
                    'room'         => $q['TenPhong'],
                    'sender'       => $q['NguoiGui'],
                    'status'       => $q['TrangThai'],
                    'status_label' => $lbl,
                    'status_cls'   => $cls,
                    'created_at'   => date('d/m/Y H:i', strtotime($q['NgayGui'])),
                    'url'          => 'index.php?c=kiemtracamera&a=index&id=' . (int)$q['MaYeuCau'],
                ];
            }
        }

        // --- Tin đồ thất lạc (dành cho Quản trị viên giám sát) ---
        if ($role === 'QUAN_TRI') {
            $st = $pdo->prepare("
                SELECT post.MaTinDang, post.TieuDe, post.TrangThai, post.NgayDang, p.TenPhong, u.HoTen AS NguoiGui
                FROM SUB2_post post
                JOIN PhongHoc p ON post.MaPhong = p.MaPhong
                JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
                JOIN `User` u ON post.MaNguoiDang = u.MaUser
                WHERE t.KyHieu = ?
                ORDER BY post.NgayDang DESC LIMIT 15
            ");
            $st->execute([$zoneKey]);
            foreach ($st->fetchAll() as $pt) {
                [$lbl, $cls] = statusMeta($pt['TrangThai']);
                $items[] = [
                    'id'           => (int)$pt['MaTinDang'],
                    'kind'         => 'timdo',
                    'kind_label'   => 'Đồ thất lạc',
                    'title'        => $pt['TieuDe'],
                    'room'         => $pt['TenPhong'],
                    'sender'       => $pt['NguoiGui'],
                    'status'       => $pt['TrangThai'],
                    'status_label' => $lbl,
                    'status_cls'   => $cls,
                    'created_at'   => date('d/m/Y H:i', strtotime($pt['NgayDang'])),
                    'url'          => 'index.php?c=nghiepvu&a=index&loai=timdo&id=' . (int)$pt['MaTinDang'],
                ];
            }
        }

        echo json_encode(['success' => true, 'role' => $role, 'zone' => $zoneKey, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Chế độ mặc định: Đếm số phiếu đang xử lý theo tòa nhà (phù hợp từng vai trò)
    $counts = [];
    if ($role === 'NV_SU_CO') {
        $st = $pdo->prepare("
            SELECT t.KyHieu AS zone_id, COUNT(*) AS n
            FROM SUB1_division d
            JOIN SUB1_reflect r ON d.MaPhanAnh = r.MaPhanAnh
            JOIN PhongHoc p ON r.MaPhong = p.MaPhong
            JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
            WHERE d.MaNhanVien = ? AND d.TrangThai <> 'HOAN_THANH'
            GROUP BY t.KyHieu
        ");
        $st->execute([$uid]);
        foreach ($st->fetchAll() as $row) $counts[$row['zone_id']] = (int)$row['n'];
    } elseif ($role === 'NV_AN_NINH') {
        $st = $pdo->prepare("
            SELECT t.KyHieu AS zone_id, COUNT(*) AS n
            FROM SUB3_divison d
            JOIN SUB3_request q ON d.MaYeuCau = q.MaYeuCau
            JOIN PhongHoc p ON q.MaPhong = p.MaPhong
            JOIN ToaNha t ON p.MaToaNha = t.MaToaNha
            WHERE d.MaNhanVien = ? AND d.TrangThai <> 'HOAN_THANH'
            GROUP BY t.KyHieu
        ");
        $st->execute([$uid]);
        foreach ($st->fetchAll() as $row) $counts[$row['zone_id']] = (int)$row['n'];
    } else {
        $sql = "
            SELECT t.KyHieu AS zone_id, COUNT(*) AS n
            FROM (
                SELECT p.MaToaNha FROM SUB1_reflect r
                JOIN PhongHoc p ON r.MaPhong = p.MaPhong
                WHERE r.TrangThai IN ('CHO_TIEP_NHAN','DA_TIEP_NHAN','DA_PHAN_CONG','DANG_XU_LY')
                UNION ALL
                SELECT p.MaToaNha FROM SUB3_request q
                JOIN PhongHoc p ON q.MaPhong = p.MaPhong
                WHERE q.TrangThai IN ('CHO_DUYET','DA_DUYET','DANG_KIEM_TRA')
            ) x
            JOIN ToaNha t ON x.MaToaNha = t.MaToaNha
            GROUP BY t.KyHieu
        ";
        foreach ($pdo->query($sql)->fetchAll() as $row) {
            $counts[$row['zone_id']] = (int)$row['n'];
        }
    }

    echo json_encode(['success' => true, 'role' => $role, 'counts' => $counts], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn dữ liệu bản đồ 3D'], JSON_UNESCAPED_UNICODE);
}
