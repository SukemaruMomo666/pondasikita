<?php
// Ambil data profil toko (variabel $toko dan $toko_id sudah ada dari file induk)
// Tidak perlu session_start() atau require_once lagi
?>
<div class="tab-pane fade show active" id="profil-content">
    <form action="../actions/proses_pengaturan_toko.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="simpan_profil">
        
        <div class="profile-form-row">
            <div class="form-label-col"><label for="nama_toko">Nama Toko</label></div>
            <div class="form-input-col">
                <input type="text" name="nama_toko" class="form-control" value="<?= htmlspecialchars($toko['nama_toko']) ?>" maxlength="50">
                <small class="form-text">Nama akan ditampilkan di halaman utama toko Anda.</small>
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label>Logo Toko</label></div>
            <div class="form-input-col">
                <div class="logo-uploader">
                    <img src="../assets/uploads/logos/<?= htmlspecialchars($toko['logo_toko'] ?? 'default.png') ?>" class="logo-preview" id="logo_preview">
                    <div>
                        <button type="button" class="btn btn-outline" id="btn_ubah_logo">Ubah Logo</button>
                        <input type="file" name="logo_toko" id="input_logo" accept=".jpg,.png" style="display: none;">
                        <div class="logo-instructions">
                            <small class="form-text">Ukuran maks: 2MB, Format: JPG, PNG.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-form-row">
            <div class="form-label-col"><label for="deskripsi_toko">Deskripsi</label></div>
            <div class="form-input-col">
                <textarea name="deskripsi_toko" class="form-control" rows="5"><?= htmlspecialchars($toko['deskripsi_toko']) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Profil</button>
        </div>
    </form>
</div>

<script>
// JS untuk preview logo
$('#btn_ubah_logo').on('click', function() { $('#input_logo').click(); });
$('#input_logo').on('change', function(e) { if (e.target.files[0]) { $('#logo_preview').attr('src', URL.createObjectURL(e.target.files[0])); } });
</script>