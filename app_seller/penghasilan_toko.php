<?php
session_start();
require_once '../config/koneksi.php'; // Sesuaikan dengan path koneksi Anda

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php");
    exit;
}

// Data Placeholder (Ganti dengan query database Anda)
$penghasilan_pending = 0;
$penghasilan_dilepas = 52459320;
$dilepas_minggu_ini = 4500000;
$dilepas_bulan_ini = 15230000;

// Placeholder untuk daftar transaksi
$transaksi_dilepas = []; // Isi dengan data dari database

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penghasilan Saya - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
        <link rel="stylesheet" href="/assets/css/sidebar.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    // Definisikan path halaman saat ini agar sidebar bisa menandai menu yang aktif
    $current_page_full_path = 'app_seller/penghasilan_toko.php';
    include 'partials/sidebar.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Penghasilan Saya</h1></div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card income-summary-card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Informasi Penghasilan</h4>
                                <div class="info-notice">
                                    <i class="mdi mdi-information"></i> Nominal "Pending" dan "Sudah Dilepas" ini belum termasuk biaya penyesuaian.
                                </div>
                                <div class="metrics-container">
                                    <div class="metric-box">
                                        <p class="metric-label">Pending</p>
                                        <h3 class="metric-value">Rp <?= number_format($penghasilan_pending, 0, ',', '.') ?></h3>
                                    </div>
                                    <div class="metric-box">
                                        <p class="metric-label">Sudah Dilepas</p>
                                        <h3 class="metric-value">Rp <?= number_format($penghasilan_dilepas, 0, ',', '.') ?></h3>
                                        <div class="sub-metrics">
                                            <div class="sub-item"><span>Minggu Ini:</span> Rp <?= number_format($dilepas_minggu_ini, 0, ',', '.') ?></div>
                                            <div class="sub-item"><span>Bulan Ini:</span> Rp <?= number_format($dilepas_bulan_ini, 0, ',', '.') ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="#" class="btn btn-primary">Saldo Penjual <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Rincian Penghasilan</h4>
                                <ul class="nav filter-tabs">
                                    <li class="nav-item"><a class="nav-link" href="#">Pending</a></li>
                                    <li class="nav-item"><a class="nav-link active" href="#">Sudah Dilepas</a></li>
                                </ul>
                                <div class="details-header">
                                    <input type="date" class="form-control" style="max-width: 250px;">
                                    <div class="ms-auto d-flex gap-2">
                                        <button class="btn btn-outline">Export</button>
                                        <input type="text" class="form-control" placeholder="Cari Pesanan">
                                    </div>
                                </div>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Pesanan</th>
                                                <th>Tanggal Dana Dilepaskan</th>
                                                <th>Status</th>
                                                <th>Metode Pembayaran</th>
                                                <th>Total Penghasilan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($transaksi_dilepas)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center p-5">
                                                        <div class="empty-state">
                                                            <i class="mdi mdi-file-document-outline" style="font-size: 3rem; color: #E5E7EB;"></i>
                                                            <p class="mt-2">Tidak Ada Data</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card side-widget-card mb-4">
                            <div class="card-body">
                                <div class="widget-header">
                                    <h4 class="card-title">Catatan Transaksi Penghasilan</h4>
                                    <a href="#">Lainnya ></a>
                                </div>
                                <div class="transaction-period-list">
                                    <div class="list-item"><span>30 Jun - 6 Jul 2025</span><a href="#"><i class="mdi mdi-download"></i></a></div>
                                    <div class="list-item"><span>23 Jun - 29 Jun 2025</span><a href="#"><i class="mdi mdi-download"></i></a></div>
                                    <div class="list-item"><span>16 Jun - 22 Jun 2025</span><a href="#"><i class="mdi mdi-download"></i></a></div>
                                </div>
                            </div>
                        </div>
                        <div class="card side-widget-card">
                            <div class="card-body">
                                <div class="widget-header">
                                    <h4 class="card-title">Faktur Saya</h4>
                                    <a href="#">Lainnya ></a>
                                </div>
                                <div class="text-center p-4 empty-state">
                                    <i class="mdi mdi-receipt" style="font-size: 3rem; color: #E5E7EB;"></i>
                                    <p class="mt-2">Tidak ada faktur</p>
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