<?php
// Pastikan session dimulai.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil koneksi database
include_once __DIR__ . '/../../config/koneksi.php';

// Ambil ID pengguna dari session.
$id_user = $_SESSION['user']['id'] ?? null;
$total_item = 0;

if ($id_user && isset($koneksi)) {
    $stmt = $koneksi->prepare("SELECT SUM(jumlah) as total FROM tb_keranjang WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $data = $result->fetch_assoc();
            $total_item = $data['total'] ?? 0;
        }
        $stmt->close();
    }
} else {
    if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
        // Jika keranjang disimpan sebagai array asosiatif (product_id => jumlah)
        // atau sebagai array of arrays dengan kunci 'jumlah'
        foreach ($_SESSION['keranjang'] as $item) {
            if (is_array($item) && isset($item['jumlah'])) {
                $total_item += $item['jumlah'];
            } else if (is_numeric($item)) { // Jika item langsung jumlah (misal: ['prod_id' => 2])
                $total_item += $item;
            }
        }
    }
}
?>

<link rel="stylesheet" href="../../assets/css/navbar.css">
<script src="https://kit.fontawesome.com/GANTI_DENGAN_ID_ANDA.js" crossorigin="anonymous"></script>

<nav>
    <div class="navbar-container">
        <div class="navbar-logo">
            <a href="../../index.php"><h1>Toko Bangunan Agung Jaya</h1></a>
        </div>

        <form method="GET" action="../search.php" class="search-bar">
            <input class="Desktop-view" type="text" name="query" placeholder="Cari di Toko Bangunan Tiga Daya..." required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <div class="hamburger" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>

        <ul class="nav-links" id="navLinks">
            <li><a href="../../index.php">Home</a></li>
            <li class="dropdown">
                <a class="nav-link dropdown-toggle" href="#">Tentang Kami ▾</a>
                <ul class="dropdown-content">
                    <li><a class="dropdown-item" href="../Tentang_kami/profil_toko.php#profil">Profil Toko</a></li>
                    <li><a class="dropdown-item" href="../Tentang_kami/profil_toko.php#visi-misi">Visi & Misi</a></li>
                    <li><a class="dropdown-item" href="../Tentang_kami/profil_toko.php#lokasi">Lokasi & Layanan</a></li>
                    <li><a class="dropdown-item" href="../Tentang_kami/profil_toko.php#kontak">Kontak Kami</a></li>
                </ul>
            </li>
            <li><a href="../produk.php">Daftar Barang</a></li>
            <li>
                <a href="../keranjang.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count" class="cart-badge"><?= (int)$total_item ?></span>
                </a>
            </li>

            <?php if (isset($_SESSION['user'])): ?>
                
                <?php // -- KONDISI DIPERBAIKI DI SINI -- ?>
                <?php if (isset($_SESSION['user']['level']) && $_SESSION['user']['level'] === 'admin'): ?>
                    
                    <li class="dropdown">
                        <a href="#">Hi, <?= htmlspecialchars($_SESSION['user']['username']) ?> ▾</a>
                        <div class="dropdown-content">
                            <a href="../profil.php">Profil</a>
                            <a href="../../admin/kelola_data/data_barang/kelola_data_barang.php">Data Master</a>
                            <a href="../../admin/transaksi.php">Kelola Pesanan</a>
                            <a href="../../auth/logout.php?action=logout">Logout</a>
                        </div>
                    </li>

                <?php else: ?>

                    <li class="dropdown">
                        <a href="#">Hi, <?= htmlspecialchars($_SESSION['user']['username']) ?> ▾</a>
                        <div class="dropdown-content">
                            <a href="../profil.php">Profil</a>
                            <a href="../pesanan_customer.php">Pesanan Saya</a>
                            <a href="../keranjang.php">Keranjang</a>
                            <a href="../../auth/logout.php?action=logout">Logout</a>
                        </div>
                    </li>

                <?php endif; ?>

            <?php else: ?>
                <li class="dropdown">
                    <a href="#">Authentifikasi ▾</a>
                    <div class="dropdown-content">
                        <a href="../../auth/signin.php">Login</a>
                        <a href="../../auth/signup.php">Register</a>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
function toggleMenu() {
    document.getElementById("navLinks").classList.toggle("active");
}
</script>   