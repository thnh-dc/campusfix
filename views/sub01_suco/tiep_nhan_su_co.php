<?php
$pageTitle = 'Tiếp nhận & Phân công sự cố (UC1-02, UC1-03)';
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
  <!-- MÀN HÌNH CHI TIẾT & THAO TÁC TIẾP NHẬN / PHÂN CÔNG -->
  <div class="card">
    <div class="detail-header-bar">
      <div>
        <a href="index.php?c=suco&a=tiepnhan" class="btn btn-ghost btn-sm" style="margin-bottom:8px">← Quay lại danh sách</a>
        <h2 style="margin:4px 0 0">Phản ánh sự cố #<?= (int)$selectedItem['MaPhanAnh'] ?> – <?= e($selectedItem['TieuDe']) ?></h2>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <?= priorityBadge($selectedItem['MucDoUuTien']) ?>
        <?= statusBadge($selectedItem['TrangThai']) ?>
      </div>
    </div>

    <div class="detail-info-grid">
      <div class="detail-meta-item">
        <small>Người gửi phản ánh</small>
        <strong><?= e($selectedItem['NguoiGui']) ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Tòa nhà &amp; Phòng học</small>
        <strong><?= e($selectedItem['TenToaNha'] . ' – Phòng ' . $selectedItem['TenPhong']) ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Vị trí chi tiết</small>
        <strong><?= e($selectedItem['ViTriChiTiet'] ?: 'Không ghi rõ') ?></strong>
      </div>
      <div class="detail-meta-item">
        <small>Thời gian gửi</small>
        <strong><?= date('d/m/Y H:i', strtotime($selectedItem['NgayGui'])) ?></strong>
      </div>
    </div>

    <div class="detail-content-box">
      <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Nội dung mô tả sự cố</div>
      <div><?= nl2br(e($selectedItem['NoiDung'])) ?></div>
      <?php if (!empty($selectedItem['AnhHienTruong'])): ?>
        <div style="margin-top:12px">
          <a href="<?= e($selectedItem['AnhHienTruong']) ?>" target="_blank" style="color:var(--primary);font-weight:600;font-size:13px">🖼️ Mở ảnh hiện trường kích thước đầy đủ</a>
          <img src="<?= e($selectedItem['AnhHienTruong']) ?>" alt="Ảnh hiện trường" class="detail-evidence-img">
        </div>
      <?php endif; ?>
    </div>

    <div class="grid-2" style="margin-top:14px">
      <?php if ($selectedItem['TrangThai'] === 'CHO_TIEP_NHAN'): ?>
        <form method="POST" class="std-form card" style="margin:0">
          <h3>1. Tiếp nhận &amp; Phân loại (UC1-02)</h3>
          <input type="hidden" name="action" value="duyet">
          <input type="hidden" name="MaPhanAnh" value="<?= (int)$selectedItem['MaPhanAnh'] ?>">
          <label>Danh mục sự cố
            <select name="MaDanhMuc">
              <?php foreach ($danhMucs as $dm): ?>
                <option value="<?= (int)$dm['MaDanhMuc'] ?>" <?= $dm['MaDanhMuc'] == $selectedItem['MaDanhMuc'] ? 'selected' : '' ?>><?= e($dm['TenDanhMuc']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Mức độ ưu tiên
            <select name="MucDoUuTien">
              <?php foreach (['THAP'=>'Thấp','TRUNG_BINH'=>'Trung bình','CAO'=>'Cao','KHAN_CAP'=>'Khẩn cấp'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $k === $selectedItem['MucDoUuTien'] ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <button type="submit" class="btn btn-primary btn-sm">Duyệt tiếp nhận</button>
        </form>

        <form method="POST" class="std-form card" style="margin:0">
          <h3>Từ chối tiếp nhận</h3>
          <input type="hidden" name="action" value="tuchoi">
          <input type="hidden" name="MaPhanAnh" value="<?= (int)$selectedItem['MaPhanAnh'] ?>">
          <label>Lý do từ chối *
            <input type="text" name="LyDoTuChoi" required placeholder="VD: Thông tin trùng lặp / không thuộc phạm vi">
          </label>
          <button type="submit" class="btn btn-danger btn-sm">Từ chối phiếu</button>
        </form>
      <?php else: ?>
        <form method="POST" class="std-form card" style="margin:0">
          <h3>2. Chuyển cho bộ phận xử lý (UC1-03)</h3>
          <input type="hidden" name="action" value="phancong">
          <input type="hidden" name="MaPhanAnh" value="<?= (int)$selectedItem['MaPhanAnh'] ?>">
          <label>Nhân viên xử lý sự cố *
            <select name="MaNhanVien" required>
              <?php foreach ($nhanViens as $nv): ?>
                <option value="<?= (int)$nv['MaUser'] ?>"><?= e($nv['HoTen'] . ' (' . $nv['DonVi'] . ')') ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Hạn xử lý
            <input type="datetime-local" name="HanXuLy">
          </label>
          <label>Ghi chú phân công
            <input type="text" name="GhiChu" placeholder="Hướng dẫn kiểm tra kỹ thuật...">
          </label>
          <button type="submit" class="btn btn-success btn-sm">Phân công xử lý</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>
  <!-- MÀN HÌNH DANH SÁCH TRƯỚC -->
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
      <div>
        <h2 style="margin:0 0 2px">📥 Danh sách phản ánh sự cố chờ tiếp nhận &amp; phân công</h2>
        <small style="color:var(--muted)">Chọn một phản ánh bên dưới để xem đầy đủ chi tiết và thực hiện tiếp nhận, từ chối hoặc phân công xử lý.</small>
      </div>
      <span class="badge badge-primary"><?= count($danhSach) ?> phiếu đang chờ</span>
    </div>

    <?php if ($selectedId > 0): ?>
      <div class="alert alert-err">⚠️ Không tìm thấy phản ánh #<?= $selectedId ?> trong danh sách chờ tiếp nhận/phân công (có thể phiếu đã được chuyển xử lý hoặc đóng).</div>
    <?php endif; ?>

    <?php if (!$danhSach): ?>
      <p class="empty">Hiện không có phản ánh nào chờ tiếp nhận hoặc phân công.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Mã</th>
              <th>Tiêu đề phản ánh</th>
              <th>Khu vực / Phòng</th>
              <th>Người gửi</th>
              <th>Mức độ</th>
              <th>Trạng thái</th>
              <th>Ngày gửi</th>
              <th style="text-align:right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($danhSach as $item): ?>
              <tr>
                <td><b>#<?= (int)$item['MaPhanAnh'] ?></b></td>
                <td><b><?= e($item['TieuDe']) ?></b></td>
                <td><?= e($item['TenToaNha']) ?><br><small style="color:var(--muted)">Phòng <?= e($item['TenPhong']) ?></small></td>
                <td><?= e($item['NguoiGui']) ?></td>
                <td><?= priorityBadge($item['MucDoUuTien']) ?></td>
                <td><?= statusBadge($item['TrangThai']) ?></td>
                <td><small><?= date('d/m/Y H:i', strtotime($item['NgayGui'])) ?></small></td>
                <td style="text-align:right">
                  <a href="index.php?c=suco&a=tiepnhan&id=<?= (int)$item['MaPhanAnh'] ?>" class="btn btn-primary btn-sm">
                    <?= $item['TrangThai'] === 'CHO_TIEP_NHAN' ? 'Xem chi tiết &amp; Duyệt →' : 'Xem chi tiết &amp; Phân công →' ?>
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
