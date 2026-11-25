<?php
require_once __DIR__ . '/config/functions.php';

$c = preg_replace('/[^a-z0-9_]/i', '', $_GET['c'] ?? 'auth');
$a = preg_replace('/[^a-z0-9_]/i', '', $_GET['a'] ?? 'index');

$controllerMap = [
    'auth'           => 'AuthController',
    'taikhoan'       => 'TaiKhoanController',
    'suco'           => 'SuCoController',
    'tindang'        => 'TinDangController',
    'lienhe'         => 'LienHeController',
    'yeucaucamera'   => 'YeuCauCameraController',
    'tiepnhancamera' => 'TiepNhanCameraController',
    'kiemtracamera'  => 'KiemTraCameraController',
    'danhmuc'        => 'DanhMucSuCoController',
    'nghiepvu'       => 'NghiepVuController',
    'khuvuc'         => 'KhuVucController',
    'thongke'        => 'ThongKeController',
];

$className = $controllerMap[$c] ?? 'AuthController';
$file = __DIR__ . '/controllers/' . $className . '.php';

if (!file_exists($file)) {
    http_response_code(404);
    exit('Không tìm thấy phân hệ yêu cầu.');
}

require_once $file;
$controller = new $className();

if (!method_exists($controller, $a)) {
    $a = 'index';
}
$controller->$a();
