<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK TAB & FILTER ---
$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['semua', 'pending', 'active', 'suspended'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

// --- QUERY PENGAMBILAN DATA TOKO ---
$sql = "SELECT t.id, t.nama_toko, c.name AS nama_kota, t.status, t.created_at, u.nama AS nama_pemilik 
        FROM tb_toko t 
        JOIN tb_user u ON t.user_id = u.id
        LEFT JOIN cities c ON t.city_id = c.id";

if ($status_filter !== 'semua') {
    $sql .= " WHERE t.status = ?";
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $koneksi->prepare($sql);
if ($status_filter !== 'semua') {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$result_toko = $stmt->get_result();
$current_page_title = 'kelola Toko Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Toko - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <style>
        .nav-tabs {display: flex;gap: 1rem;border-bottom: 2px solid #ddd;}
        .nav-tabs .nav-link {padding: 10px 16px;border-radius: 8px 8px 0 0;background-color: #f3f4f6;text-decoration: none;font-weight: 500;color: #333;}
        .nav-tabs .nav-link.active {background-color: #fff;border-bottom: 2px solid #2563eb;color: #2563eb;}
        .status-badge {padding: 4px 8px;border-radius: 5px;font-size: 12px;font-weight: 600;}
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-suspended { background: #f8d7da; color: #721c24; }
        .feedback-alert {padding: 1rem;margin-bottom: 1.5rem;border-radius: 8px;font-weight: 500;display: flex;justify-content: space-between;align-items: center;}
        .feedback-alert.sukses { background-color: #D1FAE5; color: #065F46; }
        .feedback-alert.error { background-color: #FEE2E2; color: #991B1B; }
        .feedback-alert .close-btn { background: none; border: none; font-size: 1.25rem; cursor: pointer; }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar_admin.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <?php include 'partials/navbar_admin.php'; ?>
                <div class="page-header">
                    <h1 class="page-title">Kelola Toko</h1>
                </div>

                <!-- Feedback -->
                <?php if (isset($_SESSION['feedback'])): ?>
                    <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>">
                        <span><?= $_SESSION['feedback']['pesan'] ?></span>
                        <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                    <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <!-- Tabs Filter -->
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="tokoTab">
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'semua' ? 'active' : '' ?>" href="?status=semua">Semua</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="?status=pending">Perlu Diverifikasi</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'active' ? 'active' : '' ?>" href="?status=active">Aktif</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'suspended' ? 'active' : '' ?>" href="?status=suspended">Ditangguhkan</a></li>
                        </ul>

                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama Toko</th>
                                        <th>Pemilik</th>
                                        <th>Kota</th>
                                        <th>Tgl Daftar</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_toko->num_rows > 0): ?>
                                        <?php while($toko = $result_toko->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($toko['nama_toko']) ?></strong></td>
                                                <td><?= htmlspecialchars($toko['nama_pemilik']) ?></td>
                                                <td><?= htmlspecialchars($toko['nama_kota'] ?? '-') ?></td>
                                                <td><?= date('d M Y', strtotime($toko['created_at'])) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= strtolower($toko['status']) ?>">
                                                        <?= htmlspecialchars($toko['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($toko['status'] == 'pending'): ?>
                                                            <button class="btn btn-success btn-sm btn-confirm-action" 
                                                                    data-action-url="actions/proses_verifikasi_toko.php?toko_id=<?= $toko['id'] ?>&action=setujui"
                                                                    data-action-message="Anda yakin ingin MENYETUJUI toko '<?= htmlspecialchars($toko['nama_toko']) ?>'?">Setujui</button>
                                                            <button class="btn btn-danger btn-sm btn-confirm-action"
                                                                    data-action-url="actions/proses_verifikasi_toko.php?toko_id=<?= $toko['id'] ?>&action=tolak"
                                                                    data-action-message="Anda yakin ingin MENOLAK pengajuan toko '<?= htmlspecialchars($toko['nama_toko']) ?>'?">Tolak</button>
                                                        <?php else: ?>
                                                            <a href="detail_toko.php?id=<?= $toko['id'] ?>" class="btn btn-warning btn-sm">Detail</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6"><div class="text-center p-5"><i class="mdi mdi-store-off" style="font-size: 3rem; color: #ccc;"></i><p class="mt-2 text-secondary">Tidak ada toko.</p></div></td></tr>
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

<!-- Modal Konfirmasi -->
<div class="modal-overlay" id="confirmationModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">Konfirmasi Tindakan</h4>
            <button type="button" class="close-btn" id="close-confirm-modal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirm-message-text">Apakah Anda yakin ingin melanjutkan?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="cancel-confirm-btn">Batal</button>
            <a href="#" id="confirm-action-link" class="btn btn-primary">Ya, Lanjutkan</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const modal = $('#confirmationModal');

    $('.btn-confirm-action').on('click', function() {
        const message = $(this).data('action-message');
        const url = $(this).data('action-url');

        $('#confirm-message-text').text(message);
        $('#confirm-action-link').attr('href', url);

        modal.fadeIn();
    });

    $('#close-confirm-modal, #cancel-confirm-btn').on('click', function() {
        modal.fadeOut();
    });
});
</script>
</body>
</html>
