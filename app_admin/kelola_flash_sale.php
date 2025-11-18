<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// Ambil semua event flash sale
$sql = "SELECT id, nama_event, tanggal_mulai, tanggal_berakhir, is_active FROM tb_flash_sale_events ORDER BY tanggal_mulai DESC";
$result_events = $koneksi->query($sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Flash Sale - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'kelola_flash_sale.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1 class="page-title">Kelola Flash Sale</h1>
                    <a href="form_flash_sale.php" class="btn btn-primary"><i class="mdi mdi-plus"></i> Buat Event Baru</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daftar Event Flash Sale</h4>
                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama Event</th>
                                        <th>Periode Berlangsung</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_events && $result_events->num_rows > 0): ?>
                                        <?php while($event = $result_events->fetch_assoc()): ?>
                                            <?php
                                                $now = new DateTime();
                                                $start = new DateTime($event['tanggal_mulai']);
                                                $end = new DateTime($event['tanggal_berakhir']);
                                                $status = 'Selesai';
                                                $status_class = 'rejected';
                                                if ($now < $start) {
                                                    $status = 'Akan Datang';
                                                    $status_class = 'pending';
                                                } elseif ($now >= $start && $now <= $end) {
                                                    $status = 'Berlangsung';
                                                    $status_class = 'active';
                                                }
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($event['nama_event']) ?></strong></td>
                                                <td><?= date('d M Y, H:i', strtotime($event['tanggal_mulai'])) ?> - <?= date('d M Y, H:i', strtotime($event['tanggal_berakhir'])) ?></td>
                                                <td><span class="status-badge status-<?= $status_class ?>"><?= $status ?></span></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="detail_flash_sale.php?id=<?= $event['id'] ?>" class="btn btn-outline btn-sm">Lihat Produk</a>
                                                        <a href="form_flash_sale.php?id=<?= $event['id'] ?>" class="btn btn-outline-dark btn-sm">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4"><div class="text-center p-5"><p class="text-secondary">Belum ada event Flash Sale yang dibuat.</p></div></td></tr>
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
