<?php
// File: /partials/navbar.php

// Pastikan session selalu aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil koneksi hanya sekali
// Gunakan __DIR__ untuk path absolut yang lebih robust
require_once __DIR__ . '/../../config/koneksi.php';

// Cek status login dari array $_SESSION['user']
// PERBAIKAN DI SINI: Akses semua data user dari $_SESSION['user']
$is_logged_in = isset($_SESSION['user']['logged_in']) && $_SESSION['user']['logged_in'] === true; // Cek penanda logged_in di dalam array user
$user_level = $is_logged_in ? $_SESSION['user']['level'] : 'guest';
$user_name = $is_logged_in ? $_SESSION['user']['nama'] : '';
$user_id = $is_logged_in ? $_SESSION['user']['id'] : null; // Ambil ID dari array user

// Hitung item di keranjang (khusus user biasa / seller)
$total_item_keranjang = 0;
// Pastikan user_id tidak null sebelum query
if ($is_logged_in && $user_level !== 'admin' && $user_id !== null) {
    $stmt = $koneksi->prepare("SELECT SUM(jumlah) as total FROM tb_keranjang WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $total_item_keranjang = $data['total'] ?? 0;
        $stmt->close();
    }
}
?>

<header class="navbar-fixed">
    <div class="navbar-container">
        <div class="navbar-left">
            <a href="/index.php" class="navbar-logo">
                <h3>Pondasikita</h3>
            </a>
            <form action="search.php" method="GET" class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="query" placeholder="Cari produk, toko, atau merek...">
            </form>
        </div>

        <nav class="navbar-right">
            <ul class="nav-links js-nav-links">
                <li><a href="produk.php" class="nav-link">Produk</a></li>
                <li><a href="semua_toko.php" class="nav-link">Toko</a></li>
            </ul>

            <div class="nav-actions">
                <a href="keranjang.php" class="action-btn cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($total_item_keranjang > 0): ?>
                        <span class="cart-badge"><?= htmlspecialchars($total_item_keranjang) ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <div class="dropdown js-dropdown">
                        <button class="action-btn profile-btn">
                            <i class="fas fa-user"></i>
                        </button>
                        <div class="dropdown-content js-dropdown-content">
                            <div class="dropdown-header">
                                Halo, <strong><?= htmlspecialchars($user_name) ?></strong>
                                <small><?= ucfirst($user_level) ?></small>
                            </div>

                            <?php if ($user_level === 'admin'): ?>
                                <a href="/app_admin/dashboard_mimin.php">Admin Dashboard</a>
                                <a href="/app_admin/kelola_toko.php">Verifikasi Toko</a>
                            <?php elseif ($user_level === 'seller'): ?>
                                <a href="/app_seller/dashboard.php">Dashboard Toko</a>
                                <a href="/app_seller/produk.php">Produk Saya</a>
                                <a href="/app_seller/pesanan.php">Pesanan Masuk</a>
                            <?php else: // 'customer' ?>
                                <a href="profil.php">Profil Saya</a>
                                <a href="pesanan_customer.php">Pesanan Saya</a>
                            <?php endif; ?>
                            <a href="../auth/logout.php" class="logout-link">Keluar</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../auth/login_customer.php" class="btn btn-secondary">Masuk</a>
                    <a href="../auth/register_customer.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>

            <div class="hamburger-menu js-hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const profileBtn = document.querySelector('.profile-btn');
    const dropdown = document.querySelector('.js-dropdown-content');

    if (profileBtn && dropdown) {
        profileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('show-dropdown');
        });

        // Tutup dropdown saat klik di luar
        document.addEventListener('click', function () {
            dropdown.classList.remove('show-dropdown');
        });
    }
});
</script>
</header>