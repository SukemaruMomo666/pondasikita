<?php
session_start();
require_once '../config/koneksi.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (empty($slug)) {
    die("Halaman tidak ditemukan.");
}

// Ambil data utama toko
$stmt_toko = $koneksi->prepare("SELECT * FROM tb_toko WHERE slug = ? AND status = 'active'");
$stmt_toko->bind_param("s", $slug);
$stmt_toko->execute();
$toko = $stmt_toko->get_result()->fetch_assoc();
if (!$toko) die("Toko tidak ditemukan.");

// Ambil komponen dekorasi yang aktif untuk toko ini
$dekorasi_query = $koneksi->prepare("SELECT * FROM tb_toko_dekorasi WHERE toko_id = ? AND is_active = 1 ORDER BY urutan ASC");
$dekorasi_query->bind_param("i", $toko['id']);
$dekorasi_query->execute();
$result_dekorasi = $dekorasi_query->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title><?= htmlspecialchars($toko['nama_toko']) ?> - Pondasikita</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/navbar_style.css">
    <link rel="stylesheet" href="../assets/css/toko_page_style.css">
</head>
<body>
<?php include '../partials/navbar.php'; ?>

<div class="store-profile-container">
    <header class="store-header" style="background-image: url('../assets/uploads/banners/<?= htmlspecialchars($toko['banner_toko'] ?? 'default-banner.jpg') ?>');">
        </header>
    <div class="store-info-bar">
        </div>

    <div class="store-dynamic-content container">
        <?php while($komponen = $result_dekorasi->fetch_assoc()): 
            $konten = json_decode($komponen['konten_json'], true);
        ?>
            <?php if ($komponen['tipe_komponen'] == 'BANNER' && !empty($konten['gambar'])): ?>
                <section class="component-banner my-4">
                    <img src="../assets/uploads/decorations/<?= htmlspecialchars($konten['gambar']) ?>" class="img-fluid rounded shadow-sm">
                </section>

            <?php elseif ($komponen['tipe_komponen'] == 'PRODUK_UNGGULAN' && !empty($konten['produk_ids'])): ?>
                <section class="component-featured-products my-5">
                    <h3 class="mb-3 font-weight-bold"><?= htmlspecialchars($konten['judul']) ?></h3>
                    <div class="products-grid">
                        <?php
                        // --- PERBAIKAN: LOGIKA UNTUK MENGAMBIL PRODUK UNGGULAN ---
                        $placeholders = implode(',', array_fill(0, count($konten['produk_ids']), '?'));
                        $types = str_repeat('i', count($konten['produk_ids']));
                        $sql_produk_unggulan = "SELECT * FROM tb_barang WHERE id IN ($placeholders)";
                        
                        $stmt_produk = $koneksi->prepare($sql_produk_unggulan);
                        $stmt_produk->bind_param($types, ...$konten['produk_ids']);
                        $stmt_produk->execute();
                        $result_produk_unggulan = $stmt_produk->get_result();

                        while ($produk = $result_produk_unggulan->fetch_assoc()) {
                            // Tampilkan product card (gunakan struktur yang sama dari halaman produk.php)
                            echo '<a href="detail_produk.php?id='.$produk['id'].'" class="product-link"><div class="product-card">...</div></a>';
                        }
                        $stmt_produk->close();
                        ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>

    <section class="component-all-products container my-5">
        <h3 class="mb-3 font-weight-bold">Semua Produk</h3>
        <div class="products-grid">
            <?php
                // --- PERBAIKAN: LOGIKA UNTUK MENGAMBIL SEMUA PRODUK TOKO ---
                $semua_produk_query = $koneksi->prepare("SELECT * FROM tb_barang WHERE toko_id = ? AND is_active = 1 AND status_moderasi = 'approved' ORDER BY created_at DESC");
                $semua_produk_query->bind_param("i", $toko['id']);
                $semua_produk_query->execute();
                $result_semua_produk = $semua_produk_query->get_result();
                
                while ($produk = $result_semua_produk->fetch_assoc()) {
                    // Tampilkan product card
                    echo '<a href="detail_produk.php?id='.$produk['id'].'" class="product-link"><div class="product-card">
                            <div class="product-image"><img src="../assets/uploads/products/'.htmlspecialchars($produk['gambar_utama']).'" alt="'.htmlspecialchars($produk['nama_barang']).'"></div>
                            <div class="product-details">
                                <h3>'.htmlspecialchars($produk['nama_barang']).'</h3>
                                <p class="price">Rp'.number_format($produk['harga']).'</p>
                            </div>
                          </div></a>';
                }
                $semua_produk_query->close();
            ?>
        </div>
    </section>
</div>

</body>
</html>