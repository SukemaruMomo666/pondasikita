<?php
// Aktifkan error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Panggil koneksi dan autoload
require_once '../config/koneksi.php';
require_once '../vendor/autoload.php';

// --- KONFIGURASI MIDTRANS ---
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl'; 
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// File log untuk debugging
$log_file = __DIR__ . '/log_midtrans.txt';

// Mulai transaksi database
$koneksi->begin_transaction();

try {
    // 1. Verifikasi notifikasi Midtrans
    $notif = new \Midtrans\Notification();
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | VERIFIED: " . json_encode($notif) . "\n", FILE_APPEND);

    // 2. Ambil data notifikasi
    $transaction_status = $notif->transaction_status;
    $fraud_status = $notif->fraud_status;
    $order_id_midtrans = $notif->order_id;

    // Ambil kode pesanan asli TANPA dipotong
    $kode_pesanan_asli = $order_id_midtrans;

    // 3. Cek apakah pesanan ada
    $stmt_check_order = $koneksi->prepare("SELECT id, status_pembayaran, status_pesanan FROM tb_pesanan WHERE kode_pesanan = ? FOR UPDATE");
    $stmt_check_order->bind_param("s", $kode_pesanan_asli);
    $stmt_check_order->execute();
    $result_check = $stmt_check_order->get_result();

    if ($result_check->num_rows == 0) {
        throw new Exception("Pesanan dengan kode {$kode_pesanan_asli} tidak ditemukan di database.");
    }

    $order_data = $result_check->fetch_assoc();
    $pesanan_id = $order_data['id'];

    // === JIKA PEMBAYARAN SUKSES ===
    if (in_array($transaction_status, ['capture', 'settlement']) && $fraud_status === 'accept') {

        if ($order_data['status_pembayaran'] == 'Paid') {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | INFO: Notifikasi duplikat. order_id: {$order_id_midtrans}\n", FILE_APPEND);
            $koneksi->commit();
            http_response_code(200);
            exit("Notification ignored, order already paid.");
        }

        // a. Update status pembayaran dan status pesanan
        $stmt_update_order = $koneksi->prepare("UPDATE tb_pesanan SET status_pesanan='Diproses', status_pembayaran='Paid' WHERE id=?");
        $stmt_update_order->bind_param("i", $pesanan_id);
        if (!$stmt_update_order->execute()) {
            throw new Exception("Gagal update pesanan: " . $stmt_update_order->error);
        }

        // b. Ambil detail pesanan
        $stmt_get_items = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_pesanan WHERE pesanan_id = ?");
        $stmt_get_items->bind_param("i", $pesanan_id);
        $stmt_get_items->execute();
        $items = $stmt_get_items->get_result();

        // c. Kurangi stok dan stok_di_pesan
        $stmt_reduce_stock = $koneksi->prepare("UPDATE tb_barang SET stok = stok - ?, stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
        while ($item = $items->fetch_assoc()) {
            $stmt_reduce_stock->bind_param("iii", $item['jumlah'], $item['jumlah'], $item['barang_id']);
            if (!$stmt_reduce_stock->execute()) {
                throw new Exception("Gagal mengurangi stok barang ID " . $item['barang_id']);
            }
        }

        file_put_contents($log_file, date('Y-m-d H:i:s') . " | SUCCESS: order_id: {$order_id_midtrans} => Paid & Diproses. Stok dikurangi.\n", FILE_APPEND);
    }

    // === JIKA PEMBAYARAN GAGAL, DIBATALKAN, ATAU KEDALUWARSA ===
    else if (in_array($transaction_status, ['expire', 'cancel', 'deny'])) {

        if ($order_data['status_pembayaran'] != 'Paid') {
            // a. Update status pesanan menjadi dibatalkan
            $stmt_update_order = $koneksi->prepare("UPDATE tb_pesanan SET status_pesanan='Dibatalkan', status_pembayaran='Unpaid' WHERE id=?");
            $stmt_update_order->bind_param("i", $pesanan_id);
            if (!$stmt_update_order->execute()) {
                throw new Exception("Gagal update status Dibatalkan: " . $stmt_update_order->error);
            }

            // b. Ambil item dan kembalikan stok_di_pesan
            $stmt_get_items = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_pesanan WHERE pesanan_id = ?");
            $stmt_get_items->bind_param("i", $pesanan_id);
            $stmt_get_items->execute();
            $items = $stmt_get_items->get_result();

            $stmt_return_stock = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
            while ($item = $items->fetch_assoc()) {
                $stmt_return_stock->bind_param("ii", $item['jumlah'], $item['barang_id']);
                if (!$stmt_return_stock->execute()) {
                    throw new Exception("Gagal mengembalikan stok barang ID " . $item['barang_id']);
                }
            }

            file_put_contents($log_file, date('Y-m-d H:i:s') . " | CANCELLED: order_id: {$order_id_midtrans} => Dibatalkan. Stok dikembalikan.\n", FILE_APPEND);
        }
    }

    // Sukses semua, commit transaksi
    $koneksi->commit();
    http_response_code(200);
    echo "Notification processed successfully.";

} catch (Exception $e) {
    $koneksi->rollback();
    $error_message = $e->getMessage();
    $order_id_from_error = isset($notif) ? $notif->order_id : "N/A";
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | ERROR: {$error_message} | order_id: {$order_id_from_error}\n", FILE_APPEND);

    if (strpos($error_message, 'signature key') !== false) {
        http_response_code(403);
    } else {
        http_response_code(500);
    }
    echo "Error processing notification.";
}
?>
