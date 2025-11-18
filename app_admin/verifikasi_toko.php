<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Query untuk mengambil semua toko yang statusnya 'pending'
$sql = "SELECT t.*, u.nama as nama_pemilik, u.email 
        FROM tb_toko t 
        JOIN tb_user u ON t.user_id = u.id 
        WHERE t.status = 'pending' 
        ORDER BY t.created_at ASC";
$result = $koneksi->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Toko - Admin Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/template/spica/template/css/style.css">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"><i class="mdi mdi-check-decagram"></i> Verifikasi Toko Baru</h3>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daftar Pengajuan Toko</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Toko</th>
                                        <th>Pemilik</th>
                                        <th>Email</th>
                                        <th>Kota</th>
                                        <th>Tgl. Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($toko = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($toko['nama_toko']) ?></td>
                                            <td><?= htmlspecialchars($toko['nama_pemilik']) ?></td>
                                            <td><?= htmlspecialchars($toko['email']) ?></td>
                                            <td><?= htmlspecialchars($toko['kota']) ?></td>
                                            <td><?= date('d M Y', strtotime($toko['created_at'])) ?></td>
                                            <td>
                                                <a href="../actions/proses_verifikasi.php?toko_id=<?= $toko['id'] ?>&action=setujui" class="btn btn-sm btn-success" onclick="return confirm('Anda yakin ingin menyetujui toko ini?')">Setujui</a>
                                                <a href="../actions/proses_verifikasi.php?toko_id=<?= $toko['id'] ?>&action=tolak" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menolak toko ini?')">Tolak</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada pengajuan toko baru.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
