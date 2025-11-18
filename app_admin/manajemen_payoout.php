<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Logika untuk Tab
$status = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'completed', 'rejected'];
if (!in_array($status, $allowed_statuses)) {
    $status = 'pending';
}

// Query untuk mengambil data payout berdasarkan status
$sql = "SELECT p.*, t.nama_toko, u.nama as nama_pemilik,
               t.rekening_bank, t.nomor_rekening, t.atas_nama_rekening
        FROM tb_payouts p
        JOIN tb_toko t ON p.toko_id = t.id
        JOIN tb_user u ON t.user_id = u.id
        WHERE p.status = ?
        ORDER BY p.tanggal_request DESC";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $status);
$stmt->execute();
$result_payouts = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Payout - Admin</title>
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
                    <h3 class="page-title"><i class="mdi mdi-currency-usd"></i> Proses Payout</h3>
                </div>
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs">
                            <li class="nav-item"><a class="nav-link <?= $status == 'pending' ? 'active' : '' ?>" href="?status=pending">Permintaan Baru</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status == 'completed' ? 'active' : '' ?>" href="?status=completed">Selesai Diproses</a></li>
                            <li class="nav-item"><a class="nav-link <?= $status == 'rejected' ? 'active' : '' ?>" href="?status=rejected">Ditolak</a></li>
                        </ul>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover">
                                <thead><tr><th>Tgl Request</th><th>Toko</th><th>Jumlah</th><th>Rekening Tujuan</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php while($payout = $result_payouts->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d M Y H:i', strtotime($payout['tanggal_request'])) ?></td>
                                        <td><?= htmlspecialchars($payout['nama_toko']) ?></td>
                                        <td>Rp<?= number_format($payout['jumlah_payout']) ?></td>
                                        <td><strong><?= htmlspecialchars($payout['rekening_bank']) ?></strong><br><?= htmlspecialchars($payout['nomor_rekening']) ?><br><small><?= htmlspecialchars($payout['atas_nama_rekening']) ?></small></td>
                                        <td>
                                            <label class="badge badge-gradient-<?= ($status=='pending'?'warning':($status=='completed'?'success':'danger')) ?>"><?= ucfirst($status) ?></label>
                                        </td>
                                        <td>
                                            <?php if ($status == 'pending'): ?>
                                                <button class="btn btn-sm btn-success btn-proses" data-id="<?= $payout['id'] ?>" data-nama="<?= htmlspecialchars($payout['nama_toko']) ?>" data-jumlah="Rp<?= number_format($payout['jumlah_payout']) ?>" data-rekening="<?= htmlspecialchars($payout['rekening_bank'].' - '.$payout['nomor_rekening'].' a/n '.$payout['atas_nama_rekening']) ?>">Proses</button>
                                                <button class="btn btn-sm btn-danger btn-tolak" data-id="<?= $payout['id'] ?>">Tolak</button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="prosesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
             <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Proses Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Anda akan memproses pembayaran untuk:</p>
                <p><strong>Toko:</strong> <span id="proses-nama-toko"></span></p>
                <p><strong>Jumlah:</strong> <span id="proses-jumlah"></span></p>
                <p><strong>Ke Rekening:</strong> <span id="proses-rekening"></span></p>
                <hr>
                <p class="text-danger">Pastikan Anda sudah melakukan transfer manual ke rekening tersebut sebelum menekan tombol "Sudah Saya Transfer".</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <a href="#" id="link-konfirmasi-proses" class="btn btn-success">Sudah Saya Transfer</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tolakModal" tabindex="-1">... (Sama seperti modal tolak di halaman moderasi produk) ...</div>

<script src="../assets/template/spica/template/vendors/js/vendor.bundle.base.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logika untuk menampilkan modal konfirmasi proses
    $('.btn-proses').on('click', function() {
        $('#proses-nama-toko').text($(this).data('nama'));
        $('#proses-jumlah').text($(this).data('jumlah'));
        $('#proses-rekening').text($(this).data('rekening'));
        $('#link-konfirmasi-proses').attr('href', `../actions/proses_payout.php?id=${$(this).data('id')}&action=approve`);
        $('#prosesModal').modal('show');
    });
    // Logika untuk modal tolak bisa ditambahkan di sini
});
</script>
</body>
</html>