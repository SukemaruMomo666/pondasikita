<?php
// Koneksi ke database
include '../../../config/koneksi.php';

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// --- 1. Notifikasi Stok Hampir Habis (stok < 10) ---
// Logika stok tersedia sudah diterapkan di sini
$sqlStok = "SELECT nama_barang, (stok - stok_di_pesan) AS stok_tersedia 
            FROM tb_barang 
            WHERE (stok - stok_di_pesan) < 10 
            ORDER BY stok_tersedia ASC 
            LIMIT 5";
$resultStok = $koneksi->query($sqlStok);

$countStok = 0;
if ($resultStok) {
    $countStokResult = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_barang WHERE (stok - stok_di_pesan) < 10");
    if ($countStokResult) $countStok = $countStokResult->fetch_assoc()['jumlah'];
}

// --- 2. Notifikasi Pesanan Baru ---
$sqlPesanan = "SELECT id, kode_invoice, tanggal_transaksi FROM tb_transaksi WHERE status_pesanan = 'pending' ORDER BY tanggal_transaksi DESC LIMIT 5";
$resultPesanan = $koneksi->query($sqlPesanan);

$countPesanan = 0;
if ($resultPesanan) {
    $countPesananResult = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_transaksi WHERE status_pesanan = 'pending'");
    if ($countPesananResult) $countPesanan = $countPesananResult->fetch_assoc()['jumlah'];
}

// --- 3. Notifikasi Pesan Customer ---
$sqlPesan = "SELECT * FROM tb_pesan WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5";
$resultPesan = $koneksi->query($sqlPesan);

$countPesan = 0;
if ($resultPesan) {
    $countPesanResult = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_pesan WHERE is_read = 0");
    if ($countPesanResult) $countPesan = $countPesanResult->fetch_assoc()['jumlah'];
}

// --- 4. Notifikasi Laporan Produk ---
$sqlLaporanProduk = "SELECT lp.id, lp.alasan, b.nama_barang, u.nama AS nama_user
                       FROM tb_laporan_produk lp
                       JOIN tb_barang b ON lp.barang_id = b.id
                       JOIN tb_user u ON lp.user_id = u.id
                       WHERE lp.status = 'Baru'
                       ORDER BY lp.created_at DESC
                       LIMIT 5";
$resultLaporanProduk = $koneksi->query($sqlLaporanProduk);

$countLaporanProduk = 0;
if ($resultLaporanProduk) {
    $countLaporanProdukResult = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_laporan_produk WHERE status = 'Baru'");
    if ($countLaporanProdukResult) $countLaporanProduk = $countLaporanProdukResult->fetch_assoc()['jumlah'];
}

// --- Total Semua Notifikasi ---
$totalNotifikasi = $countStok + $countPesanan + $countPesan + $countLaporanProduk;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manajemen Produk</title>
    <link rel="stylesheet" href="../../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">
</head>
<body>

 <nav class="navbar col-lg-12 col-12 px-0 py-0 py-lg-4 d-flex flex-row">
    <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
        <div class="navbar-brand-wrapper">
            <a class="navbar-brand brand-logo" href="kelola_data_barang.php"><img src="../../../assets/image/logo.jpg" alt="logo" height="50px" width="50px"/></a>
            <a class="navbar-brand brand-logo-mini" href="kelola_data_barang.php"><img src="../../../assets/template/spica/template/images/logo-mini.svg" alt="logo"/></a>
        </div>
        <h4 class="font-weight-bold mb-0 d-none d-md-block mt-1">Selamat datang kembali, Admin</h4>
        <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
                    <i class="mdi mdi-bell-outline"></i>
                    <?php if ($totalNotifikasi > 0): ?>
                        <span class="count bg-danger"><?= $totalNotifikasi ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" aria-labelledby="notificationDropdown" style="max-height: 450px; overflow-y: auto;">
                    <p class="mb-0 font-weight-normal float-left dropdown-header">Notifikasi</p>

                    <?php if ($resultStok && $resultStok->num_rows > 0): ?>
                        <p class="font-weight-bold text-warning px-3 mt-2">Stok Hampir Habis</p>
                        <?php while ($row = $resultStok->fetch_assoc()): ?>
                            <a href="edit_product.php?id=<?= $row['id'] ?? '' ?>" class="dropdown-item preview-item">
                                <div class="preview-thumbnail">
                                    <div class="preview-icon bg-warning"><i class="mdi mdi-alert-circle-outline mx-0"></i></div>
                                </div>
                                <div class="preview-item-content">
                                    <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($row['nama_barang']) ?></h6>
                                    <p class="font-weight-light small-text mb-0 text-muted">Tersisa <?= (int)$row['stok_tersedia'] ?> unit</p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if ($resultPesanan && $resultPesanan->num_rows > 0): ?>
                        <p class="font-weight-bold text-info px-3 mt-3">Pesanan Baru</p>
                        <?php while ($row = $resultPesanan->fetch_assoc()): ?>
                            <a href="detail_pesanan.php?id=<?= $row['id'] ?? '' ?>" class="dropdown-item preview-item">
                                <div class="preview-thumbnail">
                                    <div class="preview-icon bg-info"><i class="mdi mdi-cart mx-0"></i></div>
                                </div>
                                <div class="preview-item-content">
                                    <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($row['kode_invoice']) ?></h6>
                                    <p class="font-weight-light small-text mb-0 text-muted">Masuk <?= date('d M Y, H:i', strtotime($row['tanggal_transaksi'])) ?></p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if ($totalNotifikasi === 0): ?>
                        <p class="font-weight-light small-text mb-0 text-muted px-3 text-center py-2">Tidak ada notifikasi baru</p>
                    <?php endif; ?>

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center text-primary" href="semua_notifikasi.php">Lihat Semua Notifikasi</a>
                </div>
            </li>

            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="profileDropdown">
                    <i class="mdi mdi-account-circle" style="font-size: 40px; color: white"></i>
                    <span class="nav-profile-name">Admin</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                    <a class="dropdown-item" href="../../../auth/logout.php?action=logout">
                        <i class="mdi mdi-logout text-primary"></i> Keluar
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<audio id="notifSound" src="../../../assets/sounds/notification.mp3" preload="auto"></audio>
<script>
    // Menyimpan jumlah notifikasi awal dari PHP
    let lastNotifCount = <?php echo $totalNotifikasi; ?>;

    function cekNotifikasi() {
        // Panggil file PHP yang bertugas menghitung notifikasi
        fetch('../../../proses/cek_notifikasi.php') 
            .then(response => response.json())
            .then(data => {
                // Jumlahkan semua jenis notifikasi dari data JSON
                const total = data.stok + data.pesanan + data.pesan + data.laporan;
                const notifBadge = document.querySelector('#notificationDropdown .count');
                const bellIcon = document.querySelector('#notificationDropdown .mdi-bell-outline');

                if (total > 0) {
                    // Jika ada notif badge, update angkanya. Jika tidak, buat baru.
                    if (notifBadge) {
                        notifBadge.textContent = total;
                    } else {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'count bg-danger';
                        newBadge.textContent = total;
                        bellIcon.parentNode.appendChild(newBadge);
                    }
                } else {
                    // Jika total 0, hapus badge jika ada
                    if (notifBadge) {
                        notifBadge.remove();
                    }
                }
                
                // Jika jumlah notifikasi baru lebih banyak dari sebelumnya, putar suara
                if (total > lastNotifCount) {
                    // Memuat ulang dropdown untuk menampilkan notif baru
                    // Ini adalah cara sederhana, bisa dioptimalkan lebih lanjut
                    if (document.querySelector('.dropdown-menu.show') === null) {
                       location.reload(); 
                    }
                    document.getElementById('notifSound').play().catch(e => console.error("Audio play failed:", e));
                }

                // Update jumlah notifikasi terakhir
                lastNotifCount = total;
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Jalankan fungsi cekNotifikasi setiap 15 detik
    setInterval(cekNotifikasi, 15000);
</script>
</body>
</html>