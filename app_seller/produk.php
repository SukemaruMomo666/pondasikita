<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN & PENGAMBILAN DATA ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); // Arahkan ke root seller login jika belum login
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'] ?? null; // Null coalescing operator
$stmt_toko->close();

if (!$toko_id) {
    die("Data toko tidak ditemukan untuk pengguna ini. Silakan hubungi admin.");
}

// Query untuk mengambil semua produk dari toko ini
$produk_query = $koneksi->prepare("SELECT * FROM tb_barang WHERE toko_id = ? ORDER BY created_at DESC");
$produk_query->bind_param("i", $toko_id);
$produk_query->execute();
$result_produk = $produk_query->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Produk - Pondasikita Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css"> 
        <link rel="stylesheet" href="/assets/css/sidebar.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    </head>
<body>
<div class="container-scroller">
    <?php 
    // FUNGSI PEMBANTU UNTUK SIDEBAR (DUPLIKASI JIKA BELUM ADA DI file PHP ini)
    // Dapatkan path relatif dari URL saat ini
    if (!function_exists('getCurrentRelativePath')) { // Check if function already exists
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

    include 'partials/sidebar.php'; 
    ?>
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
                    <h1 class="page-title"><i class="mdi mdi-cube-unfolded"></i> Produk Saya</h1>
                    <a href="form_produk.php" class="btn btn-primary">+ Tambah Produk Baru</a>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Daftar Semua Produk</h4>
                        
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                            <div class="search-bar d-flex flex-grow-1 me-3 mb-2 mb-md-0">
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Cari berdasarkan nama produk">
                                <button class="btn btn-secondary ms-2"><i class="mdi mdi-magnify"></i></button>
                            </div>
                            <select id="productStatusFilter" class="form-select w-auto mb-2 mb-md-0">
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="pending">Menunggu</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>

                        <div class="table-responsive">
                            <table id="produkTable" class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Gambar</th>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_produk->num_rows > 0): ?>
                                        <?php $no = 1; while($produk = $result_produk->fetch_assoc()): ?>
                                        <tr data-status-moderasi="<?= htmlspecialchars($produk['status_moderasi']) ?>" data-is-active="<?= $produk['is_active'] ? 'active' : 'inactive' ?>">
                                            <td><?= $no++ ?></td>
                                            <td><img src="/assets/img/products/<?= htmlspecialchars($produk['gambar_utama'] ?? 'default.jpg') ?>" class="product-image-thumb" alt="<?= htmlspecialchars($produk['nama_barang']) ?>" onerror="this.onerror=null;this.src='https://via.placeholder.com/60';"></td>
                                            <td><?= htmlspecialchars($produk['nama_barang']) ?></td>
                                            <td>Rp<?= number_format($produk['harga'], 0, ',', '.') ?></td>
                                            <td><?= $produk['stok'] ?></td>
                                            <td>
                                                <?php 
                                                    $status_badge_text = '';
                                                    $badge_class = '';
                                                    if ($produk['status_moderasi'] == 'pending') {
                                                        $status_badge_text = 'Menunggu';
                                                        $badge_class = 'badge-warning';
                                                    } elseif ($produk['status_moderasi'] == 'approved' && $produk['is_active']) {
                                                        $status_badge_text = 'Aktif';
                                                        $badge_class = 'badge-success';
                                                    } elseif ($produk['status_moderasi'] == 'rejected') {
                                                        $status_badge_text = 'Ditolak';
                                                        $badge_class = 'badge-danger';
                                                    } else { // Approved but inactive
                                                        $status_badge_text = 'Nonaktif';
                                                        $badge_class = 'badge-secondary';
                                                    }
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $status_badge_text ?></span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-icon dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="mdi mdi-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="form_produk.php?edit=<?= $produk['id'] ?>">Edit</a></li>
                                                        <li><a class="dropdown-item" href="../actions/proses_produk.php?hapus=<?= $produk['id'] ?>" onclick="return confirm('Anda yakin ingin menghapus produk ini?')">Hapus</a></li>
                                                        <?php if ($produk['status_moderasi'] == 'approved'): ?>
                                                            <li>
                                                                <a class="dropdown-item toggle-product-status" href="#" data-id="<?= $produk['id'] ?>" data-is-active="<?= $produk['is_active'] ? '0' : '1' ?>">
                                                                    <?= $produk['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Belum ada produk yang ditambahkan. <a href="tambah_produk.php">Tambah produk pertama Anda!</a></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
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

    // --- Inisialisasi DataTables ---
    var produkTable = $('#produkTable').DataTable({
        "order": [[0, "asc"]], // Urutkan berdasarkan kolom No (index 0) ascending
        "pagingType": "full_numbers",
        "searching": true, // Aktifkan pencarian DataTables
        "info": true, // Tampilkan info halaman
        "columnDefs": [
            { "orderable": false, "targets": [1, 6] } // Kolom Gambar dan Aksi tidak bisa diurutkan
        ]
    });

    // --- Kustom Search dan Filter untuk DataTables ---
    $('#productSearchInput').on('keyup', function() {
        produkTable.search(this.value).draw();
    });

    $('#productStatusFilter').on('change', function() {
        var statusFilter = $(this).val();
        // Hapus filter yang ada jika ada
        produkTable.columns().search('').draw(); // Clear all column searches
        
        if (statusFilter === 'active') {
            produkTable.columns(5).search('Aktif').draw();
        } else if (statusFilter === 'inactive') {
            produkTable.columns(5).search('Nonaktif').draw();
        } else if (statusFilter === 'pending') {
            produkTable.columns(5).search('Menunggu').draw();
        } else if (statusFilter === 'rejected') {
            produkTable.columns(5).search('Ditolak').draw();
        } else {
            produkTable.columns(5).search('').draw(); // Tampilkan semua
        }
    });

    // --- Logika Toggle Aktif/Nonaktif Produk via AJAX ---
    $(document).on('click', '.toggle-product-status', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const isActive = $(this).data('is-active'); // Ambil status yang ingin diubah (0 atau 1)
        const confirmationMessage = isActive == 1 ? 'Aktifkan produk ini?' : 'Nonaktifkan produk ini?';

        if (confirm(confirmationMessage)) {
            $.ajax({
                url: '../actions/proses_produk.php', // Path ke proses_produk.php
                type: 'POST',
                data: {
                    action: 'toggle_status',
                    product_id: productId,
                    is_active: isActive
                },
                success: function(response) {
                    // Handle response (misal: refresh halaman atau update UI)
                    console.log('Status produk updated:', response);
                    alert('Status produk berhasil diperbarui!');
                    location.reload(); // Refresh halaman untuk melihat perubahan
                },
                error: function(xhr, status, error) {
                    console.error('Error updating product status:', error);
                    alert('Gagal memperbarui status produk. Silakan coba lagi.');
                }
            });
        }
    });
});
</script>
</body>
</html>