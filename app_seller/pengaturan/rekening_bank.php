<?php
session_start();
require_once '../../config/koneksi.php'; // Path disesuaikan, naik 2 level

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../../auth/login.php"); exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id, rekening_bank, nomor_rekening, atas_nama_rekening FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko = $stmt_toko->get_result()->fetch_assoc();
if (!$toko) die("Toko tidak valid.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Rekening Bank - Pengaturan Toko</title>
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../assets/template/spica/template/css/style.css">
</head>
<body>
<div class="container-scroller">
    <?php include '../partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"><i class="mdi mdi-settings"></i> Pengaturan Toko</h3>
                </div>
                <div class="card">
                    <div class="card-body">
                        <?php include 'partials/navbar_pengaturan.php'; // Memanggil sub-navbar ?>

                        <h4 class="card-title mt-4">Pengaturan Rekening Bank</h4>
                        <p class="card-description">
                            Pastikan data rekening sudah benar untuk kelancaran proses penarikan dana (payout).
                        </p>
                        
                        <form action="../../actions/proses_rekening.php" method="POST">
                            <div class="form-group">
                                <label for="rekening_bank">Nama Bank</label>
                                <input type="text" name="rekening_bank" class="form-control" placeholder="Contoh: BCA" value="<?= htmlspecialchars($toko['rekening_bank'] ?? '') ?>" required>
                            </div>
                             <div class="form-group">
                                <label for="nomor_rekening">Nomor Rekening</label>
                                <input type="text" name="nomor_rekening" class="form-control" placeholder="Masukkan nomor rekening" value="<?= htmlspecialchars($toko['nomor_rekening'] ?? '') ?>" required>
                            </div>
                             <div class="form-group">
                                <label for="atas_nama_rekening">Nama Pemilik Rekening</label>
                                <input type="text" name="atas_nama_rekening" class="form-control" placeholder="Nama sesuai buku tabungan" value="<?= htmlspecialchars($toko['atas_nama_rekening'] ?? '') ?>" required>
                            </div>
                            
                            <hr>
                            <p class="text-danger">Untuk keamanan, masukkan kata sandi Anda saat ini untuk menyimpan perubahan.</p>
                            <div class="form-group">
                                <label for="password_sekarang">Verifikasi Kata Sandi</label>
                                <input type="password" name="password_sekarang" class="form-control" placeholder="Masukkan kata sandi Anda" required>
                            </div>

                            <button type="submit" class="btn btn-gradient-primary">Simpan Rekening Bank</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>