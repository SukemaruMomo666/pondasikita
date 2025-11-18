<?php
require '../config/koneksi.php';

// Hitung stok hampir habis
$stok = $koneksi->query("SELECT COUNT(*) AS total FROM tb_barang WHERE stok < 10")->fetch_assoc()['total'];

// Hitung pesanan pending
$pesanan = $koneksi->query("SELECT COUNT(*) AS total FROM tb_pesanan WHERE status = 'pending'")->fetch_assoc()['total'];

// Hitung pesan customer terbaru dalam 24 jam
$pesan = $koneksi->query("SELECT COUNT(*) AS total FROM tb_pesan WHERE created_at >= NOW() - INTERVAL 1 DAY")->fetch_assoc()['total'];

echo json_encode([
    'stok' => (int)$stok,
    'pesanan' => (int)$pesanan,
    'pesan' => (int)$pesan
]);
