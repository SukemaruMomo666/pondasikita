<?php
// ===== PENAMBAHAN KODE SESSION DIMULAI DI SINI =====

// 1. Memulai Sesi
session_start();

// 2. Cek apakah pengguna sudah login dan apakah perannya adalah 'admin'
// Jika tidak ada session 'login' atau session 'role' bukan 'admin', maka alihkan
if (!isset($_SESSION['user']) || $_SESSION['user']['level'] != 'admin') {
    // Jika tidak, alihkan ke halaman login dengan pesan error
    header("Location: ../../../login.php?pesan=akses_ditolak"); // Ganti dengan URL login Anda
    exit; // Wajib, untuk menghentikan eksekusi kode halaman ini
}

// Ganti path koneksi.php sesuai struktur Anda
// Dari pages/pesanan/transaksi.php naik 2x ke admin/ lalu ke config
include '../config/koneksi.php'; 

// --- Variabel untuk Filter dan Sorting ---
$filter_sumber = isset($_GET['filter_sumber']) ? mysqli_real_escape_string($koneksi, $_GET['filter_sumber']) : '';
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($koneksi, $_GET['sort_by']) : 'tanggal_transaksi';
$sort_order = isset($_GET['sort_order']) ? mysqli_real_escape_string($koneksi, $_GET['sort_order']) : 'DESC';

// --- Paginasi ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Membangun Query SQL ---
$sql_where_clauses = [];
if (!empty($filter_sumber)) {
    $sql_where_clauses[] = "t.sumber_transaksi = '$filter_sumber'";
}
$where_sql = "";
if (count($sql_where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $sql_where_clauses);
}

// Validasi sorting
$allowed_sort_columns = ['kode_invoice', 'tanggal_transaksi', 'total_harga', 'nama_pelanggan'];
if (!in_array($sort_by, $allowed_sort_columns)) $sort_by = 'tanggal_transaksi';
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) $sort_order = 'DESC';
$order_sql = "ORDER BY t.$sort_by $sort_order";

// Query untuk menghitung total
$queryCount = "SELECT COUNT(t.id) AS total FROM tb_transaksi t $where_sql";
$resultCount = mysqli_query($koneksi, $queryCount);
$rowCount = mysqli_fetch_assoc($resultCount);
$total_transaksi = $rowCount['total'];
$total_pages = ceil($total_transaksi / $limit);

// Query utama untuk mengambil data transaksi
$queryTransaksi = "SELECT t.*, u.username AS nama_user_online
                 FROM tb_transaksi t
                 LEFT JOIN tb_user u ON t.user_id = u.id 
                 $where_sql 
                 $order_sql 
                 LIMIT $limit OFFSET $offset";
$resultTransaksi = mysqli_query($koneksi, $queryTransaksi);

// Helper function untuk status badge
function getStatusBadge($status) {
    if(empty($status)) return '';
    $statusLower = strtolower($status);
    $badgeClass = 'bg-secondary';
    if (strpos($statusLower, 'menunggu') !== false || strpos($statusLower, 'unpaid') !== false) $badgeClass = 'bg-warning text-dark';
    elseif (strpos($statusLower, 'diproses') !== false || strpos($statusLower, 'paid') !== false) $badgeClass = 'bg-info';
    elseif (strpos($statusLower, 'dikirim') !== false) $badgeClass = 'bg-primary';
    elseif (strpos($statusLower, 'selesai') !== false) $badgeClass = 'bg-success';
    elseif (strpos($statusLower, 'dibatalkan') !== false) $badgeClass = 'bg-danger';
    return "<span class='badge {$badgeClass}'>" . htmlspecialchars($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Transaksi - Admin</title>
    
    <link rel="stylesheet" href="../assets/css/styles.css"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style> .table th, .table td { vertical-align: middle; } .filter-form .form-select, .filter-form .btn { font-size: 0.875rem; } </style>
</head>
<body>
<div class="container-scroller">
    <?php include('partials/sidebar.php') ?>
    
    <div class="page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <h3 class="mb-3">Daftar Semua Transaksi</h3>

                <div class="card card-body mb-4 filter-form shadow-sm">
                    <form method="GET" action="" class="row gx-3 gy-2 align-items-end">
                        <div class="col-md-3">
                            <label for="filter_sumber" class="form-label">Sumber Transaksi:</label>
                            <select name="filter_sumber" id="filter_sumber" class="form-select">
                                <option value="">Semua Sumber</option>
                                <option value="online" <?php echo ($filter_sumber == 'online') ? 'selected' : ''; ?>>Online</option>
                                <option value="offline" <?php echo ($filter_sumber == 'offline') ? 'selected' : ''; ?>>Kasir (Offline)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="limit" class="form-label">Tampilkan per Halaman:</label>
                            <select name="limit" id="limit" class="form-select">
                                <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                <option value="20" <?php if ($limit == 20) echo 'selected'; ?>>20</option>
                                <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Terapkan</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive shadow-sm">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Kode Invoice</th>
                                <th>Sumber</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Metode Bayar</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultTransaksi && $resultTransaksi->num_rows > 0) : ?>
                                <?php $nomor = $offset + 1; ?>
                                <?php while ($trx = $resultTransaksi->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?php echo $nomor++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($trx['kode_invoice']); ?></strong></td>
                                        <td class="text-center">
                                            <?php if($trx['sumber_transaksi'] == 'offline'): ?>
                                                <span class="badge bg-dark">KASIR</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">ONLINE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($trx['nama_pelanggan'] ?? $trx['nama_user_online'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($trx['tanggal_transaksi'])); ?></td>
                                        <td class="text-end">Rp <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($trx['metode_pembayaran']); ?></td>
                                        <td class="text-center">
                                            <?php echo getStatusBadge($trx['status_pembayaran']); ?>
                                            <?php echo getStatusBadge($trx['status_pesanan']); ?>
                                        </td>
                                <td class="text-center">
                                    <a href="admin_detail_pesanan.php?kode_pesanan=<?php echo htmlspecialchars($pesanan['kode_pesanan']); ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                        <i class="bi bi-eye-fill"></i> Detail
                                    </a>
                                </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted fst-italic">Tidak ada data transaksi yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1) : ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $queryParams = "filter_sumber=$filter_sumber&limit=$limit";
                        if ($page > 1) { echo "<li class='page-item'><a class='page-link' href='?page=".($page-1)."&$queryParams'>«</a></li>"; }
                        for ($i = 1; $i <= $total_pages; $i++) {
                            if ($i == $page) { echo "<li class='page-item active'><span class='page-link'>$i</span></li>"; } 
                            else { echo "<li class='page-item'><a class='page-link' href='?page=$i&$queryParams'>$i</a></li>"; }
                        }
                        if ($page < $total_pages) { echo "<li class='page-item'><a class='page-link' href='?page=".($page+1)."&$queryParams'>»</a></li>"; }
                        ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>