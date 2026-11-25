</main>
</div>

<!-- Modal xác nhận đăng xuất (chuẩn UC0-03) -->
<div class="modal-backdrop" id="logout-modal" hidden role="dialog" aria-modal="true" aria-labelledby="logout-modal-title">
  <div class="modal-card">
    <h3 id="logout-modal-title">Xác nhận đăng xuất</h3>
    <p>Bạn có chắc chắn muốn đăng xuất hệ thống ?</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost btn-sm" id="btn-logout-cancel">Hủy bỏ</button>
      <a href="index.php?c=auth&a=logout" class="btn btn-danger btn-sm">Đồng ý đăng xuất</a>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status" hidden></div>

<footer class="footer">
  <div>© 2026 <strong> CampusFix</strong> – Hệ thống Hỗ trợ tiện ích và an ninh trong trường đại học</div>
  <div>Hotline: <strong>0900 1000</strong> · Email: <strong>hotro@qnu.edu.vn</strong></div>
</footer>

<script>
(function() {
  const btnMenu = document.getElementById('btn-menu');
  const sidebarEl = document.getElementById('sidebar');
  const backdropEl = document.getElementById('sidebar-backdrop');

  function toggleSidebar(forceState) {
    if (!sidebarEl) return;
    const willOpen = typeof forceState === 'boolean' ? forceState : !sidebarEl.classList.contains('open');
    sidebarEl.classList.toggle('open', willOpen);
    if (backdropEl) backdropEl.hidden = !willOpen;
    setTimeout(() => window.dispatchEvent(new Event('resize')), 220);
  }

  if (btnMenu) btnMenu.addEventListener('click', () => toggleSidebar());
  if (backdropEl) backdropEl.addEventListener('click', () => toggleSidebar(false));

  // Modal xác nhận đăng xuất (UC0-03)
  const logoutBtn = document.getElementById('btn-logout-trigger');
  const logoutModal = document.getElementById('logout-modal');
  const logoutCancel = document.getElementById('btn-logout-cancel');

  if (logoutBtn && logoutModal) {
    logoutBtn.addEventListener('click', () => { logoutModal.hidden = false; });
    if (logoutCancel) logoutCancel.addEventListener('click', () => { logoutModal.hidden = true; });
    logoutModal.addEventListener('click', (e) => {
      if (e.target === logoutModal) logoutModal.hidden = true;
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !logoutModal.hidden) logoutModal.hidden = true;
    });
  }
})();
</script>
</body>
</html>