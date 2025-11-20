<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- KONFIGURASI PAGINATION ---
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// --- FILTER & PENCARIAN ---
$level_filter = $_GET['level'] ?? 'semua';
$search_query = $_GET['search'] ?? '';

$allowed_levels = ['semua', 'admin', 'seller', 'customer'];
if (!in_array($level_filter, $allowed_levels)) {
    $level_filter = 'semua';
}

// --- MEMBANGUN WHERE CLAUSE (Dipakai untuk Hitung Total & Ambil Data) ---
$where_clauses = [];
$params = [];
$types = '';

if ($level_filter !== 'semua') {
    $where_clauses[] = "level = ?";
    $params[] = $level_filter;
    $types .= 's';
}
if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $where_clauses[] = "(username LIKE ? OR nama LIKE ? OR email LIKE ?)";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= 'sss';
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

// --- QUERY 1: HITUNG TOTAL DATA (Untuk Pagination) ---
$sql_count = "SELECT COUNT(*) as total FROM tb_user" . $where_sql;
$stmt_count = $koneksi->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_results = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $limit);

// --- QUERY 2: AMBIL DATA DENGAN LIMIT ---
$sql = "SELECT id, username, nama, email, no_telepon, level, is_verified, is_banned, created_at FROM tb_user" . $where_sql . " ORDER BY created_at DESC LIMIT ?, ?";

// Tambahkan parameter limit dan offset ke binding
$params[] = $start;
$params[] = $limit;
$types .= 'ii';

$stmt = $koneksi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result_user = $stmt->get_result();

$current_page_title = 'Kelola Pengguna Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/kelolapengguna.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>

<div class="container-scroller">

    <?php include 'partials/sidebar_admin.php'; ?>

    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">

                <?php include 'partials/navbar_admin.php'; ?>

                <div class="page-header">
                    <div>
                        <h1 class="page-title">Kelola Pengguna</h1>
                        <p class="page-subtitle">Total <?= number_format($total_results) ?> pengguna terdaftar</p>
                    </div>
                    <a href="form_pengguna.php" class="btn btn-primary btn-icon-text">
                        <i class="mdi mdi-plus btn-icon-prepend"></i> Tambah Pengguna
                    </a>
                </div>

                <?php if (isset($_SESSION['feedback'])): ?>
                <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="mdi <?= $_SESSION['feedback']['tipe'] == 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' ?>"></i>
                        <span><?= $_SESSION['feedback']['pesan'] ?></span>
                    </div>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <!-- Filter & Search Bar -->
                        <div class="toolbar-container">
                            <ul class="nav nav-tabs" id="userTab">
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'semua' ? 'active' : '' ?>" href="?level=semua">Semua</a></li>
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'admin' ? 'active' : '' ?>" href="?level=admin">Admin</a></li>
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'seller' ? 'active' : '' ?>" href="?level=seller">Seller</a></li>
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'customer' ? 'active' : '' ?>" href="?level=customer">Customer</a></li>
                            </ul>
                            
                            <form action="" method="GET" class="search-form">
                                <input type="hidden" name="level" value="<?= htmlspecialchars($level_filter) ?>">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Cari nama, email..." value="<?= htmlspecialchars($search_query) ?>">
                                </div>
                            </form>
                        </div>

                        <!-- Tabel Data -->
                        <div class="table-responsive">
                            <table class="table custom-table">
                                <thead>
                                    <tr>
                                        <th>Pengguna</th>
                                        <th>Kontak</th>
                                        <th>Level</th>
                                        <th>Status</th>
                                        <th>Bergabung</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_user->num_rows > 0): ?>
                                        <?php while($user = $result_user->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info-cell">
                                                        <!-- Avatar Inisial Warna-warni -->
                                                        <?php 
                                                            $colors = ['#4F46E5', '#059669', '#D97706', '#DC2626', '#2563EB'];
                                                            $bg_color = $colors[rand(0, 4)];
                                                        ?>
                                                        <div class="avatar" style="background-color: <?= $bg_color ?>;">
                                                            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                                                        </div>
                                                        <div class="user-details">
                                                            <div class="full-name"><?= htmlspecialchars($user['nama']) ?></div>
                                                            <div class="username text-muted">@<?= htmlspecialchars($user['username']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="contact-info">
                                                        <div class="email"><?= htmlspecialchars($user['email']) ?></div>
                                                        <div class="phone"><?= htmlspecialchars($user['no_telepon'] ?? '-') ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-level <?= strtolower($user['level']) ?>">
                                                        <?= ucfirst(htmlspecialchars($user['level'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($user['is_banned']): ?>
                                                        <span class="badge status-banned">Banned</span>
                                                    <?php else: ?>
                                                        <span class="badge status-active">Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted font-sm">
                                                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="action-buttons">
                                                        <a href="form_pengguna.php?id=<?= $user['id'] ?>" class="btn-action btn-edit" title="Edit">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </a>
                                                        
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <?php if($user['is_banned']): ?>
                                                                <a href="actions/proses_kelola_pengguna.php?user_id=<?= $user['id'] ?>&action=unban" class="btn-action btn-restore" title="Aktifkan Kembali" onclick="return confirm('Aktifkan kembali pengguna ini?')">
                                                                    <i class="mdi mdi-restore"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="actions/proses_kelola_pengguna.php?user_id=<?= $user['id'] ?>&action=ban" class="btn-action btn-ban" title="Banned Pengguna" onclick="return confirm('Anda yakin ingin mem-banned pengguna ini?')">
                                                                    <i class="mdi mdi-block-helper"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="mdi mdi-account-search-outline"></i>
                                                    <p>Tidak ada pengguna ditemukan.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <span class="pagination-info">Menampilkan <?= $result_user->num_rows ?> dari <?= $total_results ?> data</span>
                            <ul class="pagination">
                                <!-- Tombol Previous -->
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&level=<?= $level_filter ?>&search=<?= $search_query ?>">
                                        <i class="mdi mdi-chevron-left"></i>
                                    </a>
                                </li>

                                <!-- Loop Nomor Halaman -->
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&level=<?= $level_filter ?>&search=<?= $search_query ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Tombol Next -->
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&level=<?= $level_filter ?>&search=<?= $search_query ?>">
                                        <i class="mdi mdi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>