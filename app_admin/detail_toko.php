<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID Toko tidak valid.");
}
$toko_id = (int)$_GET['id'];

// Query ambil data toko + user + wilayah lengkap
$sql_toko = "SELECT t.*, u.nama AS nama_pemilik, u.email AS email_pemilik,
             pr.name AS provinsi,
             c.name AS kota,
             d.name AS kecamatan
             FROM tb_toko t
             JOIN tb_user u ON t.user_id = u.id
             LEFT JOIN provinces pr ON t.province_id = pr.id
             LEFT JOIN cities c ON t.city_id = c.id
             LEFT JOIN districts d ON t.district_id = d.id
             WHERE t.id = ?";
$stmt_toko = $koneksi->prepare($sql_toko);
$stmt_toko->bind_param("i", $toko_id);
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();

if ($result_toko->num_rows === 0) {
    die("Toko tidak ditemukan.");
}

$toko = $result_toko->fetch_assoc();

// Statistik toko
$sql_stats = "SELECT 
    (SELECT COUNT(id) FROM tb_barang WHERE toko_id = ?) AS total_produk,
    (SELECT COUNT(DISTINCT transaksi_id) FROM tb_detail_transaksi WHERE toko_id = ?) AS total_pesanan,
    (SELECT SUM(subtotal) FROM tb_detail_transaksi WHERE toko_id = ?) AS total_penjualan";
$stmt_stats = $koneksi->prepare($sql_stats);
$stmt_stats->bind_param("iii", $toko_id, $toko_id, $toko_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Produk terbaru toko
$sql_produk = "SELECT id, nama_barang, harga, stok FROM tb_barang WHERE toko_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt_produk = $koneksi->prepare($sql_produk);
$stmt_produk->bind_param("i", $toko_id);
$stmt_produk->execute();
$result_produk = $stmt_produk->get_result();

$current_page_name = 'kelola_toko.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Detail Toko</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../assets/css/admin_style.css" />
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <style>
        /* style badge dan layout sama seperti sebelumnya */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-suspended { background: #f8d7da; color: #721c24; }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .stat-title { font-size: 14px; color: #6b7280; }
        .stat-value { font-size: 20px; font-weight: 600; color: #111827; }
        .info-list { list-style: none; padding: 0; }
        .info-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        .label { color: #6b7280; }
        .content-wrapper .row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .col-md-5, .col-md-7 { flex: 1; min-width: 300px; }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        table.table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        table.table th, table.table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        table.table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar_admin.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <?php include 'partials/navbar_admin.php'; ?>

                <!-- Header Toko -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div>
                            <h1 class="page-title mb-1"><?= htmlspecialchars($toko['nama_toko']) ?></h1>
                            <p class="text-secondary m-0">Detail lengkap dan statistik toko.</p>
                            <a href="kelola_toko.php" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div>
                        <?php if ($toko['status'] === 'active'): ?>
                            <a href="actions/proses_suspend_toko.php?id=<?= $toko['id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Suspend toko ini?')">Suspend</a>
                        <?php elseif ($toko['status'] === 'suspended'): ?>
                            <a href="actions/proses_suspend_toko.php?id=<?= $toko['id'] ?>" 
                               class="btn btn-success" 
                               onclick="return confirm('Aktifkan toko ini kembali?')">Aktifkan</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistik -->
                <div class="detail-grid">
                    <div class="stat-card">
                        <div class="stat-title">Total Penjualan</div>
                        <div class="stat-value">Rp <?= number_format($stats['total_penjualan'] ?? 0) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Total Pesanan</div>
                        <div class="stat-value"><?= number_format($stats['total_pesanan'] ?? 0) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Jumlah Produk</div>
                        <div class="stat-value"><?= number_format($stats['total_produk'] ?? 0) ?></div>
                    </div>
                </div>

                <!-- Detail Toko dan Produk -->
                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <h4 class="card-title">Informasi Toko</h4>
                            <ul class="info-list mt-3">
                                <li><span class="label">Nama Pemilik</span><span class="value"><?= htmlspecialchars($toko['nama_pemilik']) ?></span></li>
                                <li><span class="label">Email</span><span class="value"><?= htmlspecialchars($toko['email_pemilik']) ?></span></li>
                                <li><span class="label">Telepon</span><span class="value"><?= htmlspecialchars($toko['telepon_toko']) ?></span></li>
                                <li><span class="label">Alamat</span><span class="value"><?= htmlspecialchars($toko['alamat_toko']) ?></span></li>
                                <li><span class="label">Provinsi</span><span class="value"><?= htmlspecialchars($toko['provinsi'] ?? '-') ?></span></li>
                                <li><span class="label">Kota</span><span class="value"><?= htmlspecialchars($toko['kota'] ?? '-') ?></span></li>
                                <li><span class="label">Kecamatan</span><span class="value"><?= htmlspecialchars($toko['kecamatan'] ?? '-') ?></span></li>
                                <li><span class="label">Kode Pos</span><span class="value"><?= htmlspecialchars($toko['kode_pos'] ?? '-') ?></span></li>
                                <li><span class="label">Status</span>
                                    <span class="value">
                                        <span class="status-badge status-<?= strtolower($toko['status']) ?>">
                                            <?= htmlspecialchars($toko['status']) ?>
                                        </span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <h4 class="card-title">10 Produk Terakhir</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_produk->num_rows > 0): ?>
                                        <?php while($produk = $result_produk->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($produk['nama_barang']) ?></td>
                                                <td>Rp <?= number_format($produk['harga']) ?></td>
                                                <td><?= htmlspecialchars($produk['stok']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada produk.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
