<?php
$pageTitle = 'Thông tin cá nhân (UC0-02)';
$u = currentUser();
require __DIR__ . '/../layouts/header.php';
?>
<div class="card" style="max-width:620px">
  <h2>👤 Thông tin cá nhân</h2>
  <form method="POST" class="std-form">
    <div class="row">
      <label>Mã số định danh
        <input type="text" value="<?= e($u['MaSo']) ?>" disabled>
      </label>
      <label>Vai trò hệ thống
        <input type="text" value="<?= e(ROLE_LABELS[$u['VaiTro']] ?? $u['VaiTro']) ?>" disabled>
      </label>
    </div>
    <label>Họ và tên *
      <input type="text" name="HoTen" value="<?= e($u['HoTen']) ?>" required>
    </label>
    <div class="row">
      <label>Email trường
        <input type="email" value="<?= e($u['Email']) ?>" disabled>
      </label>
      <label>Số điện thoại
        <input type="text" name="SoDienThoai" value="<?= e($u['SoDienThoai']) ?>">
      </label>
    </div>
    <label>Đơn vị / Khoa / Lớp
      <input type="text" name="DonVi" value="<?= e($u['DonVi']) ?>">
    </label>
    <label>Đổi mật khẩu mới (để trống nếu không đổi)
      <input type="password" name="MatKhauMoi" placeholder="Tối thiểu 6 ký tự">
    </label>
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
  </form>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
