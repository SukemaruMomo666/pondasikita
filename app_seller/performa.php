<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); exit;
}
$user_id = $_SESSION['user_id'];
// ... (kode untuk mengambil $toko_id dari $user_id) ...
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
if (!$toko_id) die("Toko tidak valid.");

// --- Logika Filter Tanggal ---
// Set default 30 hari terakhir jika tidak ada filter
$tanggal_mulai = $_GET['mulai'] ?? date('Y-m-d', strtotime('-29 days'));
$tanggal_berakhir = $_GET['akhir'] ?? date('Y-m-d');
// Tambahkan waktu agar mencakup keseluruhan hari terakhir
$tanggal_berakhir_sql = $tanggal_berakhir . ' 23:59:59';

// --- Query Data Berdasarkan Rentang Tanggal ---

// 1. KPI Cards
$sql_kpi = "SELECT 
                SUM(d.subtotal) as pendapatan,
                COUNT(DISTINCT d.transaksi_id) as jumlah_pesanan,
                SUM(d.jumlah) as produk_terjual
            FROM tb_detail_transaksi d
            JOIN tb_transaksi t ON d.transaksi_id = t.id
            WHERE d.toko_id = ? AND t.tanggal_transaksi BETWEEN ? AND ?";
$stmt_kpi = $koneksi->prepare($sql_kpi);
$stmt_kpi->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_berakhir_sql);
$stmt_kpi->execute();
$kpi = $stmt_kpi->get_result()->fetch_assoc();

// 2. Data untuk Grafik Tren Penjualan Harian
$sql_grafik = "SELECT 
                   DATE(t.tanggal_transaksi) as tanggal, 
                   SUM(d.subtotal) as total_harian
               FROM tb_detail_transaksi d
               JOIN tb_transaksi t ON d.transaksi_id = t.id
               WHERE d.toko_id = ? AND t.tanggal_transaksi BETWEEN ? AND ?
               GROUP BY DATE(t.tanggal_transaksi)
               ORDER BY tanggal ASC";
$stmt_grafik = $koneksi->prepare($sql_grafik);
$stmt_grafik->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_berakhir_sql);
$stmt_grafik->execute();
$result_grafik = $stmt_grafik->get_result();
$data_grafik = [];
while($row = $result_grafik->fetch_assoc()){
    $data_grafik['labels'][] = date('d M', strtotime($row['tanggal']));
    $data_grafik['data'][] = $row['total_harian'];
}

// 3. Data untuk Tabel Produk Terlaris
$sql_top_produk = "SELECT b.nama_barang, SUM(d.jumlah) as total_terjual
                   FROM tb_detail_transaksi d
                   JOIN tb_barang b ON d.barang_id = b.id
                   JOIN tb_transaksi t ON d.transaksi_id = t.id
                   WHERE d.toko_id = ? AND t.tanggal_transaksi BETWEEN ? AND ?
                   GROUP BY d.barang_id
                   ORDER BY total_terjual DESC LIMIT 5";
$stmt_top_produk = $koneksi->prepare($sql_top_produk);
$stmt_top_produk->bind_param("iss", $toko_id, $tanggal_mulai, $tanggal_berakhir_sql);
$stmt_top_produk->execute();
$result_top_produk = $stmt_top_produk->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Performa Toko - Seller Center</title>
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/template/spica/template/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"><i class="mdi mdi-chart-line"></i> Performa Toko</h3>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form class="form-inline">
                            <div class="form-group mr-3">
                                <label class="mr-2">Dari Tanggal:</label>
                                <input type="date" name="mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                            </div>
                            <div class="form-group mr-3">
                                <label class="mr-2">Sampai Tanggal:</label>
                                <input type="date" name="akhir" class="form-control" value="<?= htmlspecialchars($tanggal_berakhir) ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Terapkan</button>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 stretch-card grid-margin"><div class="card bg-gradient-success text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Pendapatan Kotor</h4>
                        <h2 class="mb-5">Rp <?= number_format($kpi['pendapatan'] ?? 0) ?></h2>
                    </div></div></div>
                    <div class="col-md-4 stretch-card grid-margin"><div class="card bg-gradient-info text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Jumlah Pesanan</h4>
                        <h2 class="mb-5"><?= number_format($kpi['jumlah_pesanan'] ?? 0) ?></h2>
                    </div></div></div>
                    <div class="col-md-4 stretch-card grid-margin"><div class="card bg-gradient-danger text-white"><div class="card-body">
                        <h4 class="font-weight-normal mb-3">Produk Terjual</h4>
                        <h2 class="mb-5"><?= number_format($kpi['produk_terjual'] ?? 0) ?></h2>
                    </div></div></div>
                </div>

                <div class="row">
                    <div class="col-lg-7 grid-margin stretch-card"><div class="card"><div class="card-body">
                        <h4 class="card-title">Tren Penjualan</h4>
                        <canvas id="salesTrendChart"></canvas>
                    </div></div></div>
                    <div class="col-lg-5 grid-margin stretch-card"><div class="card"><div class="card-body">
                        <h4 class="card-title">Produk Terlaris</h4>
                        <table class="table">
                            <thead><tr><th>Produk</th><th>Terjual</th></tr></thead>
                            <tbody>
                                <?php while($p = $result_top_produk->fetch_assoc()): ?>
                                <tr><td><?= htmlspecialchars($p['nama_barang']) ?></td><td><?= $p['total_terjual'] ?></td></tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div></div></div>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
// Grafik Tren Penjualan
new Chart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($data_grafik['labels'] ?? []) ?>,
        datasets: [{
            label: 'Pendapatan Harian',
            data: <?= json_encode($data_grafik['data'] ?? []) ?>,
            borderColor: 'rgba(79, 70, 229, 1)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.1
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>