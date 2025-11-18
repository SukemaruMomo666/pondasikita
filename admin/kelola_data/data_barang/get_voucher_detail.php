<?php
include '../../../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID Voucher tidak disediakan.']);
    exit;
}

$id = intval($_GET['id']);

if (!isset($koneksi)) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
    exit;
}

$sql = "SELECT * FROM vouchers WHERE id = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $voucher = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $voucher]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Voucher tidak ditemukan.']);
}

$stmt->close();
$koneksi->close();
?>