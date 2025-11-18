<?php
// FILE: get_products_list.php (VERSI PERBAIKAN)

header('Content-Type: application/json');
require_once '../../../config/koneksi.php';

if (!$koneksi) {
    echo json_encode(['error' => 'Koneksi database gagal.']);
    exit;
}

// Menambahkan b.kategori_id pada SELECT
$query = "SELECT 
            b.id,
            b.kode_barang, 
            b.nama_barang, 
            b.kategori_id, -- <<<---- BARIS INI YANG DITAMBAHKAN, INI KUNCINYA
            k.nama_kategori,
            b.harga,
            b.is_active,
            b.created_at,
            (b.stok - b.stok_di_pesan) as stok_tersedia,
            (SELECT nama_file FROM tb_gambar_barang WHERE barang_id = b.id AND is_utama = 1 LIMIT 1) as gambar_utama
        FROM tb_barang b
        LEFT JOIN tb_kategori k ON b.kategori_id = k.id
        ORDER BY b.nama_barang ASC"; // diganti order by nama_barang agar lebih rapi

$result = mysqli_query($koneksi, $query);

if (!$result) {
    echo json_encode(['error' => 'Query SQL gagal: ' . mysqli_error($koneksi)]);
    exit;
}

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Pastikan stok tidak negatif
    $row['stok_tersedia'] = max(0, intval($row['stok_tersedia']));
    $products[] = $row;
}

echo json_encode($products);

mysqli_close($koneksi);
?>