<?php
// Koneksi ke database
include __DIR__ . '/../../../config/koneksi.php';

// Cek ID valid atau tidak
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: kelola_data_barang.php?status=error&message=ID tidak valid');
    exit;
}

$id = $_GET['id'];

// Cek apakah barang masih digunakan di tb_item_pesanan
$sql_check = "SELECT COUNT(*) as total FROM tb_detail_transaksi WHERE barang_id = ?";
$stmt_check = $koneksi->prepare($sql_check);
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$count_data = $result_check->fetch_assoc();

if ($count_data['total'] > 0) {
    // Barang masih digunakan, tolak penghapusan
    header('Location: kelola_data_barang.php?status=error&message=Barang tidak bisa dihapus karena sudah digunakan dalam pesanan');
    exit;
}

// Ambil gambar dulu
$sql_select = "SELECT gambar_barang FROM tb_barang WHERE id = ?";
$stmt_select = $koneksi->prepare($sql_select);
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$result = $stmt_select->get_result();
$product = $result->fetch_assoc();

// Hapus data barang
$sql_delete = "DELETE FROM tb_barang WHERE id = ?";
$stmt_delete = $koneksi->prepare($sql_delete);
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    // Hapus gambar jika ada
    if ($product && $product['gambar_barang']) {
        $file_path = __DIR__ . '/../../../assets/' . $product['gambar_barang'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    header('Location: kelola_data_barang.php?status=success&message=Produk berhasil dihapus');
} else {
    header('Location: kelola_data_barang.php?status=error&message=Gagal menghapus produk');
}

$stmt_check->close();
$stmt_select->close();
$stmt_delete->close();
$koneksi->close();
exit;
?>
