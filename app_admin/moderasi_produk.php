<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK FILTER & PENCARIAN ---
$status_filter = $_GET['status'] ?? 'pending';
$search_query = $_GET['search'] ?? '';

$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

// --- QUERY PENGAMBILAN DATA PRODUK ---
$sql = "SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, b.status_moderasi, k.nama_kategori, t.nama_toko 
        FROM tb_barang b 
        JOIN tb_toko t ON b.toko_id = t.id
        LEFT JOIN tb_kategori k ON b.kategori_id = k.id";

$params = [];
$types = '';
$where_clauses = ["b.status_moderasi = ?"];
$params[] = $status_filter;
$types .= 's';

// Filter berdasarkan pencarian
if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    // Mencari di nama produk atau nama toko
    $where_clauses[] = "(b.nama_barang LIKE ? OR t.nama_toko LIKE ?)";
    array_push($params, $search_term, $search_term);
    $types .= 'ss';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_produk = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Moderasi Produk - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
    <style>
        
    </style>
<div class="container-scroller">
    <?php 
    $current_page_name = 'moderasi_produk.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Moderasi Produk</h1></div>

                <?php if (isset($_SESSION['feedback'])): ?>
                <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>" style="margin-bottom: 1.5rem;">
                    <span><?= $_SESSION['feedback']['pesan'] ?></span>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <ul class="nav nav-tabs" id="produkTab">
                                <li class="nav-item"><a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="?status=pending">Menunggu Persetujuan</a></li>
                                <li class="nav-item"><a class="nav-link <?= $status_filter == 'approved' ? 'active' : '' ?>" href="?status=approved">Disetujui</a></li>
                                <li class="nav-item"><a class="nav-link <?= $status_filter == 'rejected' ? 'active' : '' ?>" href="?status=rejected">Ditolak</a></li>
                            </ul>
                            <!-- FORM PENCARIAN DITAMBAHKAN DI SINI -->
                            <form action="" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                                <input type="text" name="search" class="form-control" placeholder="Cari nama produk/toko..." value="<?= htmlspecialchars($search_query) ?>">
                                <button type="submit" class="btn btn-primary">Cari</button>
                            </form>
                        </div>

                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr><th>Produk</th><th>Toko</th><th>Kategori</th><th>Harga</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_produk->num_rows > 0): ?>
                                        <?php while($produk = $result_produk->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-info">
                                                        <img src="../assets/uploads/products/<?= htmlspecialchars($produk['gambar_utama'] ?? 'default.jpg') ?>" alt="Gambar Produk" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                        <span><?= htmlspecialchars($produk['nama_barang']) ?></span>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($produk['nama_toko']) ?></td>
                                                <td><?= htmlspecialchars($produk['nama_kategori']) ?></td>
                                                <td>Rp <?= number_format($produk['harga'], 0, ',', '.') ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="detail_produk_admin.php?id=<?= $produk['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
                                                        <?php if ($produk['status_moderasi'] == 'pending'): ?>
                                                            <a href="actions/proses_moderasi.php?id=<?= $produk['id'] ?>&action=approve" class="btn btn-success btn-sm" onclick="return confirm('Anda yakin ingin menyetujui produk ini?')">Setujui</a>
                                                            <button class="btn btn-danger btn-sm btn-tolak" data-id="<?= $produk['id'] ?>">Tolak</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5"><div class="text-center p-5"><p class="text-secondary">Tidak ada produk yang ditemukan.</p></div></td></tr>
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

<!-- Modal untuk menolak produk -->
<div class="modal-overlay" id="tolakProdukModal">
    <div class="modal-content">
        <form action="actions/proses_moderasi.php" method="POST">
            <div class="modal-header"><h4 class="modal-title">Tolak Produk</h4><button type="button" class="close-btn" data-close-modal>&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="produk_id" id="produk_id_tolak">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="alasan_penolakan">Alasan Penolakan</label>
                    <textarea class="form-control" name="alasan_penolakan" rows="4" required placeholder="Jelaskan mengapa produk ini ditolak..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
                <button type="submit" class="btn btn-danger">Kirim Penolakan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const modal = $('#tolakProdukModal');
    $('.btn-tolak').on('click', function() {
        $('#produk_id_tolak').val($(this).data('id'));
        modal.css('display', 'flex');
    });
    $('[data-close-modal]').on('click', function() {
        modal.fadeOut();
    });
});
</script>
</body>
</html>
