<?php
$pageTitle = 'Đăng nhập hệ thống CampusFix';
$redirectParam = $_GET['redirect'] ?? 'index.php';
require __DIR__ . '/../layouts/header.php';
?>
<div class="card auth-card">
  <div class="auth-header">
    <div class="auth-icon-circle">🔑</div>
    <h2 style="margin:0 0 6px">Đăng nhập hệ thống</h2>
    <p style="color:var(--muted);font-size:13.5px;margin:0">Sử dụng tài khoản được cấp hoặc đăng kí tài khoản bằng MSSV/MGV để đăng nhập hệ thống</p>
  </div>

  <form method="POST" class="std-form" id="login-form">
    <input type="hidden" name="redirect" value="<?= e($redirectParam) ?>">
    <label>Mã số SV/GV hoặc Gmail *
      <input type="text" name="username" id="login-username" required autofocus placeholder="Nhập MSSV, MGV, tên đăng nhập hoặc Gmail">
    </label>
    <label>Mật khẩu *
      <input type="password" name="password" id="login-password" required placeholder="Nhập mật khẩu">
    </label>
    <button type="submit" class="btn btn-primary" style="width:100%;padding:11px 16px;font-size:14.5px">Đăng nhập ngay</button>
  </form>

  <div style="margin-top:16px;text-align:center;font-size:13.5px;color:var(--muted)">
    Chưa có tài khoản? <a href="index.php?c=auth&a=register&redirect=<?= urlencode($redirectParam) ?>" style="color:var(--primary);font-weight:700">Đăng ký tài khoản mới</a>
  </div>

<script>
document.querySelectorAll('.demo-pill-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const u = document.getElementById('login-username');
    const p = document.getElementById('login-password');
    if (u) u.value = btn.dataset.demo;
    if (p) p.value = '123456';
    if (u) u.focus();
  });
});
</script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>