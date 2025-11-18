<?php
include '../../../config/koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Semua Notifikasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notif-section {
            margin-bottom: 30px;
        }
        .notif-title {
            border-left: 5px solid maroon;
            padding-left: 10px;
            font-weight: bold;
            font-size: 18px;
            color: #5E1914;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-center">Semua Notifikasi</h2>

    <!-- 🔸 Stok Hampir Habis -->
    <div class="notif-section">
        <div class="notif-title">Stok Hampir Habis</div>
        <ul class="list-group mt-2">
            <?php
            $stokQuery = $koneksi->query("SELECT nama_barang, stok FROM tb_barang WHERE stok < 10 ORDER BY stok ASC");
            if ($stokQuery && $stokQuery->num_rows > 0):
                while ($row = $stokQuery->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($row['nama_barang']) ?>
                        <span class="badge bg-danger rounded-pill">Stok: <?= $row['stok'] ?></span>
                    </li>
                <?php endwhile;
            else: ?>
                <li class="list-group-item text-muted">Tidak ada barang dengan stok rendah</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- 🔸 Pesanan Baru -->
    <div class="notif-section">
        <div class="notif-title">Pesanan Baru</div>
        <ul class="list-group mt-2">
            <?php
            $pesananQuery = $koneksi->query("SELECT kode_pesanan, tanggal_pesanan FROM tb_transaksi WHERE status_pesanan = 'pending' ORDER BY tanggal_pesanan DESC");
            if ($pesananQuery && $pesananQuery->num_rows > 0):
                while ($row = $pesananQuery->fetch_assoc()): ?>
                    <li class="list-group-item">
                        Pesanan <strong><?= htmlspecialchars($row['kode_pesanan']) ?></strong>
                        <div class="text-muted small">Masuk pada <?= date('d-m-Y H:i', strtotime($row['tanggal_pesanan'])) ?></div>
                    </li>
                <?php endwhile;
            else: ?>
                <li class="list-group-item text-muted">Tidak ada pesanan baru</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- 🔸 Pesan Customer -->
    <div class="notif-section">
        <div class="notif-title">Pesan dari Customer</div>
        <ul class="list-group mt-2">
            <?php
            $pesanQuery = $koneksi->query("SELECT name, message, created_at FROM tb_pesan ORDER BY created_at DESC LIMIT 10");
            if ($pesanQuery && $pesanQuery->num_rows > 0):
                while ($row = $pesanQuery->fetch_assoc()): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($row['name']) ?>:</strong> <?= htmlspecialchars($row['message']) ?>
                        <div class="text-muted small">Dikirim pada <?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></div>
                    </li>
                <?php endwhile;
            else: ?>
                <li class="list-group-item text-muted">Belum ada pesan dari customer</li>
            <?php endif; ?>
        </ul>
    </div>

   <!-- 🔸 Laporan Produk -->
<div class="notif-section">
    <div class="notif-title">Laporan Produk</div>
    <ul class="list-group mt-2">
        <?php
        $laporanQuery = $koneksi->query("
            SELECT lp.created_at, u.username, b.nama_barang, lp.alasan, lp.deskripsi, lp.status
            FROM tb_laporan_produk lp
            JOIN tb_barang b ON lp.barang_id = b.id
            JOIN tb_user u ON lp.user_id = u.id
            ORDER BY lp.created_at DESC
        ");
        if ($laporanQuery && $laporanQuery->num_rows > 0):
            while ($row = $laporanQuery->fetch_assoc()): ?>
                <li class="list-group-item">
                    <strong><?= htmlspecialchars($row['username']) ?></strong> melaporkan 
                    <strong><?= htmlspecialchars($row['nama_barang']) ?></strong><br>
                    <small class="text-muted">Alasan: <?= htmlspecialchars($row['alasan']) ?></small><br>
                    <small><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></small><br>
                    <span class="badge bg-<?= $row['status'] == 'Baru' ? 'danger' : ($row['status'] == 'Diproses' ? 'warning text-dark' : 'success') ?>">
                        <?= $row['status'] ?>
                    </span>
                    <div class="text-muted small">Dilaporkan pada <?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></div>
                </li>
            <?php endwhile;
        else: ?>
            <li class="list-group-item text-muted">Belum ada laporan produk</li>
        <?php endif; ?>
    </ul>
</div>