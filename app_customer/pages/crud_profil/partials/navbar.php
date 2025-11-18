<?php
// File: /partials/navbar.php atau /templates/navbar.php

// Pastikan session selalu ada di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Panggil koneksi sekali saja. Path ini diasumsikan dari file utama yang memanggil navbar.
require_once __DIR__ . '/../../../../config/koneksi.php';

// Cek status login dan peran pengguna
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_level = $is_logged_in ? $_SESSION['level'] : 'guest';
$user_name = $is_logged_in ? $_SESSION['nama'] : '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Hitung item di keranjang
$total_item_keranjang = 0;
if ($is_logged_in && $user_level !== 'admin') {
    $stmt = $koneksi->prepare("SELECT SUM(jumlah) as total FROM tb_keranjang WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $total_item_keranjang = $data['total'] ?? 0;
    $stmt->close();
}
?>

<header class="navbar-fixed">
    <div class="navbar-container">
        <div class="navbar-left">
            <a href="/index.php" class="navbar-logo">
                <h3>Pondasikita</h3>
            </a>
            <form action="../search.php" method="GET" class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="query" placeholder="Cari produk, toko, atau merek...">
            </form>
        </div>

        <nav class="navbar-right">
            <!-- ✅ PENGAIT JAVASCRIPT DITAMBAHKAN DI SINI -->
            <ul class="nav-links js-nav-links">
                <li><a href="../produk.php" class="nav-link">Produk</a></li>
                <li><a href="/semua_toko.php" class="nav-link">Toko</a></li>
            </ul>

            <div class="nav-actions">
                <a href="../keranjang.php" class="action-btn cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($total_item_keranjang > 0): ?>
                        <span class="cart-badge"><?= htmlspecialchars($total_item_keranjang) ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <!-- ✅ PENGAIT JAVASCRIPT DITAMBAHKAN DI SINI -->
                    <div class="dropdown js-dropdown">
                        <button class="action-btn profile-btn">
                            <i class="fas fa-user"></i>
                        </button>
                        <!-- ✅ PENGAIT JAVASCRIPT DITAMBAHKAN DI SINI -->
                        <div class="dropdown-content js-dropdown-content">
                            <div class="dropdown-header">
                                Halo, <strong><?= htmlspecialchars($user_name) ?></strong>
                                <small><?= ucfirst($user_level) ?></small>
                            </div>
                            <?php if ($user_level == 'admin'): ?>
                                <a href="/app_admin/dashboard_mimin.php">Admin Dashboard</a>
                                <a href="/app_admin/kelola_toko.php">Verifikasi Toko</a>
                            <?php elseif ($user_level == 'seller'): ?>
                                <a href="/app_seller/dashboard.php">Dashboard Toko</a>
                                <a href="/app_seller/produk.php">Produk Saya</a>
                                <a href="/app_seller/pesanan.php">Pesanan Masuk</a>
                            <?php else: // Customer ?>
                                <a href="../profil.php">Profil Saya</a>
                                <a href="../pesanan.php">Pesanan Saya</a>
                            <?php endif; ?>
                            <a href="/auth/logout.php" class="logout-link">Keluar</a>
                        </div>
                    </div>
                <?php else: // Pengguna adalah tamu (guest) ?>
                    <a href="/auth/login_customer.php" class="btn btn-secondary">Masuk</a>
                    <a href="/auth/register_customer.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>
            
            <!-- ✅ PENGAIT JAVASCRIPT DITAMBAHKAN DI SINI -->
            <div class="hamburger-menu js-hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </div>
</header>
