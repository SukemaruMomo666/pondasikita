<?php
session_start();
require_once '../../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// --- PROSES TAMBAH KATEGORI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    // Set parent_id ke NULL jika form mengirim string kosong
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (empty($nama_kategori)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Nama kategori tidak boleh kosong.'];
    } else {
        $sql = "INSERT INTO tb_kategori (nama_kategori, parent_id) VALUES (?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("si", $nama_kategori, $parent_id);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Kategori berhasil ditambahkan.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menambahkan kategori: ' . $stmt->error];
        }
        $stmt->close();
    }
}

// --- PROSES EDIT KATEGORI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kategori'])) {
    $kategori_id = (int)$_POST['kategori_id'];
    $nama_kategori = trim($_POST['nama_kategori']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (empty($nama_kategori)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Nama kategori tidak boleh kosong.'];
    } elseif ($kategori_id === $parent_id) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Kategori tidak bisa menjadi induk dari dirinya sendiri.'];
    } else {
        $sql = "UPDATE tb_kategori SET nama_kategori = ?, parent_id = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sii", $nama_kategori, $parent_id, $kategori_id);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Kategori berhasil diperbarui.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal memperbarui kategori: ' . $stmt->error];
        }
        $stmt->close();
    }
}

// --- PROSES HAPUS KATEGORI ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'hapus') {
    if (!isset($_GET['id'])) {
        die("ID Kategori tidak valid.");
    }
    $kategori_id = (int)$_GET['id'];

    // Validasi sebelum menghapus: Cek sub-kategori
    $stmt_check_child = $koneksi->prepare("SELECT id FROM tb_kategori WHERE parent_id = ?");
    $stmt_check_child->bind_param("i", $kategori_id);
    $stmt_check_child->execute();
    if ($stmt_check_child->get_result()->num_rows > 0) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menghapus. Kategori ini masih memiliki sub-kategori.'];
        header("Location: ../pengaturan_kategori.php");
        exit;
    }
    $stmt_check_child->close();

    // Validasi sebelum menghapus: Cek produk yang menggunakan kategori ini
    $stmt_check_product = $koneksi->prepare("SELECT id FROM tb_barang WHERE kategori_id = ?");
    $stmt_check_product->bind_param("i", $kategori_id);
    $stmt_check_product->execute();
    if ($stmt_check_product->get_result()->num_rows > 0) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menghapus. Kategori ini masih digunakan oleh produk.'];
        header("Location: ../pengaturan_kategori.php");
        exit;
    }
    $stmt_check_product->close();

    // Jika aman, lanjutkan penghapusan
    $sql = "DELETE FROM tb_kategori WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $kategori_id);

    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Kategori berhasil dihapus.'];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menghapus kategori: ' . $stmt->error];
    }
    $stmt->close();
}

// Kembalikan admin ke halaman pengaturan kategori setelah semua aksi selesai
header("Location: ../pengaturan_kategori.php");
exit;
?>
