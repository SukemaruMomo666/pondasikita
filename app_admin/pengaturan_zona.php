<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// Ambil semua zona pengiriman dari database
$result_zona = $koneksi->query("SELECT * FROM tb_zona_pengiriman ORDER BY nama_zona ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Zona Pengiriman - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'pengaturan_zona.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Pengaturan Logistik & Pengiriman</h1>
                </div>

                <?php if (isset($_SESSION['feedback'])): ?>
                <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>" style="margin-bottom: 1.5rem;">
                    <span><?= $_SESSION['feedback']['pesan'] ?></span>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Daftar Zona Pengiriman</h4>
                                <div class="table-wrapper mt-4">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Nama Zona</th>
                                                <th>Deskripsi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result_zona && $result_zona->num_rows > 0): ?>
                                                <?php while($zona = $result_zona->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($zona['nama_zona']) ?></strong></td>
                                                    <td><?= htmlspecialchars($zona['deskripsi']) ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <!-- TOMBOL BARU UNTUK MENGATUR BIAYA -->
                                                            <a href="pengaturan_biaya_kirim.php?zona_id=<?= $zona['id'] ?>" class="btn btn-primary btn-sm">Atur Biaya</a>
                                                            <a href="actions/proses_zona.php?action=hapus&id=<?= $zona['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus zona ini?')">Hapus</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada zona pengiriman.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Tambah Zona Baru</h4>
                                <form action="actions/proses_zona.php" method="POST">
                                    <div class="form-group mt-4">
                                        <label for="nama_zona">Nama Zona</label>
                                        <input type="text" name="nama_zona" class="form-control" required placeholder="Contoh: Jakarta Pusat">
                                    </div>
                                    <div class="form-group">
                                        <label for="deskripsi">Deskripsi (Opsional)</label>
                                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Contoh: Area Jakarta Pusat dan sekitarnya"></textarea>
                                    </div>
                                    <button type="submit" name="tambah_zona" class="btn btn-primary w-100">Tambah Zona</button>
                                </form>
                            </div>
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
