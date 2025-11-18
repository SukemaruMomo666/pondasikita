<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK FILTER & PENCARIAN ---
$level_filter = $_GET['level'] ?? 'semua';
$search_query = $_GET['search'] ?? '';

$allowed_levels = ['semua', 'admin', 'seller', 'customer'];
if (!in_array($level_filter, $allowed_levels)) {
    $level_filter = 'semua';
}

// --- QUERY PENGAMBILAN DATA PENGGUNA ---
$sql = "SELECT id, username, nama, email, no_telepon, level, is_verified, is_banned, created_at FROM tb_user";
$params = [];
$types = '';
$where_clauses = [];

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

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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
    <link rel="stylesheet" href="../assets/css/kelolapengguna.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    
</head>
<body>

<div class="container-scroller">

    <?php include 'partials/sidebar_admin.php'; ?>

    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">

                <!-- Navbar di dalam content-wrapper, setelah sidebar -->
                <?php include 'partials/navbar_admin.php'; ?>

                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1 class="page-title">Kelola Pengguna</h1>
                    <a href="form_pengguna.php" class="btn btn-primary"><i class="mdi mdi-plus"></i> Tambah Pengguna</a>
                </div>

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
                            <ul class="nav nav-tabs" id="userTab">
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'semua' ? 'active' : '' ?>" href="?level=semua">Semua</a></li>
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'admin' ? 'active' : '' ?>" href="?level=admin">Admin</a></li>
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'seller' ? 'active' : '' ?>" href="?level=seller">Seller</a></li>
                                <li class="nav-item"><a class="nav-link <?= $level_filter == 'customer' ? 'active' : '' ?>" href="?level=customer">Customer</a></li>
                            </ul>
                            <form action="" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="level" value="<?= htmlspecialchars($level_filter) ?>">
                                <input type="text" name="search" class="form-control" placeholder="Cari pengguna..." value="<?= htmlspecialchars($search_query) ?>">
                                <button type="submit" class="btn btn-primary">Cari</button>
                            </form>
                        </div>

                        <div class="table-wrapper mt-4">
                            <table class="table">
                                <thead>
                                    <tr><th>Pengguna</th><th>Kontak</th><th>Level</th><th>Status</th><th>Bergabung</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_user->num_rows > 0): ?>
                                        <?php while($user = $result_user->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info-cell">
                                                        <div class="avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
                                                        <div>
                                                            <div class="username"><?= htmlspecialchars($user['username']) ?></div>
                                                            <div class="full-name"><?= htmlspecialchars($user['nama']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($user['email']) ?><br><small class="text-secondary"><?= htmlspecialchars($user['no_telepon'] ?? '-') ?></small></td>
                                                <td><?= htmlspecialchars($user['level']) ?></td>
                                                <td>
                                                    <?php if($user['is_banned']): ?>
                                                        <span class="status-badge status-suspended">Dibanned</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-active">Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="form_pengguna.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-success">Edit</a>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): // Admin tidak bisa mem-banned diri sendiri ?>
                                                            <?php if($user['is_banned']): ?>
                                                                <a href="actions/proses_kelola_pengguna.php?user_id=<?= $user['id'] ?>&action=unban" class="btn btn-sm btn-success" onclick="return confirm('Aktifkan kembali pengguna ini?')">Aktifkan</a>
                                                            <?php else: ?>
                                                                 <a href="actions/proses_kelola_pengguna.php?user_id=<?= $user['id'] ?>&action=ban" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin mem-banned pengguna ini?')">Banned</a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6"><div class="text-center p-5"><p class="text-secondary">Tidak ada pengguna ditemukan.</p></div></td></tr>
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
</body>
</html>
