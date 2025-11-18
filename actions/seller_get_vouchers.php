<?php
session_start(); require_once '../config/koneksi.php'; header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode([]); exit; }

$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

// PERUBAHAN PENTING: Mengambil voucher HANYA dari toko_id ini
$stmt = $koneksi->prepare("SELECT * FROM vouchers WHERE toko_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $toko_id);
$stmt->execute();
$result = $stmt->get_result();
$vouchers = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($vouchers);
?>