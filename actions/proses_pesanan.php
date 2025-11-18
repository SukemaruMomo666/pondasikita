<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak diizinkan.");
}

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_POST['detail_transaksi_id']) || !isset($_POST['status_baru'])) {
    die("Data tidak lengkap.");
}

$user_id = $_SESSION['user_id'];
$detail_transaksi_id = (int)$_POST['detail_transaksi_id'];
$status_baru = $_POST['status_baru'];
$allowed_statuses = ['diproses', 'siap_kirim', 'dikirim', 'sampai_tujuan', 'dibatalkan'];

if (!in_array($status_baru, $allowed_statuses)) {
    die("Status tidak valid.");
}

// --- VERIFIKASI KEPEMILIKAN PESANAN ---
// Ambil toko_id dari user yang sedang login
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
$stmt_toko->close();

if (!$toko_id) {
    die("Toko tidak ditemukan.");
}

// Cek apakah item pesanan yang mau diupdate BENAR-BENAR milik toko ini
$stmt_verify = $koneksi->prepare("SELECT COUNT(*) as count FROM tb_detail_transaksi WHERE id = ? AND toko_id = ?");
$stmt_verify->bind_param("ii", $detail_transaksi_id, $toko_id);
$stmt_verify->execute();
$is_owner = $stmt_verify->get_result()->fetch_assoc()['count'] > 0;
$stmt_verify->close();

if (!$is_owner) {
    die("AKSES DITOLAK! Anda tidak berhak mengubah pesanan ini.");
}

// --- PROSES UPDATE STATUS ---
$stmt_update = $koneksi->prepare("UPDATE tb_detail_transaksi SET status_pesanan_item = ? WHERE id = ?");
$stmt_update->bind_param("si", $status_baru, $detail_transaksi_id);

if ($stmt_update->execute()) {
    // Berhasil
    header("Location: ../app_seller/pesanan.php?status=update_sukses");
} else {
    // Gagal
    header("Location: ../app_seller/pesanan.php?status=update_gagal");
}

$stmt_update->close();
$koneksi->close();
?>