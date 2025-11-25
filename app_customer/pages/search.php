<?php
// Pastikan session aktif
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include koneksi (Path absolut biar aman)
require_once __DIR__ . '/../../config/koneksi.php';

// --- 1. PERSIAPAN FILTER ---
// Ambil Kategori untuk Sidebar
$kategori_query = $koneksi->query("SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");

// Tangkap Parameter URL
$keyword = $_GET['query'] ?? '';
$filter_kategori = $_GET['kategori'] ?? []; // Array ID kategori
$harga_min = filter_input(INPUT_GET, 'harga_min', FILTER_VALIDATE_INT);
$harga_max = filter_input(INPUT_GET, 'harga_max', FILTER_VALIDATE_INT);

// --- 2. BUILD QUERY PRODUK ---
// Dasar Query: Ambil semua kolom barang + nama kategori
$sql = "SELECT b.*, k.nama_kategori 
        FROM tb_barang b 
        LEFT JOIN tb_kategori k ON b.kategori_id = k.id 
        WHERE b.is_active = 1"; // Pastikan hanya barang aktif

$params = [];
$types = "";

// Filter Keyword (Nama Barang / Deskripsi)
if (!empty($keyword)) {
    $sql .= " AND (b.nama_barang LIKE ? OR b.deskripsi LIKE ?)";
    $types .= "ss";
    $search_term = "%{$keyword}%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Filter Kategori (Checkbox)
if (!empty($filter_kategori) && is_array($filter_kategori)) {
    // Sanitasi array integer
    $kat_ids = array_map('intval', $filter_kategori);
    if (!empty($kat_ids)) {
        $placeholders = implode(',', array_fill(0, count($kat_ids), '?'));
        $sql .= " AND b.kategori_id IN ($placeholders)";
        $types .= str_repeat('i', count($kat_ids));
        $params = array_merge($params, $kat_ids);
    }
}

// Filter Harga
if ($harga_min) {
    $sql .= " AND b.harga >= ?";
    $types .= "i";
    $params[] = $harga_min;
}
if ($harga_max) {
    $sql .= " AND b.harga <= ?";
    $types .= "i";
    $params[] = $harga_max;
}

// Urutkan hasil (Terbaru dulu)
$sql .= " ORDER BY b.id DESC";

// Eksekusi Query
$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian "<?= htmlspecialchars($keyword) ?>" - Pondasikita</title>
    
    <link rel="stylesheet" href="/assets/css/navbar_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* --- LAYOUT UTAMA --- */
        :root { --primary: #007bff; --text-dark: #333; --bg-light: #f4f6f9; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); margin: 0; }
        
        .page-wrapper {
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            position: relative;
        }

        /* --- SIDEBAR FILTER (Sticky) --- */
        .sidebar-filter {
            width: 280px;
            flex-shrink: 0;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #eee;
            height: fit-content;
            position: sticky;
            top: 90px; /* Jarak dari navbar */
            z-index: 90;
        }

        .filter-section { margin-bottom: 25px; border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; }
        .filter-section:last-child { border-bottom: none; }
        .filter-title { font-weight: 600; margin-bottom: 15px; display: block; font-size: 0.95rem; color: #000; }
        
        /* Checkbox Style */
        .checkbox-group label {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 10px; cursor: pointer; font-size: 0.9rem; color: #555;
        }
        .checkbox-group input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary); }

        /* Input Harga */
        .price-inputs { display: flex; gap: 10px; align-items: center; }
        .form-control-sm {
            width: 100%; padding: 8px; border: 1px solid #ddd;
            border-radius: 6px; font-size: 0.85rem; outline: none;
        }
        .btn-apply {
            width: 100%; background: var(--primary); color: #fff;
            border: none; padding: 10px; border-radius: 6px;
            font-weight: 600; cursor: pointer; margin-top: 10px;
            transition: 0.2s;
        }
        .btn-apply:hover { background: #0056b3; }

        /* --- PRODUCT GRID --- */
        .main-content { flex: 1; }
        
        .result-header {
            margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;
            background: #fff; padding: 15px 20px; border-radius: 8px; border: 1px solid #eee;
        }
        .result-header h3 { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .result-header span { font-size: 0.9rem; color: #777; }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        /* Product Card Style (Konsisten dengan Halaman Lain) */
        .product-card {
            background: #fff; border: 1px solid #eee; border-radius: 10px;
            overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none; color: inherit; display: flex; flex-direction: column;
            position: relative;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }

        .card-img {
            width: 100%; height: 200px; object-fit: cover;
            background-color: #f9f9f9; border-bottom: 1px solid #f0f0f0;
        }
        
        .card-body { padding: 15px; flex: 1; display: flex; flex-direction: column; }
        .card-title { font-size: 0.95rem; font-weight: 600; margin: 0 0 8px; line-height: 1.4; color: #333; }
        .card-price { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-top: auto; }
        .card-stock { font-size: 0.8rem; color: #888; margin-top: 5px; }
        .card-store { font-size: 0.75rem; color: #aaa; margin-top: 8px; display: flex; align-items: center; gap: 5px; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; width: 100%; }
        .empty-state i { font-size: 3rem; color: #ddd; margin-bottom: 15px; }

        /* Tombol Filter Mobile */
        .mobile-filter-btn { display: none; }

        /* Responsive Mobile */
        @media (max-width: 768px) {
            .page-wrapper { flex-direction: column; padding: 15px; }
            .sidebar-filter {
                position: fixed; top: 0; left: -100%; width: 80%; height: 100vh;
                z-index: 2000; overflow-y: auto; transition: 0.3s; box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            .sidebar-filter.active { left: 0; }
            
            .mobile-filter-btn {
                display: inline-flex; align-items: center; gap: 8px;
                background: #fff; border: 1px solid #ddd; padding: 8px 15px;
                border-radius: 20px; font-size: 0.9rem; font-weight: 600;
                margin-bottom: 15px; cursor: pointer; width: fit-content;
            }
            
            /* Overlay Gelap saat filter aktif */
            .filter-overlay {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.5); z-index: 1999; display: none;
            }
            .filter-overlay.active { display: block; }
            
            .close-filter { display: block; position: absolute; top: 15px; right: 20px; font-size: 1.5rem; cursor: pointer; }
        }
        
        @media (min-width: 769px) {
            .close-filter { display: none; } /* Sembunyikan tombol X di desktop */
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="filter-overlay" id="filterOverlay"></div>

<div class="page-wrapper">
    
    <div class="mobile-filter-btn" id="openFilterBtn">
        <i class="fas fa-filter"></i> Filter Produk
    </div>

    <aside class="sidebar-filter" id="sidebarFilter">
        <span class="close-filter" id="closeFilterBtn">&times;</span>
        <form action="search.php" method="GET">
            <input type="hidden" name="query" value="<?= htmlspecialchars($keyword) ?>">

            <div class="filter-section">
                <span class="filter-title">Rentang Harga</span>
                <div class="price-inputs">
                    <input type="number" name="harga_min" class="form-control-sm" placeholder="Min" value="<?= htmlspecialchars($harga_min ?? '') ?>">
                    <span>-</span>
                    <input type="number" name="harga_max" class="form-control-sm" placeholder="Max" value="<?= htmlspecialchars($harga_max ?? '') ?>">
                </div>
            </div>

            <div class="filter-section">
                <span class="filter-title">Kategori</span>
                <div class="checkbox-group">
                    <?php if ($kategori_query): ?>
                        <?php while ($kat = $kategori_query->fetch_assoc()): ?>
                            <label>
                                <input type="checkbox" name="kategori[]" value="<?= $kat['id'] ?>" 
                                    <?= (in_array($kat['id'], $filter_kategori)) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </label>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-apply">Terapkan Filter</button>
        </form>
    </aside>

    <main class="main-content">
        <div class="result-header">
            <?php if (!empty($keyword)): ?>
                <h3>Hasil pencarian "<?= htmlspecialchars($keyword) ?>"</h3>
            <?php else: ?>
                <h3>Semua Produk</h3>
            <?php endif; ?>
            <span>Ditemukan <strong><?= count($products) ?></strong> produk</span>
        </div>

        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>Tidak ada produk yang cocok dengan kriteria pencarianmu.</p>
                    <a href="search.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Reset Filter</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <?php 
                        // Hitung Stok & Gambar (Fallback kalau null)
                        $stok_real = $p['stok'] - ($p['stok_di_pesan'] ?? 0);
                        $img_url = !empty($p['gambar_utama']) 
                                    ? "/assets/uploads/products/" . htmlspecialchars($p['gambar_utama']) 
                                    : "/assets/images/default-product.jpg"; 
                    ?>
                    
                    <a href="detail_produk.php?id=<?= $p['id'] ?>" class="product-card">
                        <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($p['nama_barang']) ?>" class="card-img"
                                onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">
                        
                        <div class="card-body">
                            <div class="card-category" style="font-size: 0.7rem; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                                <?= htmlspecialchars($p['nama_kategori'] ?? 'Umum') ?>
                            </div>
                            <h4 class="card-title"><?= htmlspecialchars($p['nama_barang']) ?></h4>
                            <div class="card-price">Rp<?= number_format($p['harga'], 0, ',', '.') ?></div>
                            <div class="card-stock">Stok: <?= max(0, $stok_real) ?></div>
                            
                            <div class="card-store">
                                <i class="fas fa-store-alt"></i> Pondasikita Official
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</div>

<script>
    const openBtn = document.getElementById('openFilterBtn');
    const closeBtn = document.getElementById('closeFilterBtn');
    const sidebar = document.getElementById('sidebarFilter');
    const overlay = document.getElementById('filterOverlay');

    function toggleFilter() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : ''; // Cegah scroll body saat filter buka
    }

    if(openBtn) openBtn.addEventListener('click', toggleFilter);
    if(closeBtn) closeBtn.addEventListener('click', toggleFilter);
    if(overlay) overlay.addEventListener('click', toggleFilter);
</script>

</body>
</html>