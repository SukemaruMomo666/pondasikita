<?php
session_start();
require_once '../config/koneksi.php'; // Path disesuaikan
date_default_timezone_set('Asia/Jakarta'); // Pastikan zona waktu sudah diatur dengan benar

// --- PENGAMANAN & PENGAMBILAN DATA ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); // Arahkan ke root seller login jika belum login
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'] ?? null;
$stmt_toko->close();

if (!$toko_id) {
    die("Data toko tidak ditemukan untuk pengguna ini. Silakan hubungi admin.");
}

// FUNGSI PEMBANTU UNTUK SIDEBAR (jika belum ada di file ini)
if (!function_exists('getCurrentRelativePath')) {
    function getCurrentRelativePath() {
        $base_dir = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $full_path = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
        $relative_path = str_replace($base_dir, '', $full_path);
        if (substr($relative_path, 0, 1) === '/') {
            $relative_path = substr($relative_path, 1);
        }
        return $relative_path;
    }
}
$current_page_full_path = getCurrentRelativePath();

// Ambil data kategori untuk dropdown
$kategori_query = $koneksi->query("SELECT id, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
$kategoris = $kategori_query->fetch_all(MYSQLI_ASSOC);

// Data produk jika mode EDIT
$is_edit = false;
$produk_data = [];
if (isset($_GET['edit'])) {
    $is_edit = true;
    $produk_id = (int)$_GET['edit'];
    $stmt_produk = $koneksi->prepare("SELECT * FROM tb_barang WHERE id = ? AND toko_id = ?");
    $stmt_produk->bind_param("ii", $produk_id, $toko_id);
    $stmt_produk->execute();
    $produk_data = $stmt_produk->get_result()->fetch_assoc();
    if (!$produk_data) {
        die("Produk tidak ditemukan atau bukan milik toko Anda.");
    }
    $stmt_produk->close();
}

// Ambil nama kategori untuk produk yang sedang diedit (untuk preview awal)
$current_kategori_name = '-';
if ($is_edit && isset($produk_data['kategori_id'])) {
    foreach ($kategoris as $kategori) {
        if ($kategori['id'] == $produk_data['kategori_id']) {
            $current_kategori_name = $kategori['nama_kategori'];
            break;
        }
    }
}

// Set default value for diskon_mulai to now if adding new product or no existing date
// Menggunakan date('c') untuk format ISO 8601 yang lebih kompatibel dengan datetime-local
// Contoh: 2025-07-03T21:30
$default_diskon_mulai = date('Y-m-d\TH:i'); 
$default_diskon_berakhir = ''; 

if ($is_edit) {
    if (!empty($produk_data['diskon_mulai'])) {
        $default_diskon_mulai = date('Y-m-d\TH:i', strtotime($produk_data['diskon_mulai']));
    }
    if (!empty($produk_data['diskon_berakhir'])) {
        $default_diskon_berakhir = date('Y-m-d\TH:i', strtotime($produk_data['diskon_berakhir']));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $is_edit ? 'Edit Produk' : 'Tambah Produk Baru' ?> - Pondasikita Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="sidebar-toggle-btn d-lg-none"><i class="mdi mdi-menu"></i></button>
            </div>
            <div class="navbar-right">
                <a href="#" class="navbar-icon"><i class="mdi mdi-bell-outline"></i></a>
                <a href="#" class="navbar-icon"><i class="mdi mdi-help-circle-outline"></i></a>
                <div class="navbar-profile">
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Seller') ?></span>
                    <i class="mdi mdi-chevron-down profile-arrow"></i>
                </div>
            </div>
        </nav>

        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title"><i class="mdi mdi-plus-box"></i> <?= $is_edit ? 'Edit Produk' : 'Tambah Produk Baru' ?></h1>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <form id="productForm" action="../actions/proses_produk.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'tambah' ?>">
                                    <?php if ($is_edit): ?>
                                    <input type="hidden" name="produk_id" value="<?= $produk_data['id'] ?>">
                                    <?php endif; ?>

                                    <ul class="nav nav-tabs product-form-tabs mb-4" id="productFormTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="info-produk-tab" data-bs-toggle="tab" data-bs-target="#info-produk" type="button" role="tab" aria-controls="info-produk" aria-selected="true">Informasi Produk</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="info-penjualan-tab" data-bs-toggle="tab" data-bs-target="#info-penjualan" type="button" role="tab" aria-controls="info-penjualan" aria-selected="false">Informasi Penjualan</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="pengiriman-tab" data-bs-toggle="tab" data-bs-target="#pengiriman" type="button" role="tab" aria-controls="pengiriman" aria-selected="false">Pengiriman</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="lainnya-tab" data-bs-toggle="tab" data-bs-target="#lainnya" type="button" role="tab" aria-controls="lainnya" aria-selected="false">Lainnya</button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="productFormTabsContent">
                                        <div class="tab-pane fade show active" id="info-produk" role="tabpanel" aria-labelledby="info-produk-tab">
                                            <h5 class="section-title">Foto Produk</h5>
                                            <div class="mb-3">
                                                <label for="gambar_utama" class="form-label">Gambar Utama Produk</label>
                                                <input class="form-control" type="file" id="gambar_utama" name="gambar_utama" accept="image/*" <?= $is_edit ? '' : 'required' ?>>
                                                <?php if ($is_edit && $produk_data['gambar_utama']): ?>
                                                    <small class="text-muted">Gambar saat ini:</small><br>
                                                    <img src="../assets/img/products/<?= htmlspecialchars($produk_data['gambar_utama']) ?>" alt="Gambar Produk" class="img-thumbnail mt-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                <?php endif; ?>
                                                <small class="form-text text-muted">Maks. 2MB. Format: JPG, PNG. Rekomendasi: 1:1 ratio.</small>
                                            </div>
                                            <h5 class="section-title mt-4">Informasi Dasar</h5>
                                            <div class="mb-3">
                                                <label for="nama_barang" class="form-label">Nama Produk</label>
                                                <input type="text" class="form-control" id="nama_barang" name="nama_barang" value="<?= htmlspecialchars($produk_data['nama_barang'] ?? '') ?>" placeholder="Nama produk (min. 25 karakter)" required minlength="25" maxlength="100">
                                            </div>
                                            <div class="mb-3">
                                                <label for="kategori_id" class="form-label">Kategori</label>
                                                <select class="form-select" id="kategori_id" name="kategori_id" required>
                                                    <option value="">Pilih Kategori</option>
                                                    <?php foreach ($kategoris as $kategori): ?>
                                                        <option value="<?= $kategori['id'] ?>" <?= ($produk_data['kategori_id'] ?? '') == $kategori['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="merk_barang" class="form-label">Merek (Opsional)</label>
                                                <input type="text" class="form-control" id="merk_barang" name="merk_barang" value="<?= htmlspecialchars($produk_data['merk_barang'] ?? '') ?>" placeholder="Contoh: Semen Gresik, Avian, Roman">
                                                <small class="form-text text-muted">Jika produk Anda memiliki merek, sebutkan di sini.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="deskripsi" class="form-label">Deskripsi Produk</label>
                                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" placeholder="Jelaskan produk Anda secara rinci (min. 100 karakter)" required minlength="100"><?= htmlspecialchars($produk_data['deskripsi'] ?? '') ?></textarea>
                                                <small class="form-text text-muted">Sertakan informasi penting: ukuran, bahan, fungsi, dll.</small>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="info-penjualan" role="tabpanel" aria-labelledby="info-penjualan-tab">
                                            <h5 class="section-title">Harga & Stok</h5>
                                            <div class="mb-3">
                                                <label for="harga" class="form-label">Harga (Rp)</label>
                                                <input type="number" class="form-control" id="harga" name="harga" value="<?= htmlspecialchars($produk_data['harga'] ?? '') ?>" placeholder="Harga produk" required min="0">
                                            </div>
                                            <div class="mb-3">
                                                <label for="stok" class="form-label">Stok</label>
                                                <input type="number" class="form-control" id="stok" name="stok" value="<?= htmlspecialchars($produk_data['stok'] ?? '') ?>" placeholder="Jumlah stok produk" required min="0">
                                            </div>
                                            <div class="mb-3">
                                                <label for="satuan_unit" class="form-label">Satuan Unit</label>
                                                <input type="text" class="form-control" id="satuan_unit" name="satuan_unit" value="<?= htmlspecialchars($produk_data['satuan_unit'] ?? 'pcs') ?>" placeholder="Contoh: pcs, sak, batang" required>
                                            </div>
                                            <h5 class="section-title mt-4">Promosi (Diskon)</h5>
                                            <div class="mb-3">
                                                <label for="tipe_diskon" class="form-label">Tipe Diskon</label>
                                                <select class="form-select" id="tipe_diskon" name="tipe_diskon">
                                                    <option value="">Tidak ada Diskon</option>
                                                    <option value="NOMINAL" <?= ($produk_data['tipe_diskon'] ?? '') == 'NOMINAL' ? 'selected' : '' ?>>Diskon Nominal (Rp)</option>
                                                    <option value="PERSEN" <?= ($produk_data['tipe_diskon'] ?? '') == 'PERSEN' ? 'selected' : '' ?>>Diskon Persen (%)</option>
                                                </select>
                                            </div>
                                            <div class="mb-3" id="nilai_diskon_group" style="display: <?= ($produk_data['tipe_diskon'] ?? '') ? 'block' : 'none' ?>;">
                                                <label for="nilai_diskon" class="form-label">Nilai Diskon</label>
                                                <input type="number" class="form-control" id="nilai_diskon" name="nilai_diskon" value="<?= htmlspecialchars($produk_data['nilai_diskon'] ?? '') ?>" placeholder="Jumlah diskon" step="0.01">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="diskon_mulai" class="form-label">Diskon Mulai</label>
                                                    <input type="datetime-local" class="form-control" id="diskon_mulai" name="diskon_mulai" value="<?= $default_diskon_mulai ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="diskon_berakhir" class="form-label">Diskon Berakhir</label>
                                                    <input type="datetime-local" class="form-control" id="diskon_berakhir" name="diskon_berakhir" value="<?= $default_diskon_berakhir ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="pengiriman" role="tabpanel" aria-labelledby="pengiriman-tab">
                                            <h5 class="section-title">Informasi Pengiriman</h5>
                                            <div class="mb-3">
                                                <label for="berat_kg" class="form-label">Berat Produk (kg)</label>
                                                <input type="number" class="form-control" id="berat_kg" name="berat_kg" value="<?= htmlspecialchars($produk_data['berat_kg'] ?? '') ?>" placeholder="Berat dalam kilogram" required step="0.01" min="0.01">
                                                <small class="form-text text-muted">Masukkan berat sebenarnya produk untuk perhitungan ongkir yang akurat.</small>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="lainnya" role="tabpanel" aria-labelledby="lainnya-tab">
                                            <h5 class="section-title">Pengaturan Lainnya</h5>
                                            <div class="mb-3">
                                                <label for="kode_barang" class="form-label">SKU (Kode Barang) (Opsional)</label>
                                                <input type="text" class="form-control" id="kode_barang" name="kode_barang" value="<?= htmlspecialchars($produk_data['kode_barang'] ?? '') ?>" placeholder="Kode unik produk Anda">
                                                <small class="form-text text-muted">Digunakan untuk identifikasi internal produk Anda.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions mt-4">
                                        <button type="submit" class="btn btn-primary btn-save">Simpan & Tampilkan</button>
                                        <button type="button" class="btn btn-light btn-cancel">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="product-preview-card sticky-top">
                            <div class="preview-header">
                                <i class="mdi mdi-cellphone"></i> Pratinjau Mobile
                            </div>
                            <div class="preview-body">
                                <img id="preview-image" src="<?= $is_edit && $produk_data['gambar_utama'] ? '../assets/img/products/' . htmlspecialchars($produk_data['gambar_utama']) : 'https://via.placeholder.com/200x200?text=Gambar+Produk' ?>" alt="Product Preview" class="img-fluid mb-2">
                                <h5 id="preview-name" class="preview-title"><?= htmlspecialchars($produk_data['nama_barang'] ?? 'Nama Produk Anda') ?></h5>
                                <div class="price-display">
                                    <p id="preview-final-price" class="final-price"></p>
                                    <p id="preview-original-price" class="original-price" style="display:none;"></p>
                                </div>
                                <hr>
                                <p id="preview-category" class="preview-info">Kategori: <?= htmlspecialchars($current_kategori_name ?? '-') ?></p>
                                <p id="preview-brand" class="preview-info">Merek: <?= htmlspecialchars($produk_data['merk_barang'] ?? '-') ?></p>
                                <p id="preview-stock" class="preview-info">Stok: <?= htmlspecialchars($produk_data['stok'] ?? 0) ?> <?= htmlspecialchars($produk_data['satuan_unit'] ?? 'pcs') ?></p>
                                <p id="preview-weight" class="preview-info">Berat: <?= htmlspecialchars($produk_data['berat_kg'] ?? 0) ?> kg</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
$(document).ready(function() {
    // --- Logika untuk Sidebar Dropdown ---
    $('[data-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $(target).toggleClass('show');
        $(this).attr('aria-expanded', $(target).hasClass('show'));
        $('.collapse.show').not(target).removeClass('show').prev('[data-toggle="collapse"]').attr('aria-expanded', 'false');
    });

    // --- Logika untuk Toggle Sidebar di Mobile ---
    $('.sidebar-toggle-btn').on('click', function() {
        $('.sidebar').toggleClass('active');
        $('.page-body-wrapper').toggleClass('sidebar-active');
    });

    // --- Preview Produk Real-time ---
    function updatePreview() {
        // Gambar
        var reader = new FileReader();
        var fileInput = $('#gambar_utama')[0];
        var defaultPlaceholder = 'https://via.placeholder.com/200x200?text=Gambar+Produk';
        var currentImageUrl = "<?= $is_edit && $produk_data['gambar_utama'] ? '../assets/img/products/' . htmlspecialchars($produk_data['gambar_utama']) : '' ?>";

        if (fileInput.files && fileInput.files[0]) {
            reader.onload = function(e) {
                $('#preview-image').attr('src', e.target.result);
            }
            reader.readAsDataURL(fileInput.files[0]);
        } else if (currentImageUrl) {
            $('#preview-image').attr('src', currentImageUrl);
        } else {
            $('#preview-image').attr('src', defaultPlaceholder);
        }
        
        // Nama Produk
        var namaProduk = $('#nama_barang').val();
        $('#preview-name').text(namaProduk || 'Nama Produk Anda');

        // Harga
        var hargaAsli = parseFloat($('#harga').val()) || 0;
        var tipeDiskon = $('#tipe_diskon').val();
        var nilaiDiskon = parseFloat($('#nilai_diskon').val()) || 0;
        var hargaFinal = hargaAsli;
        var formattedHargaAsli = 'Rp ' + hargaAsli.toLocaleString('id-ID');
        var formattedHargaFinal;

        var diskonMulai = $('#diskon_mulai').val();
        var diskonBerakhir = $('#diskon_berakhir').val();
        var now = new Date();
        var diskonAktif = false;

        // Cek apakah diskon memiliki tipe dan nilai, dan dalam rentang waktu yang valid
        if (tipeDiskon && nilaiDiskon > 0) {
            if (diskonMulai && diskonBerakhir) {
                var startDate = new Date(diskonMulai);
                var endDate = new Date(diskonBerakhir);
                // Convert current time to WIB (UTC+7) for comparison if needed, or simply compare as UTC
                // For simplicity here, comparison is directly on Date objects which are UTC internally.
                // It's crucial that dates from DB / input are also treated consistently (e.g., all UTC or all local WIB)
                if (now >= startDate && now <= endDate) {
                    diskonAktif = true;
                }
            } else if (!diskonMulai && !diskonBerakhir) { // Diskon tanpa tanggal (selalu aktif)
                diskonAktif = true;
            }
        }

        if (diskonAktif) {
            if (tipeDiskon === 'NOMINAL') {
                hargaFinal = Math.max(0, hargaAsli - nilaiDiskon);
            } else if (tipeDiskon === 'PERSEN') {
                hargaFinal = hargaAsli * (1 - (nilaiDiskon / 100));
            }
            formattedHargaFinal = 'Rp ' + hargaFinal.toLocaleString('id-ID');

            // Tampilkan harga diskon sebagai utama, dan harga asli dicoret
            $('#preview-original-price').text(formattedHargaAsli).show(); 
            $('#preview-final-price').text(formattedHargaFinal).addClass('has-discount');
        } else {
            // Sembunyikan harga asli jika tidak ada diskon
            $('#preview-original-price').hide().text(''); 
            // Tampilkan harga asli sebagai harga utama
            $('#preview-final-price').text(formattedHargaAsli).removeClass('has-discount'); 
        }

        // Kategori
        var kategoriText = $('#kategori_id option:selected').text();
        $('#preview-category').text('Kategori: ' + (kategoriText && kategoriText !== 'Pilih Kategori' ? kategoriText : '-'));

        // Merek
        var merkProduk = $('#merk_barang').val();
        $('#preview-brand').text('Merek: ' + (merkProduk || '-'));

        // Stok & Satuan
        var stok = $('#stok').val() || 0;
        var satuan = $('#satuan_unit').val() || 'pcs';
        $('#preview-stock').text('Stok: ' + stok + ' ' + satuan);

        // Berat
        var berat = $('#berat_kg').val() || 0;
        $('#preview-weight').text('Berat: ' + berat + ' kg');
    }

    // Panggil updatePreview saat halaman dimuat dan setiap kali input berubah
    updatePreview(); 
    $('#productForm input, #productForm select, #productForm textarea').on('keyup change', updatePreview);
    
    // Logika untuk menampilkan/menyembunyikan input nilai diskon
    $('#tipe_diskon').on('change', function() {
        if ($(this).val() === 'NOMINAL' || $(this).val() === 'PERSEN') {
            $('#nilai_diskon_group').show();
            $('#nilai_diskon').attr('required', true);
        } else {
            $('#nilai_diskon_group').hide();
            $('#nilai_diskon').attr('required', false).val('');
            updatePreview(); // Panggil ulang untuk update harga di preview
        }
    });

    // Validasi Form sebelum submit (contoh sederhana)
    $('#productForm').on('submit', function(e) {
        // Contoh: Cek nama produk min length
        if ($('#nama_barang').val().length < 25) {
            alert('Nama Produk minimal 25 karakter!');
            e.preventDefault();
            $('#info-produk-tab').tab('show'); // Kembali ke tab Informasi Produk
            $('#nama_barang').focus();
            return;
        }
        if ($('#deskripsi').val().length < 100) {
            alert('Deskripsi Produk minimal 100 karakter!');
            e.preventDefault();
            $('#info-produk-tab').tab('show'); // Kembali ke tab Informasi Produk
            $('#deskripsi').focus();
            return;
            // Removed the duplicated check for `!diskonMulai`
        }
        // Tambahkan validasi tanggal diskon jika ada
        var tipeDiskon = $('#tipe_diskon').val();
        var nilaiDiskon = parseFloat($('#nilai_diskon').val()) || 0;
        var diskonMulai = $('#diskon_mulai').val();
        var diskonBerakhir = $('#diskon_berakhir').val();
        
        if ((tipeDiskon === 'NOMINAL' || tipeDiskon === 'PERSEN') && nilaiDiskon > 0) {
            if (!diskonMulai || !diskonBerakhir) {
                alert('Tanggal mulai dan berakhir diskon harus diisi jika diskon diaktifkan!');
                e.preventDefault();
                $('#info-penjualan-tab').tab('show');
                return;
            }
            if (new Date(diskonMulai) >= new Date(diskonBerakhir)) {
                alert('Tanggal berakhir diskon harus setelah tanggal mulai!');
                e.preventDefault();
                $('#info-penjualan-tab').tab('show');
                return;
            }
        }
        
        // Tambahkan validasi lain sesuai kebutuhan
    });

    // Redirect Batal
    $('.btn-cancel').on('click', function() {
        if (confirm('Anda yakin ingin membatalkan? Perubahan tidak akan disimpan.')) {
            window.location.href = 'produk.php'; // Kembali ke halaman produk saya
        }
    });
});
</script>
</body>
</html>