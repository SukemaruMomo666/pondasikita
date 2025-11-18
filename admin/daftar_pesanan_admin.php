<?php
session_start();

// PENTING: Tambahkan pengecekan apakah pengguna adalah admin
// Asumsikan Anda memiliki 'role' dalam session user.
// Jika tidak ada, Anda perlu mekanisme lain untuk proteksi halaman admin.
// Perbaikan: Role harusnya 'admin', bukan 'customer' untuk halaman admin
if (!isset($_SESSION['user']['id']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    $_SESSION['pesan'] = ['jenis' => 'danger', 'isi' => 'Akses tidak diizinkan.'];
    header("Location: ../signin.php"); // Ganti dengan halaman login yang sesuai
    exit;
}

include '../config/koneksi.php'; // Sesuaikan path jika perlu

// --- Variabel untuk Filter dan Sorting ---
$filter_status_pesanan = isset($_GET['filter_status_pesanan']) ? mysqli_real_escape_string($koneksi, $_GET['filter_status_pesanan']) : '';
$filter_status_pembayaran = isset($_GET['filter_status_pembayaran']) ? mysqli_real_escape_string($koneksi, $_GET['filter_status_pembayaran']) : '';
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($koneksi, $_GET['sort_by']) : 'tanggal_transaksi'; // Default sort
$sort_order = isset($_GET['sort_order']) ? mysqli_real_escape_string($koneksi, $_GET['sort_order']) : 'DESC'; // Default order
$filter_sumber = isset($_GET['filter_sumber']) ? mysqli_real_escape_string($koneksi, $_GET['filter_sumber']) : '';

// --- Paginasi ---
$limit = 15; // Jumlah pesanan per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Membangun Query SQL dengan Filter dan Sorting ---
$sql_where_clauses = [];
if (!empty($filter_sumber)) {
    $sql_where_clauses[] = "p.sumber_transaksi = '$filter_sumber'";
}
if (!empty($filter_status_pesanan)) {
     $sql_where_clauses[] = "p.status_pesanan = '$filter_status_pesanan'";
}
if (!empty($filter_status_pembayaran)) {
    $sql_where_clauses[] = "p.status_pembayaran = '$filter_status_pembayaran'";
}

$where_sql = "";
if (count($sql_where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $sql_where_clauses);
}

// Validasi sort_by agar aman
$allowed_sort_columns = ['kode_invoice', 'tanggal_transaksi', 'total_harga', 'nama_pemesan']; // Changed 'total_pesanan_final' to 'grand_total' as it's a direct column name now
if (!in_array($sort_by, $allowed_sort_columns)) {
     $sort_by = 'tanggal_transaksi'; // Default jika tidak valid
}
// Validasi sort_order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC'; // Default jika tidak valid
}

$order_sql = "ORDER BY $sort_by $sort_order";

// Query untuk menghitung total pesanan (untuk paginasi)
$queryCount = "SELECT COUNT(p.id) AS total 
               FROM tb_transaksi p 
               LEFT JOIN tb_user u ON p.user_id = u.id 
               $where_sql";
$resultCount = mysqli_query($koneksi, $queryCount);
$rowCount = mysqli_fetch_assoc($resultCount);
$total_pesanan = $rowCount['total'];
$total_pages = ceil($total_pesanan / $limit);


// Query utama untuk mengambil data pesanan
// Ditambahkan p.total_diskon dan p.voucher_digunakan
// Query untuk menghitung total pesanan (untuk paginasi)


// Query utama untuk mengambil data pesanan
$queryPesanan = "SELECT p.*, u.username AS nama_pemesan 
                 FROM tb_transaksi p
                 LEFT JOIN tb_user u ON p.user_id = u.id 
                 $where_sql 
                 $order_sql 
                 LIMIT $limit OFFSET $offset";


$resultPesanan = mysqli_query($koneksi, $queryPesanan);


// Helper function untuk status badge
function getStatusBadge($status) {
    $statusLower = strtolower($status ?? '');
    $badgeClass = 'bg-secondary'; // Default
    if (strpos($statusLower, 'belum dibayar') !== false || strpos($statusLower, 'menunggu pembayaran') !== false || strpos($statusLower, 'unpaid') !== false) {
        $badgeClass = 'bg-warning text-dark';
    } elseif (strpos($statusLower, 'sudah dibayar') !== false || strpos($statusLower, 'diproses') !== false || strpos($statusLower, 'menunggu konfirmasi') !== false || strpos($statusLower, 'pembayaran diverifikasi') !== false || strpos($statusLower, 'paid') !== false || strpos($statusLower, 'siap diambil') !== false) {
        $badgeClass = 'bg-info';
    } elseif (strpos($statusLower, 'dikirim') !== false) {
        $badgeClass = 'bg-primary';
    } elseif (strpos($statusLower, 'selesai') !== false || strpos($statusLower, 'diterima') !== false) {
        $badgeClass = 'bg-success';
    } elseif (strpos($statusLower, 'dibatalkan') !== false || strpos($statusLower, 'pembayaran ditolak') !== false || strpos($statusLower, 'gagal') !== false) {
        $badgeClass = 'bg-danger';
    }
    return "<span class='badge {$badgeClass}'>" . htmlspecialchars($status) . "</span>";
}

// Opsi untuk filter dropdown
// Ambil status unik dari database untuk filter yang lebih dinamis (opsional, bisa hardcode juga)
$opsiStatusPesananDb = [];
$opsiStatusPembayaranDb = [];
$resStatusP = mysqli_query($koneksi, "SELECT DISTINCT status_pesanan FROM tb_transaksi WHERE status_pesanan IS NOT NULL AND status_pesanan != '' ORDER BY status_pesanan ASC");
while($row = mysqli_fetch_assoc($resStatusP)) $opsiStatusPesananDb[] = $row['status_pesanan'];
$resStatusByr = mysqli_query($koneksi, "SELECT DISTINCT status_pembayaran FROM tb_transaksi WHERE status_pembayaran IS NOT NULL AND status_pembayaran != '' ORDER BY status_pembayaran ASC");
while($row = mysqli_fetch_assoc($resStatusByr)) $opsiStatusPembayaranDb[] = $row['status_pembayaran'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pesanan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .table th { vertical-align: middle; }
        .table td { vertical-align: middle; }
        .filter-form .form-select, .filter-form .btn { font-size: 0.875rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="kelola_data/data_barang/kelola_data_barang.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAdmin" aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                   <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="daftar_pesanan_admin.php">Daftar Pesanan</a></li>
                    </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Daftar Semua Pesanan Masuk</h3>
            </div>

        <?php
        // Tampilkan pesan dari session jika ada
        if (isset($_SESSION['pesan'])) {
            echo "<div class='alert alert-" . htmlspecialchars($_SESSION['pesan']['jenis']) . " alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($_SESSION['pesan']['isi']);
            echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
            echo "</div>";
            unset($_SESSION['pesan']);
        }
        ?>

        <div class="card card-body mb-4 filter-form shadow-sm">
            <form method="GET" action="" class="row gx-2 gy-2 align-items-end">
                <div class="col-md-3">
                    <label for="filter_status_pesanan" class="form-label">Status Pesanan:</label>
                    <select name="filter_status_pesanan" id="filter_status_pesanan" class="form-select">
                        <option value="">Semua Status</option>
                        <?php foreach ($opsiStatusPesananDb as $status) : ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filter_status_pesanan == $status) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
    <label for="filter_sumber" class="form-label">Sumber Transaksi:</label>
    <select name="filter_sumber" id="filter_sumber" class="form-select">
        <option value="">Semua Sumber</option>
        <option value="online" <?php echo ($filter_sumber == 'online') ? 'selected' : ''; ?>>Online</option>
        <option value="offline" <?php echo ($filter_sumber == 'offline') ? 'selected' : ''; ?>>Kasir (Offline)</option>
    </select>
</div>
                <div class="col-md-3">
                    <label for="filter_status_pembayaran" class="form-label">Status Pembayaran:</label>
                    <select name="filter_status_pembayaran" id="filter_status_pembayaran" class="form-select">
                        <option value="">Semua Status</option>
                           <?php foreach ($opsiStatusPembayaranDb as $status) : ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filter_status_pembayaran == $status) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Urutkan:</label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="tanggal_pesanan" <?php echo ($sort_by == 'tanggal_pesanan') ? 'selected' : ''; ?>>Tanggal</option>
                        <option value="kode_invoice" <?php echo ($sort_by == 'kode_invoice') ? 'selected' : ''; ?>>Kode Pesanan</option>
                        <option value="grand_total" <?php echo ($sort_by == 'grand_total') ? 'selected' : ''; ?>>Total Bayar</option>
                        <option value="nama_pemesan" <?php echo ($sort_by == 'nama_pemesan') ? 'selected' : ''; ?>>Nama Pemesan</option>
                    </select>
                </div>
                <div class="col-md-2">
                       <label for="sort_order" class="form-label">Order:</label>
                    <select name="sort_order" id="sort_order" class="form-select">
                        <option value="DESC" <?php echo (strtoupper($sort_order) == 'DESC') ? 'selected' : ''; ?>>Menurun (DESC)</option>
                        <option value="ASC" <?php echo (strtoupper($sort_order) == 'ASC') ? 'selected' : ''; ?>>Menaik (ASC)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
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
    <th class="text-end">Total Bayar</th>
    <th class="text-center">Metode Pembayaran</th>
    <th class="text-center">Status Pesanan</th>
    <th class="text-center">Status Pembayaran</th>
    <th class="text-center">Aksi</th>
</tr>
                </thead>
                <tbody>
                    <?php if ($resultPesanan && mysqli_num_rows($resultPesanan) > 0) : ?>
                        <?php $nomor = $offset + 1; ?>
                        <?php while ($pesanan = mysqli_fetch_assoc($resultPesanan)) : ?>
<tr>
    <td><?php echo $nomor++; ?></td>
    <td><strong><?php echo htmlspecialchars($pesanan['kode_invoice']); ?></strong></td>
    <td class="text-center">
        <?php if($pesanan['sumber_transaksi'] == 'offline'): ?>
            <span class="badge bg-dark">KASIR</span>
        <?php else: ?>
            <span class="badge bg-info">ONLINE</span>
        <?php endif; ?>
    </td>
    <td><?php echo htmlspecialchars($pesanan['nama_pemesan'] ?? $pesanan['nama_pelanggan'] ?? 'N/A'); ?></td>
    <td><?php echo date('d M Y, H:i', strtotime($pesanan['tanggal_transaksi'])); ?></td>
    <td class="text-end">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
    <td class="text-center"><?php echo htmlspecialchars($pesanan['metode_pembayaran']); ?></td>
    <td class="text-center"><?php echo getStatusBadge($pesanan['status_pesanan']); ?></td>
    <td class="text-center"><?php echo getStatusBadge($pesanan['status_pembayaran']); ?></td>
    <td class="text-center">
        <a href="admin_detail_pesanan.php?kode_invoice=<?php echo htmlspecialchars($pesanan['kode_invoice']); ?>" class="btn btn-sm btn-info" title="Lihat Detail">
            <i class="bi bi-eye-fill"></i>
        </a>
    </td>
</tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted fst-italic">Tidak ada data pesanan yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1) : ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php
                // Link ke halaman sebelumnya
                if ($page > 1) {
                   $queryParams = "filter_sumber=$filter_sumber&filter_status_pesanan=$filter_status_pesanan&filter_status_pembayaran=$filter_status_pembayaran&sort_by=$sort_by&sort_order=$sort_order";
// ...
echo "<li class='page-item'><a class='page-link' href='?page=" . ($page - 1) . "&$queryParams'>« Sebelumnya</a></li>";
                } else {
                    echo "<li class='page-item disabled'><span class='page-link'>« Sebelumnya</span></li>";
                }

                // Tampilkan link halaman
                // Logic untuk menampilkan beberapa nomor halaman (misal, 2 sebelum dan 2 sesudah halaman aktif)
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo "<li class='page-item'><a class='page-link' href='?page=1&filter_status_pesanan=$filter_status_pesanan&filter_status_pembayaran=$filter_status_pembayaran&sort_by=$sort_by&sort_order=$sort_order'>1</a></li>";
                    if ($start_page > 2) {
                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo "<li class='page-item active' aria-current='page'><span class='page-link'>$i</span></li>";
                    } else {
                        echo "<li class='page-item'><a class='page-link' href='?page=$i&filter_status_pesanan=$filter_status_pesanan&filter_status_pembayaran=$filter_status_pembayaran&sort_by=$sort_by&sort_order=$sort_order'>$i</a></li>";
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                    echo "<li class='page-item'><a class='page-link' href='?page=$total_pages&filter_status_pesanan=$filter_status_pesanan&filter_status_pembayaran=$filter_status_pembayaran&sort_by=$sort_by&sort_order=$sort_order'>$total_pages</a></li>";
                }


                // Link ke halaman berikutnya
                if ($page < $total_pages) {
                    echo "<li class='page-item'><a class='page-link' href='?page=" . ($page + 1) . "&filter_status_pesanan=$filter_status_pesanan&filter_status_pembayaran=$filter_status_pembayaran&sort_by=$sort_by&sort_order=$sort_order'>Berikutnya »</a></li>";
                } else {
                    echo "<li class='page-item disabled'><span class='page-link'>Berikutnya »</span></li>";
                }
                ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div>

    <footer class="py-4 bg-light mt-5">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">Hak Cipta © Toko Anda <?php echo date("Y"); ?></div>
            </div>
        </div>
    </footer>

</body>
</html>