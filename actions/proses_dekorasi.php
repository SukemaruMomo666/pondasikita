<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php"); exit;
}
$user_id = $_SESSION['user_id'];
// ... (kode untuk mengambil $toko_id) ...

// Aksi Tambah Komponen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $tipe = $_POST['tipe_komponen'];
    $konten_json = '';

    if ($tipe === 'BANNER') {
        // Logika upload gambar banner
        $gambar = $_FILES['konten_gambar']; // Proses upload file di sini
        $nama_file_baru = 'banner_toko_' . $toko_id . '_' . time() . '.jpg';
        move_uploaded_file($gambar['tmp_name'], '../assets/uploads/decorations/' . $nama_file_baru);
        $konten_json = json_encode(['gambar' => $nama_file_baru]);
    } elseif ($tipe === 'PRODUK_UNGGULAN') {
        $judul = $_POST['konten_judul'];
        $produk_ids = $_POST['konten_produk_ids']; // Ini akan menjadi array
        $konten_json = json_encode(['judul' => $judul, 'produk_ids' => $produk_ids]);
    }

    $stmt = $koneksi->prepare("INSERT INTO tb_toko_dekorasi (toko_id, tipe_komponen, konten_json) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $toko_id, $tipe, $konten_json);
    $stmt->execute();
}

// Aksi Hapus Komponen
if (isset($_GET['hapus'])) {
    $komponen_id = (int)$_GET['hapus'];
    // Penting: Hapus hanya jika komponen itu milik toko yang login
    $stmt = $koneksi->prepare("DELETE FROM tb_toko_dekorasi WHERE id = ? AND toko_id = ?");
    $stmt->bind_param("ii", $komponen_id, $toko_id);
    $stmt->execute();
}

header("Location: ../app_seller/pengaturan/dekorasi_toko.php?status=sukses");
?>