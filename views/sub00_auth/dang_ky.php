<?php
$pageTitle = 'Đăng ký tài khoản CampusFix';
$redirectParam = $_GET['redirect'] ?? 'index.php';
require __DIR__ . '/../layouts/header.php';
?>
<div class="card auth-card" style="max-width:540px">
  <div class="auth-header">
    <div class="auth-icon-circle" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">📝</div>
    <h2 style="margin:0 0 6px">Đăng ký tài khoản</h2>
    <p style="color:var(--muted);font-size:13.5px;margin:0">Dành cho Sinh viên và Giảng viên Trường Đại học Quy Nhơn sử dụng tiện ích &amp; an ninh khuôn viên.</p>
  </div>

  <form method="POST" class="std-form">
    <input type="hidden" name="redirect" value="<?= e($redirectParam) ?>">
    <div class="row">
      <label>Đối tượng *
        <select name="VaiTro" required>
          <option value="SINH_VIEN">Sinh viên</option>
          <option value="GIANG_VIEN">Giảng viên</option>
        </select>
      </label>
      <label>Mã số SV / Mã GV *
        <input type="text" name="MaSo" required placeholder="VD: 455105....">
      </label>
    </div>
    <label>Họ và tên *
      <input type="text" name="HoTen" required placeholder="Nhập đầy đủ họ và tên">
    </label>
    <label>Gmail / Email *
      <input type="email" name="Email" required placeholder="VD: nguyenvana@gmail.com">
    </label>
    <label>Khoa / Đơn vị / Lớp
      <input type="text" name="DonVi" placeholder="VD: Khoa Công nghệ thông tin">
    </label>
    <div class="row">
      <label>Mật khẩu * (tối thiểu 6 ký tự)
        <input type="password" name="password" minlength="6" required placeholder="Nhập mật khẩu">
      </label>
      <label>Xác nhận mật khẩu *
        <input type="password" name="confirm_password" minlength="6" required placeholder="Nhập lại mật khẩu">
      </label>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;padding:11px 16px;font-size:14.5px">Hoàn tất đăng ký</button>
  </form>

  <div style="margin-top:16px;text-align:center;font-size:13.5px;color:var(--muted)">
    Đã có tài khoản? <a href="index.php?c=auth&a=login&redirect=<?= urlencode($redirectParam) ?>" style="color:var(--primary);font-weight:700">Đăng nhập ngay</a>
  </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>