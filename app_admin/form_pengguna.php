<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

$is_edit_mode = false;
$user_data = [
    'id' => '', 'username' => '', 'nama' => '', 'email' => '', 
    'no_telepon' => '', 'alamat' => '', 'level' => 'customer'
];

if (isset($_GET['id'])) {
    $is_edit_mode = true;
    $user_id = (int)$_GET['id'];
    
    $stmt = $koneksi->prepare("SELECT * FROM tb_user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    } else {
        die("Pengguna tidak ditemukan.");
    }
    $stmt->close();
}
 $current_page_title = 'kelola pengguna admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $is_edit_mode ? 'Edit' : 'Tambah' ?> Pengguna - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/css/formpengguna.css">
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
                <div class="page-header">
                    <h1 class="page-title"><?= $is_edit_mode ? 'Edit' : 'Tambah' ?> Pengguna</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="actions/proses_pengguna.php" method="POST">
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="user_id" value="<?= $user_data['id'] ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="nama">Nama Lengkap</label>
                                <input type="text" id="nama" name="nama" class="form-control" value="<?= htmlspecialchars($user_data['nama']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user_data['username']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control" <?= $is_edit_mode ? '' : 'required' ?>>
                                <small class="text-secondary"><?= $is_edit_mode ? 'Kosongkan jika tidak ingin mengubah password.' : 'Wajib diisi untuk pengguna baru.' ?></small>
                            </div>

                            <div class="form-group">
                                <label for="level">Level Pengguna</label>
                                <select id="level" name="level" class="form-select">
                                    <option value="customer" <?= $user_data['level'] == 'customer' ? 'selected' : '' ?>>Customer</option>
                                    <option value="seller" <?= $user_data['level'] == 'seller' ? 'selected' : '' ?>>Seller</option>
                                    <option value="admin" <?= $user_data['level'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" name="simpan_pengguna" class="btn btn-primary">Simpan</button>
                                <a href="kelola_pengguna.php" class="btn btn-outline">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
