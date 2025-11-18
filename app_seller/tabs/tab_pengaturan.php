<?php
// Ambil data jam operasional yang sudah ada
$jam_operasional = [];
$jam_query = $koneksi->prepare("SELECT * FROM tb_toko_jam_operasional WHERE toko_id = ?");
$jam_query->bind_param("i", $toko_id);
$jam_query->execute();
$result_jam = $jam_query->get_result();
while($row = $result_jam->fetch_assoc()) {
    $jam_operasional[$row['hari']] = $row;
}
?>
<div class="tab-pane fade show active" id="pengaturan-content">
    <form action="../actions/proses_pengaturan_toko.php" method="POST">
        <input type="hidden" name="action" value="simpan_pengaturan_dasar">

        <h4 class="section-title">Mode Toko</h4>
        <div class="profile-form-row">
            <div class="form-label-col">Status Toko</div>
            <div class="form-input-col">
                <select name="status_operasional" class="form-control">
                    <option value="Buka" <?= ($toko['status_operasional'] ?? 'Buka') == 'Buka' ? 'selected' : '' ?>>Buka (Normal)</option>
                    <option value="Tutup" <?= ($toko['status_operasional'] ?? 'Buka') == 'Tutup' ? 'selected' : '' ?>>Tutup (Mode Libur)</option>
                </select>
                <small class="form-text">Saat mode libur, produk Anda tidak akan dapat dibeli oleh pelanggan.</small>
            </div>
        </div>
        
        <hr class="my-4">

        <h4 class="section-title">Jam Operasional</h4>
        <?php for ($i = 1; $i <= 7; $i++): 
            $nama_hari = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"][$i-1];
            $data_hari = $jam_operasional[$i] ?? null;
        ?>
        <div class="courier-item"> <div class="courier-info">
                 <div class="form-check">
                    <label class="form-check-label d-flex align-items-center">
                        <input type="checkbox" name="is_buka[<?= $i ?>]" class="form-check-input" value="1" <?= ($data_hari['is_buka'] ?? 0) ? 'checked' : '' ?>>
                        <span class="ms-2" style="width: 80px;"><?= $nama_hari ?></span>
                    </label>
                </div>
            </div>
            <div class="courier-actions">
                <input type="time" name="jam_buka[<?= $i ?>]" class="form-control" style="width: 120px;" value="<?= $data_hari['jam_buka'] ?? '' ?>">
                <span class="mx-2">-</span>
                <input type="time" name="jam_tutup[<?= $i ?>]" class="form-control" style="width: 120px;" value="<?= $data_hari['jam_tutup'] ?? '' ?>">
            </div>
        </div>
        <?php endfor; ?>

        <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </div>
    </form>
</div>