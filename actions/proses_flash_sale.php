<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['level'] !== 'seller') {
    die("Akses ditolak.");
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

// Ambil data dari form
$event_id = (int)$_POST['event_id'];
$barang_id = (int)$_POST['barang_id'];
$harga_fs = (float)$_POST['harga_flash_sale'];
$stok_fs = (int)$_POST['stok_flash_sale'];

// Validasi
// 1. Ambil harga asli & stok asli
$q_produk = $koneksi->prepare("SELECT harga, stok FROM tb_barang WHERE id = ? AND toko_id = ?");
$q_produk->bind_param("ii", $barang_id, $toko_id);
$q_produk->execute();
$produk = $q_produk->get_result()->fetch_assoc();

if (!$produk) die("Produk tidak valid atau bukan milik Anda.");

// 2. Harga flash sale harus lebih rendah dari harga asli
if ($harga_fs >= $produk['harga']) {
    header("Location: ../app_seller/flash_sale.php?error=harga_tidak_valid");
    exit;
}
// 3. Stok flash sale tidak boleh melebihi stok utama
if ($stok_fs > $produk['stok']) {
    header("Location: ../app_seller/flash_sale.php?error=stok_tidak_cukup");
    exit;
}

// Simpan pendaftaran produk ke database
$stmt_insert = $koneksi->prepare("INSERT INTO tb_flash_sale_produk (event_id, toko_id, barang_id, harga_flash_sale, stok_flash_sale, status_moderasi) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt_insert->bind_param("iiidi", $event_id, $toko_id, $barang_id, $harga_fs, $stok_fs);

if ($stmt_insert->execute()) {
    header("Location: ../app_seller/flash_sale.php?status=sukses");
} else {
    header("Location: ../app_seller/flash_sale.php?status=gagal");
}
?>