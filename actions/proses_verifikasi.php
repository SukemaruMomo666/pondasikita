<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') die("Akses tidak diizinkan.");
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['toko_id']) || !isset($_GET['action'])) {
    die("Data tidak lengkap.");
}

$toko_id = (int)$_GET['toko_id'];
$action = $_GET['action'];

// Tentukan status baru berdasarkan aksi
$status_baru = '';
if ($action === 'setujui') {
    $status_baru = 'active';
} elseif ($action === 'tolak') {
    $status_baru = 'rejected'; // Kita bisa gunakan status 'rejected' atau hapus recordnya
} else {
    die("Aksi tidak valid.");
}

// --- PROSES UPDATE STATUS TOKO ---
$stmt = $koneksi->prepare("UPDATE tb_toko SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status_baru, $toko_id);

if ($stmt->execute()) {
    // Berhasil
    // Di sini Anda bisa menambahkan logika untuk mengirim email notifikasi ke penjual
    header("Location: ../app_admin/verifikasi_toko.php?status=sukses");
} else {
    // Gagal
    header("Location: ../app_admin/verifikasi_toko.php?status=gagal");
}

$stmt->close();
$koneksi->close();
?>