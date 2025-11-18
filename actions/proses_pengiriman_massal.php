<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    die("Akses tidak diizinkan.");
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
if (!$toko_id) die("Toko tidak valid.");

// Validasi input
$pesanan_ids = $_POST['pesanan_ids'] ?? [];
if (empty($pesanan_ids) || !is_array($pesanan_ids)) {
    header("Location: ../app_seller/pesanan.php?error=tidak_ada_pesanan_dipilih");
    exit;
}

// Ubah semua ID menjadi integer untuk keamanan
$pesanan_ids = array_map('intval', $pesanan_ids);

// Buat placeholder untuk query IN (...)
$placeholders = implode(',', array_fill(0, count($pesanan_ids), '?'));

// Kueri untuk UPDATE status menjadi 'dikirim'
// PENTING: Ada 'AND toko_id = ?' untuk memastikan seller tidak mengubah pesanan toko lain
$sql = "UPDATE tb_detail_transaksi 
        SET status_pesanan_item = 'dikirim' 
        WHERE id IN ($placeholders) AND toko_id = ?";

$stmt_update = $koneksi->prepare($sql);

// Bind semua parameter
$types = str_repeat('i', count($pesanan_ids)) . 'i';
$params = array_merge($pesanan_ids, [$toko_id]);
$stmt_update->bind_param($types, ...$params);

if ($stmt_update->execute()) {
    // Di sini nantinya bisa ditambahkan logika untuk generate PDF label pengiriman massal
    header("Location: ../app_seller/pesanan.php?status=mass_shipping_sukses");
} else {
    header("Location: ../app_seller/pesanan.php?status=mass_shipping_gagal");
}

$stmt_update->close();
$koneksi->close();
?>