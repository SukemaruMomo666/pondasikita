<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

$tugas['toko_pending'] = $koneksi->query("SELECT COUNT(id) as total FROM tb_toko WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$tugas['produk_pending'] = $koneksi->query("SELECT COUNT(id) as total FROM tb_barang WHERE status_moderasi = 'pending'")->fetch_assoc()['total'] ?? 0;
$tugas['payout_pending'] = $koneksi->query("SELECT COUNT(id) as total FROM tb_payouts WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

$statistik['total_penjualan'] = $koneksi->query("SELECT SUM(total_final) as total FROM tb_transaksi WHERE status_pembayaran = 'paid'")->fetch_assoc()['total'] ?? 0;
$statistik['total_pengguna'] = $koneksi->query("SELECT COUNT(id) as total FROM tb_user WHERE level != 'bot'")->fetch_assoc()['total'] ?? 0;
$statistik['total_toko'] = $koneksi->query("SELECT COUNT(id) as total FROM tb_toko WHERE status = 'active'")->fetch_assoc()['total'] ?? 0;
$statistik['total_produk'] = $koneksi->query("SELECT COUNT(id) as total FROM tb_barang WHERE status_moderasi = 'approved'")->fetch_assoc()['total'] ?? 0;

$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sql_chart = "SELECT COUNT(id) as total FROM tb_user WHERE DATE(created_at) = '$date'";
    $count = $koneksi->query($sql_chart)->fetch_assoc()['total'] ?? 0;
    $chart_data['labels'][] = date('d M', strtotime($date));
    $chart_data['values'][] = $count;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<style>
    /* Layout kotak tugas admin */
.admin-card-grid {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.admin-card {
    flex: 1;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    text-align: center;
    transition: transform 0.2s ease;
}
.admin-card:hover {
    transform: translateY(-5px);
}
.admin-card .icon {
    font-size: 36px;
    margin-bottom: 10px;
}
.admin-card p {
    margin: 0;
    font-weight: 500;
}
.admin-card h3 {
    margin: 10px 0;
    font-size: 28px;
}
.admin-card a {
    text-decoration: none;
    color: #3498db;
    font-weight: 500;
}

.warning .icon { color: #f39c12; }
.info .icon { color: #3498db; }
.success .icon { color: #2ecc71; }

/* Statistik grid */
.statistik-grid {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}
.statistik-card {
    flex: 1;
    background:rgb(247, 247, 247);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.statistik-card p {
    margin: 0;
    font-size: 14px;
    color: #777;
}
.statistik-card h3 {
    margin-top: 10px;
    font-size: 26px;
    font-weight: bold;
}

/* Section title */
.section-title {
    font-size: 20px;
    margin: 20px 0 10px;
    font-weight: 600;
    
}

/* Chart container (responsive) */
.chart-container {
    width: 100%;
    position: relative;
}

</style>
 <?php 
$current_page_name = 'dashboard_admin.php';
$current_page_title = 'Dashboard Admin';
?>

<div class="container-scroller">

    <?php include 'partials/sidebar_admin.php'; ?>

    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">

                <!-- Navbar di dalam content-wrapper, setelah sidebar -->
                <?php include 'partials/navbar_admin.php'; ?>


                <!-- Tugas Admin -->
                <h4 class="section-title">Perlu Tindakan</h4>
                <div class="admin-card-grid">
                    <div class="admin-card warning">
                        <i class="mdi mdi-store icon"></i>
                        <p>Toko Perlu Verifikasi</p>
                        <h3><?= $tugas['toko_pending'] ?></h3>
                        <a href="kelola_toko.php?status=pending">Lihat</a>
                    </div>
                    <div class="admin-card info">
                        <i class="mdi mdi-cube-send icon"></i>
                        <p>Produk Perlu Moderasi</p>
                        <h3><?= $tugas['produk_pending'] ?></h3>
                        <a href="moderasi_produk.php?status=pending">Lihat</a>
                    </div>
                    <div class="admin-card success">
                        <i class="mdi mdi-currency-usd icon"></i>
                        <p>Payout Perlu Diproses</p>
                        <h3><?= $tugas['payout_pending'] ?></h3>
                        <a href="kelola_payout.php?status=pending">Lihat</a>
                    </div>
                </div>

                <!-- Statistik Utama -->
                <h4 class="section-title mt-4">Statistik Platform</h4>
                <div class="statistik-grid">
                    <div class="statistik-card">
                        <p>Total Penjualan</p>
                        <h3>Rp <?= number_format($statistik['total_penjualan']) ?></h3>
                    </div>
                    <div class="statistik-card">
                        <p>Total Pengguna</p>
                        <h3><?= number_format($statistik['total_pengguna']) ?></h3>
                    </div>
                    <div class="statistik-card">
                        <p>Total Toko Aktif</p>
                        <h3><?= number_format($statistik['total_toko']) ?></h3>
                    </div>
                    <div class="statistik-card">
                        <p>Total Produk Aktif</p>
                        <h3><?= number_format($statistik['total_produk']) ?></h3>
                    </div>
                </div>

                <!-- Grafik -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h4 class="card-title">Pertumbuhan Pengguna Baru (7 Hari Terakhir)</h4>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_data['labels']) ?>,
            datasets: [{
                label: 'Pengguna Baru',
                data: <?= json_encode($chart_data['values']) ?>,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
</body>
</html>
