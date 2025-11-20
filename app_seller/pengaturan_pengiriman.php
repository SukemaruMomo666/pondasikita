
<?php
session_start();
require_once '../config/koneksi.php'; // Path disesuaikan, karena folder 'pengaturan'
date_default_timezone_set('Asia/Jakarta');

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../index.php"); // Arahkan ke root seller login
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

// Ambil daftar kurir yang sudah ada untuk toko ini
$kurir_query = $koneksi->prepare("SELECT * FROM tb_kurir_toko WHERE toko_id = ? ORDER BY tipe_kurir, nama_kurir ASC");
$kurir_query->bind_param("i", $toko_id);
$kurir_query->execute();
$result_kurir = $kurir_query->get_result();

// Kelompokkan kurir berdasarkan tipe
$grouped_kurir = [];
$tipe_order = [
    'REGULAR' => 'Reguler (Cashless)',
    'HEMAT' => 'Hemat',
    'KARGO' => 'Kargo',
    'INSTANT' => 'Instan',
    'SAME_DAY' => 'Same Day',
    'NEXT_DAY' => 'Next Day',
    'AMBIL_DI_TEMPAT' => 'Ambil di Tempat',
    'PILIHAN_LAINNYA' => 'Pilihan Jasa Kirim Lainnya'
];

while($kurir = $result_kurir->fetch_assoc()) {
    $grouped_kurir[$kurir['tipe_kurir']][] = $kurir;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Pengiriman - Pondasikita Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../assets/css/seller_style.css"> 
        <link rel="stylesheet" href="/assets/css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?> <div class="page-body-wrapper">
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
                    <h1 class="page-title"><i class="mdi mdi-truck-delivery"></i> Pengaturan Pengiriman</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Pengaturan Jasa Kirim Toko Anda</h4>
                        <p class="card-subtitle">Atur jasa kirim yang akan ditampilkan kepada pembeli. "Kurir Toko" untuk pengiriman mandiri/same-day/kustom, "Pihak Ketiga" untuk ekspedisi umum.</p>
                        
                        <?php 
                        foreach ($tipe_order as $tipe_key => $tipe_label): 
                            $kurir_list = $grouped_kurir[$tipe_key] ?? [];
                        ?>
                            <div class="shipping-category">
                                <h5 class="category-title" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $tipe_key ?>" aria-expanded="true" aria-controls="collapse-<?= $tipe_key ?>">
                                    <?= $tipe_label ?> <i class="mdi mdi-chevron-down toggle-arrow"></i>
                                </h5>
                                <div class="collapse show" id="collapse-<?= $tipe_key ?>"> <div class="category-content">
                                        <?php if (!empty($kurir_list)): ?>
                                            <?php foreach ($kurir_list as $kurir): ?>
                                                <div class="courier-item">
                                                    <div class="courier-info">
                                                        <span class="courier-name"><?= htmlspecialchars($kurir['nama_kurir']) ?></span>
                                                        <p class="courier-desc"><?= htmlspecialchars($kurir['estimasi_waktu']) ?> | Rp<?= number_format($kurir['biaya'], 0, ',', '.') ?></p>
                                                    </div>
                                                    <div class="courier-actions">
                                                        <label class="toggle-switch">
                                                            <input type="checkbox" class="toggle-input" data-id="<?= $kurir['id'] ?>" <?= $kurir['is_active'] ? 'checked' : '' ?>>
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                        
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-icon dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="mdi mdi-dots-vertical"></i> Tukar
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item btn-edit-courier" href="#" data-id="<?= $kurir['id'] ?>">Edit</a></li>
                                                                <li><a class="dropdown-item btn-delete-courier" href="../../actions/proses_pengiriman.php?hapus=<?= $kurir['id'] ?>" onclick="return confirm('Yakin ingin hapus layanan ini?')" title="Hapus Layanan">Hapus</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="empty-state small-empty-state">
                                                <i class="mdi mdi-package-variant-closed"></i>
                                                <p>Belum ada layanan pengiriman untuk kategori ini.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr class="mt-4 mb-4">
                        <button class="btn btn-primary" id="addCourierBtn"><i class="mdi mdi-plus"></i> Tambah Opsi Pengiriman Baru</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="kurirModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../../actions/proses_pengiriman.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Opsi Pengiriman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="form-action" value="tambah">
                    <input type="hidden" name="kurir_id" id="kurir_id">
                    
                    <div class="mb-3">
                        <label for="nama_kurir" class="form-label">Nama Layanan</label>
                        <input type="text" name="nama_kurir" id="nama_kurir" class="form-control" placeholder="Contoh: JNE REG / Instan Same Day" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipe_kurir" class="form-label">Tipe Layanan</label>
                        <select name="tipe_kurir" id="tipe_kurir" class="form-select">
                            <?php foreach ($tipe_order as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="estimasi_waktu" class="form-label">Estimasi Waktu</label>
                        <input type="text" name="estimasi_waktu" id="estimasi_waktu" class="form-control" placeholder="Contoh: 1-2 Hari / 2 Jam" required>
                    </div>
                    <div class="mb-3">
                        <label for="biaya" class="form-label">Biaya (Rp)</label>
                        <input type="number" name="biaya" id="biaya" class="form-control" placeholder="Isi 0 jika ongkir nego" required min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="is_active_modal" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="is_active_modal">Aktifkan layanan ini</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
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

    // --- Modal Logic ---
    $('#addCourierBtn').on('click', function() {
        $('#modalTitle').text('Tambah Opsi Pengiriman');
        $('#kurirModal form').trigger('reset');
        $('#form-action').val('tambah');
        $('#is_active_modal').prop('checked', true); // Pastikan checkbox modal aktif
        var kurirModal = new bootstrap.Modal(document.getElementById('kurirModal'));
        kurirModal.show();
    });

    // Event listener for edit buttons
    $(document).on('click', '.btn-edit-courier', function(e) {
        e.preventDefault();
        const kurirId = $(this).data('id');
        fetch(`../../actions/api_get_kurir_detail.php?id=${kurirId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalTitle').text('Edit Opsi Pengiriman');
                    $('#form-action').val('update');
                    $('#kurir_id').val(data.data.id);
                    $('#nama_kurir').val(data.data.nama_kurir);
                    $('#tipe_kurir').val(data.data.tipe_kurir);
                    $('#estimasi_waktu').val(data.data.estimasi_waktu);
                    $('#biaya').val(data.data.biaya);
                    $('#is_active_modal').prop('checked', data.data.is_active == 1); // Perbaiki ID
                    var kurirModal = new bootstrap.Modal(document.getElementById('kurirModal'));
                    kurirModal.show();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error fetching courier detail:', error));
    });

    // --- Toggle Switch Logic (Update status via AJAX) ---
    $(document).on('change', '.toggle-input', function() {
        const kurirId = $(this).data('id');
        const isActive = $(this).is(':checked') ? 1 : 0;
        
        // Kirim permintaan AJAX untuk update status
        $.ajax({
            url: '../../actions/proses_pengiriman.php', // Sesuaikan path action Anda
            type: 'POST',
            data: {
                action: 'toggle_status',
                kurir_id: kurirId,
                is_active: isActive
            },
            success: function(response) {
                // Handle response (misal: tampilkan notifikasi sukses/gagal)
                console.log('Status updated:', response);
                // Anda bisa parse JSON response dan menampilkan alert/toast
            },
            error: function(xhr, status, error) {
                console.error('Error updating status:', error);
                alert('Gagal memperbarui status. Silakan coba lagi.');
                // Kembalikan toggle ke posisi semula jika gagal
                $('.toggle-input[data-id="' + kurirId + '"]').prop('checked', !isActive);
            }
        });
    });

    // --- Category Collapse Toggle (Default Bootstrap 5 behavior) ---
    // Atribut data-bs-toggle dan data-bs-target sudah cukup.
    // Menambahkan class untuk arrow rotation
    $('.shipping-category .category-title').on('click', function() {
        $(this).find('.toggle-arrow').toggleClass('rotated');
    });

    // Pastikan semua category-title memiliki aria-expanded yang benar saat halaman dimuat
    // agar panah sesuai dengan state collapse
    $('.shipping-category .category-title').each(function() {
        var targetId = $(this).data('bs-target');
        var isExpanded = $(targetId).hasClass('show');
        $(this).attr('aria-expanded', isExpanded);
        if (isExpanded) {
            $(this).find('.toggle-arrow').addClass('rotated');
        }
    });

});
</script>
</body>
=======
<?php

require_once '../config/koneksi.php'; // Path disesuaikan, karena folder 'pengaturan'
date_default_timezone_set('Asia/Jakarta');

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../index.php"); // Arahkan ke root seller login
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

// Ambil daftar kurir yang sudah ada untuk toko ini
$kurir_query = $koneksi->prepare("SELECT * FROM tb_kurir_toko WHERE toko_id = ? ORDER BY tipe_kurir, nama_kurir ASC");
$kurir_query->bind_param("i", $toko_id);
$kurir_query->execute();
$result_kurir = $kurir_query->get_result();

// Kelompokkan kurir berdasarkan tipe
$grouped_kurir = [];
$tipe_order = [
    'REGULAR' => 'Reguler (Cashless)',
    'HEMAT' => 'Hemat',
    'KARGO' => 'Kargo',
    'INSTANT' => 'Instan',
    'SAME_DAY' => 'Same Day',
    'NEXT_DAY' => 'Next Day',
    'AMBIL_DI_TEMPAT' => 'Ambil di Tempat',
    'PILIHAN_LAINNYA' => 'Pilihan Jasa Kirim Lainnya'
];

while($kurir = $result_kurir->fetch_assoc()) {
    $grouped_kurir[$kurir['tipe_kurir']][] = $kurir;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Pengiriman - Pondasikita Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../assets/css/seller_style.css"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?> <div class="page-body-wrapper">
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
                    <h1 class="page-title"><i class="mdi mdi-truck-delivery"></i> Pengaturan Pengiriman</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Pengaturan Jasa Kirim Toko Anda</h4>
                        <p class="card-subtitle">Atur jasa kirim yang akan ditampilkan kepada pembeli. "Kurir Toko" untuk pengiriman mandiri/same-day/kustom, "Pihak Ketiga" untuk ekspedisi umum.</p>
                        
                        <?php 
                        foreach ($tipe_order as $tipe_key => $tipe_label): 
                            $kurir_list = $grouped_kurir[$tipe_key] ?? [];
                        ?>
                            <div class="shipping-category">
                                <h5 class="category-title" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $tipe_key ?>" aria-expanded="true" aria-controls="collapse-<?= $tipe_key ?>">
                                    <?= $tipe_label ?> <i class="mdi mdi-chevron-down toggle-arrow"></i>
                                </h5>
                                <div class="collapse show" id="collapse-<?= $tipe_key ?>"> <div class="category-content">
                                        <?php if (!empty($kurir_list)): ?>
                                            <?php foreach ($kurir_list as $kurir): ?>
                                                <div class="courier-item">
                                                    <div class="courier-info">
                                                        <span class="courier-name"><?= htmlspecialchars($kurir['nama_kurir']) ?></span>
                                                        <p class="courier-desc"><?= htmlspecialchars($kurir['estimasi_waktu']) ?> | Rp<?= number_format($kurir['biaya'], 0, ',', '.') ?></p>
                                                    </div>
                                                    <div class="courier-actions">
                                                        <label class="toggle-switch">
                                                            <input type="checkbox" class="toggle-input" data-id="<?= $kurir['id'] ?>" <?= $kurir['is_active'] ? 'checked' : '' ?>>
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                        
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-icon dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="mdi mdi-dots-vertical"></i> Tukar
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item btn-edit-courier" href="#" data-id="<?= $kurir['id'] ?>">Edit</a></li>
                                                                <li><a class="dropdown-item btn-delete-courier" href="../../actions/proses_pengiriman.php?hapus=<?= $kurir['id'] ?>" onclick="return confirm('Yakin ingin hapus layanan ini?')" title="Hapus Layanan">Hapus</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="empty-state small-empty-state">
                                                <i class="mdi mdi-package-variant-closed"></i>
                                                <p>Belum ada layanan pengiriman untuk kategori ini.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr class="mt-4 mb-4">
                        <button class="btn btn-primary" id="addCourierBtn"><i class="mdi mdi-plus"></i> Tambah Opsi Pengiriman Baru</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="kurirModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../../actions/proses_pengiriman.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Opsi Pengiriman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="form-action" value="tambah">
                    <input type="hidden" name="kurir_id" id="kurir_id">
                    
                    <div class="mb-3">
                        <label for="nama_kurir" class="form-label">Nama Layanan</label>
                        <input type="text" name="nama_kurir" id="nama_kurir" class="form-control" placeholder="Contoh: JNE REG / Instan Same Day" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipe_kurir" class="form-label">Tipe Layanan</label>
                        <select name="tipe_kurir" id="tipe_kurir" class="form-select">
                            <?php foreach ($tipe_order as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="estimasi_waktu" class="form-label">Estimasi Waktu</label>
                        <input type="text" name="estimasi_waktu" id="estimasi_waktu" class="form-control" placeholder="Contoh: 1-2 Hari / 2 Jam" required>
                    </div>
                    <div class="mb-3">
                        <label for="biaya" class="form-label">Biaya (Rp)</label>
                        <input type="number" name="biaya" id="biaya" class="form-control" placeholder="Isi 0 jika ongkir nego" required min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="is_active_modal" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="is_active_modal">Aktifkan layanan ini</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
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

    // --- Modal Logic ---
    $('#addCourierBtn').on('click', function() {
        $('#modalTitle').text('Tambah Opsi Pengiriman');
        $('#kurirModal form').trigger('reset');
        $('#form-action').val('tambah');
        $('#is_active_modal').prop('checked', true); // Pastikan checkbox modal aktif
        var kurirModal = new bootstrap.Modal(document.getElementById('kurirModal'));
        kurirModal.show();
    });

    // Event listener for edit buttons
    $(document).on('click', '.btn-edit-courier', function(e) {
        e.preventDefault();
        const kurirId = $(this).data('id');
        fetch(`../../actions/api_get_kurir_detail.php?id=${kurirId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalTitle').text('Edit Opsi Pengiriman');
                    $('#form-action').val('update');
                    $('#kurir_id').val(data.data.id);
                    $('#nama_kurir').val(data.data.nama_kurir);
                    $('#tipe_kurir').val(data.data.tipe_kurir);
                    $('#estimasi_waktu').val(data.data.estimasi_waktu);
                    $('#biaya').val(data.data.biaya);
                    $('#is_active_modal').prop('checked', data.data.is_active == 1); // Perbaiki ID
                    var kurirModal = new bootstrap.Modal(document.getElementById('kurirModal'));
                    kurirModal.show();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error fetching courier detail:', error));
    });

    // --- Toggle Switch Logic (Update status via AJAX) ---
    $(document).on('change', '.toggle-input', function() {
        const kurirId = $(this).data('id');
        const isActive = $(this).is(':checked') ? 1 : 0;
        
        // Kirim permintaan AJAX untuk update status
        $.ajax({
            url: '../../actions/proses_pengiriman.php', // Sesuaikan path action Anda
            type: 'POST',
            data: {
                action: 'toggle_status',
                kurir_id: kurirId,
                is_active: isActive
            },
            success: function(response) {
                // Handle response (misal: tampilkan notifikasi sukses/gagal)
                console.log('Status updated:', response);
                // Anda bisa parse JSON response dan menampilkan alert/toast
            },
            error: function(xhr, status, error) {
                console.error('Error updating status:', error);
                alert('Gagal memperbarui status. Silakan coba lagi.');
                // Kembalikan toggle ke posisi semula jika gagal
                $('.toggle-input[data-id="' + kurirId + '"]').prop('checked', !isActive);
            }
        });
    });

    // --- Category Collapse Toggle (Default Bootstrap 5 behavior) ---
    // Atribut data-bs-toggle dan data-bs-target sudah cukup.
    // Menambahkan class untuk arrow rotation
    $('.shipping-category .category-title').on('click', function() {
        $(this).find('.toggle-arrow').toggleClass('rotated');
    });

    // Pastikan semua category-title memiliki aria-expanded yang benar saat halaman dimuat
    // agar panah sesuai dengan state collapse
    $('.shipping-category .category-title').each(function() {
        var targetId = $(this).data('bs-target');
        var isExpanded = $(targetId).hasClass('show');
        $(this).attr('aria-expanded', isExpanded);
        if (isExpanded) {
            $(this).find('.toggle-arrow').addClass('rotated');
        }
    });

});
</script>
</body>
>>>>>>> 11b7866a3de6448a488ec32e1c1b398ae3984787
</html>