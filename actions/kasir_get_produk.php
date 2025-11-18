<?php
session_start();
require_once '../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

if(!$toko_id) {
    echo json_encode(['error' => 'Toko tidak ditemukan']);
    exit;
}

$query = "SELECT id, kode_barang, nama_barang, kategori_id, harga, stok, gambar_utama 
          FROM tb_barang WHERE toko_id = ? AND is_active = 1";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $toko_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($products);
$stmt->close();
$koneksi->close();
?>