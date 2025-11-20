<?php
// File: /pages/produk/detail_produk.php

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan path ke file koneksi sudah benar
require_once __DIR__ . '/../../config/koneksi.php';

// Cek apakah koneksi database berhasil
if (!isset($koneksi) || $koneksi->connect_error) {
    die("<h1>Koneksi ke database gagal.</h1><p>Periksa file konfigurasi koneksi Anda.</p>");
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    die("<h1>ID Produk tidak valid.</h1>");
}

// Query Utama
$product_query = "SELECT p.*, k.nama_kategori, t.id AS toko_id, t.nama_toko, t.slug AS slug_toko, t.city_id AS kota_toko, t.logo_toko 
                  FROM tb_barang p 
                  LEFT JOIN tb_kategori k ON p.kategori_id = k.id 
                  JOIN tb_toko t ON p.toko_id = t.id 
                  WHERE p.id = ? AND p.is_active = 1 AND p.status_moderasi = 'approved'";
$stmt = $koneksi->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die("<h1>Produk Tidak Ditemukan atau Tidak Aktif.</h1>");
}

// Mengambil gambar produk
$gallery_images = [];
$gambar_query = "SELECT nama_file FROM tb_gambar_barang WHERE barang_id = ? ORDER BY is_utama DESC, id ASC";
$stmt_gambar = $koneksi->prepare($gambar_query);
$stmt_gambar->bind_param("i", $product_id);
$stmt_gambar->execute();
$result_gambar = $stmt_gambar->get_result();
while ($row_gambar = $result_gambar->fetch_assoc()) {
    $gallery_images[] = $row_gambar['nama_file'];
}
$stmt_gambar->close();

if (empty($gallery_images) && !empty($product['gambar_utama'])) {
    $gallery_images[] = $product['gambar_utama'];
}

// Query produk terkait
$related_query = "SELECT id, nama_barang, harga, gambar_utama FROM tb_barang WHERE toko_id = ? AND id != ? AND is_active = 1 LIMIT 5";
$stmt_related = $koneksi->prepare($related_query);
$stmt_related->bind_param("ii", $product['toko_id'], $product_id);
$stmt_related->execute();
$related_products = $stmt_related->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_related->close();

// Query ulasan
$ulasan_query = "SELECT r.*, u.nama AS username FROM tb_review_produk r JOIN tb_user u ON r.user_id = u.id WHERE r.barang_id = ? ORDER BY r.created_at DESC";
$stmt_ulasan = $koneksi->prepare($ulasan_query);
$stmt_ulasan->bind_param("i", $product['id']);
$stmt_ulasan->execute();
$ulasan_produk = $stmt_ulasan->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ulasan->close();

$total_rating = 0;
$jumlah_ulasan = count($ulasan_produk);
$avg_rating = 0;
if ($jumlah_ulasan > 0) {
    foreach ($ulasan_produk as $ulasan) {
        $total_rating += $ulasan['rating'];
    }
    $avg_rating = $total_rating / $jumlah_ulasan;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['nama_barang']) ?> - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/navbar_style.css">
    <link rel="stylesheet" href="/assets/css/produk_detail.css">
</head>
<body>

<?php require_once __DIR__ . '/partials/navbar.php'; ?>

<main class="container">
    <section class="product-showcase">
        <div class="product-gallery">
            <div class="main-image-wrapper">
                <img src="/assets/uploads/products/<?= htmlspecialchars($gallery_images[0] ?? 'default.jpg') ?>" alt="Gambar Utama Produk" class="main-image" id="mainProductImage">
            </div>
            <div class="thumbnail-strip">
                <?php foreach ($gallery_images as $index => $img) : ?>
                    <img src="/assets/uploads/products/<?= htmlspecialchars($img ?? 'default.jpg') ?>" alt="Thumbnail <?= $index + 1 ?>" class="thumbnail <?= $index == 0 ? 'is-active' : '' ?>" data-full-image="/assets/uploads/products/<?= htmlspecialchars($img ?? 'default.jpg') ?>">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="product-info-container">
            <h1 class="product-title"><?= htmlspecialchars($product['nama_barang']) ?></h1>
            
            <div class="product-meta">
                <div class="meta-rating">
                    <span class="rating-value"><?= number_format($avg_rating, 1) ?></span>
                    <i class="mdi mdi-star"></i>
                </div>
                <div class="meta-reviews">
                    <a href="#reviews" style="color: inherit;"><strong><?= $jumlah_ulasan ?></strong> Ulasan</a>
                </div>
                <div class="meta-sold">
                    Stok: <strong><?= $product['stok'] ?></strong>
                </div>
            </div>

            <div class="product-price">
                Rp <?= number_format($product['harga'], 0, ',', '.') ?>
            </div>

            <form id="formTambahKeranjang">
                <input type="hidden" name="barang_id" value="<?= $product['id'] ?>">
                
                <div class="product-actions">
                    <div class="action-row">
                        <span class="action-label">Kuantitas</span>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" id="btn-minus">-</button>
                            <input type="number" class="quantity-input" name="jumlah" id="quantity-input" value="1" min="1" max="<?= $product['stok'] ?>">
                            <button type="button" class="quantity-btn" id="btn-plus">+</button>
                        </div>
                    </div>
                </div>

                <?php if ($product['stok'] > 0) : ?>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn--secondary">
                            <i class="mdi mdi-cart-plus"></i> Masukkan Keranjang
                        </button>
                        <a href="#" onclick="alert('Fitur Beli Langsung menyusul ya!')" class="btn btn--primary">
                            Beli Sekarang
                        </a>
                    </div>
                <?php else : ?>
                    <div class="out-of-stock-notice">
                        Stok produk ini telah habis.
                    </div>
                <?php endif; ?>
            </form>
            </div>
    </section>

    <section class="product-details-section">
        <div class="details-main">
            <div class="card">
                <h2 class="card-header">Spesifikasi Produk</h2>
                <table class="specs-table">
                    <tbody>
                        <tr><td>Kategori</td><td><?= htmlspecialchars($product['nama_kategori'] ?? 'Tidak ada kategori') ?></td></tr>
                        <tr><td>Stok</td><td><?= htmlspecialchars($product['stok']) ?></td></tr>
                        <tr><td>Berat</td><td><?= htmlspecialchars($product['berat_kg'] ?? 'N/A') ?> kg</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="card-header">Deskripsi Produk</h2>
                <div class="product-description">
                    <p><?= nl2br(htmlspecialchars($product['deskripsi'])) ?: 'Deskripsi produk belum tersedia.' ?></p>
                </div>
            </div>

            <div class="card" id="reviews">
                <h2 class="card-header">Ulasan Produk (<?= $jumlah_ulasan ?>)</h2>
                <?php if ($jumlah_ulasan > 0) : ?>
                    <?php foreach ($ulasan_produk as $ulasan) : ?>
                        <article class="review-card">
                            <div class="review-author"><span class="name"><?= htmlspecialchars($ulasan['username']) ?></span></div>
                            <div class="review-content">
                                <div class="review-rating">
                                    <?php for ($i = 0; $i < 5; $i++) : ?>
                                        <i class="mdi mdi-star" style="color: <?= $i < $ulasan['rating'] ? '#FFC107' : '#E0E0E0' ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-body"><?= htmlspecialchars($ulasan['ulasan']) ?></p>
                                <small class="review-date"><?= date('d M Y', strtotime($ulasan['created_at'])) ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>Belum ada ulasan untuk produk ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <aside class="details-sidebar">
            <div class="card">
                <div class="seller-card">
                    <img src="/assets/uploads/logos/<?= htmlspecialchars($product['logo_toko'] ?? 'default-logo.png') ?>" alt="Logo Toko" class="seller-logo">
                    <div class="seller-info">
                        <a href="/toko.php?slug=<?= $product['slug_toko'] ?>" class="name"><?= htmlspecialchars($product['nama_toko']) ?></a>
                        <span class="location"><i class="mdi mdi-map-marker-outline"></i> <?= htmlspecialchars($product['kota_toko']) ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="card-header">Produk Lainnya</h3>
                <div class="related-products-grid">
                    <?php foreach ($related_products as $related) : ?>
                        <a href="detail_produk.php?id=<?= $related['id'] ?>" class="related-product-card">
                            <div class="image-wrapper"><img src="/assets/uploads/products/<?= htmlspecialchars($related['gambar_utama'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($related['nama_barang']) ?>"></div>
                            <div class="details">
                                <h4 class="title"><?= htmlspecialchars($related['nama_barang']) ?></h4>
                                <p class="price">Rp <?= number_format($related['harga'], 0, ',', '.') ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </section>
</main>

<script src="/assets/js/navbar.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. LOGIKA GAMBAR & QUANTITY (Bawaan)
    const mainImage = document.getElementById('mainProductImage');
    const thumbnails = document.querySelectorAll('.thumbnail');
    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                mainImage.src = this.dataset.fullImage;
                thumbnails.forEach(t => t.classList.remove('is-active'));
                this.classList.add('is-active');
            });
        });
    }

    const quantityInput = document.getElementById('quantity-input');
    const btnMinus = document.getElementById('btn-minus');
    const btnPlus = document.getElementById('btn-plus');
    if (quantityInput && btnMinus && btnPlus) {
        const maxStock = parseInt(quantityInput.max, 10);
        const minStock = parseInt(quantityInput.min, 10);

        btnMinus.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value, 10);
            if (currentValue > minStock) quantityInput.value = currentValue - 1;
        });

        btnPlus.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value, 10);
            if (currentValue < maxStock) quantityInput.value = currentValue + 1;
        });
    }

    // 2. LOGIKA AJAX KERANJANG (YANG KITA PERBAIKI)
    const formKeranjang = document.getElementById('formTambahKeranjang');

    if (formKeranjang) {
        formKeranjang.addEventListener('submit', function(e) {
            e.preventDefault(); // Tahan dulu, jangan reload

            const formData = new FormData(this);

            // Kirim data ke backend
            fetch('/actions/tambah_keranjang.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Tampilkan Pesan Sukses
                    alert('✅ Berhasil! ' + data.message);
                    
                    // [PENTING] Reload Halaman supaya angka Keranjang di Navbar berubah
                    window.location.reload(); 
                    
                } else {
                    alert('❌ Gagal: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem.');
            });
        });
    }
});
</script>

</body>
</html>