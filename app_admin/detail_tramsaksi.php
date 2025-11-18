<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- VALIDASI & AMBIL DATA TRANSAKSI ---
if (!isset($_GET['id'])) {
    die("ID Transaksi tidak valid.");
}
$transaksi_id = (int)$_GET['id'];

// Query untuk detail transaksi utama
$sql_trx = "SELECT t.*, u.nama as nama_pelanggan, u.email as email_pelanggan, u.no_telepon as telepon_pelanggan
            FROM tb_transaksi t 
            JOIN tb_user u ON t.user_id = u.id 
            WHERE t.id = ?";
$stmt_trx = $koneksi->prepare($sql_trx);
$stmt_trx->bind_param("i", $transaksi_id);
$stmt_trx->execute();
$result_trx = $stmt_trx->get_result();
if ($result_trx->num_rows === 0) {
    die("Transaksi tidak ditemukan.");
}
$trx = $result_trx->fetch_assoc();

// Query untuk detail item/produk dalam transaksi
$sql_items = "SELECT dt.*, b.gambar_utama, t.nama_toko
              FROM tb_detail_transaksi dt
              JOIN tb_barang b ON dt.barang_id = b.id
              JOIN tb_toko t ON dt.toko_id = t.id
              WHERE dt.transaksi_id = ?";
$stmt_items = $koneksi->prepare($sql_items);
$stmt_items->bind_param("i", $transaksi_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'laporan_transaksi.php'; // Anggap ini bagian dari laporan transaksi
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Detail Transaksi</h1>
                        <p class="text-secondary">Invoice: #<?= htmlspecialchars($trx['kode_invoice']) ?></p>
                    </div>
                </div>

                <div class="row">
                    <!-- Kolom Kiri: Info Pengiriman & Status -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Status Pesanan</h4>
                                <ul class="timeline mt-3">
                                    <li class="timeline-item completed">
                                        <div class="timeline-icon"></div>
                                        <div class="timeline-content">
                                            <p class="status">Pesanan Dibuat</p>
                                            <p class="timestamp"><?= date('d M Y, H:i', strtotime($trx['tanggal_transaksi'])) ?></p>
                                        </div>
                                    </li>
                                    <li class="timeline-item <?= $trx['status_pembayaran'] == 'paid' ? 'completed' : '' ?>">
                                        <div class="timeline-icon"></div>
                                        <div class="timeline-content">
                                            <p class="status">Pembayaran Diterima</p>
                                            <p class="timestamp"><?= $trx['status_pembayaran'] == 'paid' ? 'Pembayaran berhasil via ' . ucfirst($trx['metode_pembayaran']) : 'Menunggu pembayaran' ?></p>
                                        </div>
                                    </li>
                                    <li class="timeline-item <?= $trx['status_pesanan_global'] == 'selesai' ? 'completed' : '' ?>">
                                        <div class="timeline-icon"></div>
                                        <div class="timeline-content">
                                            <p class="status">Pesanan Selesai</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Info Pelanggan & Pengiriman</h4>
                                <ul class="info-list mt-3">
                                    <li><span class="label">Nama Pelanggan</span><span class="value"><?= htmlspecialchars($trx['nama_pelanggan']) ?></span></li>
                                    <li><span class="label">Email</span><span class="value"><?= htmlspecialchars($trx['email_pelanggan']) ?></span></li>
                                    <li><span class="label">Telepon</span><span class="value"><?= htmlspecialchars($trx['telepon_pelanggan'] ?? '-') ?></span></li>
                                    <li><span class="label">Alamat Pengiriman</span><span class="value" style="white-space: pre-wrap;"><?= htmlspecialchars($trx['alamat_pengiriman']) ?></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Rincian Produk & Pembayaran -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Rincian Pesanan</h4>
                                <ul class="order-item-list mt-3">
                                    <?php while($item = $result_items->fetch_assoc()): ?>
                                    <li class="order-item">
                                        <img src="../assets/uploads/products/<?= htmlspecialchars($item['gambar_utama'] ?? 'default.jpg') ?>" alt="Produk">
                                        <div class="item-details">
                                            <p class="item-name"><?= htmlspecialchars($item['nama_barang_saat_transaksi']) ?></p>
                                            <p class="item-meta"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga_saat_transaksi'], 0, ',', '.') ?></p>
                                            <p class="item-meta">Dijual oleh: <strong><?= htmlspecialchars($item['nama_toko']) ?></strong></p>
                                        </div>
                                        <div class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                                <ul class="payment-summary">
                                    <li><span>Subtotal Produk</span><span>Rp <?= number_format($trx['total_harga_produk'], 0, ',', '.') ?></span></li>
                                    <li><span>Diskon</span><span>- Rp <?= number_format($trx['total_diskon'], 0, ',', '.') ?></span></li>
                                    <!-- Logika ongkir bisa ditambahkan di sini jika ada -->
                                    <li class="total"><span>Total Pembayaran</span><span>Rp <?= number_format($trx['total_final'], 0, ',', '.') ?></span></li>
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
