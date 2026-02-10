<?php
session_start();
// Sesuaikan path ini dengan struktur folder kamu
require_once '../../config/koneksi.php'; 

// =================================================================
// 1. HELPER FUNCTIONS (Untuk Inisial & Warna)
// =================================================================
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

// =================================================================
// 2. QUERY DATA TOKO
// =================================================================
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (empty($slug)) { die("Halaman tidak ditemukan."); }

// Ambil data toko + Nama Kota (JOIN)
$stmt_toko = $koneksi->prepare("
    SELECT t.*, c.name as nama_kota 
    FROM tb_toko t 
    LEFT JOIN cities c ON t.city_id = c.id 
    WHERE t.slug = ? AND t.status = 'active'
");
$stmt_toko->bind_param("s", $slug);
$stmt_toko->execute();
$toko = $stmt_toko->get_result()->fetch_assoc();

if (!$toko) die("Toko tidak ditemukan.");

// Ambil komponen dekorasi
$dekorasi_query = $koneksi->prepare("SELECT * FROM tb_toko_dekorasi WHERE toko_id = ? AND is_active = 1 ORDER BY urutan ASC");
$dekorasi_query->bind_param("i", $toko['id']);
$dekorasi_query->execute();
$result_dekorasi = $dekorasi_query->get_result();

// Ambil produk toko
$semua_produk_query = $koneksi->prepare("SELECT * FROM tb_barang WHERE toko_id = ? AND is_active = 1 AND status_moderasi = 'approved' ORDER BY created_at DESC");
$semua_produk_query->bind_param("i", $toko['id']);
$semua_produk_query->execute();
$result_semua_produk = $semua_produk_query->get_result();

// Siapkan Variabel Tampilan Toko
$storeColor = getStoreColor($toko['nama_toko']);
$storeInitials = getStoreInitials($toko['nama_toko']);

// Cek Banner
$bannerPathRel = '../../assets/uploads/banners/' . $toko['banner_toko'];
$hasBanner = !empty($toko['banner_toko']) && file_exists(__DIR__ . '/../' . $bannerPathRel); // Sesuaikan path relative check
$bannerStyle = $hasBanner 
    ? "background-image: url('$bannerPathRel');" 
    : "background-color: $storeColor; opacity: 0.9;"; // Fallback warna

// Cek Logo
$logoPathRel = '../../assets/uploads/logos/' . $toko['logo_toko'];
$hasLogo = !empty($toko['logo_toko']) && file_exists(__DIR__ . '/../' . $logoPathRel);
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
    
    <style>
        /* CSS Tambahan untuk Inisial Toko & Banner */
        .store-header {
            background-size: cover;
            background-position: center;
            height: 250px; /* Tinggi banner */
            position: relative;
        }
        
        .store-info-logo-container {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            background: #fff;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .store-info-logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .store-info-initial {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            color: #fff;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/partials/navbar.php'; ?>

<div class="store-profile-container">
    
    <header class="store-header" style="<?= $bannerStyle ?>">
        <div style="position:absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.1);"></div>
    </header>
    
    <div class="store-info-bar">
        <div class="container">
            <div class="store-info-content">
                
                <div class="store-info-logo-container">
                    <?php if ($hasLogo): ?>
                        <img src="<?= $logoPathRel ?>" alt="Logo Toko" class="store-info-logo-img">
                    <?php else: ?>
                        <div class="store-info-initial" style="background-color: <?= $storeColor ?>;">
                            <?= $storeInitials ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="store-info-details">
                    <h1 class="store-name"><?= htmlspecialchars($toko['nama_toko']) ?></h1>
                    <div class="store-meta">
                        <span><i class="mdi mdi-star" style="color:orange;"></i> 4.8 Rating</span>
                        
                        <?php if(!empty($toko['nama_kota'])): ?>
                             <span><i class="mdi mdi-map-marker"></i> <?= htmlspecialchars($toko['nama_kota']) ?></span>
                        <?php endif; ?>
                        
                        <span class="badge badge-success" style="padding: 2px 8px; border-radius:4px; font-size:12px; background:#28a745; color:white;">
                            <?= ($toko['status_operasional'] == 'Buka') ? 'Buka' : 'Tutup' ?>
                        </span>
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
        $dekorasi_query->execute(); 
        $result_dekorasi = $dekorasi_query->get_result();
        
        while($komponen = $result_dekorasi->fetch_assoc()): 
            $konten = json_decode($komponen['konten_json'], true);
        ?>
            <?php if ($komponen['tipe_komponen'] == 'BANNER' && !empty($konten['gambar'])): ?>
                <section class="component-banner my-5">
                    <img src="../../assets/uploads/decorations/<?= htmlspecialchars($konten['gambar']) ?>" alt="Banner Promosi" style="width:100%; border-radius:8px;">
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
                            $imgSrc = !empty($produk['gambar_utama']) ? '../../assets/uploads/products/'.$produk['gambar_utama'] : '../../assets/uploads/products/default.jpg';
                            
                            echo '<a href="detail_produk.php?id='.$produk['id'].'" class="product-card">';
                            echo '<div class="product-image"><img src="'.$imgSrc.'" onerror="this.src=\'../../assets/uploads/products/default.jpg\'" alt="'.htmlspecialchars($produk['nama_barang']).'"></div>';
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
                    <?php while ($produk = $result_semua_produk->fetch_assoc()): 
                        // Tentukan Gambar Produk
                        $imgSrc = !empty($produk['gambar_utama']) 
                            ? '../../assets/uploads/products/' . $produk['gambar_utama'] 
                            : '../../assets/uploads/products/default.jpg';
                    ?>
                        <a href="detail_produk.php?id=<?= $produk['id'] ?>&toko_slug=<?= $slug ?>" class="product-card">
                            <div class="product-image">
                                <img src="<?= $imgSrc ?>" 
                                     onerror="this.onerror=null; this.src='../../assets/uploads/products/default.jpg';" 
                                     loading="lazy" 
                                     alt="<?= htmlspecialchars($produk['nama_barang']) ?>">
                            </div>
                            <div class="product-details">
                                <h3 class="product-title"><?= htmlspecialchars($produk['nama_barang']) ?></h3>
                                <p class="product-price">Rp<?= number_format($produk['harga'], 0, ',', '.') ?></p>
                                <div class="product-seller-info">
                                    <span class="store-location">
                                        <i class="mdi mdi-map-marker-outline"></i> <?= htmlspecialchars($toko['nama_kota'] ?? 'Indonesia') ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #999; background: #f9f9f9; border-radius: 8px;">
                    <i class="mdi mdi-package-variant" style="font-size: 48px; display:block; margin-bottom:10px;"></i>
                    <p>Belum ada produk di toko ini.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

</body>
</html>