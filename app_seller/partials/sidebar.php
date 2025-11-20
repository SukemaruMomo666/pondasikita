<?php
// Dapatkan path dari URL saat ini
if (!isset($current_page_full_path)) {
    // Fallback sederhana jika variabel tidak didefinisikan dari file utama
    $current_page_full_path = basename($_SERVER['PHP_SELF']); 
}

// Fungsi untuk mengecek link aktif
function isActive($menuPath, $currentPageFullPath) {
    // Normalisasi path untuk perbandingan yang akurat
    $menuPath = '/' . ltrim(str_replace(basename($_SERVER['DOCUMENT_ROOT']), '', $menuPath), '/');
    $currentPageFullPath = '/' . ltrim(str_replace(basename($_SERVER['DOCUMENT_ROOT']), '', $currentPageFullPath), '/');
    if (basename($menuPath) === $currentPageFullPath) {
        return 'active';
    }
    return '';
}

// Fungsi untuk mengecek apakah parent menu harus dibuka (expanded)
function isParentActive($subMenusFullPaths, $currentPageFullPath) {
    foreach ($subMenusFullPaths as $menuPath) {
        if (isActive($menuPath, $currentPageFullPath) === 'active') {
            return 'true';
        }
    }
    return 'false';
}

// Fungsi untuk mengecek apakah parent menu harus memiliki kelas 'show'
function isParentShow($subMenusFullPaths, $currentPageFullPath) {
    foreach ($subMenusFullPaths as $menuPath) {
         if (isActive($menuPath, $currentPageFullPath) === 'active') {
            return 'show';
        }
    }
    return '';
}

// Definisi submenu (path relatif dari folder web root)
$pesanan_sub_menus = [
    'app_seller/pesanan.php', 
    'app_seller/pengembalian.php',
    'app_seller/pengaturan_pengiriman.php'
];
$produk_sub_menus = [
    'app_seller/produk.php',
    'app_seller/form_produk.php'
];
$promosi_sub_menus = [
    'app_seller/diskon.php',
    'app_seller/voucher.php',
    'app_seller/buat_promo_toko.php', // Tambahkan halaman create agar parent tetap aktif
    'app_seller/buat_voucher.php'      // Tambahkan halaman create agar parent tetap aktif
];
$layanan_pembeli_sub_menus = [
    'app_seller/manajemen_chat.php',
    'app_seller/penilaian_toko.php'
];

// --- PERUBAHAN 1: Tambahkan 'penghasilan_toko.php' ke array ini ---
$keuangan_sub_menus = [
    'app_seller/penghasilan_toko.php', // Ditambahkan
    'app_seller/rekening_bank.php'
];

$data_sub_menus = [
    'app_seller/performa_toko.php',
    'app_seller/kesehatan_toko.php'
];
$toko_sub_menus = [
    'app_seller/profil_toko.php',
    'app_seller/dekorasi_toko.php',
    'app_seller/pengaturan_toko.php'
];

?>

<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <div class="sidebar-brand-logo">
        <a class="brand-link" href="/app_customer/index.php" title="Lihat Tampilan Toko">Pondasikita</a>
        <span class="brand-subtext">Seller Center</span>
    </div>
<div class="sidebar-profile">
    <div class="profile-card">
        <div class="profile-avatar">
            <!-- Ambil huruf depan nama -->
            <?= strtoupper(substr($_SESSION['nama'] ?? 'S', 0, 1)) ?>
        </div>
        <div class="profile-info">
            <div class="profile-welcome">Selamat Datang,</div>
            <div class="profile-name" title="<?= htmlspecialchars($_SESSION['nama'] ?? 'Seller') ?>">
                <?= htmlspecialchars($_SESSION['nama'] ?? 'Seller') ?>
            </div>
        </div>
    </div>
</div>
    <ul class="nav">
        <li class="nav-item <?= isActive('app_seller/dashboard.php', $current_page_full_path) ?>">
            <a class="nav-link" href="/app_seller/dashboard.php">
                <i class="mdi mdi-view-dashboard menu-icon"></i>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>
        
        <li class="nav-item-header">Manajemen Penjualan</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#pesanan-menu" 
               aria-expanded="<?= isParentActive($pesanan_sub_menus, $current_page_full_path) ?>" 
               aria-controls="pesanan-menu">
                <i class="mdi mdi-receipt menu-icon"></i>
                <span class="menu-title">Pesanan</span>
                <i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($pesanan_sub_menus, $current_page_full_path) ?>" id="pesanan-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/pesanan.php', $current_page_full_path) ?>" href="/app_seller/pesanan.php">Pesanan Saya</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/pengembalian.php', $current_page_full_path) ?>" href="/app_seller/pengembalian.php">Pengembalian/Pembatalan</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/pengaturan_pengiriman.php', $current_page_full_path) ?>" href="/app_seller/pengaturan_pengiriman.php">Pengaturan Pengiriman</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item-header">Produk</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#produk-menu" 
               aria-expanded="<?= isParentActive($produk_sub_menus, $current_page_full_path) ?>" 
               aria-controls="produk-menu">
                <i class="mdi mdi-cube-unfolded menu-icon"></i><span class="menu-title">Produk</span><i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($produk_sub_menus, $current_page_full_path) ?>" id="produk-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/produk.php', $current_page_full_path) ?>" href="/app_seller/produk.php">Produk Saya</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/form_produk.php', $current_page_full_path) ?>" href="/app_seller/form_produk.php">Tambah Produk</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item-header">Pusat Promosi</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#promosi-menu" 
               aria-expanded="<?= isParentActive($promosi_sub_menus, $current_page_full_path) ?>" 
               aria-controls="promosi-menu">
                <i class="mdi mdi-ticket-percent menu-icon"></i><span class="menu-title">Pusat Promosi</span><i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($promosi_sub_menus, $current_page_full_path) ?>" id="promosi-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/diskon.php', $current_page_full_path) ?>" href="/app_seller/diskon.php">Diskon Produk</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/voucher.php', $current_page_full_path) ?>" href="/app_seller/voucher.php">Voucher Toko</a></li>
                </ul>
            </div>
        </li>
        
        <li class="nav-item-header">Layanan Pembeli</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#layanan-pembeli-menu" 
               aria-expanded="<?= isParentActive($layanan_pembeli_sub_menus, $current_page_full_path) ?>" 
               aria-controls="layanan-pembeli-menu">
                <i class="mdi mdi-headset-mic menu-icon"></i><span class="menu-title">Layanan Pembeli</span><i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($layanan_pembeli_sub_menus, $current_page_full_path) ?>" id="layanan-pembeli-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/manajemen_chat.php', $current_page_full_path) ?>" href="/app_seller/manajemen_chat.php">Manajemen Chat</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/penilaian_toko.php', $current_page_full_path) ?>" href="/app_seller/penilaian_toko.php">Penilaian Toko</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item-header">Keuangan</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#keuangan-menu" 
               aria-expanded="<?= isParentActive($keuangan_sub_menus, $current_page_full_path) ?>" 
               aria-controls="keuangan-menu">
                <i class="mdi mdi-currency-usd menu-icon"></i>
                <span class="menu-title">Keuangan</span>
                <i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($keuangan_sub_menus, $current_page_full_path) ?>" id="keuangan-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> 
                        <a class="nav-link <?= isActive('app_seller/penghasilan_toko.php', $current_page_full_path) ?>" href="/app_seller/penghasilan_toko.php">Penghasilan Toko</a>
                    </li>
                    <li class="nav-item"> 
                        <a class="nav-link <?= isActive('app_seller/rekening_bank.php', $current_page_full_path) ?>" href="/app_seller/rekening_bank.php">Rekening Bank</a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item-header">Data</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#data-menu" 
               aria-expanded="<?= isParentActive($data_sub_menus, $current_page_full_path) ?>" 
               aria-controls="data-menu">
                <i class="mdi mdi-chart-bar menu-icon"></i><span class="menu-title">Data</span><i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($data_sub_menus, $current_page_full_path) ?>" id="data-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/performa_toko.php', $current_page_full_path) ?>" href="/app_seller/performa_toko.php">Performa Toko</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/kesehatan_toko.php', $current_page_full_path) ?>" href="/app_seller/kesehatan_toko.php">Kesehatan Toko</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item-header">Toko</li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#toko-menu" 
               aria-expanded="<?= isParentActive($toko_sub_menus, $current_page_full_path) ?>" 
               aria-controls="toko-menu">
                <i class="mdi mdi-store menu-icon"></i><span class="menu-title">Toko</span><i class="mdi mdi-chevron-right menu-arrow"></i>
            </a>
            <div class="collapse <?= isParentShow($toko_sub_menus, $current_page_full_path) ?>" id="toko-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/profil_toko.php', $current_page_full_path) ?>" href="/app_seller/profil_toko.php">Profil Toko</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/dekorasi_toko.php', $current_page_full_path) ?>" href="/app_seller/dekorasi_toko.php">Dekorasi Toko</a></li>
                    <li class="nav-item"> <a class="nav-link <?= isActive('app_seller/pengaturan_toko.php', $current_page_full_path) ?>" href="/app_seller/pengaturan_toko.php">Pengaturan Toko</a></li>
                </ul>
            </div>
        </li>
    </ul>

    <div class="sidebar-footer">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="/auth/logout.php">
                    <i class="mdi mdi-logout menu-icon"></i>
                    <span class="menu-title">Keluar</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
