<?php
session_start();
require_once '../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$user_id = $_SESSION['user_id'];
// Kueri ini mengambil kategori yang HANYA ADA pada produk yang dijual oleh toko tersebut.
$sql = "SELECT DISTINCT k.id, k.nama_kategori 
        FROM tb_kategori k
        JOIN tb_barang b ON k.id = b.kategori_id
        JOIN tb_toko t ON b.toko_id = t.id
        WHERE t.user_id = ? ORDER BY k.nama_kategori ASC";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($categories);
$stmt->close();
$koneksi->close();
?>