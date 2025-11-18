<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../auth/signin.php");
    exit;
}

include '../config/koneksi.php';
$user_id = $_SESSION['user']['id'];

$query = "SELECT * FROM tb_pesanan WHERE user_id = $user_id ORDER BY tanggal_pesanan DESC";
$result = mysqli_query($koneksi, $query);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <?php include '../partials/navbar.php'; ?>

<div class="container mt-5">
    <h3>Status Pesanan Saya</h3>

    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="alert alert-info">Kamu belum memiliki pesanan.</div>
    <?php else: ?>
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Kode Pesanan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Status Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_pesanan']) ?></td>
                        <td><?= $row['tanggal_pesanan'] ?></td>
                        <td>Rp <?= number_format($row['total_pesanan'], 0, ',', '.') ?></td>
                        <td>
                            <?php
                            $status = $row['status'];
                            $warna = '';
                            if ($status == 'menunggu') {
                                $warna = 'warning';
                            } elseif ($status == 'diproses') {
                                $warna = 'primary';
                            } elseif ($status == 'dikirim') {
                                $warna = 'info';
                            } elseif ($status == 'selesai') {
                                $warna = 'success';
                            } else {
                                $warna = 'secondary';
                            }
                            ?>
                            <span class="badge bg-<?= $warna ?>"><?= strtoupper($status) ?></span>
                        </td>
                        <td>
                            <?php
                            $bayar = $row['status_pembayaran'];
                            $warnaBayar = '';
                            if ($bayar == 'menunggu') {
                                $warnaBayar = 'warning';
                            } elseif ($bayar == 'dibayar') {
                                $warnaBayar = 'success';
                            } elseif ($bayar == 'gagal') {
                                $warnaBayar = 'danger';
                            } elseif ($bayar == 'dikembalikan') {
                                $warnaBayar = 'secondary';
                            } else {
                                $warnaBayar = 'dark';
                            }
                            ?>
                            <span class="badge bg-<?= $warnaBayar ?>"><?= strtoupper($bayar) ?></span>
                        </td>
                    </tr>
                    <?php endwhile ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
</body>
</html>