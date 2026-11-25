<?php
$u = currentUser();
$flash = getFlash();
$notifies = [];
$unreadCount = 0;
if ($u) {
    $stN = db()->prepare("SELECT * FROM Notify WHERE MaNguoiNhan = ? ORDER BY ThoiGianTao DESC LIMIT 8");
    $stN->execute([$u['MaUser']]);
    $notifies = $stN->fetchAll();
    foreach ($notifies as $n) if (!$n['DaDoc']) $unreadCount++;
}

$initials = 'U';
if ($u && !empty($u['HoTen'])) {
    $parts = preg_split('/\s+/', trim($u['HoTen']));
    $last = end($parts);
    $first = reset($parts);
    $initials = mb_strtoupper(
        ($first !== $last ? mb_substr($first, 0, 1) : '') . mb_substr($last, 0, 1),
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'CampusFix 2.0 – Hệ thống Hỗ trợ Tiện ích & An ninh QNU') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= ($activeMenu ?? '') === 'home' ? 'is-home' : '' ?>">
<header class="header">
  <button class="icon-btn menu-toggle-btn" id="btn-menu" aria-label="Mở/Thu gọn menu" title="Menu chức năng">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>

  <a class="brand" href="index.php">
    <span class="brand-logo" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 21h18"/>
        <path d="M5 21V7l7-4 7 4v14"/>
        <path d="M9 21v-5h6v5"/>
        <path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M9 13h.01"/><path d="M15 13h.01"/>
      </svg>
    </span>
    <span class="brand-text">
      <span class="brand-title-row">
        <strong>CAMPUSFIX</strong>
      </span>
      <small>Hệ thống Hỗ trợ tiện ích và an ninh </small>
    </span>
  </a>

  <?php if (($activeMenu ?? '') === 'home'): ?>
  <div class="header-search">
    <svg class="header-search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input id="zone-search" type="search" list="zone-list" placeholder="Tìm kiếm nhanh tòa nhà..." autocomplete="off">
    <datalist id="zone-list"></datalist>
  </div>
  <?php else: ?>
  <div style="margin-left:auto"></div>
  <?php endif; ?>

  <nav class="header-actions">
    <?php if ($u): ?>
      <details class="notify-box">
        <summary class="btn btn-ghost btn-sm" style="cursor:pointer" title="Thông báo hệ thống">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span>Thông báo</span>
          <?php if ($unreadCount > 0): ?><span class="badge-pill"><?= $unreadCount ?></span><?php endif; ?>
        </summary>
        <div class="notify-dropdown">
          <div class="notify-header">
            <strong style="font-size:14px">Thông báo hệ thống</strong>
            <?php if ($unreadCount > 0): ?>
              <span class="badge badge-primary"><?= $unreadCount ?> mới</span>
            <?php endif; ?>
          </div>
          <?php if (!$notifies): ?>
            <p class="muted" style="font-size:13px;margin:12px 0 4px;text-align:center">Chưa có thông báo mới.</p>
          <?php else: foreach ($notifies as $n): ?>
            <a href="index.php?read_notify=<?= (int)$n['MaThongBao'] ?>" class="notify-item <?= $n['DaDoc'] ? '' : 'unread' ?>">
              <div><b><?= e($n['TieuDe']) ?></b></div>
              <small><?= e($n['NoiDung']) ?></small>
            </a>
          <?php endforeach; endif; ?>
        </div>
      </details>

      <a href="index.php?c=taikhoan&a=canhan" class="user-chip" title="Cập nhật thông tin cá nhân">
        <span class="user-avatar"><?= e($initials) ?></span>
        <span class="user-meta">
          <strong><?= e($u['HoTen']) ?></strong>
          <small><?= e(ROLE_LABELS[$u['VaiTro']] ?? $u['VaiTro']) ?></small>
        </span>
      </a>

      <button type="button" class="btn btn-primary btn-sm" id="btn-logout-trigger">Đăng xuất</button>
    <?php else: ?>
      <a href="index.php?c=auth&a=register" class="btn btn-ghost btn-sm">Đăng ký</a>
      <a href="index.php?c=auth&a=login" class="btn btn-primary btn-sm">Đăng nhập</a>
    <?php endif; ?>
  </nav>
</header>

<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>

<div class="layout">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <main class="main">
    <?php if ($flash): ?>
      <div class="alert <?= $flash['type'] === 'err' ? 'alert-err' : 'alert-ok' ?>">
        <span><?= $flash['type'] === 'err' ? '⚠️' : '✅' ?></span>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>