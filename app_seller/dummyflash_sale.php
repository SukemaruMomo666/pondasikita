<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
if (!$toko_id) die("Toko tidak valid.");

// 1. Cek apakah ada event Flash Sale yang sedang aktif
$now = date('Y-m-d H:i:s');
$event_query = $koneksi->prepare("SELECT * FROM tb_flash_sale_events WHERE is_active = 1 AND ? BETWEEN tanggal_mulai AND tanggal_berakhir LIMIT 1");
$event_query->bind_param("s", $now);
$event_query->execute();
$active_event = $event_query->get_result()->fetch_assoc();

if ($active_event) {
    // 2. Ambil produk milik seller yang BISA didaftarkan (yang belum ikut event ini)
    $produk_tersedia_query = $koneksi->prepare(
        "SELECT id, nama_barang FROM tb_barang 
         WHERE toko_id = ? AND id NOT IN (SELECT barang_id FROM tb_flash_sale_produk WHERE event_id = ?)"
    );
    $produk_tersedia_query->bind_param("ii", $toko_id, $active_event['id']);
    $produk_tersedia_query->execute();
    $result_produk_tersedia = $produk_tersedia_query->get_result();

    // 3. Ambil produk yang SUDAH didaftarkan di event ini
    $produk_terdaftar_query = $koneksi->prepare(
        "SELECT fsp.*, b.nama_barang FROM tb_flash_sale_produk fsp
         JOIN tb_barang b ON fsp.barang_id = b.id
         WHERE fsp.toko_id = ? AND fsp.event_id = ?"
    );
    $produk_terdaftar_query->bind_param("ii", $toko_id, $active_event['id']);
    $produk_terdaftar_query->execute();
    $result_produk_terdaftar = $produk_terdaftar_query->get_result();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Flash Sale Toko - Seller Center</title>
    </head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"><i class="mdi mdi-flash"></i> Flash Sale Toko Saya</h3>
                </div>

                <?php if ($active_event): ?>
                    <div class="alert alert-success">
                        <h4>Event Sedang Berlangsung: <?= htmlspecialchars($active_event['nama_event']) ?></h4>
                        <p>Daftarkan produk Anda sekarang! Event akan berakhir pada <?= date('d M Y, H:i', strtotime($active_event['tanggal_berakhir'])) ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-5 grid-margin">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Daftarkan Produk</h4>
                                    <form action="../actions/proses_flash_sale.php" method="POST">
                                        <input type="hidden" name="event_id" value="<?= $active_event['id'] ?>">
                                        <div class="form-group">
                                            <label>Pilih Produk</label>
                                            <select name="barang_id" class="form-control" required>
                                                <option value="">-- Pilih Produk --</option>
                                                <?php while($p = $result_produk_tersedia->fetch_assoc()): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_barang']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Harga Flash Sale (Rp)</label>
                                            <input type="number" name="harga_flash_sale" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Stok untuk Flash Sale</label>
                                            <input type="number" name="stok_flash_sale" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Daftarkan</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7 grid-margin">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Produk Terdaftar di Event Ini</h4>
                                    <table class="table">
                                        <thead><tr><th>Produk</th><th>Harga FS</th><th>Stok FS</th><th>Status</th></tr></thead>
                                        <tbody>
                                            <?php while($pf = $result_produk_terdaftar->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($pf['nama_barang']) ?></td>
                                                    <td>Rp<?= number_format($pf['harga_flash_sale']) ?></td>
                                                    <td><?= $pf['stok_flash_sale'] ?></td>
                                                    <td><span class="badge badge-info"><?= ucfirst($pf['status_moderasi']) ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="card-title">Tidak Ada Event Flash Sale</h4>
                            <p>Saat ini tidak ada event flash sale yang sedang aktif. Nantikan informasi selanjutnya dari admin.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>