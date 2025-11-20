<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Voucher Toko Saya - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
        <link rel="stylesheet" href="/assets/css/sidebar.css">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Voucher Toko Saya</h1></div>

                <div class="hero-card mb-4">
                    <div class="hero-text">
                        <h2>Buat Voucher Sekarang & Tingkatkan Pesananmu!</h2>
                        <p>Penjual yang membuat voucher melihat peningkatan konversi penjualan hingga 25%.</p>
                        <a href="buat_voucher.php" class="btn btn-primary btn-lg btn-pill">Buat Voucher Sekarang <i class="mdi mdi-arrow-right"></i></a>
                    </div>
                    <div class="hero-image d-none d-md-block">
                        <i class="mdi mdi-ticket-percent" style="font-size: 8rem; color: #C7D2FE;"></i>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Buat Voucher</h4>
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <div class="promo-type-card">
                                    <div class="icon-wrapper"><i class="mdi mdi-store"></i></div>
                                    <h3>Voucher Toko</h3>
                                    <p>Voucher berlaku untuk semua produk agar penjualannya meningkat.</p>
                                    <a href="buat_voucher.php?tipe=toko" class="btn btn-primary">Buat</a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="promo-type-card">
                                    <div class="icon-wrapper"><i class="mdi mdi-cube-outline"></i></div>
                                    <h3>Voucher Produk</h3>
                                    <p>Voucher untuk produk terpilih sebagai bagian dari promosi tertentu.</p>
                                    <a href="buat_voucher.php?tipe=produk" class="btn btn-primary">Buat</a>
                                </div>
                            </div>
                             <div class="col-md-4 mb-3">
                                <div class="promo-type-card">
                                    <div class="icon-wrapper"><i class="mdi mdi-eye-off"></i></div>
                                    <h3>Voucher Terbatas</h3>
                                    <p>Voucher untuk Pembeli tertentu yang hanya dapat dibagikan melalui kode.</p>
                                    <a href="buat_voucher.php?tipe=terbatas" class="btn btn-primary">Buat</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daftar Voucher</h4>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama Voucher / Kode</th>
                                        <th>Tipe Voucher</th>
                                        <th>Diskon</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" class="text-center p-5"><div class="empty-state"><i class="mdi mdi-ticket-confirmation-outline" style="font-size: 3rem; color: #E5E7EB;"></i><p class="mt-2">Tidak ada Voucher yang dibuat.</p></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>