<?php
include '../../../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// [ ... KODE PHP ANDA UNTUK MENGAMBIL DATA ... ]
// (Biarkan bagian ini sama persis seperti sebelumnya)
// Total Produk Aktif
$q1 = $koneksi->query("SELECT COUNT(*) as total FROM tb_barang WHERE is_active = 1");
if (!$q1) die("Query Total Produk error: " . $koneksi->error);
$total_produk = $q1->fetch_assoc()['total'] ?? 0;

// Total Penjualan
$q2 = $koneksi->query("SELECT SUM(total_harga) as total FROM tb_transaksi WHERE status_pembayaran = 'Paid'");
if (!$q2) die("Query Total Penjualan error: " . $koneksi->error);
$total_penjualan = $q2->fetch_assoc()['total'] ?? 0;

// Total Pelanggan
$q3 = $koneksi->query("SELECT COUNT(*) as total FROM tb_user");
if (!$q3) die("Query Total Pelanggan error: " . $koneksi->error);
$total_pelanggan = $q3->fetch_assoc()['total'] ?? 0;

// Transaksi Berhasil
$q4 = $koneksi->query("SELECT COUNT(*) as total FROM tb_transaksi WHERE status_pembayaran = 'Paid'");
if (!$q4) die("Query Total Transaksi error: " . $koneksi->error);
$total_transaksi = $q4->fetch_assoc()['total'] ?? 0;

// Metode Pembayaran
$metode_query = $koneksi->query("SELECT metode_pembayaran, COUNT(*) as jumlah FROM tb_transaksi GROUP BY metode_pembayaran");
$metode_data = [];
if ($metode_query) {
    while ($row = $metode_query->fetch_assoc()) {
        $metode_data[$row['metode_pembayaran']] = (int)$row['jumlah'];
    }
} else {
    die("Query Metode Pembayaran error: " . $koneksi->error);
}
$metode_data = $metode_data ?: ["Tidak Ada Data" => 0];

// Grafik Penjualan
$tahun_sekarang = date('Y');
$tahun_lalu = $tahun_sekarang - 1;
$penjualan_tahun_ini = array_fill(1, 12, 0);
$penjualan_tahun_lalu = array_fill(1, 12, 0);
$q_penjualan_ini = $koneksi->query("
    SELECT MONTH(tanggal_transaksi) AS bulan, SUM(total_harga) AS total
    FROM tb_transaksi
    WHERE YEAR(tanggal_transaksi) = '$tahun_sekarang' AND status_pembayaran = 'Paid'
    GROUP BY MONTH(tanggal_transaksi)
");
if ($q_penjualan_ini) {
    while ($row = $q_penjualan_ini->fetch_assoc()) {
        $penjualan_tahun_ini[(int)$row['bulan']] = (float)$row['total'];
    }
}
$q_penjualan_lalu = $koneksi->query("
    SELECT MONTH(tanggal_transaksi) AS bulan, SUM(total_harga) AS total
    FROM tb_transaksi
    WHERE YEAR(tanggal_transaksi) = '$tahun_lalu' AND status_pembayaran = 'Paid'
    GROUP BY MONTH(tanggal_transaksi)
");
if ($q_penjualan_lalu) {
    while ($row = $q_penjualan_lalu->fetch_assoc()) {
        $penjualan_tahun_lalu[(int)$row['bulan']] = (float)$row['total'];
    }
}
$labels_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Laporan Penjualan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../../assets/css/styles.css">
    <link rel="stylesheet" href="../../../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../../assets/css/vendor.bundle.base.css">
    <link rel="shortcut icon" href="../../../assets/template/spica/template/images/favicon.png" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  </head>
<body>

<div class="container-scroller">
    <?php include('partials/sidebar.php'); ?> 
    
    <div class="page-body-wrapper">
        
        <div class="main-panel">
            
            <?php include('partials/navbar.php'); ?>

            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">
                        <span class="page-title-icon">
                            <i class="mdi mdi-home"></i>
                        </span> 
                        Dashboard Laporan Penjualan
                    </h3>
                </div>

                <div class="card dashboard-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Grafik Penjualan Bulanan</h5>
                        <div id="penjualanBulananChart-container">
                            <canvas id="penjualanBulananChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <div class="text-primary"><i class="mdi mdi-cube-outline"></i></div>
                                <h5>Total Produk Aktif</h5>
                                <h3><?= number_format($total_produk) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <div class="text-success"><i class="mdi mdi-cash-multiple"></i></div>
                                <h5>Total Penjualan</h5>
                                <h3>Rp <?= number_format($total_penjualan, 0, ',', '.') ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <div class="text-info"><i class="mdi mdi-account-group"></i></div>
                                <h5>Total Pelanggan</h5>
                                <h3><?= number_format($total_pelanggan) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card dashboard-card text-center">
                            <div class="card-body">
                                <div class="text-warning"><i class="mdi mdi-receipt"></i></div>
                                <h5>Transaksi Berhasil</h5>
                                <h3><?= number_format($total_transaksi) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Distribusi Metode Pembayaran</h5>
                        <div class="chart-container">
                            <canvas id="pembayaranChart"></canvas>
                        </div>
                    </div>
                </div>

                <footer class="footer mt-auto py-3">
                    <div class="d-sm-flex justify-content-center justify-content-sm-between">
                        <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
                            Copyright © <?= date('Y') ?> Toko Bangunan Tiga Daya. All rights reserved.
                        </span>
                    </div>
                </footer>
            </div> </div> </div> </div> <script src="../../../assets/template/spica/template/vendors/js/vendor.bundle.base.js"></script>
<script src="../../../assets/template/spica/template/js/off-canvas.js"></script>
<script src="../../../assets/template/spica/template/js/hoverable-collapse.js"></script>

<script>
    const dataPembayaran = {
        labels: <?= json_encode(array_keys($metode_data)) ?>,
        datasets: [{
            label: "Jumlah Transaksi",
            data: <?= json_encode(array_values($metode_data)) ?>,
            backgroundColor: [
                "rgba(94, 25, 20, 0.7)", "rgba(255, 127, 80, 0.7)", "rgba(70, 130, 180, 0.7)",
                "rgba(34, 139, 34, 0.7)", "rgba(147, 112, 219, 0.7)", "rgba(255, 99, 132, 0.7)",
                "rgba(75, 192, 192, 0.7)", "rgba(255, 205, 86, 0.7)"
            ],
            borderColor: [
                "rgba(94, 25, 20, 1)", "rgba(255, 127, 80, 1)", "rgba(70, 130, 180, 1)",
                "rgba(34, 139, 34, 1)", "rgba(147, 112, 219, 1)", "rgba(255, 99, 132, 1)",
                "rgba(75, 192, 192, 1)", "rgba(255, 205, 86, 1)"
            ],
            borderWidth: 1
        }]
    };
    new Chart(document.getElementById("pembayaranChart"), {
        type: "doughnut",
        data: dataPembayaran,
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "top" }, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed} transaksi` } } } }
    });

    const dataPenjualanBulanan = {
        labels: <?= json_encode($labels_bulan) ?>,
        datasets: [ { label: "Penjualan <?= $tahun_sekarang ?>", data: <?= json_encode(array_values($penjualan_tahun_ini)) ?>, backgroundColor: "rgba(94,25,20,0.4)", borderColor: "rgba(94,25,20,1)", tension: 0.4, fill: true }, { label: "Penjualan <?= $tahun_lalu ?>", data: <?= json_encode(array_values($penjualan_tahun_lalu)) ?>, backgroundColor: "rgba(255,127,80,0.4)", borderColor: "rgba(255,127,80,1)", tension: 0.4, fill: true } ]
    };
    new Chart(document.getElementById("penjualanBulananChart"), {
        type: "line",
        data: dataPenjualanBulanan,
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "top" }, tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: Rp ${new Intl.NumberFormat('id-ID').format(ctx.parsed.y)}` } } }, scales: { x: { title: { display: true, text: "Bulan" } }, y: { beginAtZero: true, title: { display: true, text: "Penjualan (Rp)" }, ticks: { callback: value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value) } } } }
    });
</script>
</body>
</html>