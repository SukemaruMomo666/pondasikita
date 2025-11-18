<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- AMBIL SEMUA DATA KATEGORI ---
$sql = "SELECT id, nama_kategori, parent_id FROM tb_kategori ORDER BY nama_kategori ASC";
$result = $koneksi->query($sql);
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// --- FUNGSI UNTUK MEMBUAT STRUKTUR HIERARKI ---
function buildCategoryTree(array $elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildCategoryTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}

// --- FUNGSI UNTUK MENAMPILKAN KATEGORI SECARA REKURSIF ---
function displayCategories(array $categories) {
    echo '<ul class="category-list">';
    foreach ($categories as $category) {
        echo '<li class="category-item">';
        echo '<span class="category-name">' . htmlspecialchars($category['nama_kategori']) . '</span>';
        echo '<div class="category-actions">';
        echo '<a href="edit_kategori.php" class="btn btn-outline btn-sm">Edit</a>';
        echo '<a href="#" class="btn btn-danger btn-sm">Hapus</a>';
        echo '</div>';
        echo '</li>';

        if (!empty($category['children'])) {
            echo '<ul class="child-categories">';
            displayCategories($category['children']);
            echo '</ul>';
        }
    }
    echo '</ul>';
}

$categoryTree = buildCategoryTree($categories);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Kategori - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'pengaturan_kategori.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Pengaturan Kategori</h1>
                </div>

                <div class="row">
                    <!-- Kolom Kiri: Daftar Kategori -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Daftar Kategori</h4>
                                <div class="mt-4">
                                    <?php 
                                    if (empty($categoryTree)) {
                                        echo '<div class="text-center p-5"><p class="text-secondary">Belum ada kategori.</p></div>';
                                    } else {
                                        displayCategories($categoryTree);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Kolom Kanan: Form Tambah Kategori -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Tambah Kategori Baru</h4>
                                <form action="actions/proses_kategori.php" method="POST">
                                    <div class="form-group mt-4">
                                        <label for="nama_kategori">Nama Kategori</label>
                                        <input type="text" name="nama_kategori" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="parent_id">Kategori Induk (Opsional)</label>
                                        <select name="parent_id" class="form-select">
                                            <option value="">-- Tidak Ada --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="tambah_kategori" class="btn btn-primary w-100">Tambah</button>
                                </form>
                            </div>
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
