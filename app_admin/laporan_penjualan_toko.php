<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- QUERY UNTUK MENGAMBIL TOP 10 TOKO ---
// Query ini menghitung total penjualan dari tb_detail_transaksi
// dan mengelompokkannya berdasarkan toko.
$sql = "SELECT 
            t.id as toko_id,
            t.nama_toko,
            COUNT(DISTINCT dt.transaksi_id) AS jumlah_pesanan,
            SUM(dt.subtotal) AS total_penjualan
        FROM 
            tb_detail_transaksi dt
        JOIN 
            tb_toko t ON dt.toko_id = t.id
        GROUP BY 
            t.id, t.nama_toko
        ORDER BY 
            total_penjualan DESC
        LIMIT 10";

$result_toko = $koneksi->query($sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan Toko - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'laporan_penjualan_toko.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Laporan Penjualan Toko</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Peringkat 10 Toko dengan Penjualan Terbesar</h4>
                        <p class="card-subtitle text-secondary">
                            Menampilkan toko berdasarkan total pendapatan kotor dari semua pesanan yang masuk.
                        </p>

                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Peringkat</th>
                                        <th>Nama Toko</th>
                                        <th>Jumlah Pesanan</th>
                                        <th>Total Penjualan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_toko && $result_toko->num_rows > 0): ?>
                                        <?php $rank = 1; ?>
                                        <?php while($toko = $result_toko->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <h3 class="font-weight-bold"><?= $rank++ ?></h3>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($toko['nama_toko']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= number_format($toko['jumlah_pesanan']) ?> Pesanan
                                                </td>
                                                <td>
                                                    <strong>Rp <?= number_format($toko['total_penjualan'], 0, ',', '.') ?></strong>
                                                </td>
                                                <td>
                                                    <!-- Link ini akan mengarah ke detail transaksi per toko -->
                                                    <a href="laporan_transaksi.php?toko_id=<?= $toko['toko_id'] ?>" class="btn btn-outline btn-sm">Lihat Transaksi</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="text-center p-5">
                                                    <i class="mdi mdi-chart-bar-stacked" style="font-size: 3rem; color: #ccc;"></i>
                                                    <p class="mt-2 text-secondary">Belum ada data penjualan untuk ditampilkan.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
