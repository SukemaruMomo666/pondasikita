<?php
header('Content-Type: application/json');
include "../../../config/koneksi.php";

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id === 0) {
    send_json_response('error', 'ID produk tidak valid.');
}

// Ambil detail produk utama
$stmt_product = $koneksi->prepare("
    SELECT b.*, k.nama_kategori 
    FROM tb_barang b
    LEFT JOIN tb_kategori k ON b.kategori_id = k.id
    WHERE b.id = ?
");
$stmt_product->bind_param("i", $product_id);
$stmt_product->execute();
$result_product = $stmt_product->get_result();
$product = $result_product->fetch_assoc();

if (!$product) {
    send_json_response('error', 'Produk tidak ditemukan.');
}

// Ambil semua gambar terkait
$stmt_images = $koneksi->prepare("
    SELECT id, nama_file, is_utama 
    FROM tb_gambar_barang 
    WHERE barang_id = ?
    ORDER BY is_utama DESC, id ASC
");
$stmt_images->bind_param("i", $product_id);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
$images = $result_images->fetch_all(MYSQLI_ASSOC);

// Gabungkan gambar ke dalam data produk
$product['images'] = $images;

send_json_response('success', 'Data produk berhasil diambil.', $product);

$stmt_product->close();
$stmt_images->close();
$koneksi->close();
?>