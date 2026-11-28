<?php
$pageTitle = 'Theo dõi & Tra cứu phản ánh sự cố (UC1-05, UC1-06)';
$selectedId = (int)($_GET['id'] ?? 0);
$selectedItem = null;
if ($selectedId > 0) {
    foreach ($danhSach as $row) {
        if ((int)$row['MaPhanAnh'] === $selectedId) {
            $selectedItem = $row;
            break;
        }
    }
}
require __DIR__ . '/../layouts/header.php';
?>

<?php if ($selectedItem): ?>
  <!-- MÀN HÌNH CHI TIẾT PHẢN ÁNH SỰ CỐ -->
  <div class="card">
    <div class="detail-header-bar">
      <div>
        <a href="index.php?c=suco&a=theodoi" class="btn btn-ghost btn-sm" style="margin-bottom:8px">← Quay lại danh sách sự cố</a>
        <h2 style="margin:4px 0 0">Chi tiết phản ánh #<?= (int)$selectedItem['MaPhanAnh'] ?> – <?= e($selectedItem['TieuDe']) ?></h2>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <?= priorityBadge($selectedItem['MucDoUuTien']) ?>
        <?= statusBadge($selectedItem['TrangThai']) ?>
      </div>
    </div>

    <div class="detail-info-grid">
      <div class="detail-meta-item">
        <small>Danh mục sự cố</small>
        <strong><?= e($selectedItem['TenDanhMuc']) ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Người gửi</small>
        <strong><?= e($selectedItem['NguoiGui']) ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Khu vực / Phòng học</small>
        <strong><?= e($selectedItem['TenToaNha'] . ' – Phòng ' . $selectedItem['TenPhong']) ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Ngày gửi</small>
        <strong><?= date('d/m/Y H:i', strtotime($selectedItem['NgayGui'])) ?></strong>
      </div>
    </div>

    <div class="detail-content-box">
      <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Nội dung phản ánh</div>
      <div><?= nl2br(e($selectedItem['NoiDung'])) ?></div>
      <?php if (!empty($selectedItem['AnhTruoc'])): ?>
        <div style="margin-top:12px">
          <a href="<?= e($selectedItem['AnhTruoc']) ?>" target="_blank" style="color:var(--primary);font-weight:600;font-size:13px">🖼️ Xem ảnh hiện trường trước xử lý</a>
          <img src="<?= e($selectedItem['AnhTruoc']) ?>" alt="Ảnh trước xử lý" class="detail-evidence-img">
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($selectedItem['LyDoTuChoi'])): ?>
      <div class="alert alert-err">⚠️ <b>Lý do từ chối:</b> <?= e($selectedItem['LyDoTuChoi']) ?></div>
    <?php endif; ?>

    <?php if (!empty($selectedItem['NoiDungXuLy']) || !empty($selectedItem['AnhSau'])): ?>
      <div class="detail-content-box" style="background:#f0fdf4;border-color:#bbf7d0">
        <div style="font-size:12px;font-weight:700;color:#15803d;text-transform:uppercase;margin-bottom:6px">Kết quả khắc phục từ bộ phận kỹ thuật</div>
        <?php if (!empty($selectedItem['NoiDungXuLy'])): ?>
          <div><?= nl2br(e($selectedItem['NoiDungXuLy'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($selectedItem['AnhSau'])): ?>
          <div style="margin-top:12px">
            <a href="<?= e($selectedItem['AnhSau']) ?>" target="_blank" style="color:#15803d;font-weight:600;font-size:13px">✅ Xem ảnh sau khi khắc phục</a>
            <img src="<?= e($selectedItem['AnhSau']) ?>" alt="Ảnh sau xử lý" class="detail-evidence-img">
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- MÀN HÌNH DANH SÁCH TRƯỚC -->
  <div class="card">
    <h2>📋 Theo dõi &amp; Tra cứu lịch sử phản ánh sự cố</h2>
    <form method="GET" class="filter-bar">
      <input type="hidden" name="c" value="suco">
      <input type="hidden" name="a" value="theodoi">
      <div>
        <label style="font-size:12px">Từ khóa</label>
        <input type="text" name="kw" value="<?= e($_GET['kw'] ?? '') ?>" placeholder="Tiêu đề, nội dung...">
      </div>
      <div>
        <label style="font-size:12px">Tòa nhà</label>
        <select name="toanha">
          <option value="0">-- Tất cả tòa nhà --</option>
          <?php foreach ($toaNhas as $tn): ?>
            <option value="<?= (int)$tn['MaToaNha'] ?>" <?= ((int)($_GET['toanha'] ?? 0) === (int)$tn['MaToaNha']) ? 'selected' : '' ?>><?= e($tn['TenToaNha']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:12px">Trạng thái</label>
        <select name="status">
          <option value="">-- Tất cả --</option>
          <?php foreach (['CHO_TIEP_NHAN'=>'Chờ tiếp nhận','DA_TIEP_NHAN'=>'Đã tiếp nhận','DA_PHAN_CONG'=>'Đã phân công','DANG_XU_LY'=>'Đang xử lý','DA_XU_LY'=>'Đã giải quyết','TU_CHOI'=>'Từ chối'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex:0"><button type="submit" class="btn btn-primary">Lọc</button></div>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Mã</th>
            <th>Tiêu đề &amp; Danh mục</th>
            <th>Khu vực</th>
            <th>Mức độ</th>
            <th>Trạng thái</th>
            <th>Ngày gửi</th>
            <th style="text-align:right">Chi tiết</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$danhSach): ?>
            <tr><td colspan="7" class="empty">Không có dữ liệu phản ánh sự cố phù hợp.</td></tr>
          <?php else: foreach ($danhSach as $r): ?>
            <tr>
              <td><b>#<?= (int)$r['MaPhanAnh'] ?></b></td>
              <td>
                <b><?= e($r['TieuDe']) ?></b><br>
                <small style="color:var(--muted)"><?= e($r['TenDanhMuc']) ?> · <?= e($r['NguoiGui']) ?></small>
              </td>
              <td><?= e($r['TenToaNha']) ?><br><small style="color:var(--muted)">Phòng <?= e($r['TenPhong']) ?></small></td>
              <td><?= priorityBadge($r['MucDoUuTien']) ?></td>
              <td><?= statusBadge($r['TrangThai']) ?></td>
              <td><small><?= date('d/m/Y H:i', strtotime($r['NgayGui'])) ?></small></td>
              <td style="text-align:right">
                <a href="index.php?c=suco&a=theodoi&id=<?= (int)$r['MaPhanAnh'] ?>" class="btn btn-ghost btn-sm">Xem chi tiết →</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
