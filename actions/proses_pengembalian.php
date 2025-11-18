<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan dasar
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php"); exit;
}
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    die("Permintaan tidak valid.");
}

$user_id = $_SESSION['user_id'];
$detail_transaksi_id = (int)$_GET['id'];
$action = $_GET['action'];

// Ambil toko_id dari user yang login
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
if (!$toko_id) die("Toko tidak valid.");

// Tentukan status baru berdasarkan aksi
$new_status = '';
if ($action === 'setujui') {
    // Di sini bisa ditambahkan logika berbeda jika pengajuan adalah 'dibatalkan' vs 'pengajuan_pengembalian'
    // Untuk sekarang, kita setujui keduanya menjadi status akhir.
    $new_status = 'pengembalian_disetujui'; 
} elseif ($action === 'tolak') {
    $new_status = 'pengembalian_ditolak';
} else {
    die("Aksi tidak dikenal.");
}

// UPDATE status, tapi validasi dulu bahwa item ini benar milik toko yg login
$stmt_update = $koneksi->prepare("UPDATE tb_detail_transaksi SET status_pesanan_item = ? WHERE id = ? AND toko_id = ?");
$stmt_update->bind_param("sii", $new_status, $detail_transaksi_id, $toko_id);

if ($stmt_update->execute()) {
    // Jika 'pengembalian_disetujui', idealnya stok produk dikembalikan.
    if($new_status == 'pengembalian_disetujui') {
        // Logika untuk mengembalikan stok bisa ditambahkan di sini.
    }
    header("Location: ../app_seller/pengembalian.php?status=sukses");
} else {
    header("Location: ../app_seller/pengembalian.php?status=gagal");
}

$stmt_update->close();
$koneksi->close();
?>