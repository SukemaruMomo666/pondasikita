<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/koneksi.php';

// --- AMBIL DATA UNTUK FILTER ---
// Ambil semua city_id unik dari toko yang aktif DAN gabungkan dengan tabel cities untuk mendapatkan nama kota
$lokasi_list_query = $koneksi->query("
    SELECT DISTINCT t.city_id, c.name AS city_name
    FROM tb_toko t
    INNER JOIN cities c ON t.city_id = c.id
    WHERE t.status = 'active' AND t.city_id IS NOT NULL
    ORDER BY c.name ASC
");

// --- LOGIKA FILTER ---
$filter_lokasi = $_GET['lokasi'] ?? 'semua';

// --- QUERY PENGAMBILAN DATA TOKO ---
// Query dasar untuk mengambil semua toko yang aktif
$sql_toko = "SELECT t.id, t.nama_toko, t.slug, t.deskripsi_toko, t.logo_toko, t.banner_toko, t.city_id, c.name as city_name,
                     (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk,
                     (SELECT AVG(rating) FROM tb_toko_review WHERE toko_id = t.id) as rating
              FROM tb_toko t
              INNER JOIN cities c ON t.city_id = c.id -- Join with cities table to get city name for display
              WHERE t.status = 'active'";

$params = [];
$types = '';

// Tambahkan kondisi WHERE jika filter lokasi dipilih
if ($filter_lokasi !== 'semua' && !empty($filter_lokasi)) {
    $sql_toko .= " AND t.city_id = ?"; // Filter based on city_id
    $params[] = $filter_lokasi;
    $types .= 'i'; // Change type to 'i' for integer city_id
}
$sql_toko .= " ORDER BY t.nama_toko ASC";

$stmt_toko = $koneksi->prepare($sql_toko);
if (!empty($params)) {
    $stmt_toko->bind_param($types, ...$params);
}
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jelajahi Toko - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <link rel="stylesheet" href="../../assets/css/navbar_style.css">
    <link rel="stylesheet" href="../../assets/css/toko_page_style.css">
    <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>

<?php include 'partials/navbar.php'; ?>

<div class="page-container">
    <main class="main-content">
        <div class="page-header">
            <h3>Temukan Toko Bahan Bangunan Terbaik</h3>
            <p>Jelajahi ribuan toko terpercaya dari seluruh Indonesia.</p>
        </div>

        <div class="filter-bar">
            <span class="filter-title">Filter Lokasi:</span>
            <form action="semua_toko.php" method="GET" class="d-flex gap-2">
                <select name="lokasi" class="form-select">
                    <option value="semua">Semua Kota</option>
                    <?php while ($lokasi = $lokasi_list_query->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($lokasi['city_id']) ?>" <?= ($filter_lokasi == $lokasi['city_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lokasi['city_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">Terapkan</button>
            </form>
        </div>

        <div class="shops-grid">
            <?php if ($result_toko && $result_toko->num_rows > 0): ?>
                <?php while($toko = $result_toko->fetch_assoc()): ?>
                    <a href="toko.php?slug=<?= $toko['slug'] ?>" class="shop-card">
                        <div class="shop-banner" style="background-image: url('../../assets/uploads/banners/<?= htmlspecialchars($toko['banner_toko'] ?? 'default-banner.jpg') ?>');">
                            <div class="shop-logo-wrapper">
                                <img src="../../assets/uploads/logos/<?= htmlspecialchars($toko['logo_toko'] ?? 'default-logo.png') ?>" alt="Logo <?= htmlspecialchars($toko['nama_toko']) ?>" class="shop-logo">
                            </div>
                        </div>
                        <div class="shop-info">
                            <h4 class="shop-name"><?= htmlspecialchars($toko['nama_toko']) ?></h4>
                            <p class="shop-location"><i class="mdi mdi-map-marker"></i> <?= htmlspecialchars($toko['city_name']) ?></p>
                            <div class="shop-stats">
                                <div class="stat-item"><span><?= number_format($toko['jumlah_produk']) ?></span> Produk</div>
                                <div class="stat-item"><i class="mdi mdi-star text-warning"></i> <span><?= number_format($toko['rating'] ?? 0, 1) ?></span> Rating</div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="mdi mdi-store-search-outline"></i>
                        <p>Oops! Tidak ada toko yang ditemukan di lokasi ini.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="/assets/js/navbar.js"></script>
</body>
</html>