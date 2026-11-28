<?php
$pageTitle = 'Gửi phản ánh sự cố tiện ích';
require __DIR__ . '/../layouts/header.php';
?>
<div class="card form-card-center" style="max-width:740px;margin:16px auto">
  <h2>✍️ Gửi phản ánh sự cố tiện ích</h2>
  <form method="POST" enctype="multipart/form-data" class="std-form">
    <label>Danh mục sự cố *
      <select name="MaDanhMuc" required>
        <option value="">-- Chọn danh mục sự cố --</option>
        <?php foreach ($danhMucs as $dm): ?>
          <option value="<?= (int)$dm['MaDanhMuc'] ?>"><?= e($dm['TenDanhMuc']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="row">
      <label>Tòa nhà *
        <select name="MaToaNha" id="select-toanha" required>
          <option value="">-- Chọn tòa nhà --</option>
          <?php foreach ($toaNhas as $tn): ?>
            <option value="<?= (int)$tn['MaToaNha'] ?>" <?= ((int)($selectedToaNha ?? 0) === (int)$tn['MaToaNha']) ? 'selected' : '' ?>>
              <?= e($tn['TenToaNha']) ?> (<?= e($tn['KyHieu']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Phòng học *
        <select name="MaPhong" id="select-phong" required disabled>
          <option value="">-- Vui lòng chọn tòa nhà trước --</option>
          <?php foreach ($phongs as $p): ?>
            <option value="<?= (int)$p['MaPhong'] ?>" data-toanha="<?= (int)$p['MaToaNha'] ?>" <?= ((int)($selectedPhong ?? 0) === (int)$p['MaPhong']) ? 'selected' : '' ?>>
              Tầng <?= (int)$p['Tang'] ?> - Phòng <?= e($p['TenPhong']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <label>Mức độ ưu tiên *
      <select name="MucDoUuTien" required>
        <option value="THAP">Thấp</option>
        <option value="TRUNG_BINH" selected>Trung bình</option>
        <option value="CAO">Cao</option>
        <option value="KHAN_CAP">Khẩn cấp</option>
      </select>
    </label>

    <label>Tiêu đề phản ánh *
      <input type="text" name="TieuDe" maxlength="200" required placeholder="VD: Máy chiếu không lên hình">
    </label>

    <label>Vị trí chi tiết trong phòng
      <input type="text" name="ViTriChiTiet" maxlength="255" placeholder="VD: Bàn giảng viên, góc trái phòng">
    </label>

    <label>Mô tả chi tiết sự cố *
      <textarea name="NoiDung" rows="4" required placeholder="Mô tả chi tiết hiện trạng sự cố..."></textarea>
    </label>

    <label>Hình ảnh minh họa (JPG, PNG, WEBP ≤ 5MB)
      <input type="file" name="tep_dinh_kem" accept="image/jpeg,image/png,image/webp">
    </label>

    <div style="display:flex;gap:10px">
      <a href="index.php" class="btn btn-ghost">Hủy</a>
      <button type="submit" class="btn btn-primary">Gửi phản ánh</button>
    </div>
  </form>
</div>

<script>
(function() {
  const selTN = document.getElementById('select-toanha');
  const selP  = document.getElementById('select-phong');
  const allOptions = Array.from(selP.querySelectorAll('option[data-toanha]'));

  function filterRooms(keepSelected) {
    const tnId = selTN.value;
    const prevVal = keepSelected ? selP.value : '';
    selP.innerHTML = '';

    if (!tnId) {
      selP.disabled = true;
      selP.innerHTML = '<option value="">-- Vui lòng chọn tòa nhà trước --</option>';
      return;
    }

    const matched = allOptions.filter(opt => opt.dataset.toanha === tnId);
    if (matched.length === 0) {
      selP.disabled = true;
      selP.innerHTML = '<option value="">-- Tòa nhà này chưa có phòng học --</option>';
      return;
    }

    selP.disabled = false;
    selP.innerHTML = '<option value="">-- Chọn phòng học --</option>';
    matched.forEach(opt => {
      const clone = opt.cloneNode(true);
      if (prevVal && clone.value === prevVal) clone.selected = true;
      selP.appendChild(clone);
    });
  }

  selTN.addEventListener('change', () => filterRooms(false));
  filterRooms(true);
})();
</script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>