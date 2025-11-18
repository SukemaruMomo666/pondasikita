<?php
// ---- KODE DEBUG: JANGAN HAPUS BAGIAN INI ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ---------------------------------------------

header('Content-Type: application/json');

// Path ini sudah disesuaikan dengan struktur folder Anda
// Dari /kasir/api/ naik 4x ke root /tigadaya/ lalu masuk ke /config/
include "../../../config/koneksi.php";

$query = "SELECT id, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    http_response_code(500);
    // Pesan error dari database akan ditampilkan
    echo json_encode(['status' => 'error', 'message' => 'Query gagal: ' . mysqli_error($koneksi)]);
    exit;
}

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

echo json_encode($categories);

mysqli_close($koneksi);
?>