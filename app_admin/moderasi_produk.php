<?php
if (!isset($current_page_name)) {
    $current_page_name = basename($_SERVER['PHP_SELF']);
}

// Helper function untuk cek aktif
function isAdminActive($link) {
    global $current_page_name;
    return $current_page_name === $link ? 'active' : '';
}

// Helper untuk cek apakah parent menu (dropdown) harus terbuka
function isParentOpen($links) {
    global $current_page_name;
    return in_array($current_page_name, $links) ? 'show' : '';
}

function isParentActive($links) {
    global $current_page_name;
    return in_array($current_page_name, $links) ? 'active' : '';
}
?>

<!-- Pastikan memanggil CSS dan JS baru -->
<link rel="stylesheet" href="../assets/css/sidebar_admin.css">

<nav class="modern-sidebar" id="adminSidebar">
    <!-- 1. HEADER: LOGO -->
    <div class="sidebar-header">
        <a href="dashboard_admin.php" class="brand-wrapper">
            <div class="brand-icon">P</div>
            <div class="brand-text">
                <span>Pondasikita</span>
                <small>Admin Panel</small>
            </div>
        </a>
    </div>

    <!-- 2. BODY: SCROLLABLE MENU -->
    <div class="sidebar-body">
        
        <!-- Profil Admin Mini -->
        <div class="admin-profile-card">
            <div class="admin-avatar">
                <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="admin-info">
                <span class="name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator') ?></span>
                <span class="role">Super Admin</span>
            </div>
        </div>

        <ul class="nav-list">
            <li class="nav-header">Utama</li>
            
            <!-- Menu: Dashboard -->
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link <?= isAdminActive('dashboard_admin.php') ?>">
                    <i class="mdi mdi-grid-large nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-header">Manajemen</li>

            <!-- Menu: Pengguna -->
            <li class="nav-item">
                <a href="kelola_pengguna.php" class="nav-link <?= isAdminActive('kelola_pengguna.php') ?>">
                    <i class="mdi mdi-account-multiple-outline nav-icon"></i>
                    <span class="nav-text">Kelola Pengguna</span>
                </a>
            </li>

            <!-- Dropdown: Toko & Produk -->
            <?php 
                $toko_group = ['kelola_toko.php', 'moderasi_produk.php']; 
            ?>
            <li class="nav-item has-sub <?= isParentActive($toko_group) ?>">
                <a href="javascript:void(0)" class="nav-link dropdown-toggle">
                    <i class="mdi mdi-storefront-outline nav-icon"></i>
                    <span class="nav-text">Toko & Produk</span>
                    <i class="mdi mdi-chevron-right chevron"></i>
                </a>
                <div class="sub-menu <?= isParentOpen($toko_group) ?>">
                    <ul>
                        <li>
                            <a href="kelola_toko.php" class="<?= isAdminActive('kelola_toko.php') ?>">
                                <span class="dot"></span> Daftar Toko
                            </a>
                        </li>
                        <li>
                            <a href="moderasi_produk.php" class="<?= isAdminActive('moderasi_produk.php') ?>">
                                <span class="dot"></span> Moderasi Produk
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-header">Keuangan & Laporan</li>

            <!-- Menu: Keuangan -->
            <li class="nav-item">
                <a href="kelola_payout.php" class="nav-link <?= isAdminActive('kelola_payout.php') ?>">
                    <i class="mdi mdi-wallet-outline nav-icon"></i>
                    <span class="nav-text">Payout</span>
                </a>
            </li>

             <!-- Dropdown: Laporan -->
             <?php 
                $laporan_group = ['laporan_penjualan_toko.php', 'laporan_transaksi.php']; 
            ?>
            <li class="nav-item has-sub <?= isParentActive($laporan_group) ?>">
                <a href="javascript:void(0)" class="nav-link dropdown-toggle">
                    <i class="mdi mdi-chart-box-outline nav-icon"></i>
                    <span class="nav-text">Laporan</span>
                    <i class="mdi mdi-chevron-right chevron"></i>
                </a>
                <div class="sub-menu <?= isParentOpen($laporan_group) ?>">
                    <ul>
                        <li>
                            <a href="laporan_penjualan_toko.php" class="<?= isAdminActive('laporan_penjualan_toko.php') ?>">
                                <span class="dot"></span> Penjualan Toko
                            </a>
                        </li>
                        <li>
                            <a href="laporan_transaksi.php" class="<?= isAdminActive('laporan_transaksi.php') ?>">
                                <span class="dot"></span> Transaksi
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
             
             <li class="nav-header">Sistem</li>

             <!-- Dropdown: Pengaturan -->
             <?php 
                $settings_group = ['pengaturan_kategori.php', 'pengaturan_zona.php', 'pengaturan_website.php', 'kelola_flash_sale.php']; 
            ?>
            <li class="nav-item has-sub <?= isParentActive($settings_group) ?>">
                <a href="javascript:void(0)" class="nav-link dropdown-toggle">
                    <i class="mdi mdi-cog-outline nav-icon"></i>
                    <span class="nav-text">Pengaturan</span>
                    <i class="mdi mdi-chevron-right chevron"></i>
                </a>
                <div class="sub-menu <?= isParentOpen($settings_group) ?>">
                    <ul>
                        <li><a href="pengaturan_kategori.php" class="<?= isAdminActive('pengaturan_kategori.php') ?>"><span class="dot"></span> Kategori</a></li>
                        <li><a href="kelola_flash_sale.php" class="<?= isAdminActive('kelola_flash_sale.php') ?>"><span class="dot"></span> Flash Sale</a></li>
                        <li><a href="pengaturan_zona.php" class="<?= isAdminActive('pengaturan_zona.php') ?>"><span class="dot"></span> Logistik</a></li>
                        <li><a href="pengaturan_website.php" class="<?= isAdminActive('pengaturan_website.php') ?>"><span class="dot"></span> Website</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- 3. FOOTER: LOGOUT -->
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-link">
            <i class="mdi mdi-logout"></i>
            <span>Keluar</span>
        </a>
    </div>
</nav>

<!-- Script JS Khusus Sidebar -->
<script src="../assets/js/sidebar_admin.js"></script>