<?php
$pageTitle = 'Bản đồ khuôn viên 3D – QNU CampusFix 2.0';
$pdo = db();
$toaNhas = $pdo->query("SELECT MaToaNha, KyHieu, TenToaNha, SoTang, MoTa FROM ToaNha WHERE TrangThai = 'HOAT_DONG' ORDER BY TenToaNha")->fetchAll();
$phongs  = $pdo->query("SELECT MaPhong, MaToaNha, TenPhong, Tang FROM PhongHoc WHERE TrangThai = 'HOAT_DONG' ORDER BY Tang ASC, TenPhong ASC")->fetchAll();
$u = currentUser();
$userRole = $u['VaiTro'] ?? 'GUEST';
$isStaffRole = in_array($userRole, ['NV_TIEP_NHAN', 'NV_SU_CO', 'NV_AN_NINH', 'QUAN_TRI'], true);
require __DIR__ . '/../layouts/header.php';
?>

<section class="map-fullscreen-wrap" id="map-wrap">
  <div id="campus-3d" role="region" aria-label="Bản đồ 3D khuôn viên Trường Đại học Quy Nhơn"></div>
  <div class="map-labels" id="map-labels"></div>
  <div class="tooltip" id="tooltip" hidden></div>

  <!-- Thanh lọc nhanh loại công trình ngay trên bản đồ -->
  <div class="map-filter-bar" id="map-filter-bar" aria-label="Lọc nhanh loại khu vực">
    <button type="button" class="map-pill active" data-filter="all">Tất cả</button>
    <button type="button" class="map-pill" data-filter="theory"><span class="dot" style="background:#2563eb"></span>Lý thuyết</button>
    <button type="button" class="map-pill" data-filter="practice"><span class="dot" style="background:#16a34a"></span>Thực hành</button>
    <button type="button" class="map-pill" data-filter="parking"><span class="dot" style="background:#ea580c"></span>Trại xe</button>
    <button type="button" class="map-pill" data-filter="dorm"><span class="dot" style="background:#d97706"></span>Ký túc xá</button>
    <button type="button" class="map-pill" data-filter="other"><span class="dot" style="background:#7c3aed"></span>Công trình khác</button>
  </div>

  <!-- Bộ điều khiển 3D & La bàn góc trên phải -->
  <div class="map-controls" aria-label="Điều khiển bản đồ 3D">
    <button type="button" class="compass-widget" id="btn-compass" title="La bàn – Nhấp để đặt lại hướng Bắc">
      <svg class="compass-needle" id="compass-needle" viewBox="0 0 24 24">
        <polygon points="12,2 15,12 12,10 9,12" fill="#dc2626"/>
        <polygon points="12,22 15,12 12,14 9,12" fill="#94a3b8"/>
        <circle cx="12" cy="12" r="2" fill="#0f172a"/>
      </svg>
    </button>
    <button type="button" class="ctrl-btn" id="btn-zoom-in" title="Phóng to (+)">＋</button>
    <span class="zoom-level" id="zoom-level">100%</span>
    <button type="button" class="ctrl-btn" id="btn-zoom-out" title="Thu nhỏ (-)">－</button>
    <div class="ctrl-sep"></div>
    <button type="button" class="ctrl-btn ctrl-text" id="btn-view-mode" title="Chuyển góc nhìn 2D / 3D">2D</button>
    <button type="button" class="ctrl-btn" id="btn-toggle-labels" title="Ẩn / Hiện nhãn tòa nhà">🏷️</button>
    <button type="button" class="ctrl-btn" id="btn-auto-rotate" title="Tự động xoay 360°">↻</button>
    <button type="button" class="ctrl-btn" id="btn-reset-view" title="Đặt lại góc nhìn mặc định (phím 0)">⟲</button>
  </div>

  <!-- HUD Trực tuyến & Hướng dẫn góc dưới trái -->
  <div class="map-hud-bottom-left">
    <div class="map-live-stats">
      <span class="live-dot"></span>
      <span><b id="hud-stat-total">0</b> <?= $userRole === 'NV_SU_CO' ? 'nhiệm vụ sự cố' : ($userRole === 'NV_AN_NINH' ? 'nhiệm vụ camera' : 'phiếu đang xử lý') ?></span>
      <span style="opacity:.4">|</span>
      <span><b id="hud-stat-zones">0</b> khu vực liên quan</span>
    </div>
    <div class="map-controls-help">
      🖱️ Cuộn: <b>Thu phóng</b> · Kéo trái: <b>Xoay 3D</b> · Kéo phải: <b>Di chuyển</b> · Nhấp tòa nhà:
      <b><?= $isStaffRole ? 'Xem nhanh phản ánh &amp; đi tới xử lý' : 'Chọn phòng &amp; gửi yêu cầu' ?></b>
    </div>
  </div>

  <!-- Góc nhìn nhanh & Chế độ Thời gian (Sáng / Chiều / Đêm) góc dưới phải -->
  <div class="map-presets-bar" aria-label="Góc nhìn và ánh sáng 3D">
    <button type="button" class="preset-btn active" data-preset="overview" title="Góc nhìn tổng quan khuôn viên">Tổng quan</button>
    <button type="button" class="preset-btn" data-preset="tower" title="Bay tới Tòa nhà 15T">Tòa 15T</button>
    <button type="button" class="preset-btn" data-preset="gate" title="Bay tới Cổng chính QNU">Cổng QNU</button>
    <div class="preset-sep"></div>
    <button type="button" class="preset-btn active" data-theme="day" title="Ánh sáng ban ngày">☀️ Sáng</button>
    <button type="button" class="preset-btn" data-theme="sunset" title="Ánh sáng hoàng hôn">🌇 Chiều</button>
    <button type="button" class="preset-btn" data-theme="night" title="Chế độ ban đêm có đèn">🌙 Đêm</button>
  </div>
</section>

<div class="overlay" id="overlay" hidden></div>
<aside class="panel" id="panel" aria-hidden="true">
  <div class="panel-head">
    <div>
      <span class="chip" id="panel-type">Khu vực</span>
      <h2 id="panel-title">Thông tin tòa nhà</h2>
    </div>
    <button class="icon-btn" id="panel-close" aria-label="Đóng">✕</button>
  </div>

  <div class="panel-body">
    <div class="building-meta-card">
      <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Tòa nhà đang chọn</div>
      <div id="panel-building-name" style="font-weight:800;font-size:17px;margin-top:4px;color:var(--text)">—</div>
      <div id="panel-building-desc" style="font-size:13px;color:var(--muted);margin-top:4px;line-height:1.45"></div>

      <div class="building-meta-stats">
        <div class="b-stat-pill">
          <strong id="panel-floors">—</strong>
          <small>Quy mô tầng</small>
        </div>
        <div class="b-stat-pill">
          <strong id="panel-room-count">0</strong>
          <small>Phòng khả dụng</small>
        </div>
        <div class="b-stat-pill">
          <strong id="panel-issue-count" style="color:var(--danger)">0</strong>
          <small><?= $isStaffRole ? 'Phiếu liên quan' : 'Phiếu đang xử lý' ?></small>
        </div>
      </div>
    </div>

    <?php if ($isStaffRole): ?>
      <!-- Giao diện xem nhanh dành riêng cho Admin & Nhân viên (Tiếp nhận / Xử lý / An ninh) -->
      <div class="staff-quick-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px">
          <h3 style="font-size:13.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin:0">
            <?php
              echo match ($userRole) {
                  'NV_TIEP_NHAN' => '📥 Phản ánh &amp; An ninh tại tòa nhà',
                  'NV_SU_CO'     => '🔧 Sự cố được phân công tại tòa nhà',
                  'NV_AN_NINH'   => '🎥 Yêu cầu camera được giao tại tòa nhà',
                  default        => '🗃️ Danh sách nghiệp vụ tại tòa nhà',
              };
            ?>
          </h3>
          <span class="badge badge-info" style="font-size:11px"><?= e(ROLE_LABELS[$userRole] ?? $userRole) ?></span>
        </div>

        <?php if ($userRole === 'NV_TIEP_NHAN' || $userRole === 'QUAN_TRI'): ?>
          <div class="staff-filter-tabs" id="staff-filter-tabs">
            <button type="button" class="staff-tab active" data-kind="all">Tất cả</button>
            <button type="button" class="staff-tab" data-kind="suco">Sự cố</button>
            <button type="button" class="staff-tab" data-kind="camera">An ninh</button>
            <?php if ($userRole === 'QUAN_TRI'): ?>
              <button type="button" class="staff-tab" data-kind="timdo">Đồ thất lạc</button>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div id="panel-staff-list" class="staff-item-list">
          <p class="empty" style="padding:16px 8px">Chọn một tòa nhà để xem nhanh danh sách phiếu.</p>
        </div>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line);display:flex;flex-wrap:wrap;gap:8px">
          <?php if ($userRole === 'NV_TIEP_NHAN'): ?>
            <a href="index.php?c=suco&a=tiepnhan" class="btn btn-primary btn-sm" style="flex:1">Trang tiếp nhận sự cố</a>
            <a href="index.php?c=tiepnhancamera&a=index" class="btn btn-ghost btn-sm" style="flex:1">Tiếp nhận camera</a>
          <?php elseif ($userRole === 'NV_SU_CO'): ?>
            <a href="index.php?c=suco&a=xuly" class="btn btn-primary btn-sm" style="flex:1">Tất cả nhiệm vụ sự cố</a>
          <?php elseif ($userRole === 'NV_AN_NINH'): ?>
            <a href="index.php?c=kiemtracamera&a=index" class="btn btn-primary btn-sm" style="flex:1">Tất cả nhiệm vụ camera</a>
          <?php elseif ($userRole === 'QUAN_TRI'): ?>
            <a href="index.php?c=nghiepvu&a=index" class="btn btn-primary btn-sm" style="flex:1">Quản lý nghiệp vụ</a>
            <a href="index.php?c=khuvuc&a=index" class="btn btn-ghost btn-sm" style="flex:1">Quản lý khu vực</a>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- Giao diện gửi yêu cầu dành riêng cho Sinh viên, Giảng viên & Khách -->
      <div class="std-form" style="margin-bottom:20px">
        <label style="font-weight:700">Chọn phòng học / khu vực cụ thể
          <select id="panel-room-select">
            <option value="">-- Chọn phòng học (tùy chọn) --</option>
          </select>
        </label>
      </div>

      <div class="support-menu">
        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:12px">Bạn cần hệ thống hỗ trợ về</h3>
        <div style="display:flex;flex-direction:column;gap:10px">
          <button type="button" class="support-option-btn" data-target="suco">
            <span class="support-ico suco">🔧</span>
            <span class="support-text">
              <strong>Phản ánh sự cố tiện ích</strong>
              <small>Điện, nước, máy chiếu, thiết bị, cơ sở vật chất</small>
            </span>
            <span class="support-arrow">→</span>
          </button>

          <button type="button" class="support-option-btn" data-target="timdo">
            <span class="support-ico timdo">🎒</span>
            <span class="support-text">
              <strong>Tìm đồ &amp; Trả đồ thất lạc</strong>
              <small>Đăng tin báo mất hoặc nhặt được tài sản</small>
            </span>
            <span class="support-arrow">→</span>
          </button>

          <button type="button" class="support-option-btn" data-target="camera">
            <span class="support-ico camera">📹</span>
            <span class="support-text">
              <strong>Kiểm tra camera &amp; An ninh</strong>
              <small>Gửi yêu cầu trích xuất camera theo khung giờ</small>
            </span>
            <span class="support-arrow">→</span>
          </button>
        </div>
      </div>

      <?php if (!isLoggedIn()): ?>
        <div style="margin-top:20px;padding:13px 15px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;font-size:13px;color:#1e40af;line-height:1.5">
          ℹ️ Bạn chưa đăng nhập. Khi chọn chức năng, hệ thống sẽ chuyển đến trang <b>Đăng nhập / Đăng ký</b> và giữ nguyên tòa nhà bạn vừa chọn.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</aside>

<script>
window.CAMPUS_AUTH = <?= isLoggedIn() ? 'true' : 'false' ?>;
window.CAMPUS_ROLE = <?= json_encode($userRole) ?>;
window.CAMPUS_IS_STAFF = <?= $isStaffRole ? 'true' : 'false' ?>;
window.CAMPUS_LOCATIONS = {
  toaNhas: <?= json_encode($toaNhas, JSON_UNESCAPED_UNICODE) ?>,
  phongs: <?= json_encode($phongs, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script src="assets/js/map-data.js"></script>
<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>