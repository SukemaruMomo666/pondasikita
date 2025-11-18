<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php");
    exit;
}
// Ambil tipe voucher dari URL, default ke 'toko'
$tipe_voucher = $_GET['tipe'] ?? 'toko';

$page_title = "Buat Voucher Baru";
if ($tipe_voucher == 'produk') $page_title = "Buat Voucher Produk";
if ($tipe_voucher == 'terbatas') $page_title = "Buat Voucher Terbatas";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title"><?= $page_title ?></h1></div>
                
                <div class="form-container">
                    <div class="form-main">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Rincian Dasar</h4>
                                <div class="form-group">
                                    <label>Tipe Voucher</label>
                                    <div class="segmented-control">
                                        <input type="radio" id="type_toko" name="tipe_voucher_utama" value="toko" <?= ($tipe_voucher == 'toko') ? 'checked' : '' ?>>
                                        <label for="type_toko">Voucher Toko</label>
                                        <input type="radio" id="type_produk" name="tipe_voucher_utama" value="produk" <?= ($tipe_voucher == 'produk') ? 'checked' : '' ?>>
                                        <label for="type_produk">Voucher Produk</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="nama_voucher">Nama Voucher</label>
                                    <input type="text" id="nama_voucher" class="form-control" placeholder="cth: Voucher Gajian">
                                </div>
                                <div class="form-group">
                                    <label for="kode_voucher">Kode Voucher</label>
                                    <div class="input-group prefix">
                                        <span class="input-group-text">PRAB</span>
                                        <input type="text" id="kode_voucher" class="form-control" placeholder="INPUT" maxlength="5" style="text-transform:uppercase;">
                                    </div>
                                    <small class="text-secondary">Masukkan A-Z, 0-9, maksimum 5 karakter.</small>
                                </div>
                                <div class="form-group">
                                    <label>Periode Pemakaian Voucher</label>
                                    <div class="row"><div class="col-6"><input type="datetime-local" class="form-control"></div><div class="col-6"><input type="datetime-local" class="form-control"></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Pengaturan Bonus</h4>
                                <div class="form-group">
                                    <label>Tipe Voucher</label>
                                    <div class="segmented-control">
                                        <input type="radio" id="bonus_diskon" name="tipe_bonus" value="diskon" checked><label for="bonus_diskon">Diskon</label>
                                        <input type="radio" id="bonus_cashback" name="tipe_bonus" value="cashback"><label for="bonus_cashback">Cashback Koin</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tipe Diskon / Diskon</label>
                                    <div class="input-group suffix">
                                        <select class="form-control" style="max-width: 150px;"><option>Nominal</option><option>Persentase</option></select>
                                        <input type="number" class="form-control" placeholder="Masukkan Nominal">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Minimum Pembelian</label>
                                    <input type="number" class="form-control" placeholder="Rp">
                                </div>
                                <div class="form-group">
                                    <label>Kuota Pemakaian</label>
                                    <input type="number" class="form-control" placeholder="Maks. kuota voucher yang dapat dipakai oleh Pembeli">
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4" id="display-settings-card">
                            <div class="card-body">
                                <h4 class="card-title">Tampilan Voucher & Produk yang Dapat Menggunakannya</h4>
                                <div id="setting-toko" class="voucher-setting">
                                    <div class="form-group">
                                        <label>Pengaturan Tampilan Voucher</label>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="display_type" id="display_all" checked><label class="form-check-label" for="display_all">Tampilkan di semua halaman</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="display_type" id="display_none"><label class="form-check-label" for="display_none">Tidak ditampilkan (hanya untuk dibagikan)</label></div>
                                    </div>
                                </div>
                                <div id="setting-produk" class="voucher-setting" style="display: none;">
                                    <p>Voucher ini hanya berlaku untuk produk yang Anda tambahkan di bawah.</p>
                                    <button class="btn btn-outline"><i class="mdi mdi-plus"></i> Tambahkan Produk</button>
                                    </div>
                            </div>
                        </div>

                         <div class="d-flex justify-content-end mt-4 gap-2">
                            <a href="voucher.php" class="btn btn-outline">Kembali</a>
                            <button class="btn btn-primary">Konfirmasi</button>
                        </div>
                    </div>
                    <div class="form-preview d-none d-lg-block">
                        <div class="preview-box">
                            <h4>Preview</h4>
                            <div class="mobile-mockup">
                                <img src="https://i.ibb.co/6PwqNbb/voucher-preview.png" alt="Voucher Preview">
                                <p id="preview-text">Pembeli dapat menggunakan Voucher ini untuk semua produk di tokomu.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function toggleVoucherSettings(type) {
        $('.voucher-setting').hide(); // Sembunyikan semua
        if (type === 'toko') {
            $('#setting-toko').show();
            $('#preview-text').text('Pembeli dapat menggunakan Voucher ini untuk semua produk di tokomu.');
        } else if (type === 'produk') {
            $('#setting-produk').show();
            $('#preview-text').text('Pembeli dapat menggunakan Voucher ini untuk produk pilihan di tokomu.');
        }
    }

    // Panggil saat halaman pertama kali dimuat
    var initialType = $('input[name="tipe_voucher_utama"]:checked').val();
    toggleVoucherSettings(initialType);

    // Event listener saat tipe voucher diubah
    $('input[name="tipe_voucher_utama"]').on('change', function() {
        var selectedType = $(this).val();
        toggleVoucherSettings(selectedType);
    });
});
</script>
</body>
</html>