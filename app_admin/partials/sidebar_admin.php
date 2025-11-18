<?php
if (!isset($current_page_name)) {
    $current_page_name = basename($_SERVER['PHP_SELF']);
}
function isAdminMenuActive($menu_page, $current_page) {
    return ($menu_page === $current_page) ? 'active' : '';
}
?>
<style>
  .sidebar {
    background-color: #1e2235;
    color: #e1e4f2;
    width: 260px;
    height: 100vh;
   
    top: 0;
    left: 0;
    overflow-y: auto;
    box-shadow: 3px 0 8px rgba(0,0,0,0.3);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.sidebar-brand-logo {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #2c3048;
    text-align: center;
}

.sidebar-brand-logo .brand-link {
    font-size: 1.6rem;
    font-weight: 700;
    color: #f5f7ff;
    text-decoration: none;
    margin-bottom: 0.2rem;
    display: inline-block;
    letter-spacing: 1.2px;
}

.sidebar-brand-logo .brand-subtext {
    font-size: 0.85rem;
    color: #a3a8c2;
    font-weight: 500;
}

.sidebar-profile {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #2c3048;
    background-color: #2a2f4a;
    font-size: 1rem;
    font-weight: 600;
    color: #c8cce5;
    user-select: none;
}

.sidebar-profile p {
    margin: 0;
}

.nav {
    list-style: none;
    padding-left: 0;
    margin: 0;
    flex-grow: 1;
}

.nav-item-header {
    padding: 0.75rem 1.5rem;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #6f73a4;
    border-bottom: 1px solid #2c3048;
    margin-top: 1rem;
}

.nav-item {
    margin: 0;
}

.nav-item.active > a.nav-link,
.nav-item > a.nav-link.active {
    background-color: #4e63ff;
    color: #fff;
    font-weight: 700;
    border-left: 4px solid #2f40ff;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.85rem 1.5rem;
    font-size: 1rem;
    color: #c8cce5;
    text-decoration: none;
    transition: background-color 0.3s, color 0.3s;
    border-left: 4px solid transparent;
}

.nav-link:hover {
    background-color: #3a3f66;
    color: #fff;
    border-left-color: #4e63ff;
}

.menu-icon {
    font-size: 1.25rem;
    margin-right: 1rem;
    color: #8a8fbf;
    flex-shrink: 0;
}

.sidebar-footer {
    border-top: 1px solid #2c3048;
    padding: 1rem 1.5rem;
    background-color: #2a2f4a;
}

.sidebar-footer .nav-link {
    color: #c75a5a;
    font-weight: 600;
    border-left: none;
    padding-left: 0;
}

.sidebar-footer .nav-link:hover {
    background-color: #a63a3a;
    color: #fff;
}

</style>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <div class="sidebar-brand-logo">
        <a class="brand-link" href="dashboard_admin.php">Pondasikita</a>
        <span class="brand-subtext">Admin Panel</span>
    </div>
    <ul class="nav">
        <li class="nav-item <?= isAdminMenuActive('dashboard_admin.php', $current_page_name) ?>">
            <a class="nav-link" href="dashboard_admin.php"><i class="mdi mdi-view-dashboard menu-icon"></i><span class="menu-title">Dashboard</span></a>
        </li>
        
        <li class="nav-item-header">Manajemen</li>
        <li class="nav-item <?= isAdminMenuActive('kelola_pengguna.php', $current_page_name) ?>">
            <a class="nav-link" href="kelola_pengguna.php"><i class="mdi mdi-account-group menu-icon"></i><span class="menu-title">Kelola Pengguna</span></a>
        </li>
        <li class="nav-item <?= isAdminMenuActive('kelola_toko.php', $current_page_name) ?>">
            <a class="nav-link" href="kelola_toko.php"><i class="mdi mdi-store menu-icon"></i><span class="menu-title">Kelola Toko</span></a>
        </li>
        <li class="nav-item <?= isAdminMenuActive('moderasi_produk.php', $current_page_name) ?>">
            <a class="nav-link" href="moderasi_produk.php"><i class="mdi mdi-cube-send menu-icon"></i><span class="menu-title">Moderasi Produk</span></a>
        </li>

        <li class="nav-item-header">Marketing</li>
        <li class="nav-item <?= isAdminMenuActive('kelola_flash_sale.php', $current_page_name) ?>">
            <a class="nav-link" href="kelola_flash_sale.php"><i class="mdi mdi-flash menu-icon"></i><span class="menu-title">Flash Sale</span></a>
        </li>

        <li class="nav-item-header">Keuangan & Laporan</li>
        <li class="nav-item <?= isAdminMenuActive('kelola_payout.php', $current_page_name) ?>">
            <a class="nav-link" href="kelola_payout.php"><i class="mdi mdi-currency-usd menu-icon"></i><span class="menu-title">Manajemen Payout</span></a>
        </li>
        <li class="nav-item <?= isAdminMenuActive('laporan_penjualan_toko.php', $current_page_name) ?>">
            <a class="nav-link" href="laporan_penjualan_toko.php"><i class="mdi mdi-chart-line menu-icon"></i><span class="menu-title">Laporan Penjualan</span></a>
        </li>
         <li class="nav-item <?= isAdminMenuActive('laporan_transaksi.php', $current_page_name) ?>">
            <a class="nav-link" href="laporan_transaksi.php"><i class="mdi mdi-file-document menu-icon"></i><span class="menu-title">Semua Transaksi</span></a>
        </li>

        <li class="nav-item-header">Pengaturan</li>
         <li class="nav-item <?= isAdminMenuActive('pengaturan_kategori.php', $current_page_name) ?>">
            <a class="nav-link" href="pengaturan_kategori.php"><i class="mdi mdi-tag-multiple menu-icon"></i><span class="menu-title">Kategori</span></a>
        </li>
        <!-- MENU BARU DITAMBAHKAN DI SINI -->
        <li class="nav-item <?= isAdminMenuActive('pengaturan_zona.php', $current_page_name) ?>">
            <a class="nav-link" href="pengaturan_zona.php"><i class="mdi mdi-map-marker-radius menu-icon"></i><span class="menu-title">Logistik & Pengiriman</span></a>
        </li>
        <li class="nav-item <?= isAdminMenuActive('pengaturan_website.php', $current_page_name) ?>">
            <a class="nav-link" href="pengaturan_website.php"><i class="mdi mdi-cogs menu-icon"></i><span class="menu-title">Pengaturan Website</span></a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <ul class="nav">
            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="mdi mdi-logout menu-icon"></i><span class="menu-title">Keluar</span></a></li>
        </ul>
    </div>
</nav>
