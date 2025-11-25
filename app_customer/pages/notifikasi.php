<?php
// ==============================================================================
// NOTIFICATION HANDLER MIDTRANS (FINAL)
// ==============================================================================

// 1. Setting Error Log (Biar Midtrans dapet respon bersih 200 OK)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di layar (browser/midtrans)
ini_set('log_errors', 1);     // Tulis error ke file log aja
ini_set('error_log', __DIR__ . '/php_error_log.txt');

// 2. AUTO-DETECT PATH KONEKSI & VENDOR
// Ini otomatis nyari file koneksi, mau ditaruh di folder 'actions' atau 'pages' tetep jalan.
$paths_to_check = [
    __DIR__ . '/../config/koneksi.php',       // Jika file ini ada di folder 'actions'
    __DIR__ . '/../../config/koneksi.php',    // Jika file ini ada di folder 'app_customer/pages'
    $_SERVER['DOCUMENT_ROOT'] . '/PondasiKita/config/koneksi.php', // Jalur mutlak (Laragon)
];

$koneksi_found = false;
foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        require_once $path;
        $koneksi_found = true;
        break;
    }
}

// Load Autoload Composer (Midtrans Library) dengan logika sama
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

// Cek Fatal Error kalau file gak ketemu
if (!$koneksi_found || !$vendor_found) {
    http_response_code(500);
    die("CRITICAL ERROR: File koneksi.php atau vendor/autoload.php tidak ditemukan. Cek struktur folder.");
}

// 3. Konfigurasi Midtrans
// GANTI ServerKey DENGAN PUNYA LU YANG BENAR
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl'; 
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Fungsi Log Sederhana
function tulisLog($msg) {
    $log_file = __DIR__ . '/midtrans_log.txt';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | " . $msg . "\n", FILE_APPEND);
}

// MULAI PROSES
$koneksi->begin_transaction();

try {
    // 4. Ambil Notifikasi
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

    // 5. Cek Transaksi di Database (tb_transaksi)
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

    // Idempotency: Kalau sudah sukses, jangan diproses lagi biar gak double deduct stok
    if ($data['status_pembayaran'] == 'success') {
        tulisLog("Order $order_id sudah lunas sebelumnya. Skip.");
        $koneksi->commit();
        http_response_code(200);
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
    http_response_code(200); // WAJIB RESPON 200 KE MIDTRANS

} catch (Exception $e) {
    $koneksi->rollback();
    tulisLog("CRITICAL ERROR: " . $e->getMessage());
    http_response_code(500); // Kasih tau Midtrans server kita error
}
?>