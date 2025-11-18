<?php
session_start();
// Pastikan user sudah login dan merupakan seller
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php"); // Sesuaikan dengan halaman login Anda
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pusat Promosi - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Pusat Promosi</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Buat Promosi</h4>
                        <p class="card-subtitle">Tingkatkan penjualan Anda dengan berbagai fitur promosi menarik.</p>
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <div class="promo-type-card">
                                    <div class="icon-wrapper"><i class="mdi mdi-tag-heart"></i></div>
                                    <h3>Promo Toko</h3>
                                    <p>Buat diskon untuk produk-produk di toko Anda untuk menarik lebih banyak pembeli.</p>
                                    <a href="buat_promo_toko.php" class="btn btn-primary">Buat</a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="promo-type-card">
                                    <div class="icon-wrapper"><i class="mdi mdi-package-variant"></i></div>
                                    <h3>Paket Diskon</h3>
                                    <p>Tawarkan harga spesial untuk pembelian beberapa produk sekaligus dalam satu paket.</p>
                                    <a href="buat_paket_diskon.php" class="btn btn-primary">Buat</a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="promo-type-card">
                                    <div class="icon-wrapper"><i class="mdi mdi-basket-plus-outline"></i></div>
                                    <h3>Kombo Hemat</h3>
                                    <p>Dorong pembeli untuk membeli lebih banyak dengan penawaran produk tambahan.</p>
                                    <button class="btn btn-primary" disabled>Segera Hadir</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daftar Promosi</h4>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama Promosi</th>
                                        <th>Tipe Promosi</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 3rem;">
                                            <div class="empty-state">
                                                <i class="mdi mdi-magnify" style="font-size: 3rem; color: #E5E7EB;"></i>
                                                <p class="mt-2">Belum ada promosi yang dibuat.</p>
                                            </div>
                                        </td>
                                    </tr>
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