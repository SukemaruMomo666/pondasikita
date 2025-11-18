<?php
session_start();
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// Validasi input
if (!isset($_SESSION['user']['id']) || empty($_POST['barang_id']) || empty($_POST['alasan']) || empty($_POST['deskripsi'])) {
    $_SESSION['pesan_error_form'] = "Semua field wajib diisi.";
    header("Location: " . $_SERVER['HTTP_REFERER']); // Kembali ke halaman sebelumnya
    exit;
}

$barang_id = (int)$_POST['barang_id'];
$user_id = (int)$_POST['user_id'];
$alasan = trim($_POST['alasan']);
$deskripsi = trim($_POST['deskripsi']);

// Simpan ke database
$stmt = $koneksi->prepare("INSERT INTO tb_laporan_produk (barang_id, user_id, alasan, deskripsi) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $barang_id, $user_id, $alasan, $deskripsi);

if ($stmt->execute()) {
    $_SESSION['pesan_sukses'] = "Laporan Anda telah berhasil dikirim. Terima kasih atas masukan Anda.";
} else {
    $_SESSION['pesan_error_form'] = "Terjadi kesalahan. Gagal mengirim laporan.";
}

$stmt->close();
$koneksi->close();

// Kembali ke halaman laporan
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>