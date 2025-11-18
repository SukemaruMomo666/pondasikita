<?php
require_once 'config/koneksi.php'; // Sesuaikan path ke file koneksi Anda

// Periksa apakah slug ada di URL
if (!isset($_GET['slug'])) {
    die("Halaman tidak ditemukan. Slug toko tidak disediakan.");
}

$slug = $_GET['slug'];

// Ambil data utama toko
// Query ini tidak menggunakan JOIN karena struktur tb_toko Anda menyimpan nama lokasi sebagai teks.
$stmt_toko = $koneksi->prepare(
    "SELECT * FROM tb_toko WHERE slug = ? AND status = 'active'"
);
$stmt_toko->bind_param("s", $slug);
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();

if ($result_toko->num_rows === 0) {
    die("Toko tidak ditemukan atau tidak aktif.");
}
$toko = $result_toko->fetch_assoc();
$toko_id = $toko['id'];
$stmt_toko->close();

// Ambil data jam operasional
$jam_operasional = [];
$jam_query = $koneksi->prepare("SELECT * FROM tb_toko_jam_operasional WHERE toko_id = ? ORDER BY hari ASC");
$jam_query->bind_param("i", $toko_id);
$jam_query->execute();
$result_jam = $jam_query->get_result();
while ($row = $result_jam->fetch_assoc()) {
    $jam_operasional[$row['hari']] = $row;
}
$jam_query->close();

$daftar_hari = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Toko: <?= htmlspecialchars($toko['nama_toko']) ?></title>
    <link rel="stylesheet" href="assets/css/public_style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="shop-profile-container">
    <header class="shop-header">
        <img src="assets/uploads/logos/<?= htmlspecialchars($toko['logo_toko'] ?? 'default.png') ?>" alt="Logo Toko" class="shop-logo">
        <div class="shop-header-info">
            <h1><?= htmlspecialchars($toko['nama_toko']) ?></h1>
            <p class="shop-status <?= strtolower($toko['status_operasional']) ?>"><?= htmlspecialchars($toko['status_operasional']) ?></p>
        </div>
    </header>

    <main class="shop-content">
        <div class="main-column">
            <div class="info-card">
                <h2>Deskripsi Toko</h2>
                <p><?= nl2br(htmlspecialchars($toko['deskripsi_toko'] ?? 'Tidak ada deskripsi.')) ?></p>
            </div>
            
            <div class="info-card">
                 <h2>Produk Toko</h2>
                 <p>Fitur daftar produk akan ditambahkan di sini.</p>
            </div>
        </div>

        <aside class="sidebar-column">
            <div class="info-card">
                <h2>Informasi Toko</h2>
                <ul class="shop-details">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Alamat</strong>
                            <p><?= htmlspecialchars($toko['alamat_toko']) ?></p>
                            <small><?= htmlspecialchars($toko['kota']) ?>, <?= htmlspecialchars($toko['provinsi']) ?> <?= htmlspecialchars($toko['kode_pos']) ?></small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <strong>Telepon</strong>
                            <p><?= htmlspecialchars($toko['telepon_toko']) ?></p>
                        </div>
                    </li>
                     <li>
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Bergabung Sejak</strong>
                            <p><?= date('d F Y', strtotime($toko['created_at'])) ?></p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="info-card">
                <h2>Jam Operasional</h2>
                <ul class="operating-hours-list">
                    <?php for ($i = 0; $i < 7; $i++): 
                        $hari_index = $i + 1;
                        $data_hari = $jam_operasional[$hari_index] ?? null;
                    ?>
                    <li>
                        <span><?= $daftar_hari[$i] ?></span>
                        <?php if ($data_hari && $data_hari['is_buka']): ?>
                            <span class="hours"><?= date('H:i', strtotime($data_hari['jam_buka'])) ?> - <?= date('H:i', strtotime($data_hari['jam_tutup'])) ?></span>
                        <?php else: ?>
                            <span class="status-closed">Tutup</span>
                        <?php endif; ?>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </aside>
    </main>
</div>

</body>
</html>