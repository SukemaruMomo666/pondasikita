<?php
// Koneksi ke database
include '../config/koneksi.php';

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// --- 1. Notifikasi Stok Hampir Habis (stok < 10) ---
$sqlStok = "SELECT nama_barang, stok FROM tb_barang WHERE stok < 10 ORDER BY stok ASC LIMIT 5";
$resultStok = $koneksi->query($sqlStok);

$countStok = 0;
if ($resultStok) {
    $countStok = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_barang WHERE stok < 10")->fetch_assoc()['jumlah'];
}

// --- 2. Notifikasi Pesanan Baru (status = 'pending') ---
$sqlPesanan = "SELECT kode_pesanan, tanggal_pesanan FROM tb_pesanan WHERE status_pesanan = 'pending' ORDER BY tanggal_pesanan DESC LIMIT 5";
$resultPesanan = $koneksi->query($sqlPesanan);

$countPesanan = 0;
if ($resultPesanan) {
    $countPesanan = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_pesanan WHERE status_pesanan = 'pending'")->fetch_assoc()['jumlah'];
}

// --- 3. Notifikasi Pesan Customer (tabel tb_pesan) ---
$sqlPesan = "SELECT * FROM tb_pesan ORDER BY created_at DESC LIMIT 5";
$resultPesan = $koneksi->query($sqlPesan);

$countPesan = 0;
if ($resultPesan) {
    $countPesan = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_pesan")->fetch_assoc()['jumlah'];
}

// --- 4. Notifikasi Laporan Produk Customer (status = 'Baru') ---
$sqlLaporanProduk = "SELECT lp.id, lp.alasan, lp.deskripsi, lp.created_at, b.nama_barang, u.nama AS nama_user
                     FROM tb_laporan_produk lp
                     JOIN tb_barang b ON lp.barang_id = b.id
                     JOIN tb_user u ON lp.user_id = u.id
                     WHERE lp.status = 'Baru'
                     ORDER BY lp.created_at DESC
                     LIMIT 5";
$resultLaporanProduk = $koneksi->query($sqlLaporanProduk);

$countLaporanProduk = 0;
if ($resultLaporanProduk) {
    $countLaporanProduk = $koneksi->query("SELECT COUNT(*) AS jumlah FROM tb_laporan_produk WHERE status = 'Baru'")
                                   ->fetch_assoc()['jumlah'];
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/vendor.bundle.base.css">
 
    <link rel="shortcut icon" href="../assets/template/spica/template/images/favicon.png" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
</head>
<body>

 <nav class="navbar col-lg-12 col-12 px-0 py-0 py-lg-4 d-flex flex-row">
                <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                        <span class="mdi mdi-menu"></span>
                    </button>
                    <div class="navbar-brand-wrapper">
                        <a class="navbar-brand brand-logo" href="kelola_data_barang.php"><img src="../assets/image/logo.jpg" alt="logo" height="50px" width="50px"/></a>
                        <a class="navbar-brand brand-logo-mini" href="kelola_data_barang.php"><img src="../assets/template/spica/template/images/logo-mini.svg" alt="logo"/></a>
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
    <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
        <div class="notifikasi-scroll">
            <p class="mb-0 font-weight-normal float-left dropdown-header">Notifikasi</p>

            <!-- Notifikasi Stok Hampir Habis -->
            <?php if ($resultStok && $resultStok->num_rows > 0): ?>
                <p class="font-weight-bold text-warning px-3 mt-2">Stok Hampir Habis</p>
                <?php while ($row = $resultStok->fetch_assoc()): ?>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <div class="preview-icon bg-warning">
                                <i class="mdi mdi-alert-circle-outline mx-0"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($row['nama_barang']) ?></h6>
                            <p class="font-weight-light small-text mb-0 text-muted">Tersisa <?= (int)$row['stok'] ?> unit</p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- Notifikasi Pesanan Baru -->
            <?php if ($resultPesanan && $resultPesanan->num_rows > 0): ?>
                <p class="font-weight-bold text-info px-3 mt-3">Pesanan Baru</p>
                <?php while ($row = $resultPesanan->fetch_assoc()): ?>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <div class="preview-icon bg-info">
                                <i class="mdi mdi-cart mx-0"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($row['kode_pesanan']) ?></h6>
                            <p class="font-weight-light small-text mb-0 text-muted">Masuk <?= date('d M Y, H:i', strtotime($row['tanggal_pesanan'])) ?></p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- Notifikasi Pesan Customer -->
            <?php if ($resultPesan && $resultPesan->num_rows > 0): ?>
                <p class="font-weight-bold text-primary px-3 mt-3">Pesan Customer</p>
                <?php while ($row = $resultPesan->fetch_assoc()): ?>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <div class="preview-icon bg-primary">
                                <i class="mdi mdi-email-outline mx-0"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($row['name']) ?></h6>
                            <p class="font-weight-light small-text mb-0 text-muted text-truncate" style="max-width: 200px;">
                                <?= htmlspecialchars($row['message']) ?>
                            </p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- Notifikasi Laporan Produk -->
            <?php if ($resultLaporanProduk && $resultLaporanProduk->num_rows > 0): ?>
                <p class="font-weight-bold text-danger px-3 mt-3">Laporan Produk</p>
                <?php while ($row = $resultLaporanProduk->fetch_assoc()): ?>
                    <a class="dropdown-item preview-item">
                        <div class="preview-thumbnail">
                            <div class="preview-icon bg-danger">
                                <i class="mdi mdi-alert mx-0"></i>
                            </div>
                        </div>
                        <div class="preview-item-content">
                            <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($row['nama_user']) ?> melaporkan</h6>
                            <p class="font-weight-light small-text mb-0 text-muted text-truncate" style="max-width: 200px;">
                                <?= htmlspecialchars($row['alasan']) ?> - <?= htmlspecialchars($row['nama_barang']) ?>
                            </p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- Tidak ada notifikasi -->
            <?php if ($totalNotifikasi === 0): ?>
                <p class="font-weight-light small-text mb-0 text-muted px-3 text-center py-2">Tidak ada notifikasi baru</p>
            <?php endif; ?>

            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-center text-primary" href="lihat_pesan.php">Lihat Semua Notifikasi</a>
        </div>
    </div>
</li>

                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="profileDropdown">
                               <i class="mdi mdi-account-circle" style="font-size: 40px; color: white"></i>
                                <span class="nav-profile-name">Admin</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                            <a class="dropdown-item" href="../auth/logout.php?action=logout">
                                <i class="mdi mdi-logout text-primary"></i> Keluar
                            </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

    <script>
    let lastNotifCount = <?php echo $totalNotifikasi; ?>;

    function cekNotifikasi() {
        fetch('cek_notifikasi.php') 
            .then(response => response.json())
            .then(data => {
                const total = data.stok + data.pesanan + data.pesan;
                const notifBadge = document.querySelector('#notificationDropdown .count');

                if (notifBadge) {
                    if (total > 0) {
                        notifBadge.textContent = total;
                        notifBadge.style.display = 'inline-block';
                    } else {
                        notifBadge.style.display = 'none';
                    }
                }

                if (total > lastNotifCount) {
                    document.getElementById('notifSound').play().catch(e => console.error("Audio play failed:", e));
                }

                lastNotifCount = total;
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    setInterval(cekNotifikasi, 15000);
    </script>
</body>
</html>