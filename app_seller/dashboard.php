<?php
session_start(); // HANYA Panggil SEKALI di awal file

require_once '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');
function getCurrentRelativePath() {
    $base_dir = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $full_path = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
    $relative_path = str_replace($base_dir, '', $full_path);
    if (substr($relative_path, 0, 1) === '/') {
        $relative_path = substr($relative_path, 1);
    }
    return $relative_path;
}

$current_page_full_path = getCurrentRelativePath(); // Ini yang digunakan di sidebar
// --- PENGAMANAN HALAMAN & PENGAMBILAN DATA TOKO ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php?error=not_seller");
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

// --- PENGAMBILAN DATA ---
// 1. Total Penjualan
$q_penjualan = $koneksi->prepare("SELECT SUM(subtotal) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_penjualan->bind_param("i", $toko_id); $q_penjualan->execute();
$total_penjualan = $q_penjualan->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Pesanan Diterima
$q_pesanan = $koneksi->prepare("SELECT COUNT(DISTINCT transaksi_id) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_pesanan->bind_param("i", $toko_id); $q_pesanan->execute();
$total_pesanan = $q_pesanan->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Item Terjual
$q_item = $koneksi->prepare("SELECT SUM(jumlah) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_item->bind_param("i", $toko_id); $q_item->execute();
$total_item_terjual = $q_item->get_result()->fetch_assoc()['total'] ?? 0;

// 4. Produk Aktif
$q_produk = $koneksi->prepare("SELECT COUNT(*) as total FROM tb_barang WHERE is_active = 1 AND toko_id = ?");
$q_produk->bind_param("i", $toko_id); $q_produk->execute();
$total_produk_aktif = $q_produk->get_result()->fetch_assoc()['total'] ?? 0;

// 5. Grafik Penjualan Bulanan
$tahun_sekarang = date('Y');
$penjualan_tahunan = array_fill(1, 12, 0);
$q_grafik = $koneksi->prepare("SELECT MONTH(t.tanggal_transaksi) AS bulan, SUM(d.subtotal) AS total FROM tb_detail_transaksi d JOIN tb_transaksi t ON d.transaksi_id = t.id WHERE d.toko_id = ? AND YEAR(t.tanggal_transaksi) = ? GROUP BY MONTH(t.tanggal_transaksi)");
$q_grafik->bind_param("is", $toko_id, $tahun_sekarang); $q_grafik->execute();
$result_grafik = $q_grafik->get_result();
while ($row = $result_grafik->fetch_assoc()) {
    $penjualan_tahunan[(int)$row['bulan']] = (float)$row['total'];
}
$labels_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

// 6. Top 5 Produk Terlaris (tetap ada meskipun tidak ditampilkan di layout baru)
$q_top_produk = $koneksi->prepare("SELECT b.nama_barang, SUM(d.jumlah) as total_terjual FROM tb_detail_transaksi d JOIN tb_barang b ON d.barang_id = b.id WHERE d.toko_id = ? GROUP BY d.barang_id ORDER BY total_terjual DESC LIMIT 5");
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
    <title>Dashboard - Pondasikita Seller Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css"> 
</head>
<body>
<div class="container-scroller">
    
    <?php include 'partials/sidebar.php'; ?>

    <div class="page-body-wrapper">
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="sidebar-toggle-btn d-lg-none"><i class="mdi mdi-menu"></i></button>
            </div>
            <div class="navbar-right">
                <a href="#" class="navbar-icon"><i class="mdi mdi-bell-outline"></i></a>
                <a href="#" class="navbar-icon"><i class="mdi mdi-help-circle-outline"></i></a>
                <div class="navbar-profile">
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Seller') ?></span>
                    <i class="mdi mdi-chevron-down profile-arrow"></i>
                </div>
            </div>
        </nav>

        <main class="main-panel">
            <div class="content-wrapper">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Yang Perlu Dilakukan</h4>
                        <div class="row text-center action-items">
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <p class="action-value">0</p>
                                <p class="action-label">Pengiriman Perlu Diproses</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <p class="action-value">0</p>
                                <p class="action-label">Pengiriman Telah Diproses</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <p class="action-value">0</p>
                                <p class="action-label">Pengembalian/Pembatalan</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <p class="action-value">0</p>
                                <p class="action-label">Produk Ditolak/Diturunkan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title d-flex justify-content-between align-items-center">
                                    Performa Toko
                                    <a href="#" class="text-link">Lainnya <i class="mdi mdi-chevron-right"></i></a>
                                </h4>
                                <p class="card-subtitle">Waktu update terakhir: GMT+7 19:00 (Perubahan data dibanding kemarin)</p>
                                <div class="row text-center performance-metrics">
                                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                                        <p class="metric-label">Penjualan</p>
                                        <p class="metric-value">Rp <?= number_format($total_penjualan, 0, ',', '.') ?></p>
                                        <p class="metric-change text-success">-- 0,00%</p>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                                        <p class="metric-label">Total Pengunjung</p>
                                        <p class="metric-value">0</p>
                                        <p class="metric-change text-success">-- 0,00%</p>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                                        <p class="metric-label">Jumlah Produk Diklik</p>
                                        <p class="metric-value">0</p>
                                        <p class="metric-change text-success">-- 0,00%</p>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                                        <p class="metric-label">Tingkat Konversi Pesanan</p>
                                        <p class="metric-value">0,00%</p>
                                        <p class="metric-change text-success">-- 0,00%</p>
                                    </div>
                                </div>
                                <div class="chart-container mt-4">
                                    <canvas id="penjualanBulananChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title d-flex justify-content-between align-items-center">
                                    Berita
                                    <a href="#" class="text-link">Lainnya <i class="mdi mdi-chevron-right"></i></a>
                                </h4>
                                <div class="news-item">
                                    <img src="https://placehold.co/100x60/E0F2F7/000000?text=News" alt="News Image" class="news-img" onerror="this.onerror=null;this.src='https://placehold.co/100x60/E0F2F7/000000?text=News';">
                                    <div class="news-content">
                                        <p class="news-title">BANJIR ORDER DI KAMPANYE 7.7</p>
                                        <p class="news-desc">Dapatkan diskon voucher dan buat tokomu lebih menjangkau.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title d-flex justify-content-between align-items-center">
                                    Misi Penjual
                                    <a href="#" class="text-link">Lainnya <i class="mdi mdi-chevron-right"></i></a>
                                </h4>
                                <div class="empty-state small-empty-state">
                                    <i class="mdi mdi-check-circle-outline"></i>
                                    <p>Tidak ada misi yang aktif saat ini.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Iklan Pondasikita</h4>
                        <p class="card-subtitle">Maksimalkan penjualanmu dengan Iklan Pondasikita</p>
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <p class="text-secondary mb-2 mb-sm-0">Pelajari lebih lanjut tentang cara terbaik mengiklankan produk dan buat iklanmu lebih terjangkau.</p>
                            <button class="btn btn-outline-primary">Pelajari Lebih Lanjut</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title">Affiliate Marketing Solution</h4>
                                <div class="row text-center performance-metrics">
                                    <div class="col-md-4 col-4">
                                        <p class="metric-label">Penjualan</p>
                                        <p class="metric-value">Rp0</p>
                                    </div>
                                    <div class="col-md-4 col-4">
                                        <p class="metric-label">Pembeli Baru</p>
                                        <p class="metric-value">0</p>
                                    </div>
                                    <div class="col-md-4 col-4">
                                        <p class="metric-label">ROI</p>
                                        <p class="metric-value">0</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title">Livestream</h4>
                                <div class="row text-center performance-metrics">
                                    <div class="col-md-6 col-6">
                                        <p class="metric-label">Penjualan</p>
                                        <p class="metric-value">0</p>
                                    </div>
                                    <div class="col-md-6 col-6">
                                        <p class="metric-label">Pesanan</p>
                                        <p class="metric-value">0</p>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="/assets/js/seller-script.js"></script>
<script>

$(document).ready(function() {
    // --- Logika untuk Sidebar Dropdown ---
    $('[data-toggle="collapse"]').on('click', function(e) {
        e.preventDefault(); // Mencegah default action dari link
        var target = $(this).attr('href'); // Ambil ID target dari href
        $(target).toggleClass('show'); // Toggle class 'show' pada target (div collapse)
        // Perbarui atribut aria-expanded
        $(this).attr('aria-expanded', $(target).hasClass('show'));

        // Opsional: Tutup dropdown lain saat satu dibuka (agar hanya satu yang terbuka)
        $('.collapse.show').not(target).removeClass('show').prev('[data-toggle="collapse"]').attr('aria-expanded', 'false');
    });

    // --- Logika untuk Toggle Sidebar di Mobile ---
    $('.sidebar-toggle-btn').on('click', function() {
        $('.sidebar').toggleClass('active'); // Tambahkan/hapus kelas 'active' pada sidebar
        $('.page-body-wrapper').toggleClass('sidebar-active'); // Sesuaikan posisi konten utama
    });


    // --- Inisialisasi Grafik Penjualan (Line Chart) ---
    const penjualanChartCtx = document.getElementById("penjualanBulananChart");
    if (penjualanChartCtx) {
        new Chart(penjualanChartCtx, {
            type: "line",
            data: {
                labels: <?= json_encode($labels_bulan) ?>,
                datasets: [{
                    label: "Pendapatan",
                    data: <?= json_encode(array_values($penjualan_tahunan)) ?>,
                    backgroundColor: "rgba(79, 70, 229, 0.1)",
                    borderColor: "rgba(79, 70, 229, 1)",
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- Inisialisasi Grafik Top Produk (Bar Chart) ---
    const topProdukChartCtx = document.getElementById("topProdukChart");
    <?php if (!empty($top_produk_data)): ?>
    if (topProdukChartCtx) {
        new Chart(topProdukChartCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($top_produk_data)) ?>,
                datasets: [{
                    label: 'Jumlah Terjual',
                    data: <?= json_encode(array_values($top_produk_data)) ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)', 'rgba(99, 102, 241, 0.7)',
                        'rgba(139, 92, 246, 0.7)', 'rgba(168, 85, 247, 0.7)',
                        'rgba(216, 27, 96, 0.7)'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }
    <?php endif; ?>
});
</script>

</body>
</html>