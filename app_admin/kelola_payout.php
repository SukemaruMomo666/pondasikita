<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK TAB ---
$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'completed', 'rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

// --- QUERY PENGAMBILAN DATA PAYOUT ---
// Query ini mengasumsikan detail rekening ada di tabel tb_toko.
// Sesuaikan jika struktur database Anda berbeda.
$sql = "SELECT p.id, p.jumlah_payout, p.status, p.tanggal_request, p.catatan_admin,
               t.nama_toko, u.nama as nama_pemilik
        FROM tb_payouts p
        JOIN tb_toko t ON p.toko_id = t.id
        JOIN tb_user u ON t.user_id = u.id
        WHERE p.status = ?
        ORDER BY p.tanggal_request DESC";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $status_filter);
$stmt->execute();
$result_payouts = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Payout - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'kelola_payout.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Manajemen Payout</h1>
                </div>

                <?php if (isset($_SESSION['feedback'])): ?>
                <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>" style="margin-bottom: 1.5rem;">
                    <span><?= $_SESSION['feedback']['pesan'] ?></span>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="payoutTab">
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="?status=pending">Permintaan Baru</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'completed' ? 'active' : '' ?>" href="?status=completed">Selesai</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status_filter == 'rejected' ? 'active' : '' ?>" href="?status=rejected">Ditolak</a></li>
                        </ul>

                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr><th>Tgl Request</th><th>Toko</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_payouts->num_rows > 0): ?>
                                        <?php while($payout = $result_payouts->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('d M Y, H:i', strtotime($payout['tanggal_request'])) ?></td>
                                                <td><strong><?= htmlspecialchars($payout['nama_toko']) ?></strong><br><small class="text-secondary"><?= htmlspecialchars($payout['nama_pemilik']) ?></small></td>
                                                <td><strong>Rp <?= number_format($payout['jumlah_payout'], 0, ',', '.') ?></strong></td>
                                                <td><span class="status-badge status-<?= strtolower($payout['status']) ?>"><?= htmlspecialchars($payout['status']) ?></span></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($payout['status'] == 'pending'): ?>
                                                            <button class="btn btn-success btn-sm btn-proses" data-id="<?= $payout['id'] ?>" data-info="Toko: <?= htmlspecialchars($payout['nama_toko']) ?> | Jumlah: Rp <?= number_format($payout['jumlah_payout'], 0, ',', '.') ?>">Proses</button>
                                                            <button class="btn btn-danger btn-sm btn-tolak" data-id="<?= $payout['id'] ?>">Tolak</button>
                                                        <?php else: ?>
                                                            <button class="btn btn-outline btn-sm" disabled>Selesai</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5"><div class="text-center p-5"><p class="text-secondary">Tidak ada data payout.</p></div></td></tr>
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

<!-- Modal Konfirmasi Proses -->
<div class="modal-overlay" id="prosesModal">
    <div class="modal-content">
        <form action="actions/proses_payout.php" method="POST">
            <div class="modal-header"><h4 class="modal-title">Konfirmasi Proses Payout</h4><button type="button" class="close-btn" data-close-modal>&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="payout_id" id="proses-payout-id">
                <input type="hidden" name="action" value="approve">
                <p>Anda akan memproses pembayaran berikut:</p>
                <p id="proses-info" class="font-weight-bold"></p>
                <hr>
                <p class="text-danger"><strong>PENTING:</strong> Pastikan Anda sudah melakukan transfer manual ke rekening seller sebelum menekan tombol "Konfirmasi".</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
                <button type="submit" class="btn btn-success">Ya, Konfirmasi Sudah Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Tolak -->
<div class="modal-overlay" id="tolakModal">
    <div class="modal-content">
        <form action="actions/proses_payout.php" method="POST">
            <div class="modal-header"><h4 class="modal-title">Tolak Permintaan Payout</h4><button type="button" class="close-btn" data-close-modal>&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="payout_id" id="tolak-payout-id">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="alasan_penolakan">Alasan Penolakan (Wajib diisi)</label>
                    <textarea class="form-control" name="catatan_admin" rows="3" required placeholder="Contoh: Nomor rekening tidak valid"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Permintaan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Logika untuk Modal Proses
    $('.btn-proses').on('click', function() {
        $('#proses-payout-id').val($(this).data('id'));
        $('#proses-info').text($(this).data('info'));
        $('#prosesModal').css('display', 'flex');
    });

    // Logika untuk Modal Tolak
    $('.btn-tolak').on('click', function() {
        $('#tolak-payout-id').val($(this).data('id'));
        $('#tolakModal').css('display', 'flex');
    });

    // Logika untuk menutup semua modal
    $('[data-close-modal]').on('click', function() {
        $(this).closest('.modal-overlay').fadeOut();
    });
});
</script>
</body>
</html>
