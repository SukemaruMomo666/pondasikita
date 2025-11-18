<?php
// PERBAIKAN: Sertakan file koneksi yang benar
include '../../../config/koneksi.php';
header('Content-Type: application/json');

if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = intval($_POST['id']);

    $sql = "DELETE FROM vouchers WHERE id = ?";
    
    // PERBAIKAN: Gunakan variabel $koneksi
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Voucher berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Voucher tidak ditemukan.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus voucher: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID Voucher tidak valid.']);
}

// PERBAIKAN: Gunakan variabel $koneksi
$koneksi->close();
?>