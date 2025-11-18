<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php"); exit;
}

// ==========================================================
// ==                  DATA PLACEHOLDER                    ==
// == Ganti semua variabel ini dengan query database Anda  ==
// ==========================================================
$status_kesehatan = "Sangat baik";
$top_summary = [
    'pesanan_terselesaikan' => 0,
    'produk_dilarang' => 0,
    'pelayanan_pembeli' => 0
];

$metrics = [
    'Pesanan Terselesaikan' => [
        ['nama' => 'Tingkat Pesanan Tidak Terselesaikan', 'sekarang' => '0.00%', 'target' => '<10.00%', 'sebelumnya' => '0.00%'],
        ['nama' => 'Tingkat Keterlambatan Pengiriman Pesanan', 'sekarang' => '0.00%', 'target' => '<10.00%', 'sebelumnya' => '0.00%'],
        ['nama' => 'Masa Pengemasan', 'sekarang' => '0.00 hari', 'target' => '<2.00 hari', 'sebelumnya' => '0.00 hari'],
    ],
    'Produk yang Dilarang' => [
        ['nama' => 'Pelanggaran Produk Berat', 'sekarang' => 0, 'target' => 0, 'sebelumnya' => 0],
        ['nama' => 'Produk Pre-order', 'sekarang' => '0.00%', 'target' => '<20.00%', 'sebelumnya' => '0.00%'],
    ],
    'Pelayanan Pembeli' => [
        ['nama' => 'Persentase Chat Dibalas', 'sekarang' => '0.00%', 'target' => '≥70.00%', 'sebelumnya' => '0.00%'],
    ]
];

$poin_penalti_kuartal_ini = 0;
$pelanggaran_penalti = [
    'Pesanan Tidak Terpenuhi' => 0,
    'Pengiriman Terlambat' => 0,
    'Produk yang Dilarang' => 0,
    'Pelanggaran Lainnya' => 0,
];

$masalah_perlu_diselesaikan = [
    'produk_bermasalah' => 0,
    'keterlambatan_pengiriman' => 0,
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kesehatan Toko - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
</head>
<body>
<div class="container-scroller">
    <?php
    $current_page_full_path = 'app_seller/kesehatan_toko.php';
    include 'partials/sidebar.php';
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Kesehatan Toko</h1></div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="page-status-header">
                            <h2 class="status-badge"><?= $status_kesehatan ?></h2>
                            <p>Semua metrik mencapai target. Pertahankan performa baik Anda untuk menarik lebih banyak Pembeli!</p>
                            <div class="top-metrics">
                                <div class="metric-item"><span>Pesanan Terselesaikan</span><p><?= $top_summary['pesanan_terselesaikan'] ?> metrik gagal</p></div>
                                <div class="metric-item"><span>Produk yang Dilarang</span><p><?= $top_summary['produk_dilarang'] ?> metrik gagal</p></div>
                                <div class="metric-item"><span>Pelayanan Pembeli</span><p><?= $top_summary['pelayanan_pembeli'] ?> metrik gagal</p></div>
                            </div>
                        </div>

                        <div class="card metric-table">
                            <div class="metric-table-header">
                                <div>Statistik</div><div>Periode Sekarang</div><div>Target</div><div>Periode Sebelumnya</div><div>Aksi</div>
                            </div>
                            <?php foreach ($metrics as $category => $items): ?>
                                <h4 class="metric-category-title"><?= $category ?></h4>
                                <?php foreach ($items as $item): ?>
                                <div class="metric-row">
                                    <div class="metric-name"><?= $item['nama'] ?></div>
                                    <div><?= $item['sekarang'] ?></div>
                                    <div><?= $item['target'] ?></div>
                                    <div><?= $item['sebelumnya'] ?></div>
                                    <div><a href="#" class="text-link">Lihat Rincian</a></div>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Star</h4>
                                <div class="text-center">
                                    <i class="mdi mdi-shield-star-outline" style="font-size: 4rem; color: #FACC15;"></i>
                                    <p class="mt-2">Capai 3 kriteria untuk menjadi Penjual Star</p>
                                    <a href="#" class="text-link">Lihat Rincian ></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="card">
                             <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center"><h4 class="card-title">Penalti Saya</h4><a href="#" class="text-link">Lainnya ></a></div>
                                <div class="penalty-card mt-3">
                                    <div>
                                        <h5>Poin Penalti Kuartal Ini</h5>
                                        <p class="points-display"><?= $poin_penalti_kuartal_ini ?> poin</p>
                                        <ul>
                                            <?php foreach($pelanggaran_penalti as $pelanggaran => $poin): ?>
                                            <li><span><?= $pelanggaran ?></span> <span><?= $poin ?> poin penalti</span></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div>
                                        <h5>Penalti Berjalan</h5>
                                        <div class="empty-state small-empty-state"><i class="mdi mdi-gavel"></i><p>Hebat! Kamu tidak memiliki penalti yang sedang berlangsung.</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                 <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="card">
                             <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center"><h4 class="card-title">Masalah Perlu Diselesaikan</h4><a href="#" class="text-link">Lainnya ></a></div>
                                <p class="card-subtitle">Ada <?= count(array_filter($masalah_perlu_diselesaikan)) ?> masalah yang dapat diselesaikan sekarang.</p>
                                <div class="issues-card mt-3">
                                    <div>
                                        <p class="text-secondary">Produk bermasalah</p>
                                        <p class="value"><?= $masalah_perlu_diselesaikan['produk_bermasalah'] ?></p>
                                    </div>
                                    <div>
                                        <p class="text-secondary">Keterlambatan pengiriman</p>
                                        <p class="value"><?= $masalah_perlu_diselesaikan['keterlambatan_pengiriman'] ?></p>
                                    </div>
                                </div>
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