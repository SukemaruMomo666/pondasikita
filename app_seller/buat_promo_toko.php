<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: /auth/login.php");
    exit;
}
// Placeholder data produk dari toko
$produk_promo = [
    [ 'id' => 1, 'nama' => 'Baju Kemeja Pria Lengan Panjang', 'gambar' => 'https://via.placeholder.com/40x40.png/EBF4FF/76A9FA?text=Baju', 'harga' => 150000 ],
    [ 'id' => 2, 'nama' => 'Celana Jeans Denim Pria Slim Fit', 'gambar' => 'https://via.placeholder.com/40x40.png/FEF2F2/F87171?text=Celana', 'harga' => 250000 ],
    [ 'id' => 3, 'nama' => 'Sepatu Sneakers Pria Casual Original', 'gambar' => 'https://via.placeholder.com/40x40.png/F0FDF4/84CC16?text=Sepatu', 'harga' => 325000 ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Promo Toko - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
    <style>
        .diskon-input-group { display: flex; align-items: center; gap: 10px; }
        .diskon-input-group .form-control { min-width: 100px; }
        .input-group { display: flex; align-items: center; }
        .input-group .input-group-text { padding: 0.75rem; background-color: #F9FAFB; border: 1px solid var(--border-color); border-left: 0; border-radius: 0 8px 8px 0; }
        .input-group .form-control { border-radius: 8px 0 0 8px; }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Buat Promo Toko</h1></div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Informasi Dasar</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama_promo">Nama Promo Toko</label>
                                    <input type="text" id="nama_promo" class="form-control" placeholder="cth: Diskon Gajian">
                                    <small class="text-secondary">Nama Promo Toko tidak akan diperlihatkan ke Pembeli.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="periode_promo">Periode Promo Toko</label>
                                    <div class="row">
                                        <div class="col-6"><input type="datetime-local" id="periode_mulai" class="form-control"></div>
                                        <div class="col-6"><input type="datetime-local" id="periode_selesai" class="form-control"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                         <div class="card-header-flex">
                            <div>
                                <h4 class="card-title">Produk dalam Promo Toko</h4>
                                <p class="card-subtitle" id="total-produk-label"><?= count($produk_promo) ?> total produk</p>
                            </div>
                            <button class="btn btn-outline"><i class="mdi mdi-plus"></i> Tambah Produk</button>
                         </div>
                         
                         <div class="bulk-edit-section">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                                <label class="form-check-label" for="select-all-checkbox"></label>
                            </div>
                            <div class="selected-count" id="selected-count-label">0 produk dipilih</div>
                            <div class="diskon-input-group">
                                <input type="number" id="bulk-diskon" class="form-control" placeholder="% Diskon">
                            </div>
                             <input type="number" id="bulk-stok" class="form-control" placeholder="Stok Promosi">
                             <input type="number" id="bulk-batas" class="form-control" placeholder="Batas Pembelian">
                             <button class="btn btn-primary btn-sm" id="bulk-apply-btn">Ubah Semua</button>
                             <button class="btn btn-outline btn-sm" id="bulk-delete-btn">Hapus</button>
                         </div>

                         <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;"></th>
                                        <th>Nama Produk</th>
                                        <th>Harga Asal</th>
                                        <th>Harga Diskon & Persentase</th>
                                        <th>Stok Promosi <i class="mdi mdi-information-outline info-tooltip" title="Jumlah stok yang tersedia selama periode promo."></i></th>
                                        <th>Batas Pembelian <i class="mdi mdi-information-outline info-tooltip" title="Jumlah maksimal yang bisa dibeli satu pelanggan."></i></th>
                                        <th>Aktifkan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="promo-product-list">
                                    <?php foreach ($produk_promo as $produk): ?>
                                    <tr data-harga-asal="<?= $produk['harga'] ?>">
                                        <td><input class="form-check-input product-checkbox" type="checkbox"></td>
                                        <td><div class="product-info"><img src="<?= $produk['gambar'] ?>" alt="Produk"><span><?= htmlspecialchars($produk['nama']) ?></span></div></td>
                                        <td>Rp <?= number_format($produk['harga'], 0, ',', '.') ?></td>
                                        <td><div class="diskon-input-group"><input type="text" class="form-control input-harga-diskon" placeholder="Rp Harga Diskon"><span class="text-secondary">Atau</span><div class="input-group"><input type="number" class="form-control input-persen-diskon" placeholder="Persen" min="0" max="100"><span class="input-group-text">%</span></div></div></td>
                                        <td><input type="number" class="form-control input-stok" placeholder="Stok"></td>
                                        <td><input type="number" class="form-control input-batas" placeholder="Tidak Terbatas"></td>
                                        <td><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></td>
                                        <td><button class="btn btn-outline" style="padding: 5px 10px;" title="Hapus"><i class="mdi mdi-trash-can-outline"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                         </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 gap-2">
                    <a href="diskon.php" class="btn btn-outline">Batal</a>
                    <button class="btn btn-primary">Konfirmasi</button>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    let isCalculating = false;
    function formatRupiah(angka) { if (isNaN(angka) || angka === null) return ""; return 'Rp ' + Math.round(angka).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    function parseRupiah(rupiah) { if (!rupiah) return 0; return parseInt(rupiah.replace(/[^0-9]/g, ''), 10) || 0; }

    const productList = $('#promo-product-list');

    // Kalkulasi diskon otomatis (sama seperti sebelumnya)
    productList.on('input', '.input-persen-diskon', function() { /* ... kode kalkulasi persen ... */ });
    productList.on('input', '.input-harga-diskon', function() { /* ... kode kalkulasi harga ... */ });
    
    // --- LOGIKA BARU UNTUK PERUBAHAN MASSAL ---
    
    // Fungsi untuk update jumlah produk yang dipilih
    function updateSelectedCount() {
        const selectedCount = productList.find('.product-checkbox:checked').length;
        $('#selected-count-label').text(selectedCount + ' produk dipilih');
    }

    // Event untuk checkbox "Pilih Semua"
    $('#select-all-checkbox').on('change', function() {
        productList.find('.product-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    // Event untuk checkbox per produk
    productList.on('change', '.product-checkbox', function() {
        if (!this.checked) {
            $('#select-all-checkbox').prop('checked', false);
        } else {
            // Jika semua checkbox produk terpilih, centang juga "Pilih Semua"
            if (productList.find('.product-checkbox:not(:checked)').length === 0) {
                $('#select-all-checkbox').prop('checked', true);
            }
        }
        updateSelectedCount();
    });

    // Event untuk tombol "Ubah Semua"
    $('#bulk-apply-btn').on('click', function() {
        const bulkDiskon = $('#bulk-diskon').val();
        const bulkStok = $('#bulk-stok').val();
        const bulkBatas = $('#bulk-batas').val();

        // Cari semua baris yang checkbox-nya tercentang
        productList.find('.product-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            
            // Terapkan diskon jika diisi
            if (bulkDiskon) {
                row.find('.input-persen-diskon').val(bulkDiskon).trigger('input'); // trigger 'input' untuk kalkulasi otomatis
            }
            // Terapkan stok jika diisi
            if (bulkStok) {
                row.find('.input-stok').val(bulkStok);
            }
            // Terapkan batas pembelian jika diisi
            if (bulkBatas) {
                row.find('.input-batas').val(bulkBatas);
            }
        });
    });

    // Event untuk tombol "Hapus" Massal
    $('#bulk-delete-btn').on('click', function() {
        if(confirm('Anda yakin ingin menghapus produk yang dipilih dari daftar promo?')) {
            productList.find('.product-checkbox:checked').closest('tr').remove();
            updateSelectedCount();
            // Update total produk
            const totalProduk = productList.find('tr').length;
            $('#total-produk-label').text(totalProduk + ' total produk');
        }
    });
});
</script>

</body>
</html>