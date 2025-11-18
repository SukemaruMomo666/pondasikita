<?php
// Koneksi ke database
require_once '../config/koneksi.php';

// Ambil data dari database
$provinces = $koneksi->query("SELECT id, name FROM provinces ORDER BY name ASC");

// Ambil semua kota
$cities = [];
$result = $koneksi->query("SELECT id, province_id, name FROM cities ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

// Ambil semua kecamatan
$districts = [];
$result = $koneksi->query("SELECT id, city_id, name FROM districts ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $districts[] = $row;
}

// Data toko jika edit
$current_province_id = $toko['province_id'] ?? null;
$current_city_id = $toko['city_id'] ?? null;
$current_district_id = $toko['district_id'] ?? null;
?>

<div class="tab-pane fade show active" id="alamat-content">
    <p class="text-secondary mb-4">Pastikan Anda mengisi alamat dan kontak dengan benar untuk memudahkan proses pengiriman dan komunikasi dengan pembeli.</p>

    <form action="../actions/proses_pengaturan_toko.php" method="POST">
        <input type="hidden" name="action" value="simpan_alamat">

        <div class="profile-form-row">
            <div class="form-label-col"><label for="alamat_toko">Alamat Lengkap</label></div>
            <div class="form-input-col">
                <textarea name="alamat_toko" id="alamat_toko" class="form-control" rows="4"><?= htmlspecialchars($toko['alamat_toko'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label for="provinsi">Provinsi</label></div>
            <div class="form-input-col">
                <select name="province_id" id="provinsi" class="form-select" required>
                    <option value="">-- Pilih Provinsi --</option>
                    <?php while ($province = $provinces->fetch_assoc()): ?>
                        <option value="<?= $province['id'] ?>" <?= ($current_province_id == $province['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($province['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label for="kota_kabupaten">Kota/Kabupaten</label></div>
            <div class="form-input-col">
                <select name="city_id" id="kota_kabupaten" class="form-select" required>
                    <option value="">-- Pilih Kota/Kabupaten --</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= $city['id'] ?>" data-provinsi="<?= $city['province_id'] ?>" <?= ($current_city_id == $city['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label for="kecamatan">Kecamatan</label></div>
            <div class="form-input-col">
                <select name="district_id" id="kecamatan" class="form-select" required>
                    <option value="">-- Pilih Kecamatan --</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= $district['id'] ?>" data-kota="<?= $district['city_id'] ?>" <?= ($current_district_id == $district['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($district['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label for="kode_pos">Kode Pos</label></div>
            <div class="form-input-col">
                <input type="text" name="kode_pos" id="kode_pos" class="form-control" value="<?= htmlspecialchars($toko['kode_pos'] ?? '') ?>">
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label for="telepon_toko">Telepon</label></div>
            <div class="form-input-col">
                <input type="text" name="telepon_toko" id="telepon_toko" class="form-control" value="<?= htmlspecialchars($toko['telepon_toko'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Alamat Toko</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const provinsiSelect = document.getElementById('provinsi');
    const kotaSelect = document.getElementById('kota_kabupaten');
    const kecamatanSelect = document.getElementById('kecamatan');

    function filterSelect(select, attr, id) {
        Array.from(select.options).forEach(opt => {
            if (!opt.value) return;
            opt.style.display = opt.getAttribute(attr) === id ? '' : 'none';
        });
    }

    function resetSelect(select) {
        select.selectedIndex = 0;
    }

    provinsiSelect.addEventListener('change', () => {
        const provId = provinsiSelect.value;
        filterSelect(kotaSelect, 'data-provinsi', provId);
        resetSelect(kotaSelect);

        filterSelect(kecamatanSelect, 'data-kota', '');
        resetSelect(kecamatanSelect);
    });

    kotaSelect.addEventListener('change', () => {
        const kotaId = kotaSelect.value;
        filterSelect(kecamatanSelect, 'data-kota', kotaId);
        resetSelect(kecamatanSelect);
    });

    // Saat load: tampilkan data sesuai nilai terpilih
    if (provinsiSelect.value) {
        filterSelect(kotaSelect, 'data-provinsi', provinsiSelect.value);
    }
    if (kotaSelect.value) {
        filterSelect(kecamatanSelect, 'data-kota', kotaSelect.value);
    }
});
</script>
