<?php
// Ganti path ini sesuai dengan struktur folder Anda
include '../../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Ambil semua data untuk filter
$kategori_list_query = $koneksi->query("SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");

// [PERBAIKAN 1] Join ke tabel cities untuk ambil nama kota, bukan hanya ID
$lokasi_list_query = $koneksi->query("SELECT DISTINCT t.city_id, c.name as nama_kota 
                                      FROM tb_toko t 
                                      JOIN cities c ON t.city_id = c.id 
                                      WHERE t.status = 'active' 
                                      ORDER BY c.name ASC");

// 2. Tangkap filter dari URL
$filter_kategori = isset($_GET['kategori']) && is_array($_GET['kategori']) ? $_GET['kategori'] : [];

// Handling Lokasi
$raw_lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : [];
if (!is_array($raw_lokasi) && !empty($raw_lokasi)) {
    $filter_lokasi = [$raw_lokasi];
} elseif (is_array($raw_lokasi)) {
    $filter_lokasi = $raw_lokasi;
} else {
    $filter_lokasi = [];
}

$filter_harga_min = filter_input(INPUT_GET, 'harga_min', FILTER_VALIDATE_INT);
$filter_harga_max = filter_input(INPUT_GET, 'harga_max', FILTER_VALIDATE_INT);

// 3. Bangun query SQL dasar
// [PERBAIKAN 2] Join ke cities (c) untuk ambil nama_kota di query utama
$sql_barang = "SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, c.name as nama_kota
                FROM tb_barang b
                JOIN tb_toko t ON b.toko_id = t.id
                LEFT JOIN cities c ON t.city_id = c.id
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
    // Filter tetap menggunakan city_id (angka) karena lebih akurat
    $placeholders = implode(',', array_fill(0, count($filter_lokasi), '?'));
    $sql_barang .= " AND t.city_id IN ($placeholders)";
    $types .= str_repeat('i', count($filter_lokasi)); // city_id biasanya integer
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
    <link rel="stylesheet" href="../../assets/css/produk_page_style.css">
    <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>

<?php include 'partials/navbar.php'; ?>

<div class="filter-overlay" id="filter-overlay"></div>
<div class="page-container">
    <aside class="sidebar-filters" id="sidebar-filters">
        <form action="produk.php" method="GET">
            <div class="filter-header">
                <span><i class="mdi mdi-filter-variant"></i> FILTER</span>
                <button type="button" class="close-filter-btn" id="close-filter-btn">&times;</button>
            </div>

            <!-- FILTER KATEGORI -->
            <div class="filter-group">
                <h4 class="filter-title">KATEGORI</h4>
                <div class="category-list">
                    <?php 
                    $counter = 0;
                    $limit = 7;
                    $kategori_list_query->data_seek(0); 
                    while ($k = $kategori_list_query->fetch_assoc()): 
                        $counter++;
                        $isChecked = in_array($k['id'], $filter_kategori) ? 'checked' : '';
                        $hiddenClass = ($counter > $limit) ? 'hidden-category' : '';
                    ?>
                        <label class="filter-option <?= $hiddenClass ?>">
                            <input type="checkbox" name="kategori[]" value="<?= $k['id'] ?>" <?= $isChecked ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </label>
                    <?php endwhile; ?>
                    
                    <?php if ($counter > $limit): ?>
                        <div class="show-more-container">
                            <button type="button" id="toggle-categories" class="btn-show-more">
                                Lihat Selengkapnya <i class="mdi mdi-chevron-down"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FILTER LOKASI (Dropdown Select) -->
            <div class="filter-group">
                <h4 class="filter-title">LOKASI TOKO</h4>
                <div class="location-select-wrapper">
                    <select name="lokasi" class="filter-select">
                        <option value="">Semua Lokasi</option>
                        <?php 
                        $lokasi_list_query->data_seek(0);
                        while ($l = $lokasi_list_query->fetch_assoc()): 
                            // Value tetap ID, tapi teks yang ditampilkan adalah Nama Kota
                            $isSelected = (in_array($l['city_id'], $filter_lokasi)) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($l['city_id']) ?>" <?= $isSelected ?>>
                                <?= htmlspecialchars($l['nama_kota']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <i class="mdi mdi-chevron-down select-icon"></i>
                </div>
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
                                    <span class="store-name"><i class="mdi mdi-store"></i> <?= htmlspecialchars($b['nama_toko']) ?></span>
                                    <!-- [PERBAIKAN 3] Tampilkan nama kota dari hasil join -->
                                    <span class="store-location"><i class="mdi mdi-map-marker"></i> <?= htmlspecialchars($b['nama_kota'] ?? 'Lokasi Tidak Tersedia') ?></span>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Filter Logic
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

        // Show More Categories Logic
        const toggleBtn = document.getElementById('toggle-categories');
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const hiddenItems = document.querySelectorAll('.hidden-category');
                const isExpanded = toggleBtn.classList.contains('expanded');

                hiddenItems.forEach(item => {
                    item.style.display = isExpanded ? 'none' : 'flex';
                });

                if (!isExpanded) {
                    toggleBtn.innerHTML = 'Sembunyikan <i class="mdi mdi-chevron-up"></i>';
                    toggleBtn.classList.add('expanded');
                } else {
                    toggleBtn.innerHTML = 'Lihat Selengkapnya <i class="mdi mdi-chevron-down"></i>';
                    toggleBtn.classList.remove('expanded');
                }
            });
        }
    });
</script>

</body>
</html>