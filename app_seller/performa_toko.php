<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php"); exit;
}
// Data Placeholder
$kriteria['penjualan'] = ['nilai' => 8500000, 'perbandingan' => 15.2];
$kriteria['pesanan'] = ['nilai' => 120, 'perbandingan' => 5.1];
$kriteria['tingkat_konversi'] = ['nilai' => 1.75, 'perbandingan' => 0.2];
$kriteria['pengunjung'] = ['nilai' => 6857, 'perbandingan' => 12.8];
$chart_labels = ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'];
$chart_data['penjualan'] = [150000, 200000, 180000, 500000, 1200000, 2500000, 3500000, 8500000];
$chart_data['pesanan'] = [5, 8, 7, 15, 30, 40, 80, 120];
$chart_data['pengunjung'] = [200, 250, 230, 800, 1500, 2500, 4500, 6857];
$saluran['halaman_produk'] = ['nilai' => 7500000, 'perbandingan' => 12.1];
$saluran['live'] = ['nilai' => 850000, 'perbandingan' => -5.5];
$saluran['video'] = ['nilai' => 150000, 'perbandingan' => 20.3];
$pembeli['pembeli_saat_ini_persen'] = 68;
$pembeli['total_pembeli'] = 85;
$pembeli['pembeli_baru'] = 58;
$pembeli_donut_chart = ['baru' => 58, 'berulang' => 27];
$pembeli['potensi_pembeli'] = 1205;
$pembeli['tingkat_pembeli_berulang'] = 31.7;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Performa Toko - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
    
    <style>
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .filter-bar .form-select, .filter-bar .form-control {
            width: auto;
        }
        .main-performance-tabs {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            white-space: nowrap;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php
    $current_page_full_path = 'app_seller/performa_toko.php';
    include 'partials/sidebar.php';
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
                    <h1 class="page-title">Performa Toko</h1>
                    <a href="#" class="btn btn-outline"><i class="mdi mdi-flash"></i> Data Real-Time</a>
                </div>

                <ul class="nav nav-tabs main-performance-tabs" id="performanceTab" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="tinjauan-tab" data-bs-toggle="tab" data-bs-target="#tinjauan-content" type="button" role="tab">Tinjauan</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="produk-tab" data-bs-toggle="tab" data-bs-target="#produk-content" type="button" role="tab">Produk</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="penjualan-tab" data-bs-toggle="tab" data-bs-target="#penjualan-content" type="button" role="tab">Penjualan</button></li>
                </ul>
                <div class="filter-bar">
                    <span>Periode Data:</span><input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                    <select class="form-select"><option>Status Pesanan</option><option>Pesanan Siap Dikirim</option></select>
                    <a href="#" class="btn btn-outline ms-auto"><i class="mdi mdi-download"></i> Download Data</a>
                </div>

                <div class="tab-content pt-3" id="performanceTabContent">
                    <div class="tab-pane fade show active" id="tinjauan-content" role="tabpanel">
                        <h4 class="section-title">Kriteria Utama</h4>
                        <div class="key-criteria-grid mb-4">
                            <div class="criteria-box"><div class="title">Penjualan</div><h3 class="value">Rp <?= number_format($kriteria['penjualan']['nilai']) ?></h3><div class="comparison">vs Kemarin <span class="text-success">+<?= $kriteria['penjualan']['perbandingan'] ?>%</span></div></div>
                            <div class="criteria-box"><div class="title">Pesanan</div><h3 class="value"><?= number_format($kriteria['pesanan']['nilai']) ?></h3><div class="comparison">vs Kemarin <span class="text-success">+<?= $kriteria['pesanan']['perbandingan'] ?>%</span></div></div>
                            <div class="criteria-box"><div class="title">Tingkat Konversi</div><h3 class="value"><?= $kriteria['tingkat_konversi']['nilai'] ?>%</h3><div class="comparison">vs Kemarin <span class="text-success">+<?= $kriteria['tingkat_konversi']['perbandingan'] ?>%</span></div></div>
                            <div class="criteria-box"><div class="title">Total Pengunjung</div><h3 class="value"><?= number_format($kriteria['pengunjung']['nilai']) ?></h3><div class="comparison">vs Kemarin <span class="text-success">+<?= $kriteria['pengunjung']['perbandingan'] ?>%</span></div></div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <h4 class="card-title">Grafik setiap Kriteria</h4>
                                    <div class="criteria-selection d-flex gap-3" id="chartCriteriaCheckboxes">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="penjualan" id="checkPenjualan" checked><label class="form-check-label" for="checkPenjualan">Penjualan</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="pesanan" id="checkPesanan"><label class="form-check-label" for="checkPesanan">Pesanan</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="pengunjung" id="checkPengunjung" checked><label class="form-check-label" for="checkPengunjung">Total Pengunjung</label></div>
                                    </div>
                                </div>
                                <div class="chart-container mt-3">
                                    <canvas id="mainPerformanceChart" width="400" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-7 mb-4">
                                <div class="card sales-channel-card h-100">
                                    <div class="card-body">
                                        <h4 class="card-title">Saluran Penjualan</h4>
                                        <ul class="nav nav-tabs"><li class="nav-item"><a class="nav-link active" href="#">Jenis Saluran</a></li><li class="nav-item"><a class="nav-link" href="#">Aktivitas Operasional</a></li></ul>
                                        <div class="channel-list-item"><span>Halaman Produk</span><div class="comparison">Rp <?= number_format($saluran['halaman_produk']['nilai']) ?> <span class="text-success">+<?= $saluran['halaman_produk']['perbandingan'] ?>%</span></div></div>
                                        <div class="channel-list-item"><span>Live</span><div class="comparison">Rp <?= number_format($saluran['live']['nilai']) ?> <span class="text-danger"><?= $saluran['live']['perbandingan'] ?>%</span></div></div>
                                        <div class="channel-list-item"><span>Video</span><div class="comparison">Rp <?= number_format($saluran['video']['nilai']) ?> <span class="text-success">+<?= $saluran['video']['perbandingan'] ?>%</span></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-4">
                                 <div class="card buyer-stats-card h-100">
                                     <div class="card-body">
                                         <div class="donut-wrapper"><canvas id="buyerDonutChart"></canvas><div class="donut-center-text"><div class="value"><?= $pembeli['pembeli_saat_ini_persen'] ?>%</div><div class="label">Pembeli Saat Ini</div></div></div>
                                         <div class="buyer-metrics-grid">
                                             <div class="metric-item"><span>Total Pembeli</span><p><?= number_format($pembeli['total_pembeli'])?></p></div>
                                             <div class="metric-item"><span>Pembeli Baru</span><p><?= number_format($pembeli['pembeli_baru'])?></p></div>
                                             <div class="metric-item"><span>Potensi Pembeli</span><p><?= number_format($pembeli['potensi_pembeli'])?></p></div>
                                             <div class="metric-item"><span>Tingkat Pembeli Berulang</span><p><?= $pembeli['tingkat_pembeli_berulang'] ?>%</p></div>
                                         </div>
                                     </div>
                                 </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 mb-4">
                                <div class="card ranked-list-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h4 class="card-title">Peringkat Produk (teratas)</h4><a href="#" class="text-link">Lainnya ></a></div><ul class="nav nav-pills mt-2"><li class="nav-item"><a class="nav-link active" href="#">Berdasarkan Penjualan</a></li><li class="nav-item"><a class="nav-link" href="#">Berdasarkan Produk</a></li></ul><div class="empty-state small-empty-state mt-3"><i class="mdi mdi-trophy-variant-outline"></i><p>Tidak Ada Data</p></div></div></div>
                            </div>
                             <div class="col-lg-4 mb-4">
                                 <div class="card ranked-list-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h4 class="card-title">Urutan Kategori</h4><a href="#" class="text-link">Lainnya ></a></div><ul class="nav nav-pills mt-2"><li class="nav-item"><a class="nav-link active" href="#">Peringkat</a></li></ul><div class="empty-state small-empty-state mt-3"><i class="mdi mdi-format-list-numbered"></i><p>Tidak Ada Data</p></div></div></div>
                             </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="produk-content" role="tabpanel"><div class="card"><div class="card-body">Konten untuk performa Produk akan ditampilkan di sini.</div></div></div>
                    <div class="tab-pane fade" id="penjualan-content" role="tabpanel"><div class="card"><div class="card-body">Konten untuk performa Penjualan akan ditampilkan di sini.</div></div></div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const chartLabels = <?= json_encode($chart_labels) ?>;
    const chartData = {
        penjualan: <?= json_encode($chart_data['penjualan']) ?>,
        pesanan: <?= json_encode($chart_data['pesanan']) ?>,
        pengunjung: <?= json_encode($chart_data['pengunjung']) ?>
    };
    const datasetConfigs = {
        penjualan: { label: 'Penjualan', borderColor: '#4F46E5', backgroundColor: 'rgba(79, 70, 229, 0.1)' },
        pesanan: { label: 'Pesanan', borderColor: '#F97316', backgroundColor: 'rgba(249, 115, 22, 0.1)' },
        pengunjung: { label: 'Total Pengunjung', borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)' }
    };
    const ctxMain = document.getElementById('mainPerformanceChart').getContext('2d');
    let mainPerformanceChart = new Chart(ctxMain, {
        type: 'line',
        data: { labels: chartLabels, datasets: [] },
        options: {
            responsive: true,
            tension: 0.4,
            fill: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    function updateMainChart() {
        const activeDatasets = [];
        document.querySelectorAll('#chartCriteriaCheckboxes input:checked').forEach(checkbox => {
            const key = checkbox.value;
            if (chartData[key] && datasetConfigs[key]) {
                activeDatasets.push({
                    label: datasetConfigs[key].label,
                    data: chartData[key],
                    borderColor: datasetConfigs[key].borderColor,
                    backgroundColor: datasetConfigs[key].backgroundColor,
                });
            }
        });
        mainPerformanceChart.data.datasets = activeDatasets;
        mainPerformanceChart.update();
    }
    document.querySelectorAll('#chartCriteriaCheckboxes input').forEach(checkbox => {
        checkbox.addEventListener('change', updateMainChart);
    });
    updateMainChart();
    new Chart(document.getElementById('buyerDonutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pembeli Baru', 'Pembeli Berulang'],
            datasets: [{
                data: [<?= $pembeli_donut_chart['baru'] ?>, <?= $pembeli_donut_chart['berulang'] ?>],
                backgroundColor: ['#4F46E5', '#C7D2FE'], borderWidth: 0, borderRadius: 5, cutout: '75%'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
    });
});
</script>
</body>
</html>