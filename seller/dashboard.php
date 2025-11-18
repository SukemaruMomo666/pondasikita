<?php
session_start();
require_once '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// --- PENGAMANAN HALAMAN & PENGAMBILAN DATA TOKO ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php?error=not_seller"); // Arahkan ke login seller
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'] ?? 0;
$stmt_toko->close();

if ($toko_id === 0) {
    die("Data toko tidak ditemukan untuk pengguna ini. Silakan hubungi admin.");
}

// --- PENGAMBILAN DATA KHUSUS TOKO INI ---

// 1. Total Penjualan Toko (dari item yang terjual)
$q_penjualan = $koneksi->prepare("SELECT SUM(subtotal) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_penjualan->bind_param("i", $toko_id); $q_penjualan->execute();
$total_penjualan = $q_penjualan->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Pesanan Diterima Toko
$q_pesanan = $koneksi->prepare("SELECT COUNT(DISTINCT transaksi_id) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_pesanan->bind_param("i", $toko_id); $q_pesanan->execute();
$total_pesanan = $q_pesanan->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Total Item Terjual oleh Toko
$q_item = $koneksi->prepare("SELECT SUM(jumlah) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_item->bind_param("i", $toko_id); $q_item->execute();
$total_item_terjual = $q_item->get_result()->fetch_assoc()['total'] ?? 0;

// 4. Total Produk Aktif Milik Toko
$q_produk = $koneksi->prepare("SELECT COUNT(*) as total FROM tb_barang WHERE is_active = 1 AND toko_id = ?");
$q_produk->bind_param("i", $toko_id); $q_produk->execute();
$total_produk_aktif = $q_produk->get_result()->fetch_assoc()['total'] ?? 0;


// 5. Grafik Penjualan Bulanan Toko
$tahun_sekarang = date('Y');
$penjualan_tahunan = array_fill(1, 12, 0);
$q_grafik = $koneksi->prepare("
    SELECT MONTH(t.tanggal_transaksi) AS bulan, SUM(d.subtotal) AS total
    FROM tb_detail_transaksi d JOIN tb_transaksi t ON d.transaksi_id = t.id
    WHERE d.toko_id = ? AND YEAR(t.tanggal_transaksi) = ?
    GROUP BY MONTH(t.tanggal_transaksi)
");
$q_grafik->bind_param("is", $toko_id, $tahun_sekarang); $q_grafik->execute();
$result_grafik = $q_grafik->get_result();
while ($row = $result_grafik->fetch_assoc()) {
    $penjualan_tahunan[(int)$row['bulan']] = (float)$row['total'];
}
$labels_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

// 6. Top 5 Produk Terlaris Milik Toko
$q_top_produk = $koneksi->prepare("
    SELECT b.nama_barang, SUM(d.jumlah) as total_terjual
    FROM tb_detail_transaksi d JOIN tb_barang b ON d.barang_id = b.id
    WHERE d.toko_id = ? GROUP BY d.barang_id ORDER BY total_terjual DESC LIMIT 5
");
$q_top_produk->bind_param("i", $toko_id); $q_top_produk->execute();
$result_top_produk = $q_top_produk->get_result();
$top_produk_data = [];
while ($row = $result_top_produk->fetch_assoc()) {
    $top_produk_data[$row['nama_barang']] = (int)$row['total_terjual'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">
    <title>Nama Halaman - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">

</head>
<body>
<div class="container-scroller">
    
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">
                        <span class="page-title-icon bg-gradient-primary text-white mr-2"><i class="mdi mdi-store"></i></span> 
                        Dashboard Toko Anda
                    </h3>
                </div>

                <div class="row">
                    <div class="col-md-3 stretch-card grid-margin"><div class="card bg-gradient-success card-img-holder text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Total Penjualan <i class="mdi mdi-chart-line mdi-24px float-right"></i></h4>
                        <h2 class="mb-5">Rp <?= number_format($total_penjualan, 0, ',', '.') ?></h2>
                    </div></div></div>
                    <div class="col-md-3 stretch-card grid-margin"><div class="card bg-gradient-info card-img-holder text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Pesanan Diterima <i class="mdi mdi-receipt mdi-24px float-right"></i></h4>
                        <h2 class="mb-5"><?= number_format($total_pesanan) ?></h2>
                    </div></div></div>
                    <div class="col-md-3 stretch-card grid-margin"><div class="card bg-gradient-danger card-img-holder text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Produk Terjual <i class="mdi mdi-cart-outline mdi-24px float-right"></i></h4>
                        <h2 class="mb-5"><?= number_format($total_item_terjual) ?></h2>
                    </div></div></div>
                    <div class="col-md-3 stretch-card grid-margin"><div class="card bg-gradient-primary card-img-holder text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Produk Aktif <i class="mdi mdi-cube-unfolded mdi-24px float-right"></i></h4>
                        <h2 class="mb-5"><?= number_format($total_produk_aktif) ?></h2>
                    </div></div></div>
                </div>
                
                <div class="row">
                    <div class="col-md-7 grid-margin stretch-card"><div class="card"><div class="card-body">
                        <h4 class="card-title">Grafik Pendapatan Tahun <?= $tahun_sekarang ?></h4>
                        <canvas id="penjualanBulananChart"></canvas>
                    </div></div></div>
                    <div class="col-md-5 grid-margin stretch-card"><div class="card"><div class="card-body">
                        <h4 class="card-title">Top 5 Produk Terlaris</h4>
                        <canvas id="topProdukChart"></canvas>
                    </div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Grafik Penjualan (Line Chart)
new Chart(document.getElementById("penjualanBulananChart"), {
    type: "line",
    data: {
        labels: <?= json_encode($labels_bulan) ?>,
        datasets: [{
            label: "Pendapatan <?= $tahun_sekarang ?>",
            data: <?= json_encode(array_values($penjualan_tahunan)) ?>,
            backgroundColor: "rgba(79, 70, 229, 0.2)",
            borderColor: "rgba(79, 70, 229, 1)",
            tension: 0.4,
            fill: true
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Grafik Top Produk (Bar Chart)
new Chart(document.getElementById("topProdukChart"), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($top_produk_data)) ?>,
        datasets: [{
            label: 'Jumlah Terjual',
            data: <?= json_encode(array_values($top_produk_data)) ?>,
            backgroundColor: [
                'rgba(239, 68, 68, 0.7)', 'rgba(249, 115, 22, 0.7)', 'rgba(245, 158, 11, 0.7)',
                'rgba(132, 204, 22, 0.7)', 'rgba(34, 197, 94, 0.7)'
            ]
        }]
    },
    options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
});
</script>
</body>
</html>