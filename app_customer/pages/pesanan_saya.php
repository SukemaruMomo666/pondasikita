<?php
include '../config/koneksi.php';
session_start();
$id_user = $_SESSION['id_user'] ?? 1;

$query = "SELECT * FROM tb_pesanan WHERE user_id = $id_user ORDER BY tanggal_pesanan DESC";
$result = mysqli_query($koneksi, $query);
?>

<h2>Pesanan Saya</h2>
<table border="1" cellpadding="10">
    <tr>
        <th>Kode</th>
        <th>Tanggal</th>
        <th>Total</th>
        <th>Status</th>
        <th>Pembayaran</th>
        <th>Aksi</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?= $row['kode_pesanan'] ?></td>
        <td><?= $row['tanggal_pesanan'] ?></td>
        <td>Rp<?= number_format($row['total_pesanan'], 0, ',', '.') ?></td>
        <td><?= $row['status'] ?></td>
        <td><?= $row['status_pembayaran'] ?></td>
        <td><a href="detail_pesanan.php?id=<?= $row['id'] ?>">Lihat</a></td>
    </tr>
    <?php endwhile; ?>
</table>
