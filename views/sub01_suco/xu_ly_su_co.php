<?php
$pageTitle = 'Xử lý phản ánh sự cố (UC1-04)';
$selectedId = (int)($_GET['id'] ?? 0);
$selectedNv = null;
if ($selectedId > 0) {
    foreach ($nhiemVus as $row) {
        if ((int)$row['MaPhanAnh'] === $selectedId) {
            $selectedNv = $row;
            break;
        }
    }
}
require __DIR__ . '/../layouts/header.php';
?>

<?php if ($selectedNv): ?>
  <!-- MÀN HÌNH CHI TIẾT NHIỆM VỤ & CẬP NHẬT KẾT QUẢ XỬ LÝ -->
  <div class="card">
    <div class="detail-header-bar">
      <div>
        <a href="index.php?c=suco&a=xuly" class="btn btn-ghost btn-sm" style="margin-bottom:8px">← Quay lại danh sách nhiệm vụ</a>
        <h2 style="margin:4px 0 0">Nhiệm vụ sự cố #<?= (int)$selectedNv['MaPhanAnh'] ?> – <?= e($selectedNv['TieuDe']) ?></h2>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <?= priorityBadge($selectedNv['MucDoUuTien']) ?>
        <?= statusBadge($selectedNv['TrangThaiPA']) ?>
      </div>
    </div>

    <div class="detail-info-grid">
      <div class="detail-meta-item">
        <small>Tòa nhà &amp; Phòng học</small>
        <strong><?= e($selectedNv['TenToaNha'] . ' – Phòng ' . $selectedNv['TenPhong']) ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Hạn xử lý</small>
        <strong><?= $selectedNv['HanXuLy'] ? date('d/m/Y H:i', strtotime($selectedNv['HanXuLy'])) : 'Không giới hạn' ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Trạng thái phân công</small>
        <strong><?= statusBadge($selectedNv['TrangThai']) ?></strong>
      </div>
    </div>

    <div class="detail-content-box">
      <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Mô tả sự cố hiện trường</div>
      <div><?= nl2br(e($selectedNv['NoiDung'])) ?></div>
      <?php if (!empty($selectedNv['GhiChu'])): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px dashed var(--line);font-size:13px;color:var(--text-2)">
          <b>Ghi chú từ bộ phận tiếp nhận:</b> <?= e($selectedNv['GhiChu']) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($selectedNv['AnhHienTruong'])): ?>
        <div style="margin-top:12px">
          <a href="<?= e($selectedNv['AnhHienTruong']) ?>" target="_blank" style="color:var(--primary);font-weight:600;font-size:13px">🖼️ Mở ảnh hiện trường</a>
          <img src="<?= e($selectedNv['AnhHienTruong']) ?>" alt="Ảnh hiện trường" class="detail-evidence-img">
        </div>
      <?php endif; ?>
    </div>

    <?php if ($selectedNv['TrangThai'] !== 'HOAN_THANH'): ?>
      <form method="POST" enctype="multipart/form-data" class="std-form card" style="margin:0">
        <h3>🔧 Cập nhật kết quả xử lý sự cố (UC1-04)</h3>
        <input type="hidden" name="MaPhanAnh" value="<?= (int)$selectedNv['MaPhanAnh'] ?>">
        <input type="hidden" name="MaPhanCong" value="<?= (int)$selectedNv['MaPhanCong'] ?>">
        <div class="row">
          <label>Trạng thái cập nhật
            <select name="TrangThaiMoi">
              <option value="DANG_XU_LY">Đang xử lý</option>
              <option value="DA_XU_LY" selected>Đã giải quyết / Hoàn thành</option>
            </select>
          </label>
          <label>Ảnh sau khi khắc phục (tùy chọn)
            <input type="file" name="anh_ket_qua" accept="image/jpeg,image/png,image/webp">
          </label>
        </div>
        <label>Nội dung kết quả khắc phục *
          <textarea name="NoiDungXuLy" rows="3" required placeholder="Mô tả chi tiết công việc đã sửa chữa/thay thế..."></textarea>
        </label>
        <label>Ghi chú thêm
          <input type="text" name="GhiChu">
        </label>
        <button type="submit" class="btn btn-primary btn-sm">Cập nhật kết quả xử lý</button>
      </form>
    <?php else: ?>
      <div class="alert alert-ok">✅ Nhiệm vụ sự cố này đã được hoàn thành.</div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- MÀN HÌNH DANH SÁCH TRƯỚC -->
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
      <div>
        <h2 style="margin:0 0 2px">🔧 Danh sách nhiệm vụ xử lý sự cố được phân công</h2>
        <small style="color:var(--muted)">Chọn một nhiệm vụ để xem chi tiết hiện trường và cập nhật kết quả khắc phục.</small>
      </div>
      <span class="badge badge-primary"><?= count($nhiemVus) ?> nhiệm vụ</span>
    </div>

    <?php if (!$nhiemVus): ?>
      <p class="empty">Chưa có nhiệm vụ xử lý sự cố nào được giao.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Mã</th>
              <th>Tiêu đề sự cố</th>
              <th>Khu vực / Phòng</th>
              <th>Mức độ</th>
              <th>Hạn xử lý</th>
              <th>Trạng thái</th>
              <th style="text-align:right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($nhiemVus as $nv): ?>
              <tr>
                <td><b>#<?= (int)$nv['MaPhanAnh'] ?></b></td>
                <td><b><?= e($nv['TieuDe']) ?></b></td>
                <td><?= e($nv['TenToaNha']) ?><br><small style="color:var(--muted)">Phòng <?= e($nv['TenPhong']) ?></small></td>
                <td><?= priorityBadge($nv['MucDoUuTien']) ?></td>
                <td><small><?= $nv['HanXuLy'] ? date('d/m/Y H:i', strtotime($nv['HanXuLy'])) : 'Không giới hạn' ?></small></td>
                <td><?= statusBadge($nv['TrangThaiPA']) ?></td>
                <td style="text-align:right">
                  <a href="index.php?c=suco&a=xuly&id=<?= (int)$nv['MaPhanAnh'] ?>" class="btn <?= $nv['TrangThai'] === 'HOAN_THANH' ? 'btn-ghost' : 'btn-primary' ?> btn-sm">
                    <?= $nv['TrangThai'] === 'HOAN_THANH' ? 'Xem chi tiết →' : 'Xem &amp; Xử lý →' ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
