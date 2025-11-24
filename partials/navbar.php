<?php
// 1. Session & Config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gunakan __DIR__ biar path-nya absolut & aman (mundur 1 langkah dari partials ke root)
// Sesuaikan jika file ini ada di folder lain
if (file_exists(__DIR__ . '/../../config/koneksi.php')) {
    require_once __DIR__ . '/../../config/koneksi.php';
} elseif (file_exists(__DIR__ . '/../config/koneksi.php')) {
    require_once __DIR__ . '/../config/koneksi.php';
}

// 2. Cek User Login
$is_logged_in = isset($_SESSION['user']['id']); // Sesuaikan dengan session login kamu
$user_level = $is_logged_in ? ($_SESSION['user']['level'] ?? 'customer') : 'guest';
$user_name = $is_logged_in ? ($_SESSION['user']['nama'] ?? 'User') : '';
$user_id = $is_logged_in ? $_SESSION['user']['id'] : null;

// 3. Hitung Keranjang (Khusus Customer)
$total_item_keranjang = 0;
if ($is_logged_in && $user_level === 'customer') {
    // Pastikan tabel keranjang & kolomnya benar
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

<nav class="navbar">
    <div class="navbar-container">
        
        <div class="navbar-brand">
            <a href="/index.php" style="text-decoration: none;">
                <h3 style="margin: 0; color: #5e1914; font-weight: 800;">Pondasikita</h3>
            </a>
        </div>

        <div class="navbar-search">
            <form action="/app_customer/pages/search.php" method="GET" style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 20px; padding: 5px 15px; background: #f9f9f9;">
                <input type="text" name="keyword" placeholder="Cari bahan bangunan..." style="border: none; background: transparent; outline: none; width: 250px; padding: 5px;">
                <button type="submit" style="border: none; background: transparent; cursor: pointer; color: #5e1914;">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <div class="navbar-menu">
            <ul class="nav-links">
                <li><a href="/app_customer/pages/produk.php">Produk</a></li>
                <li><a href="/app_customer/pages/toko.php">Toko</a></li>
            </ul>

            <div class="nav-icons">
                <a href="/app_customer/pages/keranjang.php" class="icon-btn" style="position: relative;">
                    <i class="fas fa-shopping-cart" style="font-size: 1.2rem; color: #333;"></i>
                    <?php if ($total_item_keranjang > 0): ?>
                        <span style="position: absolute; top: -8px; right: -10px; background: #dc3545; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 50%;">
                            <?= $total_item_keranjang ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <div class="dropdown" style="position: relative; display: inline-block;">
                        <a href="/app_customer/pages/profil.php" class="icon-btn">
                            <i class="fas fa-user-circle" style="font-size: 1.4rem; color: #007bff;"></i>
                        </a>
                        </div>
                    <a href="/auth/logout.php" style="margin-left: 10px; color: #dc3545; font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                        Keluar
                    </a>
                <?php else: ?>
                    <div class="auth-buttons" style="margin-left: 15px;">
                        <a href="/auth/login_customer.php" style="margin-right: 10px; text-decoration: none; color: #333; font-weight: 600;">Masuk</a>
                        <a href="/auth/register_customer.php" style="text-decoration: none; background: #007bff; color: white; padding: 8px 15px; border-radius: 5px; font-weight: 600;">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</nav>