<?php
// File: actions/notification_handler.php

// 1. Matikan display error biar Midtrans dapet HTTP 200 bersih
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

// 2. Load file koneksi (Mundur 1 langkah dari folder 'actions' ke root)
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// 3. Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl'; // Pastikan Server Key BENAR
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Fungsi Log Sederhana
function tulisLog($msg) {
    $log_file = __DIR__ . '/midtrans_log.txt';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | " . $msg . "\n", FILE_APPEND);
}

// Mulai Transaksi Database
$koneksi->begin_transaction();

try {
    // 4. Ambil Notifikasi dari Midtrans
    try {
        $notif = new \Midtrans\Notification();
    } catch (Exception $e) {
        tulisLog("GAGAL INIT NOTIF: " . $e->getMessage());
        http_response_code(403);
        exit("Invalid Signature");
    }

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;

    tulisLog("Notif Masuk: $order_id | Status: $transaction");

    // 5. Cek Transaksi di Database
    $stmt = $koneksi->prepare("SELECT id, status_pembayaran FROM tb_transaksi WHERE kode_invoice = ? FOR UPDATE");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        tulisLog("Order ID $order_id TIDAK DITEMUKAN di database.");
        $koneksi->rollback();
        http_response_code(404);
        exit("Order not found");
    }

    $data = $result->fetch_assoc();
    $trx_id = $data['id'];

    // Idempotency: Kalau sudah sukses, jangan diproses lagi
    if ($data['status_pembayaran'] == 'success') {
        tulisLog("Order $order_id sudah lunas sebelumnya. Skip.");
        $koneksi->commit();
        exit("OK");
    }

    // 6. Tentukan Status Baru
    $status_bayar = null;
    $status_global = null;

    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                $status_bayar = 'challenge';
                $status_global = 'menunggu_pembayaran';
            } else {
                $status_bayar = 'success';
                $status_global = 'diproses';
            }
        }
    } else if ($transaction == 'settlement') {
        $status_bayar = 'success';
        $status_global = 'diproses';
    } else if ($transaction == 'pending') {
        $status_bayar = 'pending';
        $status_global = 'menunggu_pembayaran';
    } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
        $status_bayar = ($transaction == 'expire') ? 'expired' : 'cancelled';
        $status_global = 'dibatalkan';
    }

    // 7. Update Database & Stok
    if ($status_bayar) {
        // Update Header Transaksi
        $upd = $koneksi->prepare("UPDATE tb_transaksi SET status_pembayaran=?, status_pesanan_global=? WHERE id=?");
        $upd->bind_param("ssi", $status_bayar, $status_global, $trx_id);
        $upd->execute();

        // Logic Stok
        if ($status_bayar == 'success') {
            // Pembayaran Sukses -> Kurangi Stok Fisik & Bersihkan Stok Booking
            $items = $koneksi->query("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = $trx_id");
            $stok_upd = $koneksi->prepare("UPDATE tb_barang SET stok = stok - ?, stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
            while($it = $items->fetch_assoc()) {
                $stok_upd->bind_param("iii", $it['jumlah'], $it['jumlah'], $it['barang_id']);
                $stok_upd->execute();
            }
            tulisLog("Stok fisik dikurangi untuk $order_id");

        } else if ($status_global == 'dibatalkan') {
            // Batal/Expired -> Kembalikan Stok Booking ke Pool
            $items = $koneksi->query("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = $trx_id");
            $stok_upd = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
            while($it = $items->fetch_assoc()) {
                $stok_upd->bind_param("ii", $it['jumlah'], $it['barang_id']);
                $stok_upd->execute();
            }
            tulisLog("Stok booking dikembalikan untuk $order_id");
        }
    }

    $koneksi->commit();
    tulisLog("Sukses update status jadi: $status_bayar");
    http_response_code(200); // WAJIB

} catch (Exception $e) {
    $koneksi->rollback();
    tulisLog("CRITICAL ERROR: " . $e->getMessage());
    http_response_code(500);
}
?>