<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <div class="sidebar-brand-logo">
        <a class="brand-link" href="/app_customer/index.php">Pondasikita</a>
        <span class="brand-subtext">Seller Center</span>
    </div>
    <div class="sidebar-profile">
        <p class="profile-name">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Seller') ?>!</p>
    </div>
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link" href="/app_seller/dashboard.php"><i class="mdi mdi-view-dashboard menu-icon"></i><span class="menu-title">Dashboard</span></a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#pesanan-menu" aria-expanded="false" aria-controls="pesanan-menu">
                <i class="mdi mdi-receipt menu-icon"></i><span class="menu-title">Pesanan</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="pesanan-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/pesanan.php">Pesanan Saya</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/pengembalian.php">Pengembalian/Batal</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/pengaturan/pengiriman.php">Pengaturan Pengiriman</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="/app_seller/produk.php"><i class="mdi mdi-cube-unfolded menu-icon"></i><span class="menu-title">Produk Saya</span></a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#promosi-menu" aria-expanded="false" aria-controls="promosi-menu">
                <i class="mdi mdi-ticket-percent menu-icon"></i><span class="menu-title">Pusat Promosi</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="promosi-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/voucher.php">Voucher Toko</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/diskon.php">Diskon Produk</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/flash_sale.php">Flash Sale Toko</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="/app_seller/keuangan.php"><i class="mdi mdi-finance menu-icon"></i><span class="menu-title">Keuangan</span></a>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#data-menu" aria-expanded="false" aria-controls="data-menu">
                <i class="mdi mdi-chart-line menu-icon"></i><span class="menu-title">Data</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="data-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/performa.php">Performa Toko</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/kesehatan_toko.php">Kesehatan Toko</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#toko-menu" aria-expanded="false" aria-controls="toko-menu">
                <i class="mdi mdi-store menu-icon"></i><span class="menu-title">Toko</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="toko-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/pengaturan/profil_toko.php">Profil Toko</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/pengaturan/dekorasi_toko.php">Dekorasi Toko</a></li>
                    <li class="nav-item"> <a class="nav-link" href="/app_seller/pengaturan_toko.php">Pengaturan Toko</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item sidebar-footer">
             <a class="nav-link" href="/auth/logout.php"><i class="mdi mdi-logout menu-icon"></i><span class="menu-title">Keluar</span></a>
        </li>
    </ul>
</nav>