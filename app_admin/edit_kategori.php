<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- VALIDASI & AMBIL DATA KATEGORI YANG AKAN DIEDIT ---
if (!isset($_GET['id'])) {
    die("ID Kategori tidak ditemukan.");
}

$id_to_edit = (int)$_GET['id'];

// Ambil data spesifik kategori ini
$stmt_current = $koneksi->prepare("SELECT id, nama_kategori, parent_id FROM tb_kategori WHERE id = ?");
$stmt_current->bind_param("i", $id_to_edit);
$stmt_current->execute();
$result_current = $stmt_current->get_result();
if ($result_current->num_rows === 0) {
    die("Kategori dengan ID tersebut tidak ditemukan.");
}
$current_category = $result_current->fetch_assoc();
$stmt_current->close();

// Ambil semua kategori untuk dropdown parent
$all_categories_result = $koneksi->query("SELECT id, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'pengaturan_kategori.php'; // Anggap ini bagian dari pengaturan kategori
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Edit Kategori</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Mengubah Detail Kategori</h4>
                        <form action="actions/proses_kategori.php" method="POST">
                            <!-- Input tersembunyi untuk ID dan aksi -->
                            <input type="hidden" name="kategori_id" value="<?= $current_category['id'] ?>">
                            
                            <div class="form-group mt-4">
                                <label for="nama_kategori">Nama Kategori</label>
                                <input type="text" name="nama_kategori" class="form-control" value="<?= htmlspecialchars($current_category['nama_kategori']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="parent_id">Kategori Induk (Opsional)</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- Tidak Ada --</option>
                                    <?php while($cat = $all_categories_result->fetch_assoc()): ?>
                                        <?php
                                        // Jangan tampilkan kategori ini sendiri sebagai pilihan parent
                                        if ($cat['id'] == $current_category['id']) continue; 
                                        ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $current_category['parent_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nama_kategori']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="edit_kategori" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="pengaturan_kategori.php" class="btn btn-outline">Batal</a>
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
