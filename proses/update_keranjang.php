<?php
include '../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$item_id = $_POST['item_id'] ?? ''; // Bisa berupa id_keranjang (int) atau kode_barang (string)

if (empty($action) || empty($item_id)) {
    echo json_encode($response);
    exit();
}

$is_user_logged_in = isset($_SESSION['user']['id']);
$user_id = $is_user_logged_in ? $_SESSION['user']['id'] : null;

// Tentukan apakah item_id adalah integer (id_keranjang) atau string (kode_barang)
$is_db_cart_id = is_numeric($item_id);

// Mulai transaksi
$koneksi->begin_transaction();

try {
    // --- LOGIKA UTAMA ---
    if ($is_user_logged_in && $is_db_cart_id) {
        // --- Pengguna Login (Database) ---
        $id_keranjang = (int)$item_id;

        // Ambil informasi keranjang dan stok
        $res = $koneksi->query("SELECT k.jumlah, b.stok, b.stok_di_pesan 
                                FROM tb_keranjang k 
                                JOIN tb_barang b ON k.barang_id = b.id 
                                WHERE k.id = $id_keranjang AND k.user_id = $user_id 
                                FOR UPDATE");

        if ($res->num_rows === 0) {
            throw new Exception("Item tidak ditemukan.");
        }

        $data = $res->fetch_assoc();
        $jumlah = (int)$data['jumlah'];
        $stok_sisa = (int)$data['stok'] - (int)$data['stok_di_pesan'];

        if ($action === 'increase') {
            if ($jumlah + 1 > $stok_sisa) {
                throw new Exception("Stok tidak mencukupi.");
            }
            $koneksi->query("UPDATE tb_keranjang SET jumlah = jumlah + 1 WHERE id = $id_keranjang AND user_id = $user_id");
        } elseif ($action === 'decrease') {
            if ($jumlah > 1) {
                $koneksi->query("UPDATE tb_keranjang SET jumlah = jumlah - 1 WHERE id = $id_keranjang AND user_id = $user_id");
            }
        } elseif ($action === 'remove') {
            $koneksi->query("DELETE FROM tb_keranjang WHERE id = $id_keranjang AND user_id = $user_id");
        }
    } elseif (!$is_user_logged_in && !$is_db_cart_id) {
        // --- Pengguna Tamu (Session) ---
        $kode_barang = $item_id;

        if (!isset($_SESSION['keranjang'][$kode_barang])) {
            throw new Exception("Item tidak ditemukan di keranjang.");
        }

        // Ambil stok barang
        $stmt = $koneksi->prepare("SELECT stok, stok_di_pesan FROM tb_barang WHERE kode_barang = ? FOR UPDATE");
        $stmt->bind_param("s", $kode_barang);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            throw new Exception("Produk tidak ditemukan.");
        }

        $data = $res->fetch_assoc();
        $jumlah = $_SESSION['keranjang'][$kode_barang];
        $stok_sisa = (int)$data['stok'] - (int)$data['stok_di_pesan'];

        if ($action === 'increase') {
            if ($jumlah + 1 > $stok_sisa) {
                throw new Exception("Stok tidak mencukupi.");
            }
            $_SESSION['keranjang'][$kode_barang]++;
        } elseif ($action === 'decrease' && $jumlah > 1) {
            $_SESSION['keranjang'][$kode_barang]--;
        } elseif ($action === 'remove') {
            unset($_SESSION['keranjang'][$kode_barang]);
        }
    } else {
        $response['message'] = "Kondisi tidak cocok (Guest mencoba mengubah ID DB atau sebaliknya).";
        echo json_encode($response);
        exit();
    }

    // --- Menghitung ulang total untuk dikirim kembali ---
    $total_item_count = 0;
    $grand_total_price = 0;
    $keranjang_items_recalculated = [];

    if ($is_user_logged_in) {
        $res = $koneksi->query("SELECT k.id, k.jumlah, b.harga 
                                FROM tb_keranjang k 
                                JOIN tb_barang b ON k.barang_id = b.id 
                                WHERE k.user_id = $user_id");
        while ($item = $res->fetch_assoc()) {
            $total_item_count += $item['jumlah'];
            $grand_total_price += $item['harga'] * $item['jumlah'];
            $keranjang_items_recalculated[$item['id']] = [
                'new_quantity' => $item['jumlah'],
                'new_subtotal' => $item['harga'] * $item['jumlah']
            ];
        }
    } else {
        if (isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
            $kode_barangs = array_keys($_SESSION['keranjang']);
            $placeholders = implode(',', array_fill(0, count($kode_barangs), '?'));
            $stmt = $koneksi->prepare("SELECT kode_barang, harga FROM tb_barang WHERE kode_barang IN ($placeholders)");
            $stmt->bind_param(str_repeat('s', count($kode_barangs)), ...$kode_barangs);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($item = $res->fetch_assoc()) {
                $qty = $_SESSION['keranjang'][$item['kode_barang']];
                $total_item_count += $qty;
                $grand_total_price += $item['harga'] * $qty;
                $keranjang_items_recalculated[$item['kode_barang']] = [
                    'new_quantity' => $qty,
                    'new_subtotal' => $item['harga'] * $qty
                ];
            }
        }
    }

    $koneksi->commit();

    $response = [
        'status' => 'success',
        'message' => 'Keranjang diperbarui.',
        'total_item_count' => $total_item_count,
        'grand_total_price' => number_format($grand_total_price, 0, ',', '.'),
        'items' => $keranjang_items_recalculated
    ];
} catch (Exception $e) {
    $koneksi->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
exit();
?>
