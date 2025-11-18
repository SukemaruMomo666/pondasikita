<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- VALIDASI & AMBIL DATA EVENT ---
if (!isset($_GET['id'])) {
    die("ID Event tidak valid.");
}
$event_id = (int)$_GET['id'];

// Query untuk detail event
$stmt_event = $koneksi->prepare("SELECT * FROM tb_flash_sale_events WHERE id = ?");
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$result_event = $stmt_event->get_result();
if ($result_event->num_rows === 0) {
    die("Event Flash Sale tidak ditemukan.");
}
$event = $result_event->fetch_assoc();
$stmt_event->close();

// --- AMBIL PRODUK BERDASARKAN STATUS MODERASI ---
function getFlashSaleProducts($koneksi, $event_id, $status) {
    $sql = "SELECT fsp.id, fsp.harga_flash_sale, fsp.stok_flash_sale, 
                   b.nama_barang, b.harga as harga_asli, b.gambar_utama, t.nama_toko
            FROM tb_flash_sale_produk fsp
            JOIN tb_barang b ON fsp.barang_id = b.id
            JOIN tb_toko t ON fsp.toko_id = t.id
            WHERE fsp.event_id = ? AND fsp.status_moderasi = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("is", $event_id, $status);
    $stmt->execute();
    return $stmt->get_result();
}

$produk_pending = getFlashSaleProducts($koneksi, $event_id, 'pending');
$produk_approved = getFlashSaleProducts($koneksi, $event_id, 'approved');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Event Flash Sale - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'kelola_flash_sale.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Detail Flash Sale: <?= htmlspecialchars($event['nama_event']) ?></h1>
                        <p class="text-secondary">Kelola dan moderasi produk yang berpartisipasi dalam event ini.</p>
                    </div>
                </div>

                <?php if (isset($_SESSION['feedback'])): ?>
                <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>" style="margin-bottom: 1.5rem;">
                    <span><?= $_SESSION['feedback']['pesan'] ?></span>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Kolom Kiri: Moderasi Produk -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Produk Perlu Disetujui (<?= $produk_pending->num_rows ?>)</h4>
                                <div class="table-wrapper mt-3">
                                    <table class="table">
                                        <thead><tr><th>Produk</th><th>Toko</th><th>Harga</th><th>Aksi</th></tr></thead>
                                        <tbody>
                                            <?php if ($produk_pending->num_rows > 0): ?>
                                                <?php while($p = $produk_pending->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="product-info">
                                                            <img src="../assets/uploads/products/<?= htmlspecialchars($p['gambar_utama']) ?>" class="product-image">
                                                            <div class="product-info">
                                                                <p class="product-name mb-0"><?= htmlspecialchars($p['nama_barang']) ?></p>
                                                                <p class="product-price-info mb-0">
                                                                    <span class="original-price">Rp <?= number_format($p['harga_asli']) ?></span> -> 
                                                                    <span class="sale-price">Rp <?= number_format($p['harga_flash_sale']) ?></span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($p['nama_toko']) ?></td>
                                                    <td>Stok: <?= $p['stok_flash_sale'] ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="actions/proses_moderasi_flash_sale.php?id=<?= $p['id'] ?>&action=approve" class="btn btn-success btn-sm" onclick="return confirm('Setujui produk ini?')">✓</a>
                                                            <a href="actions/proses_moderasi_flash_sale.php?id=<?= $p['id'] ?>&action=reject" class="btn btn-danger btn-sm" onclick="return confirm('Tolak produk ini?')">✕</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center text-secondary py-4">Tidak ada produk yang menunggu persetujuan.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Kolom Kanan: Produk Disetujui -->
                    <div class="col-lg-4">
                        <div class="card product-list-card">
                            <div class="card-body">
                                <h4 class="card-title">Produk Disetujui (<?= $produk_approved->num_rows ?>)</h4>
                                <ul class="list-group list-group-flush mt-3">
                                     <?php if ($produk_approved->num_rows > 0): ?>
                                        <?php while($p = $produk_approved->fetch_assoc()): ?>
                                        <li class="list-group-item">
                                            <img src="../assets/uploads/products/<?= htmlspecialchars($p['gambar_utama']) ?>" class="product-image">
                                            <div class="product-info">
                                                <p class="product-name mb-0"><?= htmlspecialchars($p['nama_barang']) ?></p>
                                                <p class="product-price-info mb-0">
                                                    <span class="sale-price">Rp <?= number_format($p['harga_flash_sale']) ?></span>
                                                </p>
                                            </div>
                                        </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center text-secondary">Belum ada produk yang disetujui.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
