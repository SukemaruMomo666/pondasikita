<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN & PENGAMBILAN DATA TOKO ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
$stmt_toko->close();
if (!$toko_id) die("Data toko tidak ditemukan.");

// --- KALKULASI FINANSIAL ---

// 1. Total Penjualan Kotor (Gross)
$q_gross = $koneksi->prepare("SELECT SUM(subtotal) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_gross->bind_param("i", $toko_id); $q_gross->execute();
$penjualan_kotor = $q_gross->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Total Komisi yang diambil Platform
// Di skema DB kita, komisi dicatat di tb_komisi. Kita jumlahkan dari sana.
$q_komisi = $koneksi->prepare("SELECT SUM(jumlah_komisi) as total FROM tb_komisi WHERE toko_id = ?");
$q_komisi->bind_param("i", $toko_id); $q_komisi->execute();
$total_komisi = $q_komisi->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Pendapatan Bersih
$pendapatan_bersih = $penjualan_kotor - $total_komisi;

// 4. Dana yang Sudah Ditarik (Payout Completed)
$q_payout = $koneksi->prepare("SELECT SUM(jumlah_payout) as total FROM tb_payouts WHERE toko_id = ? AND status = 'completed'");
$q_payout->bind_param("i", $toko_id); $q_payout->execute();
$sudah_ditarik = $q_payout->get_result()->fetch_assoc()['total'] ?? 0;

// 5. Saldo Tersedia untuk Ditarik
$saldo_tersedia = $pendapatan_bersih - $sudah_ditarik;

// 6. Ambil Riwayat Payout
$q_history = $koneksi->prepare("SELECT * FROM tb_payouts WHERE toko_id = ? ORDER BY tanggal_request DESC");
$q_history->bind_param("i", $toko_id); $q_history->execute();
$riwayat_payout = $q_history->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keuangan Toko - Seller Center</title>
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
                    <h3 class="page-title"><i class="mdi mdi-finance"></i> Keuangan</h3>
                </div>
                
                <div class="row">
                    <div class="col-md-6 stretch-card grid-margin">
                        <div class="card bg-gradient-success card-img-holder text-white">
                            <div class="card-body">
                                <h4 class="font-weight-normal mb-3">Saldo Tersedia <i class="mdi mdi-wallet mdi-24px float-right"></i></h4>
                                <h2 class="mb-5">Rp <?= number_format($saldo_tersedia, 0, ',', '.') ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 stretch-card grid-margin">
                        <div class="card bg-gradient-info card-img-holder text-white">
                            <div class="card-body">
                                <h4 class="font-weight-normal mb-3">Total Pendapatan Bersih <i class="mdi mdi-chart-line mdi-24px float-right"></i></h4>
                                <h2 class="mb-5">Rp <?= number_format($pendapatan_bersih, 0, ',', '.') ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-5 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Ajukan Penarikan Dana</h4>
                                <form action="../actions/proses_keuangan.php" method="POST">
                                    <div class="form-group">
                                        <label>Jumlah Penarikan (Rp)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-gradient-primary text-white">Rp</span>
                                            </div>
                                            <input type="number" name="jumlah_payout" class="form-control" placeholder="min. 50.000" min="50000" max="<?= $saldo_tersedia ?>" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-gradient-primary mr-2" <?= $saldo_tersedia < 50000 ? 'disabled' : '' ?>>
                                        Kirim Permintaan
                                    </button>
                                     <?php if($saldo_tersedia < 50000) echo "<small class='text-danger'>Saldo tidak mencukupi untuk penarikan.</small>"; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Riwayat Penarikan Dana</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Status</th></tr></thead>
                                        <tbody>
                                        <?php while($row = $riwayat_payout->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('d M Y', strtotime($row['tanggal_request'])) ?></td>
                                                <td>Rp <?= number_format($row['jumlah_payout'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php 
                                                        $status_class = 'secondary';
                                                        if($row['status'] == 'completed') $status_class = 'success';
                                                        if($row['status'] == 'rejected') $status_class = 'danger';
                                                    ?>
                                                    <label class="badge badge-<?= $status_class ?>"><?= ucfirst($row['status']) ?></label>
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
    </div>
</div>
</body>
</html>