<?php
// actions/cek_status_transaksi.php

// Matikan error display agar output murni JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/koneksi.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No ID']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $koneksi->prepare("SELECT status_pembayaran, status_pesanan_global FROM tb_transaksi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
    'status_bayar' => $row['status_pembayaran'], // ini balikin 'paid'
    // ...
]);
} else {
    echo json_encode(['status' => 'not_found']);
}
?>  