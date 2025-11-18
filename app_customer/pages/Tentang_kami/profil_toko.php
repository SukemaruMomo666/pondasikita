<?php
// Ganti path ini sesuai dengan struktur folder Anda
// include '../config/koneksi.php'; // Jika diperlukan
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tentang Toko Bangunan Agung Jaya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Pastikan path CSS sudah benar -->
    <link rel="stylesheet" href="../../assets/css/about.css"> <!-- Kita akan buat file CSS baru ini -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; // Asumsikan path navbar sudah benar ?>

<main class="about-page">
    
    <!-- Hero Section -->
    <header class="about-hero">
        <div class="container">
            <h1>Mengenal Lebih Dekat <strong>Toko Bangunan Agung Jaya</strong></h1>
            <p class="subtitle">Partner terpercaya untuk setiap kebutuhan konstruksi dan renovasi Anda sejak 2010.</p>
        </div>
    </header>

    <!-- Konten Utama -->
    <div class="container content-container">
        
        <!-- Section: Profil Toko -->
        <section class="content-section" id="profil">
            <div class="section-icon">
                <i class="fas fa-store"></i>
            </div>
            <h2>Profil Toko Bangunan Agung Jaya</h2>
            <p>Toko Bangunan Agung Jaya didirikan untuk menyediakan bahan bangunan berkualitas tinggi dengan harga terjangkau. Kami telah melayani pelanggan sejak tahun 2010, baik untuk kebutuhan proyek besar maupun renovasi rumah.</p>
            <p>Kami percaya bahwa pelayanan cepat, pilihan produk lengkap, dan kepercayaan pelanggan adalah kunci kesuksesan kami.</p>
        </section>

        <!-- Section: Visi & Misi -->
        <section class="content-section" id="visi-misi">
            <div class="section-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <h2>Visi & Misi Kami</h2>
            <div class="vision-mission-grid">
                <div class="grid-item">
                    <h4>Visi</h4>
                    <p>Menjadi toko bangunan terbaik dan terpercaya di Indonesia yang selalu menghadirkan produk berkualitas dan pelayanan profesional.</p>
                </div>
                <div class="grid-item">
                    <h4>Misi</h4>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Menyediakan barang bangunan berkualitas tinggi.</li>
                        <li><i class="fas fa-check-circle"></i> Memberikan pelayanan cepat dan ramah.</li>
                        <li><i class="fas fa-check-circle"></i> Menjaga harga tetap kompetitif dan transparan.</li>
                        <li><i class="fas fa-check-circle"></i> Menjadi mitra terpercaya bagi kontraktor dan pemilik rumah.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Section: Lokasi & Layanan -->
        <section class="content-section" id="lokasi">
            <div class="section-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <h2>Lokasi & Layanan</h2>
            <p>Kami berlokasi di <strong>TB. Agung Jaya, Subang, Jawa Barat</strong>. Kami melayani pengiriman ke area Subang dan sekitarnya dengan armada sendiri untuk memastikan barang sampai dengan aman.</p>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.7023954203664!2d107.80347836957047!3d-6.559201237973981!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e693b39028b2e6d%3A0xb211383448e1bd62!2sTB.%20Agung%20Jaya!5e0!3m2!1sid!2sid!4v1750922512528!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </section>

        <!-- Section: Kontak & Jam Operasional -->
        <section class="content-section" id="kontak">
            <div class="section-icon">
                <i class="fas fa-phone-volume"></i>
            </div>
            <h2>Kontak & Jam Operasional</h2>
            <p>Hubungi kami untuk pertanyaan, pemesanan, atau konsultasi kebutuhan bangunan Anda:</p>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fab fa-whatsapp"></i>
                    <div>
                        <strong>WhatsApp</strong>
                        <a href="https://wa.me/6281234567890" target="_blank">0812-3456-7890</a>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <strong>Email</strong>
                        <a href="mailto:info@agungjaya.com">info@agungjaya.com</a>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <strong>Telepon</strong>
                        <a href="tel:0315556789">(031) 555-6789</a>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Jam Operasional</strong>
                        <span>Senin - Sabtu: 08.00 - 17.00</span>
                        <span>Minggu & Hari Libur: Tutup</span>
                    </div>
                </div>
            </div>
        </section>

    </div>
</main>

<?php include 'footer.php'; // Asumsikan path footer sudah benar ?>

</body>
</html>