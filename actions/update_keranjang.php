<?php
// actions/update_keranjang.php
session_start();
require_once '../config/koneksi.php';

header('Content-Type: application/json');

// 1. Deteksi User ID (Sesuai standar baru kita)
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
}

// Jika tidak ada user_id, tolak akses
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi tidak valid. Silakan login ulang.']);
    exit;
}

// 2. Validasi Input POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? ''; // 'increase', 'decrease', atau 'remove'
$item_id = intval($_POST['item_id'] ?? 0);

if ($item_id <= 0 || empty($action)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit;
}

// 3. Proses Database
try {
    // Cek dulu apakah item ini benar milik user yang sedang login
    $stmt_cek = $koneksi->prepare("SELECT k.id, k.jumlah, b.stok, b.stok_di_pesan 
                                   FROM tb_keranjang k 
                                   JOIN tb_barang b ON k.barang_id = b.id 
                                   WHERE k.id = ? AND k.user_id = ?");
    $stmt_cek->bind_param("ii", $item_id, $user_id);
    $stmt_cek->execute();
    $result = $stmt_cek->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Item tidak ditemukan atau bukan milik Anda.");
    }

    $item = $result->fetch_assoc();
    $current_qty = $item['jumlah'];
    $max_stok = $item['stok'] - $item['stok_di_pesan'];

    if ($action === 'remove') {
        // --- LOGIKA HAPUS ---
        $stmt_del = $koneksi->prepare("DELETE FROM tb_keranjang WHERE id = ?");
        $stmt_del->bind_param("i", $item_id);
        
        if ($stmt_del->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Item berhasil dihapus.']);
        } else {
            throw new Exception("Gagal menghapus item.");
        }
        $stmt_del->close();

    } elseif ($action === 'increase') {
        // --- LOGIKA TAMBAH JUMLAH ---
        if ($current_qty >= $max_stok) {
            throw new Exception("Stok maksimal tercapai ({$max_stok}).");
        }
        $new_qty = $current_qty + 1;
        $stmt_upd = $koneksi->prepare("UPDATE tb_keranjang SET jumlah = ? WHERE id = ?");
        $stmt_upd->bind_param("ii", $new_qty, $item_id);
        $stmt_upd->execute();
        echo json_encode(['status' => 'success', 'message' => 'Jumlah ditambah.']);

    } elseif ($action === 'decrease') {
        // --- LOGIKA KURANG JUMLAH ---
        if ($current_qty > 1) {
            $new_qty = $current_qty - 1;
            $stmt_upd = $koneksi->prepare("UPDATE tb_keranjang SET jumlah = ? WHERE id = ?");
            $stmt_upd->bind_param("ii", $new_qty, $item_id);
            $stmt_upd->execute();
            echo json_encode(['status' => 'success', 'message' => 'Jumlah dikurangi.']);
        } else {
            // Jika jumlah 1 dan dikurangi, hapus item? (Opsional, biasanya user prefer tombol hapus)
            throw new Exception("Minimal pembelian 1 item. Gunakan tombol hapus jika ingin membatalkan.");
        }
    } else {
        throw new Exception("Aksi tidak dikenali.");
    }

    $stmt_cek->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>