<?php
session_start();
require_once '../../config/koneksi.php';

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

// Ambil semua produk dari toko ini
$semua_produk_query = $koneksi->prepare("SELECT * FROM tb_barang WHERE toko_id = ? AND is_active = 1 AND status_moderasi = 'approved' ORDER BY created_at DESC");
$semua_produk_query->bind_param("i", $toko['id']);
$semua_produk_query->execute();
$result_semua_produk = $semua_produk_query->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($toko['nama_toko']) ?> - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <link rel="stylesheet" href="../../assets/css/navbar_style.css">
    <link rel="stylesheet" href="../../assets/css/toko_page_style.css"> 
    <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<?php include '../partials/navbar.php'; ?>

<div class="store-profile-container">
    
    <header class="store-header" style="background-image: url('../../assets/uploads/banners/<?= htmlspecialchars($toko['banner_toko'] ?? 'default-banner.jpg') ?>');">
        </header>
    
    <div class="store-info-bar">
        <div class="container">
            <div class="store-info-content">
                <img src="../../assets/uploads/logos/<?= htmlspecialchars($toko['logo_toko'] ?? 'default-logo.png') ?>" alt="Logo Toko" class="store-info-logo">
                
                <div class="store-info-details">
                    <h1 class="store-name"><?= htmlspecialchars($toko['nama_toko']) ?></h1>
                    <div class="store-meta">
                        <span><i class="mdi mdi-star"></i> 4.8 Rating</span>
                        <span><i class="mdi mdi-account-group"></i> 1,2rb Pengikut</span>
                        <?php if(!empty($toko['city_id'])): ?>
                             <span><i class="mdi mdi-map-marker"></i> <?= htmlspecialchars($toko['city_id']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="store-info-actions">
                    <button class="btn-action btn-follow"><i class="mdi mdi-plus"></i> Ikuti</button>
                    <button class="btn-action btn-chat"><i class="mdi mdi-chat-processing-outline"></i> Chat</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container store-dynamic-content">
        <?php 
        // Reset pointer dekorasi jika diperlukan atau gunakan loop baru
        $dekorasi_query->execute(); 
        $result_dekorasi = $dekorasi_query->get_result();
        
        while($komponen = $result_dekorasi->fetch_assoc()): 
            $konten = json_decode($komponen['konten_json'], true);
        ?>
            <?php if ($komponen['tipe_komponen'] == 'BANNER' && !empty($konten['gambar'])): ?>
                <section class="component-banner my-5">
                    <img src="../../assets/uploads/decorations/<?= htmlspecialchars($konten['gambar']) ?>" alt="Banner Promosi">
                </section>

            <?php elseif ($komponen['tipe_komponen'] == 'PRODUK_UNGGULAN' && !empty($konten['produk_ids'])): ?>
                <section class="component-featured-products my-5">
                    <h3 class="section-title"><?= htmlspecialchars($konten['judul']) ?></h3>
                    <div class="products-grid">
                        <?php
                        $placeholders = implode(',', array_fill(0, count($konten['produk_ids']), '?'));
                        $types = str_repeat('i', count($konten['produk_ids']));
                        $sql_produk_unggulan = "SELECT * FROM tb_barang WHERE id IN ($placeholders)";
                        
                        $stmt_produk = $koneksi->prepare($sql_produk_unggulan);
                        $stmt_produk->bind_param($types, ...$konten['produk_ids']);
                        $stmt_produk->execute();
                        $result_produk_unggulan = $stmt_produk->get_result();

                        while ($produk = $result_produk_unggulan->fetch_assoc()) {
                            echo '<a href="detail_produk.php?id='.$produk['id'].'" class="product-card">';
                            echo '<div class="product-image"><img src="../../assets/uploads/products/'.htmlspecialchars($produk['gambar_utama']).'" alt="'.htmlspecialchars($produk['nama_barang']).'"></div>';
                            echo '<div class="product-details">';
                            echo '<h3 class="product-title">'.htmlspecialchars($produk['nama_barang']).'</h3>';
                            echo '<p class="product-price">Rp'.number_format($produk['harga'], 0, ',', '.').'</p>';
                            echo '</div>';
                            echo '</a>';
                        }
                        $stmt_produk->close();
                        ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>

    <div class="container my-5">
        <section class="component-all-products">
            <h3 class="section-title">Semua Produk</h3>
            
            <?php if($result_semua_produk->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($produk = $result_semua_produk->fetch_assoc()): ?>
                        <a href="detail_produk.php?id=<?= $produk['id'] ?>" class="product-card">
                            <div class="product-image">
                                <img src="../../assets/uploads/products/<?= htmlspecialchars($produk['gambar_utama']) ?>" loading="lazy" alt="<?= htmlspecialchars($produk['nama_barang']) ?>">
                            </div>
                            <div class="product-details">
                                <h3 class="product-title"><?= htmlspecialchars($produk['nama_barang']) ?></h3>
                                <p class="product-price">Rp<?= number_format($produk['harga'], 0, ',', '.') ?></p>
                                <div class="product-seller-info">
                                    <span class="store-location"><i class="mdi mdi-map-marker-outline"></i> <?= htmlspecialchars($toko['city_id']) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #999;">
                    <i class="mdi mdi-package-variant" style="font-size: 48px;"></i>
                    <p>Belum ada produk di toko ini.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

</body>
</html>