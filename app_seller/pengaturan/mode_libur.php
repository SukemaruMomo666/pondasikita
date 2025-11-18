<?php
session_start();
require_once '../../config/koneksi.php'; // Path disesuaikan, naik 2 level

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../../auth/login.php"); exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id, status_operasional FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko = $stmt_toko->get_result()->fetch_assoc();
if (!$toko) die("Toko tidak valid.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Mode Libur - Pengaturan Toko</title>
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../assets/template/spica/template/css/style.css">
    <style>
        /* CSS Khusus untuk Toggle Switch */
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
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

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <h4 class="card-title">Aktifkan Fitur Toko Libur</h4>
                                <p class="card-description">
                                    Saat fitur ini aktif, pembeli tidak dapat membuat pesanan baru. <br>
                                    Anda tetap harus menyelesaikan pesanan yang sedang berjalan.
                                </p>
                            </div>
                            <div>
<form action="../../actions/proses_pengaturan_toko.php" method="POST">
    <input type="hidden" name="action" value="toggle_libur"> <label class="toggle-switch">
        <input type="checkbox" name="mode_libur" value="1" <?= $toko['status_operasional'] == 'Tutup' ? 'checked' : '' ?> onchange="this.form.submit()">
        <span class="slider"></span>
    </label>
</form>
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