<?php
header('Content-Type: application/json');
include "../../../../config/koneksi.php"; // Sesuaikan path jika perlu
session_start();

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['cart'])) {
    send_json_response('error', 'Data transaksi tidak lengkap.');
}

// Data dari Frontend Kasir
$cart = $data['cart'];
$total_harga = floatval($data['total']);
$metode_pembayaran = $data['paymentMethod'] ?? 'Tunai';
$jumlah_dibayar = floatval($data['amountPaid'] ?? 0);
$kembalian = floatval($data['change'] ?? 0);
$nama_pelanggan = !empty($data['customerName']) ? trim($data['customerName']) : 'Pelanggan Offline';
$id_user_kasir = $_SESSION['user']['id'] ?? null; // Ambil ID kasir dari sesi

// Generate kode pesanan/invoice unik
$kode_pesanan = 'POS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

$koneksi->begin_transaction();

try {
    // 1. Simpan ke tb_pesanan (bukan tb_transaksi lagi)
    $stmt_pesanan = $koneksi->prepare(
        "INSERT INTO tb_pesanan (kode_pesanan, user_id, nama_penerima, grand_total, metode_pembayaran, status_pembayaran, status_pesanan, sumber_transaksi, id_user_kasir, jumlah_dibayar, kembalian) 
         VALUES (?, ?, ?, ?, ?, 'Paid', 'Selesai', 'offline', ?, ?, ?)"
    );
    // user_id bisa di-set ke user admin/kasir, atau user default untuk offline
    $user_id_default_offline = $id_user_kasir ?? 1; 
    $stmt_pesanan->bind_param("sisdsidd", $kode_pesanan, $user_id_default_offline, $nama_pelanggan, $total_harga, $metode_pembayaran, $id_user_kasir, $jumlah_dibayar, $kembalian);
    $stmt_pesanan->execute();
    
    $pesanan_id = $koneksi->insert_id;
    if ($pesanan_id === 0) throw new Exception("Gagal membuat pesanan baru.");

    // 2. Simpan ke tb_detail_pesanan
    $stmt_detail = $koneksi->prepare(
        "INSERT INTO tb_detail_pesanan (pesanan_id, barang_id, jumlah, harga) VALUES (?, ?, ?, ?)"
    );
    $stmt_update_stok = $koneksi->prepare(
        "UPDATE tb_barang SET stok_di_pesan = stok_di_pesan + ? WHERE id = ?"
    );

    foreach ($cart as $item) {
        $stmt_detail->bind_param("iiid", $pesanan_id, $item['id'], $item['quantity'], $item['price']);
        $stmt_detail->execute();

        $stmt_update_stok->bind_param("ii", $item['quantity'], $item['id']);
        $stmt_update_stok->execute();
    }

    $koneksi->commit();
    send_json_response('success', 'Transaksi kasir berhasil disimpan!', ['invoiceCode' => $kode_pesanan]);

} catch (Exception $e) {
    $koneksi->rollback();
    send_json_response('error', 'Gagal memproses transaksi: ' . $e->getMessage());
} finally {
    if (isset($stmt_pesanan)) $stmt_pesanan->close();
    if (isset($stmt_detail)) $stmt_detail->close();
    if (isset($stmt_update_stok)) $stmt_update_stok->close();
    $koneksi->close();
}
?>