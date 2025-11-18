<?php
// Selalu mulai session di awal
session_start();

// Sesuaikan path ini jika perlu. Diasumsikan folder 'actions' sejajar dengan 'config'
require_once __DIR__ . '/../config/koneksi.php';

// Atur header untuk memberitahu browser bahwa responsnya adalah JSON
header('Content-Type: application/json');

// Fungsi helper untuk mengirimkan respons JSON dan menghentikan skrip
function send_response($status, $message, $data = []) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// 1. Validasi Keamanan dan Sesi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response('error', 'Metode request tidak valid.');
}

// ✅ PERBAIKAN: Menggunakan $_SESSION['user_id'] yang konsisten dengan file lain
if (!isset($_SESSION['user_id'])) {
    send_response('error', 'Sesi tidak valid. Silakan login kembali.');
}

// 2. Ambil dan Validasi Input
$id_user = $_SESSION['user_id']; // ✅ PERBAIKAN: Menggunakan variabel session yang benar
$action = $_POST['action'] ?? '';
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if (empty($action) || $item_id <= 0) {
    send_response('error', 'Aksi atau ID item tidak lengkap.');
}

// Mulai transaksi untuk menjaga integritas data
$koneksi->begin_transaction();

try {
    // 3. Ambil data item keranjang & stok produk dalam satu query
    $stmt_check = $koneksi->prepare(
        "SELECT k.jumlah, b.stok 
         FROM tb_keranjang k 
         JOIN tb_barang b ON k.barang_id = b.id 
         WHERE k.id = ? AND k.user_id = ?"
    );
    $stmt_check->bind_param("ii", $item_id, $id_user);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $item_data = $result->fetch_assoc();
    $stmt_check->close();

    // Jika item tidak ditemukan atau bukan milik user, kirim error
    if (!$item_data) {
        throw new Exception('Item keranjang tidak ditemukan atau bukan milik Anda.');
    }

    $current_qty = $item_data['jumlah'];
    $stok_tersedia = $item_data['stok'];

    // 4. Logika Berdasarkan Aksi (Action)
    if ($action === 'increase') {
        if ($current_qty < $stok_tersedia) {
            $stmt = $koneksi->prepare("UPDATE tb_keranjang SET jumlah = jumlah + 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $item_id, $id_user);
            $stmt->execute();
        } else {
            throw new Exception('Stok tidak mencukupi untuk menambah jumlah.');
        }
    } 
    elseif ($action === 'decrease') {
        if ($current_qty > 1) {
            $stmt = $koneksi->prepare("UPDATE tb_keranjang SET jumlah = jumlah - 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $item_id, $id_user);
            $stmt->execute();
        }
        // Jika jumlah sudah 1, tidak melakukan apa-apa (tombol minus seharusnya non-aktif di frontend)
    } 
    elseif ($action === 'remove') {
        $stmt = $koneksi->prepare("DELETE FROM tb_keranjang WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $item_id, $id_user);
        $stmt->execute();
    } 
    else {
        throw new Exception('Aksi tidak dikenal.');
    }

    // Jika ada statement yang dieksekusi, tutup
    if (isset($stmt)) {
        $stmt->close();
    }

    // Jika semua query berhasil, commit transaksi
    $koneksi->commit();
    send_response('success', 'Keranjang berhasil diperbarui.');

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $koneksi->rollback();
    send_response('error', $e->getMessage());
}
?>
