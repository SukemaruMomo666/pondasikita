<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

if (!isset($_GET['zona_id'])) {
    die("ID Zona tidak valid.");
}
$zona_id = (int)$_GET['zona_id'];

// Ambil info zona
$stmt_zona = $koneksi->prepare("SELECT nama_zona FROM tb_zona_pengiriman WHERE id = ?");
$stmt_zona->bind_param("i", $zona_id);
$stmt_zona->execute();
$result_zona = $stmt_zona->get_result();
if ($result_zona->num_rows === 0) {
    die("Zona tidak ditemukan.");
}
$zona = $result_zona->fetch_assoc();
$stmt_zona->close();

// Ambil data biaya yang sudah ada untuk zona ini
$stmt_biaya = $koneksi->prepare("SELECT * FROM tb_biaya_pengiriman WHERE zona_id = ?");
$stmt_biaya->bind_param("i", $zona_id);
$stmt_biaya->execute();
$result_biaya = $stmt_biaya->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Atur Biaya Kirim - Admin Panel</title>
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
                    <h1 class="page-title">Atur Biaya Kirim untuk Zona: <?= htmlspecialchars($zona['nama_zona']) ?></h1>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Daftar Tarif</h4>
                                <div class="table-wrapper mt-4">
                                    <table class="table">
                                        <thead><tr><th>Tipe Biaya</th><th>Tarif</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
                                        <tbody>
                                            <?php if ($result_biaya && $result_biaya->num_rows > 0): ?>
                                                <?php while($biaya = $result_biaya->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= ucfirst($biaya['tipe_biaya']) ?></strong></td>
                                                    <td>Rp <?= number_format($biaya['biaya']) ?></td>
                                                    <td><?= htmlspecialchars($biaya['deskripsi']) ?></td>
                                                    <td><a href="#" class="btn btn-danger btn-sm">Hapus</a></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada tarif untuk zona ini.</td></tr>
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
                                <h4 class="card-title">Tambah Tarif Baru</h4>
                                <form action="actions/proses_biaya_kirim.php" method="POST">
                                    <input type="hidden" name="zona_id" value="<?= $zona_id ?>">
                                    <div class="form-group mt-4">
                                        <label for="tipe_biaya">Tipe Biaya</label>
                                        <select name="tipe_biaya" class="form-select">
                                            <option value="flat">Flat (Tarif Tetap)</option>
                                            <option value="per_km">Per Kilometer</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="biaya">Biaya (Rp)</label>
                                        <input type="number" name="biaya" class="form-control" required placeholder="Contoh: 50000">
                                    </div>
                                    <div class="form-group">
                                        <label for="deskripsi">Deskripsi (Opsional)</label>
                                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Contoh: Tarif flat untuk mobil pickup"></textarea>
                                    </div>
                                    <button type="submit" name="tambah_biaya" class="btn btn-primary w-100">Tambah Tarif</button>
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
