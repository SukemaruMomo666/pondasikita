<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php"); exit;
}

// Placeholder untuk data diri
$data_diri = [
    'status' => 'Disetujui',
    'waktu_verifikasi' => '08 Jul 2025, 17:30',
    'jenis_usaha' => 'Perorangan',
    'nama' => 'PRABU ALAM TIAN TRY SUHERMAN',
    'nik' => '3213************',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Diri - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
</head>
<body>
<div class="container-scroller">
    <?php
    // Path halaman ini bisa dibuat unik atau disamakan dengan profil toko
    // agar menu sidebar 'Profil Toko' tetap aktif
    $current_page_full_path = 'app_seller/pengaturan/profil_toko.php';
    include __DIR__ . '/partials/sidebar.php';
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Profil Toko</h1></div>

                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="profileTab" role="tablist">
                            <li class="nav-item"><a class="nav-link" href="profil_toko.php">Informasi dasar</a></li>
                            <li class="nav-item"><a class="nav-link" href="profil_toko.php#bisnis-content">Informasi Bisnis</a></li>
                             <li class="nav-item"><a class="nav-link active" href="#">Data Diri</a></li>
                        </ul>

                        <div class="tab-content pt-4" id="profileTabContent">
                            <div class="info-alert">
                                <i class="mdi mdi-information"></i>
                                <span>Silakan menghubungi CS untuk informasi penggantian data diri pribadi atau badan usaha.</span>
                            </div>
                            <h4 class="section-title">Data Diri 
                                <span class="status-dot approved"></span>
                                <span class="status-text approved"><?= $data_diri['status'] ?></span>
                            </h4>
                            <div class="mt-4">
                                <div class="profile-form-row">
                                    <div class="form-label-col"><label>Waktu Verifikasi</label></div>
                                    <div class="form-input-col"><p class="form-data-text"><?= htmlspecialchars($data_diri['waktu_verifikasi']) ?></p></div>
                                </div>
                                <div class="profile-form-row">
                                    <div class="form-label-col"><label>Jenis Usaha</label></div>
                                    <div class="form-input-col"><p class="form-data-text"><?= htmlspecialchars($data_diri['jenis_usaha']) ?></p></div>
                                </div>
                                <div class="profile-form-row">
                                    <div class="form-label-col"><label>Nama</label></div>
                                    <div class="form-input-col"><p class="form-data-text"><?= htmlspecialchars($data_diri['nama']) ?></p></div>
                                </div>
                                <div class="profile-form-row">
                                    <div class="form-label-col"><label>NIK</label></div>
                                    <div class="form-input-col"><p class="form-data-text"><?= htmlspecialchars($data_diri['nik']) ?></p></div>
                                </div>
                                <div class="profile-form-row">
                                    <div class="form-label-col"><label>Foto KTP</label></div>
                                    <div class="form-input-col"><div class="image-placeholder-locked"><i class="mdi mdi-lock-outline"></i></div></div>
                                </div>
                                <div class="profile-form-row">
                                    <div class="form-label-col"><label>Selfie</label></div>
                                    <div class="form-input-col"><div class="image-placeholder-locked"><i class="mdi mdi-lock-outline"></i></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>