<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Jalur yang benar untuk koneksi.php relatif dari proses_batal_pesanan.php
// Jika proses_batal_pesanan.php ada di app_customer/proses/, maka naik dua level ke root 'pondasikita'
// lalu masuk ke folder 'config'
require '../config/koneksi.php';

// --- Keamanan & Validasi Awal ---
if (!isset($_SESSION['user']['id'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Metode request tidak valid.");
}
if (!isset($_POST['transaksi_id']) || !is_numeric($_POST['transaksi_id'])) {
    die("ID transaksi tidak valid.");
}

$id_user_login = (int)$_SESSION['user']['id'];
$id_transaksi = (int)$_POST['transaksi_id'];

// Mulai transaksi database untuk memastikan semua proses berhasil atau tidak sama sekali
$koneksi->begin_transaction();

try {
    // --- Langkah 1: Ambil data pesanan & pastikan pesanan ini milik user yang login ---
    // Menggunakan 'status_pesanan_global'
    $stmt_cek = $koneksi->prepare("SELECT kode_invoice, status_pesanan_global, voucher_digunakan FROM tb_transaksi WHERE id = ? AND user_id = ?");
    $stmt_cek->bind_param("ii", $id_transaksi, $id_user_login);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows === 0) {
        throw new Exception("Transaksi tidak ditemukan atau Anda tidak memiliki hak akses.");
    }
    $transaksi = $result_cek->fetch_assoc();

    // --- Langkah 2: Validasi apakah pesanan masih bisa dibatalkan ---
    // Menggunakan 'status_pesanan_global' dan nilai enum huruf kecil 'menunggu_pembayaran'
    if (strtolower($transaksi['status_pesanan_global']) !== 'menunggu_pembayaran') {
        throw new Exception("Pesanan ini tidak dapat dibatalkan karena sudah diproses atau selesai.");
    }

    // --- Langkah 3: Ambil semua item di pesanan untuk mengembalikan stok ---
    $stmt_items = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = ?");
    $stmt_items->bind_param("i", $id_transaksi);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $items_to_restore = [];
    while ($item = $result_items->fetch_assoc()) {
        $items_to_restore[] = $item;
    }

    // --- Langkah 4: Kembalikan stok yang di-tahan (stok_di_pesan) ---
    if (!empty($items_to_restore)) {
        $stmt_stok = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
        foreach ($items_to_restore as $item) {
            $stmt_stok->bind_param("ii", $item['jumlah'], $item['barang_id']);
            $stmt_stok->execute();
        }
    }

    // --- Langkah 5: Kembalikan kuota voucher jika digunakan ---
    if (!empty($transaksi['voucher_digunakan'])) {
        $stmt_voucher = $koneksi->prepare("UPDATE vouchers SET kuota_terpakai = kuota_terpakai - 1 WHERE kode_voucher = ? AND kuota_terpakai > 0");
        $stmt_voucher->bind_param("s", $transaksi['voucher_digunakan']);
        $stmt_voucher->execute();
    }

    // --- Langkah 6: Update status pesanan menjadi "Dibatalkan" ---
    // Menggunakan 'status_pesanan_global' dan nilai enum huruf kecil 'dibatalkan'
    $stmt_batal = $koneksi->prepare("UPDATE tb_transaksi SET status_pesanan_global = 'dibatalkan', status_pembayaran = 'cancelled', snap_token = NULL WHERE id = ?");
    $stmt_batal->bind_param("i", $id_transaksi);
    $stmt_batal->execute();

    // Jika semua query berhasil, commit transaksi
    $koneksi->commit();

    // Redirect kembali ke halaman detail dengan pesan sukses
    header("Location: ../app_customer/pages/detail_pesanan.php?id=" . $id_transaksi . "&cancel_success=1");
    exit();

} catch (Exception $e) {
    // Jika ada error di salah satu langkah, batalkan semua perubahan
    $koneksi->rollback();
    die("Gagal membatalkan pesanan: " . $e->getMessage());
}
