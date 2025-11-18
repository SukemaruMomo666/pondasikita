<?php
// Ganti path ini sesuai dengan struktur folder Anda
include '../../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Ambil semua data untuk filter
$kategori_list_query = $koneksi->query("SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");
$lokasi_list_query = $koneksi->query("SELECT DISTINCT city_id FROM tb_toko WHERE status = 'active' ORDER BY city_id ASC");

// 2. Tangkap filter dari URL
$filter_kategori = isset($_GET['kategori']) && is_array($_GET['kategori']) ? $_GET['kategori'] : [];
$filter_lokasi = isset($_GET['lokasi']) && is_array($_GET['lokasi']) ? $_GET['lokasi'] : [];
$filter_harga_min = filter_input(INPUT_GET, 'harga_min', FILTER_VALIDATE_INT);
$filter_harga_max = filter_input(INPUT_GET, 'harga_max', FILTER_VALIDATE_INT);

// 3. Bangun query SQL dasar
$sql_barang = "SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.city_id
               FROM tb_barang b
               JOIN tb_toko t ON b.toko_id = t.id
               WHERE b.is_active = 1 AND b.status_moderasi = 'approved' AND t.status = 'active'";

$params = [];
$types = '';

// 4. Tambahkan kondisi filter dinamis
if (!empty($filter_kategori)) {
    $placeholders = implode(',', array_fill(0, count($filter_kategori), '?'));
    $sql_barang .= " AND b.kategori_id IN ($placeholders)";
    $types .= str_repeat('i', count($filter_kategori));
    $params = array_merge($params, $filter_kategori);
}
if (!empty($filter_lokasi)) {
    $placeholders = implode(',', array_fill(0, count($filter_lokasi), '?'));
    $sql_barang .= " AND t.city_id IN ($placeholders)";
    $types .= str_repeat('s', count($filter_lokasi));
    $params = array_merge($params, $filter_lokasi);
}
if ($filter_harga_min) {
    $sql_barang .= " AND b.harga >= ?"; $types .= 'i'; $params[] = $filter_harga_min;
}
if ($filter_harga_max) {
    $sql_barang .= " AND b.harga <= ?"; $types .= 'i'; $params[] = $filter_harga_max;
}

// 5. Eksekusi query
$stmt = $koneksi->prepare($sql_barang);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $barang_result = $stmt->get_result();
} else {
    die("Error preparing statement: " . $koneksi->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jelajahi Produk - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <link rel="stylesheet" href="../../assets/css/navbar_style.css">
    <!-- PERUBAHAN: Link ke file CSS baru -->
    <link rel="stylesheet" href="../../assets/css/produk_page_style.css">
    <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>

<?php include 'partials/navbar.php'; // Path disesuaikan ?>

<div class="filter-overlay" id="filter-overlay"></div>
<div class="page-container">
    <aside class="sidebar-filters" id="sidebar-filters">
        <form action="produk.php" method="GET">
            <div class="filter-header">
                <span><i class="mdi mdi-filter-variant"></i> FILTER</span>
                <button type="button" class="close-filter-btn" id="close-filter-btn">&times;</button>
            </div>

            <div class="filter-group">
                <h4 class="filter-title">KATEGORI</h4>
                <?php $kategori_list_query->data_seek(0); while ($k = $kategori_list_query->fetch_assoc()): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="kategori[]" value="<?= $k['id'] ?>" <?= (in_array($k['id'], $filter_kategori) ? 'checked' : '') ?>>
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </label>
                <?php endwhile; ?>
            </div>

            <div class="filter-group">
                <h4 class="filter-title">LOKASI TOKO</h4>
                <?php while ($l = $lokasi_list_query->fetch_assoc()): ?>
                    <label class="filter-option">
                        <input type="checkbox" name="lokasi[]" value="<?= $l['city_id'] ?>" <?= (in_array($l['city_id'], $filter_lokasi) ? 'checked' : '') ?>>
                        <?= htmlspecialchars($l['city_id']) ?>
                    </label>
                <?php endwhile; ?>
            </div>

            <div class="filter-group">
                <h4 class="filter-title">HARGA</h4>
                <div class="filter-harga">
                    <input type="number" name="harga_min" placeholder="Rp Minimum" value="<?= htmlspecialchars($filter_harga_min ?? '') ?>">
                    <input type="number" name="harga_max" placeholder="Rp Maksimum" value="<?= htmlspecialchars($filter_harga_max ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="apply-filter-btn">Terapkan Filter</button>
        </form>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <span>Menampilkan <strong><?= $barang_result->num_rows ?> produk</strong> ditemukan.</span>
        </div>

        <div class="mobile-action-bar">
            <button class="mobile-btn" id="mobile-filter-btn"><i class="mdi mdi-filter-variant"></i> Filter</button>
        </div>

        <div class="products-grid">
            <?php if ($barang_result && $barang_result->num_rows > 0): ?>
                <?php while($b = $barang_result->fetch_assoc()): ?>
                    <a href="detail_produk.php?id=<?= $b['id'] ?>" class="product-link">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="/assets/uploads/products/<?= htmlspecialchars($b['gambar_utama'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($b['nama_barang']) ?>">
                            </div>
                            <div class="product-details">
                                <h3><?= htmlspecialchars($b['nama_barang']) ?></h3>
                                <p class="price">Rp<?= number_format($b['harga'], 0, ',', '.') ?></p>
                                <div class="product-seller-info">
                                    <!-- PERUBAHAN: Ikon diubah ke MDI -->
                                    <span class="store-name"><i class="mdi mdi-store"></i> <?= htmlspecialchars($b['nama_toko']) ?></span>
                                    <span class="store-location"><i class="mdi mdi-map-marker"></i> <?= htmlspecialchars($b['city_id']) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>Oops! Produk tidak ditemukan.</h3>
                    <p>Coba ubah kata kunci atau filter pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="/assets/js/navbar.js"></script>
<script>
    // JavaScript untuk filter mobile
    document.addEventListener('DOMContentLoaded', function() {
        const mobileFilterBtn = document.getElementById('mobile-filter-btn');
        const sidebarFilters = document.getElementById('sidebar-filters');
        const closeFilterBtn = document.getElementById('close-filter-btn');
        const filterOverlay = document.getElementById('filter-overlay');
        
        if (mobileFilterBtn) {
            mobileFilterBtn.addEventListener('click', () => {
                sidebarFilters.classList.add('active');
                filterOverlay.classList.add('active');
            });
        }
        const hideFilter = () => {
            sidebarFilters.classList.remove('active');
            filterOverlay.classList.remove('active');
        };
        if (closeFilterBtn) closeFilterBtn.addEventListener('click', hideFilter);
        if (filterOverlay) filterOverlay.addEventListener('click', hideFilter);
    });
</script>

</body>
</html>
