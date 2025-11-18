<?php
session_start(); require_once '../../config/koneksi.php';
// ... (Kode keamanan & ambil $toko_id) ...
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

// Ambil komponen yang sudah ada
$dekorasi_query = $koneksi->prepare("SELECT * FROM tb_toko_dekorasi WHERE toko_id = ? ORDER BY urutan ASC");
$dekorasi_query->bind_param("i", $toko_id);
$dekorasi_query->execute();
$result_dekorasi = $dekorasi_query->get_result();

// Ambil produk untuk pilihan "Produk Unggulan"
$produk_query = $koneksi->prepare("SELECT id, nama_barang FROM tb_barang WHERE toko_id = ?");
$produk_query->bind_param("i", $toko_id);
$produk_query->execute();
$result_produk = $produk_query->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dekorasi Toko - Pengaturan</title>
    </head>
<body>
<div class="container-scroller">
    <?php include '../partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h3 class="page-title"><i class="mdi mdi-palette"></i> Dekorasi Toko</h3></div>
                <div class="row">
                    <div class="col-md-4 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Tambah Komponen Baru</h4>
                                <form action="../../actions/proses_dekorasi.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="tambah">
                                    <div class="form-group">
                                        <label>Tipe Komponen</label>
                                        <select name="tipe_komponen" id="tipe_komponen_select" class="form-control">
                                            <option value="BANNER">Banner Gambar</option>
                                            <option value="PRODUK_UNGGULAN">Produk Unggulan</option>
                                        </select>
                                    </div>
                                    <div id="form-dinamis-container"></div>
                                    <button type="submit" class="btn btn-primary mt-3">Tambah Komponen</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Layout Halaman Toko Anda</h4>
                                <?php while($komponen = $result_dekorasi->fetch_assoc()): ?>
                                    <div class="border p-3 mb-2 d-flex justify-content-between">
                                        <span><i class="mdi mdi-drag-vertical"></i> <?= htmlspecialchars($komponen['tipe_komponen']) ?></span>
                                        <a href="../../actions/proses_dekorasi.php?hapus=<?= $komponen['id'] ?>" class="text-danger">Hapus</a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk menampilkan form sesuai tipe komponen yang dipilih
const formContainer = document.getElementById('form-dinamis-container');
const tipeSelect = document.getElementById('tipe_komponen_select');

function renderForm() {
    const tipe = tipeSelect.value;
    if (tipe === 'BANNER') {
        formContainer.innerHTML = `
            <div class="form-group">
                <label>Upload Gambar Banner</label>
                <input type="file" name="konten_gambar" class="form-control" required>
            </div>`;
    } else if (tipe === 'PRODUK_UNGGULAN') {
        formContainer.innerHTML = `
            <div class="form-group">
                <label>Judul Section</label>
                <input type="text" name="konten_judul" class="form-control" value="Produk Pilihan" required>
            </div>
            <div class="form-group">
                <label>Pilih Produk (maks. 4)</label>
                <select name="konten_produk_ids[]" class="form-control" multiple required>
                    <?php $result_produk->data_seek(0); while($p = $result_produk->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_barang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>`;
    }
}
tipeSelect.addEventListener('change', renderForm);
renderForm(); // Panggil saat halaman dimuat
</script>
</body>
</html>