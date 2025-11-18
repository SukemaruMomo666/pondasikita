<?php
session_start();
require_once '../config/koneksi.php';

// --- PENGAMANAN & VALIDASI AWAL ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Akses tidak diizinkan.");
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}
if (!isset($_POST['jumlah_payout']) || !is_numeric($_POST['jumlah_payout']) || $_POST['jumlah_payout'] < 50000) {
    header("Location: ../app_seller/keuangan.php?error=jumlah_tidak_valid");
    exit;
}

$user_id = $_SESSION['user_id'];
$jumlah_diminta = (float)$_POST['jumlah_payout'];

// --- VALIDASI KEPEMILIKAN & SALDO ---
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
$stmt_toko->close();
if (!$toko_id) die("Toko tidak ditemukan.");

// Validasi Ulang Saldo di Server untuk Keamanan
// (Logika yang sama persis seperti di halaman keuangan.php)
$q_gross = $koneksi->prepare("SELECT SUM(subtotal) as total FROM tb_detail_transaksi WHERE toko_id = ?");
$q_gross->bind_param("i", $toko_id); $q_gross->execute();
$penjualan_kotor = $q_gross->get_result()->fetch_assoc()['total'] ?? 0;

$q_komisi = $koneksi->prepare("SELECT SUM(jumlah_komisi) as total FROM tb_komisi WHERE toko_id = ?");
$q_komisi->bind_param("i", $toko_id); $q_komisi->execute();
$total_komisi = $q_komisi->get_result()->fetch_assoc()['total'] ?? 0;

$pendapatan_bersih = $penjualan_kotor - $total_komisi;

$q_payout = $koneksi->prepare("SELECT SUM(jumlah_payout) as total FROM tb_payouts WHERE toko_id = ? AND status = 'completed'");
$q_payout->bind_param("i", $toko_id); $q_payout->execute();
$sudah_ditarik = $q_payout->get_result()->fetch_assoc()['total'] ?? 0;

$saldo_tersedia_server = $pendapatan_bersih - $sudah_ditarik;

// Cek apakah saldo di server mencukupi
if ($jumlah_diminta > $saldo_tersedia_server) {
    header("Location: ../app_seller/keuangan.php?error=saldo_kurang");
    exit;
}

// --- PROSES INSERT PENGAJUAN PAYOUT ---
$stmt_insert = $koneksi->prepare("INSERT INTO tb_payouts (toko_id, jumlah_payout, status) VALUES (?, ?, 'pending')");
$stmt_insert->bind_param("id", $toko_id, $jumlah_diminta);

if ($stmt_insert->execute()) {
    header("Location: ../app_seller/keuangan.php?status=payout_sukses");
} else {
    header("Location: ../app_seller/keuangan.php?status=payout_gagal");
}

$stmt_insert->close();
$koneksi->close();
?>