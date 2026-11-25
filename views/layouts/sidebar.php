<?php
$u = currentUser();
$role = $u['VaiTro'] ?? 'GUEST';
$am = $activeMenu ?? '';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand-mini">
    <span>ĐIỀU HƯỚNG HỆ THỐNG</span>
  </div>

  <nav class="menu">
    <a href="index.php" class="menu-item <?= $am === 'home' ? 'active' : '' ?>">🗺️ <span>Bản đồ khuôn viên 3D</span></a>

    <?php if (!$u): ?>
      <div class="menu-group">CHỨC NĂNG HỆ THỐNG</div>
      <a href="index.php?c=suco&a=gui" class="menu-item">✍️ <span>Gửi phản ánh sự cố</span></a>
      <a href="index.php?c=tindang&a=dang" class="menu-item">📢 <span>Đăng tin mất / nhặt đồ</span></a>
      <a href="index.php?c=tindang&a=tracuu" class="menu-item">🔎 <span>Tra cứu đồ thất lạc</span></a>
      <a href="index.php?c=yeucaucamera&a=gui" class="menu-item">📹 <span>Yêu cầu kiểm tra camera</span></a>

      <div class="menu-group">TÀI KHOẢN</div>
      <a href="index.php?c=auth&a=login" class="menu-item">🔑 <span>Đăng nhập</span></a>
      <a href="index.php?c=auth&a=register" class="menu-item">📝 <span>Đăng ký tài khoản</span></a>

    <?php elseif ($role === 'QUAN_TRI'): ?>
      <div class="menu-group">QUẢN TRỊ HỆ THỐNG</div>
      <a href="index.php?c=taikhoan&a=index" class="menu-item <?= $am === 'qt_taikhoan' ? 'active' : '' ?>">👥 <span>Tài khoản &amp; Phân quyền</span></a>
      <a href="index.php?c=danhmuc&a=index" class="menu-item <?= $am === 'qt_danhmuc' ? 'active' : '' ?>">🏷️ <span>Danh mục sự cố &amp; an ninh</span></a>
      <a href="index.php?c=khuvuc&a=index" class="menu-item <?= $am === 'qt_khuvuc' ? 'active' : '' ?>">🏢 <span>Khu vực &amp; Phòng học</span></a>
      <a href="index.php?c=nghiepvu&a=index" class="menu-item <?= $am === 'qt_nghiepvu' ? 'active' : '' ?>">🗃️ <span>Tra cứu &amp; Kiểm duyệt tin</span></a>
      <a href="index.php?c=thongke&a=index" class="menu-item <?= $am === 'qt_thongke' ? 'active' : '' ?>">📊 <span>Thống kê &amp; Xuất báo cáo</span></a>

    <?php elseif ($role === 'NV_TIEP_NHAN'): ?>
      <div class="menu-group">NGHIỆP VỤ TIẾP NHẬN</div>
      <a href="index.php?c=suco&a=tiepnhan" class="menu-item <?= $am === 'suco_tiepnhan' ? 'active' : '' ?>">📥 <span>Tiếp nhận &amp; Phân công sự cố</span></a>
      <a href="index.php?c=suco&a=theodoi" class="menu-item <?= $am === 'suco_theodoi' ? 'active' : '' ?>">📋 <span>Tra cứu danh sách sự cố</span></a>
      <a href="index.php?c=tiepnhancamera&a=index" class="menu-item <?= $am === 'cam_tiepnhan' ? 'active' : '' ?>">📨 <span>Tiếp nhận &amp; Chuyển an ninh</span></a>
      <a href="index.php?c=yeucaucamera&a=theodoi" class="menu-item <?= $am === 'cam_theodoi' ? 'active' : '' ?>">🛡️ <span>Tra cứu yêu cầu camera</span></a>

    <?php elseif ($role === 'NV_SU_CO'): ?>
      <div class="menu-group">NGHIỆP VỤ XỬ LÝ SỰ CỐ</div>
      <a href="index.php?c=suco&a=xuly" class="menu-item <?= $am === 'suco_xuly' ? 'active' : '' ?>">🔧 <span>Xử lý sự cố được giao</span></a>
      <a href="index.php?c=suco&a=theodoi" class="menu-item <?= $am === 'suco_theodoi' ? 'active' : '' ?>">📋 <span>Theo dõi &amp; Tra cứu sự cố</span></a>

    <?php elseif ($role === 'NV_AN_NINH'): ?>
      <div class="menu-group">NGHIỆP VỤ AN NINH &amp; CAMERA</div>
      <a href="index.php?c=kiemtracamera&a=index" class="menu-item <?= $am === 'cam_xuly' ? 'active' : '' ?>">🎥 <span>Cập nhật kết quả camera</span></a>
      <a href="index.php?c=yeucaucamera&a=theodoi" class="menu-item <?= $am === 'cam_theodoi' ? 'active' : '' ?>">🛡️ <span>Theo dõi &amp; Lịch sử camera</span></a>

    <?php else: /* SINH_VIEN, GIANG_VIEN */ ?>
      <div class="menu-group">PHẢN ÁNH SỰ CỐ TIỆN ÍCH</div>
      <a href="index.php?c=suco&a=gui" class="menu-item <?= $am === 'suco_gui' ? 'active' : '' ?>">✍️ <span>Gửi phản ánh sự cố</span></a>
      <a href="index.php?c=suco&a=theodoi" class="menu-item <?= $am === 'suco_theodoi' ? 'active' : '' ?>">📋 <span>Theo dõi &amp; Tra cứu sự cố</span></a>

      <div class="menu-group">TÌM &amp; TRẢ ĐỒ THẤT LẠC</div>
      <a href="index.php?c=tindang&a=dang" class="menu-item <?= $am === 'timdo_dang' ? 'active' : '' ?>">📢 <span>Đăng tin mất / nhặt đồ</span></a>
      <a href="index.php?c=tindang&a=tracuu" class="menu-item <?= $am === 'timdo_tracuu' ? 'active' : '' ?>">🔎 <span>Tra cứu đồ thất lạc</span></a>
      <a href="index.php?c=lienhe&a=index" class="menu-item <?= $am === 'timdo_lienhe' ? 'active' : '' ?>">💬 <span>Hội thoại trao trả đồ</span></a>
      <a href="index.php?c=tindang&a=quanly" class="menu-item <?= $am === 'timdo_quanly' ? 'active' : '' ?>">🗂️ <span>Quản lý tin của tôi</span></a>

      <div class="menu-group">CAMERA &amp; AN NINH</div>
      <a href="index.php?c=yeucaucamera&a=gui" class="menu-item <?= $am === 'cam_gui' ? 'active' : '' ?>">📹 <span>Yêu cầu kiểm tra camera</span></a>
      <a href="index.php?c=yeucaucamera&a=theodoi" class="menu-item <?= $am === 'cam_theodoi' ? 'active' : '' ?>">🛡️ <span>Theo dõi &amp; Lịch sử camera</span></a>
    <?php endif; ?>
  </nav>

  <?php if ($am === 'home'): ?>
  <div class="sidebar-block">
    <h3>Chú thích bản đồ 3D</h3>
    <ul class="legend" id="legend"></ul>
    <button class="btn btn-ghost btn-sm" id="btn-show-all" style="width:100%">Hiện tất cả khu vực</button>
  </div>
  <div class="sidebar-block">
    <h3>Tổng quan trực tuyến</h3>
    <div class="stats-grid-mini">
      <div class="stat"><b id="stat-total">0</b><span>Phiếu đang mở</span></div>
      <div class="stat"><b id="stat-zones">0</b><span>Khu vực sự cố</span></div>
    </div>
  </div>
  <?php endif; ?>
</aside>