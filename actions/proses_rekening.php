<?php
session_start();
require_once '../config/koneksi.php';

// --- Keamanan & Validasi Awal ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../auth/login.php"); exit;
}
if (empty($_POST['rekening_bank']) || empty($_POST['nomor_rekening']) || empty($_POST['atas_nama_rekening']) || empty($_POST['password_sekarang'])) {
    header("Location: ../app_seller/pengaturan/rekening_bank.php?error=kolom_kosong");
    exit;
}

$user_id = $_SESSION['user_id'];
$password_sekarang = $_POST['password_sekarang'];

// --- Verifikasi Password Pengguna ---
$stmt_user = $koneksi->prepare("SELECT password FROM tb_user WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$user || !password_verify($password_sekarang, $user['password'])) {
    header("Location: ../app_seller/pengaturan/rekening_bank.php?error=password_salah");
    exit;
}

// --- Jika Password Benar, Lanjutkan Update ---
$toko_id_stmt = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$toko_id_stmt->bind_param("i", $user_id);
$toko_id_stmt->execute();
$toko_id = $toko_id_stmt->get_result()->fetch_assoc()['id'];
$toko_id_stmt->close();

if (!$toko_id) {
    die("Toko tidak valid untuk user ini.");
}

$rekening_bank = $_POST['rekening_bank'];
$nomor_rekening = $_POST['nomor_rekening'];
$atas_nama = $_POST['atas_nama_rekening'];

$stmt_update = $koneksi->prepare("UPDATE tb_toko SET rekening_bank=?, nomor_rekening=?, atas_nama_rekening=? WHERE id=?");
$stmt_update->bind_param("sssi", $rekening_bank, $nomor_rekening, $atas_nama, $toko_id);

if ($stmt_update->execute()) {
    header("Location: ../app_seller/pengaturan/rekening_bank.php?status=sukses");
} else {
    header("Location: ../app_seller/pengaturan/rekening_bank.php?status=gagal");
}

$stmt_update->close();
$koneksi->close();
?>