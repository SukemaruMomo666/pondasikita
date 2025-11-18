<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK FILTER ---
$filter_toko_id = filter_input(INPUT_GET, 'toko_id', FILTER_VALIDATE_INT);
$filter_tanggal_mulai = $_GET['start_date'] ?? '';
$filter_tanggal_selesai = $_GET['end_date'] ?? '';
$filter_status_pembayaran = $_GET['payment_status'] ?? 'semua';
$filter_status_pesanan = $_GET['order_status'] ?? 'semua';

// --- QUERY PENGAMBILAN DATA TRANSAKSI ---
$sql = "SELECT DISTINCT
            t.id, t.kode_invoice, t.total_final, t.metode_pembayaran, 
            t.status_pembayaran, t.status_pesanan_global, t.tanggal_transaksi,
            u.nama as nama_pelanggan
        FROM tb_transaksi t
        JOIN tb_user u ON t.user_id = u.id
        -- Join ke detail transaksi jika ada filter toko
        " . ($filter_toko_id ? "JOIN tb_detail_transaksi dt ON t.id = dt.transaksi_id" : "");

$params = [];
$types = '';
$where_clauses = [];

// Filter berdasarkan toko_id (dari halaman laporan penjualan)
if ($filter_toko_id) {
    $where_clauses[] = "dt.toko_id = ?";
    $params[] = $filter_toko_id;
    $types .= 'i';
}

// Filter berdasarkan rentang tanggal
if (!empty($filter_tanggal_mulai)) {
    $where_clauses[] = "DATE(t.tanggal_transaksi) >= ?";
    $params[] = $filter_tanggal_mulai;
    $types .= 's';
}
if (!empty($filter_tanggal_selesai)) {
    $where_clauses[] = "DATE(t.tanggal_transaksi) <= ?";
    $params[] = $filter_tanggal_selesai;
    $types .= 's';
}

// Filter berdasarkan status pembayaran
if ($filter_status_pembayaran !== 'semua' && !empty($filter_status_pembayaran)) {
    $where_clauses[] = "t.status_pembayaran = ?";
    $params[] = $filter_status_pembayaran;
    $types .= 's';
}

// Filter berdasarkan status pesanan
if ($filter_status_pesanan !== 'semua' && !empty($filter_status_pesanan)) {
    $where_clauses[] = "t.status_pesanan_global = ?";
    $params[] = $filter_status_pesanan;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY t.tanggal_transaksi DESC";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_transaksi = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'laporan_transaksi.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Laporan Semua Transaksi</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="" method="GET">
                            <div class="report-filter-bar">
                                <div class="filter-item">
                                    <label for="start_date">Dari Tanggal</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filter_tanggal_mulai) ?>">
                                </div>
                                <div class="filter-item">
                                    <label for="end_date">Sampai Tanggal</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filter_tanggal_selesai) ?>">
                                </div>
                                <div class="filter-item">
                                    <label for="payment_status">Status Pembayaran</label>
                                    <select name="payment_status" id="payment_status" class="form-select">
                                        <option value="semua" <?= $filter_status_pembayaran == 'semua' ? 'selected' : '' ?>>Semua</option>
                                        <option value="paid" <?= $filter_status_pembayaran == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="pending" <?= $filter_status_pembayaran == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="failed" <?= $filter_status_pembayaran == 'failed' ? 'selected' : '' ?>>Failed</option>
                                    </select>
                                </div>
                                <div class="filter-item">
                                    <label for="order_status">Status Pesanan</label>
                                    <select name="order_status" id="order_status" class="form-select">
                                        <option value="semua" <?= $filter_status_pesanan == 'semua' ? 'selected' : '' ?>>Semua</option>
                                        <option value="diproses" <?= $filter_status_pesanan == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                        <option value="selesai" <?= $filter_status_pesanan == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                        <option value="dibatalkan" <?= $filter_status_pesanan == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                    </select>
                                </div>
                                <div class="filter-item mt-auto">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </div>
                        </form>

                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr><th>Invoice</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_transaksi->num_rows > 0): ?>
                                        <?php while($trx = $result_transaksi->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($trx['kode_invoice']) ?></strong></td>
                                                <td><?= htmlspecialchars($trx['nama_pelanggan']) ?></td>
                                                <td><strong>Rp <?= number_format($trx['total_final'], 0, ',', '.') ?></strong></td>
                                                <td class="payment-status <?= strtolower($trx['status_pembayaran']) ?>"><?= ucfirst($trx['status_pembayaran']) ?></td>
                                                <td><span class="status-badge status-<?= strtolower(str_replace(' ', '_', $trx['status_pesanan_global'])) ?>"><?= str_replace('_', ' ', $trx['status_pesanan_global']) ?></span></td>
                                                <td><?= date('d M Y, H:i', strtotime($trx['tanggal_transaksi'])) ?></td>
                                                <td><a href="detail_transaksi.php?id=<?= $trx['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7"><div class="text-center p-5"><p class="text-secondary">Tidak ada transaksi yang cocok dengan filter.</p></div></td></tr>
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
