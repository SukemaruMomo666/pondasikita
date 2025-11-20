<?php
if (!isset($current_page_name)) {
    $current_page_name = basename($_SERVER['PHP_SELF']);
}

// --- PENCEGAHAN ERROR PHP (Function Redeclare) ---
if (!function_exists('isAdminActive')) {
    function isAdminActive($link) {
        global $current_page_name;
        return $current_page_name === $link ? 'active' : '';
    }
}

if (!function_exists('isParentOpen')) {
    function isParentOpen($links) {
        global $current_page_name;
        return in_array($current_page_name, $links) ? 'show' : '';
    }
}

if (!function_exists('isParentActive')) {
    function isParentActive($links) {
        global $current_page_name;
        return in_array($current_page_name, $links) ? 'active' : '';
    }
}
?>

<!-- Memanggil CSS & JS Sidebar -->
<link rel="stylesheet" href="../assets/css/sidebar_admin.css">

<nav class="modern-sidebar" id="adminSidebar">
    <!-- 1. HEADER: LOGO -->
    <div class="sidebar-header">
        <a href="dashboard_admin.php" class="brand-wrapper">
            <div class="brand-icon">P</div>
            <div class="brand-text">
                <span>Pondasikita</span>
                <small>ADMIN PANEL</small>
            </div>
        </a>
    </div>

    <!-- 2. BODY: MENU SCROLL -->
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
            <li class="nav-header">UTAMA</li>
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link <?= isAdminActive('dashboard_admin.php') ?>">
                    <i class="mdi mdi-view-dashboard-outline nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-header">MANAJEMEN</li>
            <li class="nav-item">
                <a href="kelola_pengguna.php" class="nav-link <?= isAdminActive('kelola_pengguna.php') ?>">
                    <i class="mdi mdi-account-group-outline nav-icon"></i>
                    <span class="nav-text">Kelola Pengguna</span>
                </a>
            </li>

            <!-- Dropdown Toko -->
            <?php $toko_group = ['kelola_toko.php', 'moderasi_produk.php']; ?>
            <li class="nav-item has-sub <?= isParentActive($toko_group) ?>">
                <a href="javascript:void(0)" class="nav-link dropdown-toggle" aria-expanded="<?= in_array($current_page_name, $toko_group) ? 'true' : 'false' ?>">
                    <i class="mdi mdi-store-outline nav-icon"></i>
                    <span class="nav-text">Toko & Produk</span>
                    <i class="mdi mdi-chevron-right chevron"></i>
                </a>
                <div class="sub-menu <?= isParentOpen($toko_group) ?>">
                    <ul>
                        <li><a href="kelola_toko.php" class="<?= isAdminActive('kelola_toko.php') ?>"><span class="dot"></span> Kelola Toko</a></li>
                        <li><a href="moderasi_produk.php" class="<?= isAdminActive('moderasi_produk.php') ?>"><span class="dot"></span> Moderasi Produk</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-header">KEUANGAN & LAPORAN</li>
            <li class="nav-item">
                <a href="kelola_payout.php" class="nav-link <?= isAdminActive('kelola_payout.php') ?>">
                    <i class="mdi mdi-wallet-outline nav-icon"></i>
                    <span class="nav-text">Payout</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="laporan.php" class="nav-link <?= isAdminActive('laporan.php') ?>">
                    <i class="mdi mdi-file-chart-outline nav-icon"></i>
                    <span class="nav-text">Laporan</span>
                </a>
            </li>

            <li class="nav-header">SISTEM</li>
            <li class="nav-item">
                <a href="pengaturan.php" class="nav-link <?= isAdminActive('pengaturan.php') ?>">
                    <i class="mdi mdi-cog-outline nav-icon"></i>
                    <span class="nav-text">Pengaturan</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- 3. FOOTER: LOGOUT -->
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-link">
            <span>Keluar</span>
        </a>
    </div>
</nav>

<!-- JS Sidebar Wajib -->
<script src="../assets/js/sidebar_admin.js"></script>