<?php
require_once '../config/koneksi.php'; // Sesuaikan path jika berbeda
session_start();

// Cek apakah pengguna sudah login
$is_logged_in = isset($_SESSION['user']['id']); // Menggunakan $_SESSION['user']['id'] untuk cek login
$nama_user = $is_logged_in ? $_SESSION['user']['nama'] : 'Tamu'; // Menggunakan $_SESSION['user']['nama']

$id_user = $is_logged_in ? $_SESSION['user']['id'] : null;
$customer_city_id = null;
$customer_district_id = null; // Tambahan untuk district_id

// Jika user login, coba ambil alamat utamanya untuk mendapatkan city_id dan district_id
if ($is_logged_in) {
    $stmt_alamat = $koneksi->prepare("
        SELECT city_id, district_id
        FROM tb_user_alamat
        WHERE user_id = ? AND is_utama = 1
    ");
    if ($stmt_alamat) {
        $stmt_alamat->bind_param("i", $id_user);
        $stmt_alamat->execute();
        $result_alamat = $stmt_alamat->get_result();
        if ($result_alamat->num_rows > 0) {
            $alamat_utama = $result_alamat->fetch_assoc();
            $customer_city_id = $alamat_utama['city_id'];
            $customer_district_id = $alamat_utama['district_id'];
        }
        $stmt_alamat->close();
    }
}

// Logika untuk query toko populer (bisa terdekat atau nasional)
$query_toko = "";
$toko_params = [];
$toko_types = "";
$toko_section_title = "Toko Populer Nasional"; // Default title

if ($customer_city_id) {
    // Jika ada city_id pelanggan, prioritaskan toko di kota yang sama atau distrik terdekat
    $query_toko = "
        SELECT 
            t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota,
            (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif
        FROM tb_toko t
        JOIN cities c ON t.city_id = c.id
        WHERE t.status = 'active' AND t.status_operasional = 'Buka' AND (t.city_id = ? OR t.district_id = ?)
        ORDER BY jumlah_produk_aktif DESC, t.nama_toko ASC
        LIMIT 4";
    $toko_params = [&$customer_city_id, &$customer_district_id];
    $toko_types = "ii";
    $toko_section_title = "Toko di Wilayah Anda"; // Ganti judul
} else {
    // Jika tidak ada city_id pelanggan, ambil toko populer nasional
    $query_toko = "
        SELECT 
            t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota,
            (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif
        FROM tb_toko t
        JOIN cities c ON t.city_id = c.id
        WHERE t.status = 'active' AND t.status_operasional = 'Buka'
        ORDER BY jumlah_produk_aktif DESC
        LIMIT 4";
}

$stmt_toko = $koneksi->prepare($query_toko);
if ($stmt_toko) {
    if (!empty($toko_params)) {
        $stmt_toko->bind_param($toko_types, ...$toko_params);
    }
    $stmt_toko->execute();
    $result_toko = $stmt_toko->get_result();
    $list_toko = [];
    while ($row = $result_toko->fetch_assoc()) {
        $list_toko[] = $row;
    }
    $stmt_toko->close();
} else {
    error_log("Failed to prepare statement for fetching stores: " . $koneksi->error);
    $list_toko = [];
}

// ==========================================================
// Logika untuk query Produk Terlaris Toko Terdekat
$list_produk_terlaris_lokal = [];
if ($customer_city_id) {
    $query_produk_lokal_sql = "
        SELECT
            b.id, b.nama_barang, b.harga, b.gambar_utama,
            t.nama_toko, t.slug AS slug_toko -- Pastikan t.slug juga diambil di sini
        FROM tb_barang b
        JOIN tb_toko t ON b.toko_id = t.id
        WHERE b.is_active = 1 AND b.status_moderasi = 'approved'
        AND (t.city_id = ? OR t.district_id = ?)
        ORDER BY (SELECT SUM(jumlah) FROM tb_detail_transaksi WHERE barang_id = b.id) DESC
        LIMIT 8";

    $stmt_produk_lokal = $koneksi->prepare($query_produk_lokal_sql);
    if ($stmt_produk_lokal) {
        $stmt_produk_lokal->bind_param("ii", $customer_city_id, $customer_district_id);
        $stmt_produk_lokal->execute();
        $result_produk_lokal = $stmt_produk_lokal->get_result();
        while ($row = $result_produk_lokal->fetch_assoc()) {
            $list_produk_terlaris_lokal[] = $row;
        }
        $stmt_produk_lokal->close();
    } else {
        error_log("Failed to prepare statement for fetching local popular products: " . $koneksi->error);
    }
}
// ==========================================================

// Cek apakah mode hyperlocal aktif dari parameter URL (ini tidak dipakai lagi, tapi biarkan saja dulu)
$is_hyperlocal_mode = isset($_GET['lat']) && isset($_GET['lon']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondasikita - Marketplace Bahan Bangunan Terlengkap</title>
    
    <link rel="stylesheet" type="text/css" href="../assets/css/theme.css"> 
    <link rel="stylesheet" type="text/css" href="../assets/css/navbar_style.css"> 
    <link rel="stylesheet" type="text/css" href="../assets/css/livechat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- HAPUS PEMANGGILAN SCRIPT livechat.js DI SINI -->
    </head>
<body>
    
    <?php include './partials/navbar.php'; ?>

    <section class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h2>Cari Bahan Bangunan?</h2>
                <h3>Temukan semua kebutuhan proyek Anda dari toko-toko terpercaya di seluruh Indonesia.</h3>
                <a href="pages/produk.php" class="btn-primary">Jelajahi Produk</a>
            </div>
        </div>
    </section>

    <main class="main-content">
        <div class="container">
            
            <section class="categories">
                <h2 class="section-title"><span>Kategori Populer</span></h2>
                <div class="category-grid">
                    <?php
                    $kategori_query = mysqli_query($koneksi, "SELECT * FROM tb_kategori LIMIT 8");
                    if ($kategori_query && mysqli_num_rows($kategori_query) > 0) {
                        while ($row = mysqli_fetch_assoc($kategori_query)) {
                            echo '
                            <a href="pages/produk.php?kategori=' . $row['id'] . '" class="category-item">
                                <div class="category-icon"><i class="' . htmlspecialchars($row['icon_class'] ?? 'fas fa-tools') . '"></i></div>
                                <p>' . htmlspecialchars($row['nama_kategori'] ?? '') . '</p>
                            </a>';
                        }
                    } else {
                        echo "<p>Tidak ada kategori untuk ditampilkan.</p>";
                    }
                    ?>
                </div>
            </section>

            <section class="featured-stores">
                <div class="section-header">
                    <h2 class="section-title"><span><?= htmlspecialchars($toko_section_title) ?></span></h2>
                    <a href="pages/semua_toko.php" class="see-all">Lihat Semua Toko <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="store-grid">
                    <?php
                    if (!empty($list_toko)) {
                        foreach ($list_toko as $toko) {
                            $logo_path = !empty($toko['logo_toko']) ? '/assets/uploads/logos/' . htmlspecialchars($toko['logo_toko']) : '/assets/images/default-store-logo.png';
                            $banner_style = !empty($toko['banner_toko']) ? 'background-image: url(/assets/uploads/banners/' . htmlspecialchars($toko['banner_toko']) . ');' : 'background-color: #f0f0f0;';
                            $jarak_display = isset($toko['jarak_km']) ? '~' . number_format($toko['jarak_km'], 1) . ' km' : '';

                            echo '
                            <a href="pages/toko.php?slug=' . htmlspecialchars($toko['slug']) . '" class="store-card">
                                <div class="store-banner" style="' . $banner_style . '"></div>
                                <div class="store-info">
                                    <img src="' . $logo_path . '" alt="Logo ' . htmlspecialchars($toko['nama_toko']) . '" class="store-logo">
                                    <h4>' . htmlspecialchars($toko['nama_toko']) . '</h4>
                                    <p><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($toko['kota']) . ' ' . $jarak_display . '</p>
                                    <p class="product-count">' . htmlspecialchars($toko['jumlah_produk_aktif']) . ' Produk</p>
                                </div>
                            </a>';
                        }
                    } else {
                        echo "<p>Belum ada toko yang tersedia di wilayah Anda atau secara nasional.</p>";
                    }
                    ?>
                </div>
            </section>

            <?php if (!empty($list_produk_terlaris_lokal)): ?>
            <section class="products">
                <div class="section-header">
                    <h2 class="section-title"><span>Produk Terlaris di Wilayah Anda</span></h2>
                    <a href="pages/produk.php?city_id=<?= htmlspecialchars($customer_city_id) ?>" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    <?php foreach ($list_produk_terlaris_lokal as $row): ?>
                        <?php
                        $gambar_produk_path = !empty($row['gambar_utama']) ? '/assets/uploads/products/' . htmlspecialchars($row['gambar_utama']) : '/assets/uploads/products/default.jpg';
                        ?>
                        <a href="pages/detail_produk.php?id=<?= htmlspecialchars($row['id']) ?>&toko_slug=<?= htmlspecialchars($row['slug_toko']) ?>" class="product-link">
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?= $gambar_produk_path ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>" onerror="this.onerror=null; this.src='/assets/uploads/products/default.jpg';">
                                </div>
                                <div class="product-details">
                                    <h3><?= htmlspecialchars(substr($row['nama_barang'], 0, 45)) . (strlen($row['nama_barang']) > 45 ? '...' : '') ?></h3>
                                    <p class="price">Rp<?= number_format($row['harga'], 0, ',', '.') ?></p>
                                    <div class="product-store-info">
                                        <i class="fas fa-store-alt"></i>
                                        <span><?= htmlspecialchars($row['nama_toko']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section id="nasional-content" class="products">
                <div class="section-header">
                    <h2 class="section-title"><span>Produk Terlaris Nasional</span></h2>
                    <a href="pages/produk.php" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                <?php
                    $query_terlaris_sql = "
                        SELECT 
                            b.id, b.nama_barang, b.harga, b.gambar_utama,
                            t.nama_toko, t.slug AS slug_toko
                        FROM tb_barang b
                        JOIN tb_toko t ON b.toko_id = t.id
                        WHERE b.is_active = 1 AND b.status_moderasi = 'approved'
                        ORDER BY (SELECT SUM(jumlah) FROM tb_detail_transaksi WHERE barang_id = b.id) DESC
                        LIMIT 8";
                    
                    $result_terlaris = $koneksi->query($query_terlaris_sql);
                    
                    if ($result_terlaris && $result_terlaris->num_rows > 0) {
                        while ($row = $result_terlaris->fetch_assoc()) {
                            // Periksa apakah gambar_utama ada dan valid, jika tidak pakai default
                            $gambar_produk_path = !empty($row['gambar_utama']) ? '/assets/uploads/products/' . htmlspecialchars($row['gambar_utama']) : '/assets/uploads/products/default.jpg';

                            echo '
                            <a href="pages/detail_produk.php?id=' . $row['id'] . '&toko_slug=' . htmlspecialchars($row['slug_toko']) . '" class="product-link">
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="' . $gambar_produk_path . '" alt="' . htmlspecialchars($row['nama_barang']) . '" onerror="this.onerror=null; this.src=\'/assets/uploads/products/default.jpg\';">
                                    </div>
                                    <div class="product-details">
                                        <h3>' . htmlspecialchars(substr($row['nama_barang'], 0, 45)) . (strlen($row['nama_barang']) > 45 ? '...' : '') . '</h3>
                                        <p class="price">Rp' . number_format($row['harga'], 0, ',', '.') . '</p>
                                        <div class="product-store-info">
                                            <i class="fas fa-store-alt"></i>
                                            <span>' . htmlspecialchars($row['nama_toko']) . '</span>
                                        </div>
                                    </div>
                                </div>
                            </a>';
                        }
                    } else {
                        echo "<p>Belum ada produk terlaris saat ini.</p>";
                    }
                ?>
                </div>
            </section>
        </div>
    </main>
    <?php include 'partials/footer.php'; ?>
    <script src="/assets/js/navbar.js"></script>
    <!-- PANGGIL livechat.js HANYA SATU KALI DI SINI -->
    <script src="/assets/js/livechat.js"></script> 

    <button id="live-chat-toggle" class="live-chat-toggle">
        <i class="fas fa-comment"></i>
        <span class="chat-toggle-text">Live Chat</span>
    </button>
    
    <div id="live-chat-window" class="live-chat-window">
        <div class="chat-header">
            <span id="chat-header-title">Live Chat</span>
            <span id="agent-status" class="status-indicator"></span>
            <button id="close-chat" class="close-chat-btn"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-messages" id="chat-messages">
            <div class="chat-message bot">Halo! Selamat datang di Pondasikita. Ada yang bisa kami bantu?</div>
        </div>
        <div class="chat-input-area">
            <span id="typing-indicator" class="typing-indicator"></span>
            <input type="text" id="chat-input" placeholder="Ketik pesan Anda...">
            <button id="send-chat-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
        <div class="chat-pre-chat-form" id="pre-chat-form">
            <h3>Mulai Percakapan</h3>
            <p>Silakan isi nama dan email Anda untuk memulai chat.</p>
            <input type="text" id="pre-chat-name" placeholder="Nama Anda" required>
            <input type="email" id="pre-chat-email" placeholder="Email Anda" required>
            <button id="start-chat-btn">Mulai Chat</button>
        </div>
    </div>
</body>
</html>