<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- VALIDASI & AMBIL DATA PRODUK ---
if (!isset($_GET['id'])) {
    die("ID Produk tidak valid.");
}
$produk_id = (int)$_GET['id'];

// Query untuk detail produk utama
$sql_produk = "SELECT b.*, k.nama_kategori, t.nama_toko 
               FROM tb_barang b 
               LEFT JOIN tb_kategori k ON b.kategori_id = k.id
               JOIN tb_toko t ON b.toko_id = t.id
               WHERE b.id = ?";
$stmt_produk = $koneksi->prepare($sql_produk);
$stmt_produk->bind_param("i", $produk_id);
$stmt_produk->execute();
$result_produk = $stmt_produk->get_result();
if ($result_produk->num_rows === 0) {
    die("Produk tidak ditemukan.");
}
$produk = $result_produk->fetch_assoc();

// Query untuk gambar-gambar lain (jika ada tabel tb_gambar_barang)
// Untuk contoh ini, kita asumsikan gambar hanya satu dari gambar_utama
$gallery_images = [$produk['gambar_utama']]; 

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Produk - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'moderasi_produk.php'; // Anggap ini bagian dari moderasi
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="product-detail-header">
                    <img src="../assets/uploads/products/<?= htmlspecialchars($produk['gambar_utama'] ?? 'default.jpg') ?>" alt="Gambar Utama" class="main-image">
                    <div class="header-info">
                        <h1 class="product-title"><?= htmlspecialchars($produk['nama_barang']) ?></h1>
                        <p class="product-meta">
                            Dijual oleh: <a href="detail_toko.php?id=<?= $produk['toko_id'] ?>"><?= htmlspecialchars($produk['nama_toko']) ?></a> | 
                            Kategori: <strong><?= htmlspecialchars($produk['nama_kategori'] ?? 'Tidak ada') ?></strong>
                        </p>
                        <span class="status-badge status-<?= strtolower($produk['status_moderasi']) ?>"><?= $produk['status_moderasi'] ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Deskripsi Produk</h4>
                                <div style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Galeri Gambar</h4>
                                <div class="image-gallery mt-3">
                                    <?php foreach($gallery_images as $img): ?>
                                    <div class="gallery-item">
                                        <img src="../assets/uploads/products/<?= htmlspecialchars($img ?? 'default.jpg') ?>" alt="Gambar Galeri">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Informasi Harga & Stok</h4>
                                <ul class="info-list mt-3">
                                    <li><span class="label">Harga</span><span class="value">Rp <?= number_format($produk['harga']) ?></span></li>
                                    <li><span class="label">Stok</span><span class="value"><?= $produk['stok'] ?> <?= $produk['satuan_unit'] ?></span></li>
                                    <li><span class="label">Berat</span><span class="value"><?= $produk['berat_kg'] ?> kg</span></li>
                                </ul>
                            </div>
                        </div>
                        <?php if ($produk['status_moderasi'] == 'pending'): ?>
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Aksi Moderasi</h4>
                                <div class="d-flex flex-column gap-2 mt-3">
                                    <a href="actions/proses_moderasi.php?id=<?= $produk['id'] ?>&action=approve" class="btn btn-success w-100" onclick="return confirm('Anda yakin ingin menyetujui produk ini?')">Setujui Produk</a>
                                    <button class="btn btn-danger w-100 btn-tolak" data-id="<?= $produk['id'] ?>">Tolak Produk</button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tolak (sama seperti di moderasi_produk.php) -->
<div class="modal-overlay" id="tolakProdukModal">
    <div class="modal-content">
        <form action="actions/proses_moderasi.php" method="POST">
            <div class="modal-header"><h4 class="modal-title">Tolak Produk</h4><button type="button" class="close-btn" data-close-modal>&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="produk_id" id="produk_id_tolak">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="alasan_penolakan">Alasan Penolakan</label>
                    <textarea class="form-control" name="alasan_penolakan" rows="4" required placeholder="Jelaskan mengapa produk ini ditolak..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
                <button type="submit" class="btn btn-danger">Kirim Penolakan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Logika modal penolakan
    const modal = $('#tolakProdukModal');
    $('.btn-tolak').on('click', function() {
        $('#produk_id_tolak').val($(this).data('id'));
        modal.css('display', 'flex');
    });
    $('[data-close-modal]').on('click', function() {
        modal.fadeOut();
    });
});
</script>
</body>
</html>
