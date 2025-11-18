<?php
// Skrip ini harus dijalankan secara otomatis oleh server setiap beberapa menit

// Sertakan koneksi database
include_once __DIR__ . '/../config/koneksi.php';

echo "Memulai pengecekan pesanan kedaluwarsa...\n";

// 1. Cari semua pesanan yang belum dibayar dan sudah melewati batas waktu
$query = "SELECT id FROM tb_pesanan WHERE status_pembayaran = 'Unpaid' AND payment_deadline < NOW()";
$result = $koneksi->query($query);

if ($result->num_rows === 0) {
    echo "Tidak ada pesanan kedaluwarsa yang ditemukan.\n";
    exit;
}

// Siapkan statement untuk efisiensi
$stmt_get_items = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_pesanan WHERE pesanan_id = ?");
$stmt_return_stock = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
$stmt_cancel_order = $koneksi->prepare("UPDATE tb_pesanan SET status_pesanan = 'dibatalkan', status_pembayaran = 'Expired' WHERE id = ?");

while ($pesanan = $result->fetch_assoc()) {
    $id_pesanan = $pesanan['id'];
    echo "Memproses pesanan ID: $id_pesanan...\n";
    
    // Mulai transaksi untuk setiap pesanan agar aman
    $koneksi->begin_transaction();

    try {
        // Ambil semua item dari pesanan ini
        $stmt_get_items->bind_param("i", $id_pesanan);
        $stmt_get_items->execute();
        $items_result = $stmt_get_items->get_result();

        // Kembalikan stok yang ditahan
        while ($item = $items_result->fetch_assoc()) {
            $stmt_return_stock->bind_param("ii", $item['jumlah'], $item['barang_id']);
            $stmt_return_stock->execute();
            echo " - Stok dikembalikan untuk barang ID: {$item['barang_id']} sebanyak {$item['jumlah']}\n";
        }

        // Ubah status pesanan menjadi 'dibatalkan' dan 'Expired'
        $stmt_cancel_order->bind_param("i", $id_pesanan);
        $stmt_cancel_order->execute();
        echo " - Status pesanan ID: $id_pesanan diubah menjadi Dibatalkan/Expired.\n";

        // Jika semua berhasil, commit
        $koneksi->commit();

    } catch (Exception $e) {
        // Jika ada error, batalkan semua perubahan untuk pesanan ini
        $koneksi->rollback();
        echo " !! Terjadi error saat memproses pesanan ID: $id_pesanan. Error: " . $e->getMessage() . "\n";
    }
}

echo "Proses selesai.\n";

?>