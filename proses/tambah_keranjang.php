<?php
// actions/tambah_keranjang.php (atau nama file action kamu)

// Selalu mulai session di baris paling atas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../config/koneksi.php';

// Atur header untuk memberitahu browser bahwa outputnya adalah JSON
header('Content-Type: application/json');

// --- DETEKSI USER ID (PERBAIKAN UTAMA) ---
$current_user_id = null;
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id']; // Format Baru
} elseif (isset($_SESSION['user']['id'])) {
    $current_user_id = $_SESSION['user']['id']; // Format Lama (Jaga-jaga)
}
// -----------------------------------------

// Fungsi untuk mengirim respon JSON dan menghentikan skrip
function json_response($status, $message, $data = []) {
    global $koneksi, $current_user_id; // Ambil variabel user_id dari luar
    
    $new_cart_count = 0;
    
    // LOGIKA HITUNG KERANJANG DIPERBAIKI
    if ($current_user_id) {
        // Jika User Login: Hitung dari Database
        $stmt_count = $koneksi->prepare("SELECT SUM(jumlah) as total FROM tb_keranjang WHERE user_id = ?");
        $stmt_count->bind_param("i", $current_user_id);
        $stmt_count->execute();
        $res = $stmt_count->get_result()->fetch_assoc();
        $new_cart_count = (int)($res['total'] ?? 0);
        $stmt_count->close();
    } else {
        // Jika Tamu: Hitung dari Session
        if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
            $new_cart_count = array_sum($_SESSION['keranjang']);
        }
    }

    $response = ['status' => $status, 'message' => $message, 'new_cart_count' => $new_cart_count];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// 1. Validasi Metode Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response('error', 'Metode tidak valid.');
}

// 2. Validasi Input dari Form
$barang_id = isset($_POST['barang_id']) ? intval($_POST['barang_id']) : 0;
$jumlah_diminta = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : 0;

if ($barang_id <= 0 || $jumlah_diminta <= 0) {
    json_response('error', 'Input produk atau jumlah tidak valid.');
}

// Gunakan transaksi untuk menjaga integritas data
$koneksi->begin_transaction();

try {
    // 3. Ambil data produk (Lock baris ini untuk mencegah race condition)
    $stmt_cek_barang = $koneksi->prepare("SELECT id, stok, stok_di_pesan, kode_barang FROM tb_barang WHERE id = ? FOR UPDATE");
    $stmt_cek_barang->bind_param("i", $barang_id);
    $stmt_cek_barang->execute();
    $result_barang = $stmt_cek_barang->get_result();

    if ($result_barang->num_rows === 0) {
        throw new Exception('Produk tidak ditemukan di database.');
    }

    $barang = $result_barang->fetch_assoc();
    $stok_tersedia_total = $barang['stok'] - $barang['stok_di_pesan'];
    $kode_barang = $barang['kode_barang'];
    $stmt_cek_barang->close();

    // 4. Logika Penambahan ke Keranjang
    if ($current_user_id) {
        // --- LOGIKA UNTUK USER YANG SUDAH LOGIN (MASUK DB) ---
        
        $stmt_cek_keranjang = $koneksi->prepare("SELECT id, jumlah FROM tb_keranjang WHERE user_id = ? AND barang_id = ? FOR UPDATE");
        $stmt_cek_keranjang->bind_param("ii", $current_user_id, $barang_id);
        $stmt_cek_keranjang->execute();
        $result_keranjang = $stmt_cek_keranjang->get_result();

        $jumlah_di_keranjang = 0;
        $keranjang_item = null;
        if ($result_keranjang->num_rows > 0) {
            $keranjang_item = $result_keranjang->fetch_assoc();
            $jumlah_di_keranjang = $keranjang_item['jumlah'];
        }

        // Validasi Stok
        if (($jumlah_di_keranjang + $jumlah_diminta) > $stok_tersedia_total) {
            $sisa_bisa_ditambah = $stok_tersedia_total - $jumlah_di_keranjang;
            throw new Exception('Stok tidak mencukupi! Anda hanya bisa menambah ' . max(0, $sisa_bisa_ditambah) . ' item lagi.');
        }

        if ($keranjang_item) {
            // Jika produk sudah ada, UPDATE jumlahnya
            $jumlah_baru = $keranjang_item['jumlah'] + $jumlah_diminta;
            $stmt_update = $koneksi->prepare("UPDATE tb_keranjang SET jumlah = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $jumlah_baru, $keranjang_item['id']);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Jika produk belum ada, INSERT baris baru
            $stmt_insert = $koneksi->prepare("INSERT INTO tb_keranjang (user_id, barang_id, jumlah) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iii", $current_user_id, $barang_id, $jumlah_diminta);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_cek_keranjang->close();
        
    } else {
        // --- LOGIKA UNTUK TAMU / BELUM LOGIN (MASUK SESSION) ---
        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }
        
        $jumlah_di_keranjang = isset($_SESSION['keranjang'][$kode_barang]) ? $_SESSION['keranjang'][$kode_barang] : 0;
        
        // Validasi Stok
        if (($jumlah_di_keranjang + $jumlah_diminta) > $stok_tersedia_total) {
            $sisa_bisa_ditambah = $stok_tersedia_total - $jumlah_di_keranjang;
            throw new Exception('Stok tidak mencukupi! Anda hanya bisa menambah ' . max(0, $sisa_bisa_ditambah) . ' item lagi.');
        }
        
        // Jika lolos validasi, tambahkan ke session
        $_SESSION['keranjang'][$kode_barang] = $jumlah_di_keranjang + $jumlah_diminta;
    }

    $koneksi->commit();
    json_response('success', 'Produk berhasil ditambahkan ke keranjang!');
    
} catch (Exception $e) {
    $koneksi->rollback();
    json_response('error', $e->getMessage());
}
?>