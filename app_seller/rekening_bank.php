<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php");
    exit;
}

// Placeholder data rekening yang sudah tersimpan
$rekening_tersimpan = [
    [
        'id' => 1,
        'nama_bank' => 'BCA',
        'no_rekening' => '**** **** **** 1234',
        'nama_pemilik' => 'Prabu A. T. S.',
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia_logo.svg'
    ]
];

// Daftar bank untuk dropdown
$daftar_bank = ['SeaBank', 'BCA', 'BRI', 'BNI', 'Bank Mandiri', 'Bank Raya Indonesia', 'CIMB Niaga', 'Bank Syariah Indonesia'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekening Bank - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
        <link rel="stylesheet" href="/assets/css/sidebar.css">
    
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    
    <style>
        /* Mengatur tampilan kotak select utama agar sesuai tema */
        .ts-control {
            border: 1px solid #E5E7EB !important; /* var(--border-color) */
            border-radius: 8px !important;
            padding: 0.75rem 1rem !important;
            font-size: 1rem !important;
            background-color: #F9FAFB !important;
            box-shadow: none !important;
        }
        .ts-control.focus {
            border-color: #4F46E5 !important; /* var(--accent-color) */
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
        }

        /* Mengatur tampilan dropdown yang muncul */
        .ts-dropdown {
            border: 1px solid #E5E7EB !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1) !important;
            /* z-index HARUS lebih tinggi dari z-index modal (1040) */
            z-index: 1050 !important; 
        }
        .ts-dropdown .option:hover, .ts-dropdown .active {
            background-color: #F3F4F6 !important;
            color: #1F2937 !important;
        }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php
    $current_page_full_path = 'app_seller/rekening_bank.php';
    include 'partials/sidebar.php';
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Rekening Bank</h1></div>

                <div class="row">
                    <?php foreach ($rekening_tersimpan as $rek): ?>
                    <div class="col-md-6 mb-4">
                        <div class="bank-account-card">
                            <img src="<?= $rek['logo'] ?>" alt="<?= $rek['nama_bank'] ?>" class="bank-logo">
                            <div class="account-details">
                                <h5><?= htmlspecialchars($rek['nama_bank']) ?></h5>
                                <p><?= htmlspecialchars($rek['no_rekening']) ?><br><small><?= htmlspecialchars($rek['nama_pemilik']) ?></small></p>
                            </div>
                            <div class="actions"><button class="btn btn-outline btn-sm">Hapus</button></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="col-md-6 mb-4">
                        <a href="#" class="add-bank-card" id="add-bank-btn">
                            <div class="add-content"><i class="mdi mdi-plus"></i><p class="mt-2">Tambah Rekening Bank</p></div>
                        </a>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<div class="modal-overlay" id="add-bank-modal">
    <div class="modal-content">
        <div class="modal-header"><h4 class="modal-title">Rekening Bank</h4><button class="close-btn" id="close-modal-btn">&times;</button></div>
        <form action="#" method="POST">
            <div class="form-group"><label for="bank-select">Bank</label><select id="bank-select" name="nama_bank" placeholder="Pilih bank..."><option value=""></option><?php foreach ($daftar_bank as $bank): ?><option value="<?= $bank ?>"><?= $bank ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="no_rekening">No. Rekening</label><input type="text" id="no_rekening" name="no_rekening" class="form-control" placeholder="Masukkan nomor rekening"></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" id="cancel-modal-btn">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
$(document).ready(function() {
    
    // ==========================================================
    // ==             PERBAIKAN UTAMA ADA DI SINI              ==
    // ==========================================================
    new TomSelect("#bank-select", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        },
        // BARIS INI ADALAH KUNCI PERBAIKANNYA:
        // Memaksa dropdown untuk muncul di level <body>, bukan di dalam modal
        dropdownParent: 'body'
    });

    // Logika untuk menampilkan dan menyembunyikan modal
    const modal = $('#add-bank-modal');
    $('#add-bank-btn').on('click', function(e) {
        e.preventDefault();
        modal.css('display', 'flex');
    });

    function closeModal() {
        modal.fadeOut();
    }

    $('#close-modal-btn, #cancel-modal-btn').on('click', closeModal);

    modal.on('click', function(e) {
        if ($(e.target).is(modal)) {
            closeModal();
        }
    });
});
</script>
</body>
</html>