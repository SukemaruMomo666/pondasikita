<?php
session_start();
require_once '../config/koneksi.php'; // Sesuaikan path

// --- Keamanan & Pengambilan Data Dasar ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT * FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko = $stmt_toko->get_result()->fetch_assoc();
if (!$toko) {
    die("Toko tidak ditemukan.");
}
$toko_id = $toko['id'];

// --- Logika untuk Navigasi Tab ---
$allowed_tabs = ['profil', 'pengaturan', 'alamat', 'pengiriman', 'notifikasi'];
$current_tab = $_GET['tab'] ?? 'profil';
if (!in_array($current_tab, $allowed_tabs)) {
    $current_tab = 'profil';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Seller Center</title>
    
    <!-- CSS Utama -->
    <link rel="stylesheet" href="../assets/css/seller_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">

    <!-- ✅ 1. TAMBAHKAN CSS UNTUK SELECT2 DI SINI -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Style tambahan agar Select2 cocok dengan tema seller_style.css */
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 1.5rem + 2px);
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--surface-color);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
            color: var(--text-primary);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 1.5rem);
            right: 5px;
        }
        .select2-dropdown {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'pengaturan'; 
    include 'partials/sidebar.php'; 
    ?>
    <div class="page-body-wrapper">
        <?php include 'partials/navbar.php'; // Navbar atas ?>
        
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">Pengaturan</h3>
                </div>
                
                <!-- (Konten pesan sukses/gagal dan card) -->
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="settingsTab" role="tablist">
                            <li class="nav-item" role="presentation"><a class="nav-link <?= $current_tab == 'profil' ? 'active' : '' ?>" href="?tab=profil">Profil Toko</a></li>
                            <li class="nav-item" role="presentation"><a class="nav-link <?= $current_tab == 'pengaturan' ? 'active' : '' ?>" href="?tab=pengaturan">Pengaturan Dasar</a></li>
                            <li class="nav-item" role="presentation"><a class="nav-link <?= $current_tab == 'alamat' ? 'active' : '' ?>" href="?tab=alamat">Alamat Toko</a></li>
                            <li class="nav-item" role="presentation"><a class="nav-link <?= $current_tab == 'pengiriman' ? 'active' : '' ?>" href="?tab=pengiriman">Pengiriman</a></li>
                            <li class="nav-item" role="presentation"><a class="nav-link <?= $current_tab == 'notifikasi' ? 'active' : '' ?>" href="?tab=notifikasi">Notifikasi</a></li>
                        </ul>
                        <div class="tab-content pt-4">
                            <?php include "tabs/tab_{$current_tab}.php"; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ 2. TAMBAHKAN JS UNTUK SELECT2 DI SINI (SETELAH JQUERY) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
