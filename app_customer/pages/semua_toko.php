<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/koneksi.php';

// --- FUNGSI HELPER ---
function getStoreInitials($nama_toko) {
    if (empty($nama_toko)) return "TK";
    $words = explode(" ", $nama_toko);
    $acronym = "";
    foreach ($words as $w) {
        $acronym .= mb_substr($w, 0, 1);
    }
    return strtoupper(substr($acronym, 0, 2));
}

function getStoreColor($nama_toko) {
    $colors = ['#e53935', '#d81b60', '#8e24aa', '#5e35b1', '#3949ab', '#1e88e5', '#039be5', '#00acc1', '#00897b', '#43a047', '#7cb342', '#c0ca33', '#fdd835', '#ffb300', '#fb8c00', '#f4511e'];
    $index = crc32($nama_toko) % count($colors);
    return $colors[$index];
}

// --- AMBIL DATA FILTER ---
$lokasi_list_query = $koneksi->query("
    SELECT DISTINCT t.city_id, c.name AS city_name
    FROM tb_toko t
    INNER JOIN cities c ON t.city_id = c.id
    WHERE t.status = 'active' AND t.city_id IS NOT NULL
    ORDER BY c.name ASC
");

// --- LOGIKA FILTER ---
$filter_lokasi = $_GET['lokasi'] ?? 'semua';

// --- QUERY TOKO ---
$sql_toko = "SELECT t.id, t.nama_toko, t.slug, t.deskripsi_toko, t.logo_toko, t.banner_toko, t.city_id, c.name as city_name,
             (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk,
             (SELECT AVG(rating) FROM tb_toko_review WHERE toko_id = t.id) as rating
             FROM tb_toko t
             INNER JOIN cities c ON t.city_id = c.id 
             WHERE t.status = 'active'";

$params = [];
$types = '';

if ($filter_lokasi !== 'semua' && !empty($filter_lokasi)) {
    $sql_toko .= " AND t.city_id = ?";
    $params[] = $filter_lokasi;
    $types .= 'i';
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
    <!-- Link ke CSS Baru -->
    <link rel="stylesheet" href="../../assets/css/toko_page.css">
    <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>

<?php require_once __DIR__ . '/partials/navbar.php'; ?>

<div class="page-container">
    <main class="main-content">
        <div class="page-header">
            <h3>Temukan Toko Bahan Bangunan Terbaik</h3>
            <p>Jelajahi ribuan toko terpercaya dari seluruh Indonesia.</p>
        </div>

        <div class="filter-bar">
            <span class="filter-title"><i class="mdi mdi-filter-variant"></i> Filter Lokasi:</span>
            <form action="semua_toko.php" method="GET" class="d-flex gap-2" style="flex-grow: 1; max-width: 400px;">
                <select name="lokasi" class="form-select">
                    <option value="semua">Semua Kota</option>
                    <?php 
                    $lokasi_list_query->data_seek(0);
                    while ($lokasi = $lokasi_list_query->fetch_assoc()): 
                    ?>
                        <option value="<?= htmlspecialchars($lokasi['city_id']) ?>" <?= ($filter_lokasi == $lokasi['city_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lokasi['city_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-primary">Terapkan</button>
            </form>
        </div>

        <div class="shops-grid">
            <?php if ($result_toko && $result_toko->num_rows > 0): ?>
                <?php while($toko = $result_toko->fetch_assoc()): 
                    // Setup Warna & Inisial
                    $storeColor = getStoreColor($toko['nama_toko']);
                    $storeInitials = getStoreInitials($toko['nama_toko']);
                    
                    // Setup Banner
                    $bannerPath = '../../assets/uploads/banners/' . $toko['banner_toko'];
                    $hasBanner = !empty($toko['banner_toko']) && file_exists(__DIR__ . '/../' . $bannerPath);
                    $bannerStyle = $hasBanner 
                        ? "background-image: url('$bannerPath');" 
                        : "background-color: $storeColor; opacity: 0.9;";

                    // Setup Logo
                    $logoPath = '../../assets/uploads/logos/' . $toko['logo_toko'];
                    $hasLogo = !empty($toko['logo_toko']) && file_exists(__DIR__ . '/../' . $logoPath);
                ?>
                    <a href="toko.php?slug=<?= $toko['slug'] ?>" class="shop-card">
                        <!-- Banner -->
                        <div class="shop-banner" style="<?= $bannerStyle ?>">
                            <div class="shop-logo-wrapper">
                                <?php if ($hasLogo): ?>
                                    <img src="<?= $logoPath ?>" alt="<?= htmlspecialchars($toko['nama_toko']) ?>" class="shop-logo">
                                <?php else: ?>
                                    <!-- Inisial jika tidak ada logo -->
                                    <div class="store-logo-initial" style="background-color: <?= $storeColor ?>;">
                                        <?= $storeInitials ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Info -->
                        <div class="shop-info">
                            <h4 class="shop-name"><?= htmlspecialchars($toko['nama_toko']) ?></h4>
                            <p class="shop-location">
                                <i class="mdi mdi-map-marker text-warning"></i> <?= htmlspecialchars($toko['city_name']) ?>
                            </p>
                            <div class="shop-stats">
                                <div class="stat-item">
                                    <span><?= number_format($toko['jumlah_produk']) ?></span> Produk
                                </div>
                                <div class="stat-item">
                                    <i class="mdi mdi-star text-warning"></i> 
                                    <span><?= number_format($toko['rating'] ?? 0, 1) ?></span> Rating
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="mdi mdi-store-search-outline"></i>
                    <p>Oops! Tidak ada toko yang ditemukan di lokasi ini.</p>
                    <?php if($filter_lokasi !== 'semua'): ?>
                        <a href="semua_toko.php" class="btn-primary" style="text-decoration:none; font-size:0.9rem; margin-top:10px; display:inline-block;">Lihat Semua Kota</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="/assets/js/navbar.js"></script>
</body>
</html>