<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php"); exit;
}

// Data Placeholder - Ganti dengan data toko dari database
$toko_info = [
    'nama_toko' => 'CHUNSTORE',
    'logo_toko' => 'https://i.pravatar.cc/100', // Ganti dengan path logo asli
    'deskripsi' => "IG: chunstore.thrifting\nhttps://linktr.ee/momo_sukemaru"
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Toko - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
        <link rel="stylesheet" href="/assets/css/sidebar.css">
</head>
<body>
<div class="container-scroller">
    <?php
    $current_page_full_path = 'app_seller/pengaturan/profil_toko.php';
    include __DIR__ . '/partials/sidebar.php';
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Profil Toko</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="profileTab" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="dasar-tab" data-bs-toggle="tab" data-bs-target="#dasar-content" type="button" role="tab">Informasi dasar</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="bisnis-tab" data-bs-toggle="tab" data-bs-target="#bisnis-content" type="button" role="tab">Informasi Bisnis</button></li>
                            <li class="nav-item">
                                <a class="nav-link" href="data_diri.php">Data Diri</a>
                            </li>
                        </ul>

                        <div class="tab-content pt-4" id="profileTabContent">
                            <div class="tab-pane fade show active" id="dasar-content" role="tabpanel">
                                <form action="#" method="POST" enctype="multipart/form-data">
                                    <div class="profile-form-row">
                                        <div class="form-label-col"><label for="nama_toko">Nama Toko</label></div>
                                        <div class="form-input-col">
                                            <input type="text" id="nama_toko" name="nama_toko" class="form-control" value="<?= htmlspecialchars($toko_info['nama_toko']) ?>" maxlength="30">
                                            <span class="char-counter" id="nama_toko_counter"></span>
                                        </div>
                                    </div>
                                    <div class="profile-form-row">
                                        <div class="form-label-col"><label>Logo Toko</label></div>
                                        <div class="form-input-col">
                                            <div class="logo-uploader">
                                                <img src="<?= $toko_info['logo_toko'] ?>" alt="Logo Toko" class="logo-preview" id="logo_preview">
                                                <div>
                                                    <button type="button" class="btn btn-outline" id="btn_ubah_logo">Ubah</button>
                                                    <input type="file" name="logo_toko" id="input_logo" accept=".jpg,.jpeg,.png" style="display: none;">
                                                    <div class="logo-instructions mt-2">
                                                        <ul>
                                                            <li>Ukuran gambar: lebar 300px, tinggi 300px</li>
                                                            <li>Besar file maks.: 2.0MB</li>
                                                            <li>Format gambar yang diterima: .JPG, .JPEG, .PNG</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="profile-form-row">
                                        <div class="form-label-col"><label for="deskripsi_toko">Deskripsi Toko</label></div>
                                        <div class="form-input-col">
                                            <textarea name="deskripsi_toko" id="deskripsi_toko" class="form-control" rows="5" maxlength="500"><?= htmlspecialchars($toko_info['deskripsi']) ?></textarea>
                                            <span class="char-counter" id="deskripsi_toko_counter"></span>
                                        </div>
                                    </div>
                                    <div class="profile-form-row">
                                        <div class="form-label-col"></div>
                                        <div class="form-input-col">
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                            <button type="button" class="btn btn-outline">Batal</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="bisnis-content" role="tabpanel"><p>Konten untuk Informasi Bisnis akan ditampilkan di sini.</p></div>
                            </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Fungsi untuk menghitung karakter
    function updateCounter(inputElement, counterElement) {
        const maxLength = $(inputElement).attr('maxlength');
        const currentLength = $(inputElement).val().length;
        $(counterElement).text(currentLength + '/' + maxLength);
    }

    // Panggil saat halaman dimuat
    updateCounter('#nama_toko', '#nama_toko_counter');
    updateCounter('#deskripsi_toko', '#deskripsi_toko_counter');

    // Panggil saat ada input
    $('#nama_toko').on('input', function() { updateCounter(this, '#nama_toko_counter'); });
    $('#deskripsi_toko').on('input', function() { updateCounter(this, '#deskripsi_toko_counter'); });

    // Logika untuk upload & preview logo
    $('#btn_ubah_logo').on('click', function() {
        $('#input_logo').click();
    });

    $('#input_logo').on('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            $('#logo_preview').attr('src', URL.createObjectURL(file));
        }
    });
});
</script>
</body>
</html>