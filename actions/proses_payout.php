<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Hanya proses jika ada ID dan Aksi
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: ../app_admin/manajemen_payout.php?error=invalid_request");
    exit;
}

$payout_id = (int)$_GET['id'];
$action = $_GET['action'];
$new_status = '';

if ($action === 'approve') {
    $new_status = 'completed';
} elseif ($action === 'reject') {
    $new_status = 'rejected';
} else {
    die("Aksi tidak valid.");
}

// Update status dan tanggal proses di database
$stmt = $koneksi->prepare("UPDATE tb_payouts SET status = ?, tanggal_proses = NOW() WHERE id = ?");
$stmt->bind_param("si", $new_status, $payout_id);

if ($stmt->execute()) {
    // Di dunia nyata, di sini Anda akan memicu email notifikasi ke seller
    header("Location: ../app_admin/manajemen_payout.php?status=sukses");
} else {
    header("Location: ../app_admin/manajemen_payout.php?status=gagal");
}

$stmt->close();
$koneksi->close();
?>