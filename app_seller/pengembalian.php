<?php
session_start();
require_once '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); 
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

// Ambil semua permintaan pembatalan/pengembalian untuk toko ini
// Menambahkan lebih banyak kolom yang mungkin relevan untuk tampilan Shopee
$sql = "SELECT d.id AS detail_id, d.jumlah, d.subtotal, d.catatan_pembeli, d.status_pesanan_item, 
               t.id AS transaksi_id, t.kode_invoice, t.tanggal_transaksi,
               b.nama_barang, b.gambar_utama, -- Menambahkan gambar_barang
               u.nama AS nama_pelanggan, u.email AS email_pelanggan
        FROM tb_detail_transaksi d
        JOIN tb_transaksi t ON d.transaksi_id = t.id
        JOIN tb_barang b ON d.barang_id = b.id
        JOIN tb_user u ON t.user_id = u.id
        WHERE d.toko_id = ? AND d.status_pesanan_item IN ('pengajuan_pengembalian', 'dibatalkan', 'disetujui_pengembalian', 'ditolak_pengembalian', 'selesai_pengembalian')
        ORDER BY t.tanggal_transaksi DESC, d.status_pesanan_item ASC";
$stmt_requests = $koneksi->prepare($sql);
$stmt_requests->bind_param("i", $toko_id);
$stmt_requests->execute();
$result_requests = $stmt_requests->get_result();

// Kelompokkan permintaan berdasarkan transaksi_id atau detail_id utama
// Untuk tampilan seperti Shopee, kita akan kelompokkan per transaksi/invoice,
// tapi karena ini pengembalian per item, kita bisa kelompokkan per detail_id
$grouped_requests = [];
while($req = $result_requests->fetch_assoc()) {
    $req['gambar_barang'] = $req['gambar_barang'] ?? 'default.jpg'; // Fallback for image
    $grouped_requests[$req['detail_id']] = $req; // Group by detail_id
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pengembalian/Pembatalan - Pondasikita Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
        <link rel="stylesheet" href="/assets/css/sidebar.css"> 
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css"> 
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
                    <h1 class="page-title"><i class="mdi mdi-undo-variant"></i> Pengajuan Pengembalian/Pembatalan</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="order-filter-tabs mb-4">
                            <a href="#" class="filter-tab active" data-status="">Semua</a>
                            <a href="#" class="filter-tab" data-status="pengajuan_pengembalian">Menunggu Respon</a>
                            <a href="#" class="filter-tab" data-status="disetujui_pengembalian">Disetujui</a>
                            <a href="#" class="filter-tab" data-status="ditolak_pengembalian">Ditolak</a>
                            <a href="#" class="filter-tab" data-status="selesai_pengembalian">Selesai</a>
                            <a href="#" class="filter-tab" data-status="dibatalkan">Pembatalan Selesai</a>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                            <div class="search-bar d-flex flex-grow-1 me-3 mb-2 mb-md-0">
                                <input type="text" id="requestSearchInput" class="form-control" placeholder="Cari berdasarkan invoice/nama produk/pelanggan">
                                <button class="btn btn-secondary ms-2"><i class="mdi mdi-magnify"></i></button>
                            </div>
                            </div>

                        <div class="table-responsive">
                            <table id="pengembalianTable" class="table">
                                <thead>
                                    <tr>
                                        <th>No. Pengajuan</th>
                                        <th>Waktu Pengajuan</th>
                                        <th>Pelanggan</th>
                                        <th style="min-width: 200px;">Produk</th>
                                        <th>Alasan Pembeli</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($grouped_requests)): ?>
                                        <?php foreach ($grouped_requests as $detail_id => $req): ?>
                                        <tr class="request-row" data-status="<?= htmlspecialchars($req['status_pesanan_item']) ?>">
                                            <td><?= htmlspecialchars($req['detail_id']) ?></td>
                                            <td><?= date('d M Y, H:i', strtotime($req['tanggal_transaksi'])) ?></td>
                                            <td><?= htmlspecialchars($req['nama_pelanggan']) ?></td>
                                            <td>
                                                <div class="product-info-cell">
                                                    <img src="../assets/img/products/<?= htmlspecialchars($req['gambar_barang']) ?>" alt="<?= htmlspecialchars($req['nama_barang']) ?>" class="product-thumb" onerror="this.onerror=null;this.src='https://via.placeholder.com/50';">
                                                    <div class="product-details">
                                                        <span class="product-name"><?= htmlspecialchars($req['nama_barang']) ?></span>
                                                        <span class="product-qty">x<?= $req['jumlah'] ?> @ Rp <?= number_format($req['subtotal']/$req['jumlah'], 0, ',', '.') ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($req['catatan_pembeli'] ?: '-') ?></td>
                                            <td>
                                                <?php
                                                    $status_text = ucfirst(str_replace('_', ' ', $req['status_pesanan_item']));
                                                    $badge_class = '';
                                                    switch($req['status_pesanan_item']) {
                                                        case 'pengajuan_pengembalian': $badge_class = 'badge-warning'; break; // Menunggu Respon
                                                        case 'disetujui_pengembalian': $badge_class = 'badge-success'; break;
                                                        case 'ditolak_pengembalian': $badge_class = 'badge-danger'; break;
                                                        case 'selesai_pengembalian': $badge_class = 'badge-primary'; break; // Misal biru
                                                        case 'dibatalkan': $badge_class = 'badge-danger'; break; // Pembatalan Selesai
                                                        default: $badge_class = 'badge-secondary';
                                                    }
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column align-items-center action-buttons">
                                                    <?php if ($req['status_pesanan_item'] == 'pengajuan_pengembalian'): ?>
                                                    <a href="../actions/proses_pengembalian.php?id=<?= $req['detail_id'] ?>&action=setujui" class="btn btn-sm btn-success mb-2" onclick="return confirm('Setujui pengajuan ini?')" title="Setujui Pengembalian">Setujui</a>
                                                    <a href="../actions/proses_pengembalian.php?id=<?= $req['detail_id'] ?>&action=tolak" class="btn btn-sm btn-danger mb-2" onclick="return confirm('Tolak pengajuan ini?')" title="Tolak Pengembalian">Tolak</a>
                                                    <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Lihat Detail">Lihat Detail</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada pengajuan pengembalian/pembatalan saat ini.</td>
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

    // --- DataTables (Nonaktif untuk Grouping Kompleks, Gunakan Fitur Manual JS) ---
    // $('#pengembalianTable').DataTable({
    //     "order": [[1, "desc"]], // Urutkan berdasarkan waktu pengajuan
    //     "pagingType": "full_numbers",
    //     "searching": true,
    //     "info": true
    // });

    // --- Logika Search Bar (Basic) ---
    $('#requestSearchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#pengembalianTable tbody tr.request-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // --- Logika Filter Tabs (Basic - hanya menampilkan/menyembunyikan baris) ---
    $('.filter-tab').on('click', function(e) {
        e.preventDefault();
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');

        var filterStatus = $(this).data('status');

        $('#pengembalianTable tbody tr.request-row').each(function() {
            var $row = $(this);
            var itemStatus = $row.data('status');

            if (filterStatus === '' || itemStatus === filterStatus) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });
});
</script>
</body>
</html>