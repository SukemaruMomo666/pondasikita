<?php
session_start();
require_once '../../config/koneksi.php';

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../../auth/login.php"); exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
if (!$toko_id) die("Toko tidak valid.");

// Ambil pengaturan yang sudah ada, atau gunakan nilai default jika belum ada
$pengaturan = $koneksi->query("SELECT * FROM tb_toko_pengaturan WHERE toko_id = $toko_id")->fetch_assoc();
if (!$pengaturan) {
    // Default values
    $pengaturan = [
        'notif_email_pesanan' => 1, 'notif_email_chat' => 1, 
        'notif_email_produk' => 1, 'notif_email_promo' => 1
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pengaturan Notifikasi - Seller Center</title>
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../assets/template/spica/template/css/style.css">
    <style>
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 28px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(22px); }
        .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #f0f0f0; }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php include '../partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"><i class="mdi mdi-settings"></i> Pengaturan Toko</h3>
                </div>
                <div class="card">
                    <div class="card-body">
                        <?php include 'partials/navbar_pengaturan.php'; // Memanggil sub-navbar ?>

                        <form action="../../actions/proses_pengaturan_toko.php" method="POST">
                            <input type="hidden" name="action" value="simpan_notifikasi">
                            <h4 class="card-title mt-4">Notifikasi Email</h4>
                            <p class="card-description">Pilih informasi apa saja yang ingin Anda terima melalui email.</p>

                            <div class="setting-item">
                                <div>
                                    <h6>Informasi Pesanan & Produk</h6>
                                    <small>Informasi terbaru dari status pesanan dan produk Anda.</small>
                                </div>
                                <label class="toggle-switch"><input type="checkbox" name="notif_email_pesanan" value="1" <?= $pengaturan['notif_email_pesanan'] ? 'checked' : '' ?>><span class="slider"></span></label>
                            </div>
                            <div class="setting-item">
                                <div>
                                    <h6>Informasi Chat</h6>
                                    <small>Notifikasi ketika ada pesan baru dari pembeli.</small>
                                </div>
                                <label class="toggle-switch"><input type="checkbox" name="notif_email_chat" value="1" <?= $pengaturan['notif_email_chat'] ? 'checked' : '' ?>><span class="slider"></span></label>
                            </div>
                            <div class="setting-item">
                                <div>
                                    <h6>Informasi Promo & Kampanye</h6>
                                    <small>Informasi eksklusif tentang promo dan penawaran dari Pondasikita.</small>
                                </div>
                                <label class="toggle-switch"><input type="checkbox" name="notif_email_promo" value="1" <?= $pengaturan['notif_email_promo'] ? 'checked' : '' ?>><span class="slider"></span></label>
                            </div>
                            
                            <button type="submit" class="btn btn-gradient-primary mt-4">Simpan Pengaturan Notifikasi</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>