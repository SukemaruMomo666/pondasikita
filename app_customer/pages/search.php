<?php
// Ganti path ini sesuai dengan struktur folder Anda
// Menggunakan dirname(__DIR__) adalah cara yang lebih robust
include_once dirname(__DIR__) . '../../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Ambil semua kategori dari database untuk ditampilkan di filter
$kategori_list_query = $koneksi->query("SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");

// 2. Tangkap semua parameter filter dari URL
$search_query = $_GET['query'] ?? '';
$filter_kategori = $_GET['kategori'] ?? [];

// [BARU] Tangkap filter harga
$filter_harga_min = null;
if (isset($_GET['harga_min']) && $_GET['harga_min'] !== '') {
    $filter_harga_min = filter_input(INPUT_GET, 'harga_min', FILTER_VALIDATE_INT);
}

$filter_harga_max = null;
if (isset($_GET['harga_max']) && $_GET['harga_max'] !== '') {
    $filter_harga_max = filter_input(INPUT_GET, 'harga_max', FILTER_VALIDATE_INT);
}

// 3. Bangun query SQL dasar
$sql_barang = "SELECT b.*, k.nama_kategori
               FROM tb_barang b
               LEFT JOIN tb_kategori k ON b.kategori_id = k.id
               WHERE b.is_active = 1";

// [MODIFIKASI] Menggunakan array untuk menampung parameter dan tipe data untuk prepared statement
$params = [];
$types = '';

// 4. Tambahkan kondisi filter secara dinamis dan aman
if (!empty($search_query)) {
    // Tambahkan klausa LIKE untuk pencarian
    $sql_barang .= " AND b.nama_barang LIKE ?";
    // Tambahkan tipe 's' (string)
    $types .= 's';
    // Tambahkan parameter pencarian dengan wildcard
    $params[] = "%" . $search_query . "%";
}

if (!empty($filter_kategori) && is_array($filter_kategori)) {
    // Pastikan semua nilai adalah integer
    $kategori_ids = array_map('intval', $filter_kategori);
    if (!empty($kategori_ids)) {
        // Buat placeholder (?) sebanyak jumlah kategori
        $placeholders = implode(',', array_fill(0, count($kategori_ids), '?'));
        $sql_barang .= " AND b.kategori_id IN ($placeholders)";
        // Tambahkan tipe 'i' (integer) untuk setiap ID
        $types .= str_repeat('i', count($kategori_ids));
        // Gabungkan ID ke array parameter
        $params = array_merge($params, $kategori_ids);
    }
}

// [BARU] Filter Harga Minimum
if ($filter_harga_min !== null && $filter_harga_min !== false) {
    $sql_barang .= " AND b.harga >= ?";
    $types .= 'i'; // tipe integer
    $params[] = $filter_harga_min;
}

// [BARU] Filter Harga Maksimum
if ($filter_harga_max !== null && $filter_harga_max !== false) {
    $sql_barang .= " AND b.harga <= ?";
    $types .= 'i'; // tipe integer
    $params[] = $filter_harga_max;
}

// 5. Eksekusi query dengan cara yang aman (Prepared Statements)
$stmt = $koneksi->prepare($sql_barang);

if ($stmt) {
    // Jika ada parameter yang perlu di-bind
    if (!empty($params)) {
        // Bind parameter ke statement
        $stmt->bind_param($types, ...$params);
    }
    // Eksekusi statement
    $stmt->execute();
    // Ambil hasilnya
    $barang_result = $stmt->get_result();
} else {
    // Jika persiapan query gagal, tampilkan error (untuk debugging)
    die("Error preparing statement: " . $koneksi->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Pencarian - Toko Bangunan Tiga Daya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/produk.css">
        <link rel="stylesheet" href="../../assets/css/theme.css">
    <link rel="stylesheet" href="../../assets/css/navbar_style.css">
    <link rel="stylesheet" href="../../assets/css/toko_page_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-harga { display: flex; flex-direction: column; gap: 10px; }
        .filter-harga .harga-input-group { display: flex; align-items: center; gap: 8px; }
        .filter-harga label { flex-basis: 80px; font-size: 0.9em; }
        .filter-harga input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; }
        .filter-harga input[type=number]::-webkit-inner-spin-button,
        .filter-harga input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .filter-harga input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body>

<?php include('partials/navbar.php'); ?>

<div class="filter-overlay" id="filter-overlay"></div>

<div class="page-container">

    <aside class="sidebar-filters" id="sidebar-filters">
        <form action="search.php" method="GET">
            <input type="hidden" name="query" value="<?= htmlspecialchars($search_query) ?>">

            <div class="filter-header">
                <span>FILTER</span>
                <button type="button" class="close-filter-btn" id="close-filter-btn">×</button>
            </div>

            <div class="filter-group">
                <h4 class="filter-title">KATEGORI</h4>
                <?php
                if ($kategori_list_query && $kategori_list_query->num_rows > 0) {
                    $kategori_list_query->data_seek(0);
                    while ($k = $kategori_list_query->fetch_assoc()):
                ?>
                        <label>
                            <input type="checkbox" name="kategori[]" value="<?= $k['id'] ?>"
                                <?= (is_array($filter_kategori) && in_array($k['id'], $filter_kategori) ? 'checked' : '') ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </label>
                <?php
                    endwhile;
                }
                ?>
            </div>

            <div class="filter-group">
                <h4 class="filter-title">HARGA</h4>
                <div class="filter-harga">
                    <div class="harga-input-group">
                        <label for="harga_min">Minimum</label>
                        <input type="number" name="harga_min" id="harga_min" placeholder="Rp 0"
                               value="<?= htmlspecialchars($filter_harga_min ?? '') ?>">
                    </div>
                    <div class="harga-input-group">
                        <label for="harga_max">Maksimum</label>
                        <input type="number" name="harga_max" id="harga_max" placeholder="Rp 1.000.000"
                               value="<?= htmlspecialchars($filter_harga_max ?? '') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="apply-filter-btn">Terapkan Filter</button>
        </form>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <?php
            if (!empty($search_query)) {
                echo '<h3>Hasil pencarian untuk "'.htmlspecialchars($search_query).'"</h3>';
            } else {
                echo '<h3>Menampilkan Semua Produk</h3>';
            }
            $jumlah_hasil = $barang_result ? $barang_result->num_rows : 0;
            echo "<p>Ditemukan $jumlah_hasil produk</p>";
            ?>
        </div>

        <div class="mobile-action-bar">
            <button class="mobile-btn" id="mobile-filter-btn"><i class="fas fa-filter"></i> Filter</button>
            <div class="sort-dropdown" id="sort-dropdown">
                <button></button>
            </div>
        </div>

        <div class="products-grid">
            <?php if ($barang_result && $barang_result->num_rows > 0): ?>
                <?php while($b = $barang_result->fetch_assoc()): ?>
                    <?php
                        $stok_tersedia = $b['stok'] - $b['stok_di_pesan'];
                    ?>
                    <a href="detail.php?kode_barang=<?= $b['kode_barang'] ?>" class="item-card">
                        <div class="item-image-container">
                            <img src="../assets/uploads/<?= htmlspecialchars($b['gambar_barang']) ?>" alt="<?= htmlspecialchars($b['nama_barang']) ?>">
                        </div>
                        <div class="item-details">
                            <p class="item-title"><?= htmlspecialchars($b['nama_barang']) ?></p>
                            <p class="item-price">Rp<?= number_format($b['harga'], 0, ',', '.') ?></p>
                            <p class="item-stock">Stok: <?= $stok_tersedia ?></p>
                            <div class="item-price-row">
                                <div class="item-meta"></div>
                                <p class="item-location">Tiga Daya</p>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center;">Produk tidak ditemukan.</p>
            <?php endif; ?>
        </div>
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileFilterBtn = document.getElementById('mobile-filter-btn');
    const sidebarFilters = document.getElementById('sidebar-filters');
    const closeFilterBtn = document.getElementById('close-filter-btn');
    const filterOverlay = document.getElementById('filter-overlay');

    if (mobileFilterBtn) {
        mobileFilterBtn.addEventListener('click', () => {
            sidebarFilters.classList.add('active');
            filterOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    const hideFilter = () => {
        sidebarFilters.classList.remove('active');
        filterOverlay.classList.remove('active');
        document.body.style.overflow = '';
    };
    if (closeFilterBtn) closeFilterBtn.addEventListener('click', hideFilter);
    if (filterOverlay) filterOverlay.addEventListener('click', hideFilter);
});
</script>

</body>
</html>