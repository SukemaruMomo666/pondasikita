<?php
header('Content-Type: application/json');
// Sesuaikan path jika perlu, ini mengasumsikan file ada di dalam folder 'proses'
require_once __DIR__ . '/../../config/koneksi.php';

if (!$koneksi) {
    echo json_encode(['stok' => 0, 'pesanan' => 0, 'pesan' => 0, 'laporan' => 0]);
    exit;
}

// 1. Hitung notifikasi stok hampir habis (stok < 10)
// PERUBAHAN: Menggunakan stok tersedia (stok - stok_di_pesan)
$queryStok = "SELECT COUNT(*) AS jumlah FROM tb_barang WHERE (stok - stok_di_pesan) < 10";
$countStok = $koneksi->query($queryStok)->fetch_assoc()['jumlah'] ?? 0;

// 2. Hitung notifikasi pesanan baru
$queryPesanan = "SELECT COUNT(*) AS jumlah FROM tb_transaksi WHERE status_pesanan = 'pending'";
$countPesanan = $koneksi->query($queryPesanan)->fetch_assoc()['jumlah'] ?? 0;

// 3. Hitung notifikasi pesan customer yang belum dibaca
$queryPesan = "SELECT COUNT(*) AS jumlah FROM tb_pesan WHERE is_read = 0";
$countPesan = $koneksi->query($queryPesan)->fetch_assoc()['jumlah'] ?? 0;

// 4. Hitung notifikasi laporan produk baru
$queryLaporan = "SELECT COUNT(*) AS jumlah FROM tb_laporan_produk WHERE status = 'Baru'";
$countLaporan = $koneksi->query($queryLaporan)->fetch_assoc()['jumlah'] ?? 0;


// Kirim data dalam format JSON yang diharapkan oleh JavaScript
echo json_encode([
    'stok' => (int)$countStok,
    'pesanan' => (int)$countPesanan,
    'pesan' => (int)$countPesan,
    'laporan' => (int)$countLaporan
]);

$koneksi->close();
?>