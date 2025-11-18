<?php
// Langkah 1: Sertakan file koneksi dari lokasi yang benar
include '../../../config/koneksi.php';

// Langkah 2: Set header agar outputnya adalah JSON
header('Content-Type: application/json');

// Pastikan variabel koneksi dari file include adalah '$koneksi'
if (!isset($koneksi) || $koneksi->connect_error) {
    // Kirim array JSON kosong jika koneksi gagal
    echo json_encode([]); 
    exit; // Hentikan skrip
}

// Langkah 3: Buat query untuk mengambil data
$sql = "SELECT * FROM vouchers ORDER BY id DESC";
$result = $koneksi->query($sql);

$vouchers = [];

// Langkah 4: Loop hasil query jika berhasil dan ada datanya
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
}

// Langkah 5: Cetak hasil dalam format JSON
echo json_encode($vouchers);

// Langkah 6: Tutup koneksi
$koneksi->close();
?>