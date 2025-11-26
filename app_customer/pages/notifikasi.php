<?php
// ==============================================================================
// NOTIFICATION HANDLER MIDTRANS (FIXED ENUM: 'paid' instead of 'success')
// ==============================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error_log.txt');

// [PENTING] Cek Metode Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    exit("Access Denied");
}

// 2. AUTO-DETECT PATH
$paths_to_check = [
    __DIR__ . '/../config/koneksi.php',       
    __DIR__ . '/../../config/koneksi.php',    
    $_SERVER['DOCUMENT_ROOT'] . '/PondasiKita/config/koneksi.php',
];

$koneksi_found = false;
foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        require_once $path;
        $koneksi_found = true;
        break;
    }
}

$vendor_paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/PondasiKita/vendor/autoload.php',
];

$vendor_found = false;
foreach ($vendor_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $vendor_found = true;
        break;
    }
}

if (!$koneksi_found || !$vendor_found) {
    http_response_code(500);
    die("CRITICAL ERROR: File koneksi/vendor tidak ketemu.");
}

// 3. Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl'; 
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

function tulisLog($msg) {
    $log_file = __DIR__ . '/midtrans_log.txt';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | " . $msg . "\n", FILE_APPEND);
}

$koneksi->begin_transaction();

try {
    try {
        $notif = new \Midtrans\Notification();
    } catch (Exception $e) {
        tulisLog("GAGAL INIT: " . $e->getMessage());
        http_response_code(403);
        exit("Invalid Signature");
    }

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;

    tulisLog("Notif Masuk: $order_id | Status: $transaction");

    // 4. Cek Database
    $stmt = $koneksi->prepare("SELECT id, status_pembayaran FROM tb_transaksi WHERE kode_invoice = ? FOR UPDATE");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        tulisLog("Order $order_id TIDAK DITEMUKAN.");
        $koneksi->rollback();
        http_response_code(404);
        exit("Order not found");
    }

    $data = $result->fetch_assoc();
    $trx_id = $data['id'];

    // [FIX] Cek status pake 'paid' (sesuai DB)
    if ($data['status_pembayaran'] == 'paid') {
        tulisLog("Order $order_id sudah lunas (paid). Skip.");
        $koneksi->commit();
        http_response_code(200);
        exit("OK");
    }

    // 5. Tentukan Status Baru (SINKRONISASI DENGAN ENUM DB)
    $status_bayar = null;
    $status_global = null;

    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                $status_bayar = 'pending'; // Challenge dianggap pending dulu
                $status_global = 'menunggu_pembayaran';
            } else {
                $status_bayar = 'paid'; // [FIX] Ganti 'success' jadi 'paid'
                $status_global = 'diproses';
            }
        }
    } else if ($transaction == 'settlement') {
        $status_bayar = 'paid'; // [FIX] Ganti 'success' jadi 'paid'
        $status_global = 'diproses';
    } else if ($transaction == 'pending') {
        $status_bayar = 'pending';
        $status_global = 'menunggu_pembayaran';
    } else if ($transaction == 'deny') {
        $status_bayar = 'failed';
        $status_global = 'dibatalkan';
    } else if ($transaction == 'expire') {
        $status_bayar = 'expired';
        $status_global = 'dibatalkan';
    } else if ($transaction == 'cancel') {
        $status_bayar = 'cancelled';
        $status_global = 'dibatalkan';
    }

    // 6. Update Database
    if ($status_bayar) {
        $upd = $koneksi->prepare("UPDATE tb_transaksi SET status_pembayaran=?, status_pesanan_global=? WHERE id=?");
        $upd->bind_param("ssi", $status_bayar, $status_global, $trx_id);
        
        if (!$upd->execute()) {
            throw new Exception("Gagal update DB: " . $upd->error);
        }

        // Logic Stok
        if ($status_bayar == 'paid') { // [FIX] Cek 'paid'
            $items = $koneksi->query("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = $trx_id");
            $stok_upd = $koneksi->prepare("UPDATE tb_barang SET stok = stok - ?, stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
            while($it = $items->fetch_assoc()) {
                $stok_upd->bind_param("iii", $it['jumlah'], $it['jumlah'], $it['barang_id']);
                $stok_upd->execute();
            }
            tulisLog("Stok fisik dikurangi untuk $order_id");

        } else if ($status_global == 'dibatalkan') {
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
    tulisLog("Sukses update jadi: $status_bayar");
    http_response_code(200);

} catch (Exception $e) {
    $koneksi->rollback();
    tulisLog("CRITICAL ERROR: " . $e->getMessage());
    http_response_code(500);
}
?>