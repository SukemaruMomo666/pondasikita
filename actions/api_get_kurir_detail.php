<?php
session_start();
require_once '../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'seller') { 
    echo json_encode(['status'=>'error', 'message'=>'Akses ditolak']); exit; 
}
$user_id = $_SESSION['user_id'];
$kurir_id = (int)$_GET['id'];

// Ambil toko_id untuk validasi
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

// Ambil data kurir HANYA jika ID dan toko_id cocok
$stmt = $koneksi->prepare("SELECT * FROM tb_kurir_toko WHERE id = ? AND toko_id = ?");
$stmt->bind_param("ii", $kurir_id, $toko_id);
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
    echo json_encode(['status'=>'success', 'data'=>$data]);
} else {
    echo json_encode(['status'=>'error', 'message'=>'Data tidak ditemukan']);
}
?>