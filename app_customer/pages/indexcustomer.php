<?php
session_start();

// Cek apakah user sudah login dan memiliki level customer
if (!isset($_SESSION['username']) || $_SESSION['level'] !== 'customer') {
    header("Location: signin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Bangunan Tiga Daya</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../partials/navbar_customer.php'; ?>

    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h2>Malas belanja ke toko?</h2>
                <h3>Toko Bangunan Tiga Daya, jaminan harga termurah!</h3>
                <a href="#" class="btn-primary">Cek Sekarang</a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Categories Section -->
            <section class="categories">
                <h2 class="section-title"><span>Kategori Pilihan</span></h2>
                <div class="category-grid">
                    <div class="category-item">
                        <div class="category-icon">
                            <i class="fas fa-hammer"></i>
                        </div>
                        <p>Perkakas</p>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">
                            <i class="fas fa-paint-roller"></i>
                        </div>
                        <p>Cat</p>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">
                            <i class="fas fa-bath"></i>
                        </div>
                        <p>Sanitari</p>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <p>Listrik</p>
                    </div>
                </div>
            </section>

           <!-- Products Section -->
            <section class="products">
                <div class="section-header">
                    <h2 class="section-title"><span>Produk Terlaris</span></h2>
                    <a href="#" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    <?php
                        include "../config/koneksi.php";

                        // Query untuk mengambil data dari tabel tb_barang
                        $query_mysql = mysqli_query($koneksi, "SELECT * FROM tb_barang");

                        // Periksa jika ada data yang ditemukan
                    foreach ($query_mysql as $row) {
                    echo '
                    <a href="../pages/detail.php?id=' . $row['id'] . '" class="product-link">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="../assets/' . htmlspecialchars($row['gambar_barang']) . '" alt="' . htmlspecialchars($row['nama_barang']) . '">
                            </div>
                            <div class="product-details">
                                <h3>' . htmlspecialchars($row['nama_barang']) . '</h3>
                                <p class="price">Rp' . number_format($row['harga'], 0, ',', '.') . '</p>
                            </div>
                        </div>
                    </a>';
                }
                ?>
                </div>
            </section>            
        </div>
    </main>

   <?php include('../partials/footer.php')?>

    </section>
</body>
</html>