<?php
// Pastikan session selalu ada di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Panggil koneksi sekali saja
require_once __DIR__ . '/../config/koneksi.php';

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
<form action="/pages/search.php" method="GET" class="search-bar">
    <i class="fas fa-magnifying-glass"></i> 
    <input type="text" ...>
</form>

        <nav class="navbar-right">
            <ul class="nav-links">
                <li><a href="/pages/produk.php" class="nav-link">Produk</a></li>
                <li><a href="/pages/semua_toko.php" class="nav-link">Toko</a></li>
                <?php if (!$is_logged_in || $user_level === 'customer'): ?>

                <?php endif; ?>
            </ul>

            <div class="nav-actions">
                <a href="/pages/keranjang.php" class="action-btn cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($total_item_keranjang > 0): ?>
                        <span class="cart-badge"><?= $total_item_keranjang ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <div class="dropdown">
                        <button class="action-btn profile-btn">
                            <i class="fas fa-user"></i>
                        </button>
                        <div class="dropdown-content">
                            <div class="dropdown-header">
                                Halo, <strong><?= htmlspecialchars($user_name) ?></strong>
                                <small><?= ucfirst($user_level) ?></small>
                            </div>
                            <?php if ($user_level == 'admin'): ?>
                                <a href="/admin/dashboard.php">Admin Dashboard</a>
                                <a href="/admin/verifikasi.php">Verifikasi Toko</a>
                            <?php elseif ($user_level == 'seller'): ?>
                                <a href="/seller/dashboard.php">Dashboard Toko</a>
                                <a href="/seller/produk.php">Produk Saya</a>
                                <a href="/seller/pesanan.php">Pesanan Masuk</a>
                            <?php else: // Customer ?>
                                <a href="/customer/profil.php">Profil Saya</a>
                                <a href="/customer/pesanan.php">Pesanan Saya</a>
                            <?php endif; ?>
                            <a href="/auth/logout.php" class="logout-link">Keluar</a>
                        </div>
                    </div>
                <?php else: // Pengguna adalah tamu (guest) ?>
                    <a href="/auth/login_customer.php" class="btn btn-secondary">Masuk</a>
                    <a href="auth/register_customer.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>
            
            <div class="hamburger-menu">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </div>
</header>