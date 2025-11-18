<?php
session_start();
require_once '../config/koneksi.php';
header('Content-Type: application/json'); // Set header untuk respons JSON

// --- Keamanan & Pengambilan Data Toko ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login sebagai penjual.']);
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];
$stmt_toko->close();
if (!$toko_id) {
    echo json_encode(['status' => 'error', 'message' => 'Data toko tidak ditemukan.']);
    exit;
}

// --- Memproses Form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Ambil semua data dari form
    $voucherId = $_POST['voucherId'] ?? null;
    $deskripsi = $_POST['deskripsi'];
    $tipe_diskon = $_POST['tipe_diskon'];
    $nilai_diskon = $_POST['nilai_diskon'];
    $maks_diskon = !empty($_POST['maks_diskon']) ? $_POST['maks_diskon'] : NULL;
    $min_pembelian = $_POST['min_pembelian'];
    $kuota = $_POST['kuota'];
    $tanggal_berakhir = date('Y-m-d H:i:s', strtotime($_POST['tanggal_berakhir'] . ' 23:59:59'));
    $kode_voucher = $_POST['kode_voucher'] ?? ('VCR' . strtoupper(bin2hex(random_bytes(4))));

    if ($action === 'tambah') {
        $tanggal_mulai = date('Y-m-d H:i:s');
        $sql = "INSERT INTO vouchers (toko_id, kode_voucher, deskripsi, tipe_diskon, nilai_diskon, maks_diskon, min_pembelian, kuota, tanggal_mulai, tanggal_berakhir, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AKTIF')";
        
        $stmt = $koneksi->prepare($sql);
        // PERBAIKAN: Mengganti '...' dengan variabel yang benar
        $stmt->bind_param('isssddiiss', $toko_id, $kode_voucher, $deskripsi, $tipe_diskon, $nilai_diskon, $maks_diskon, $min_pembelian, $kuota, $tanggal_mulai, $tanggal_berakhir);
        $pesanSukses = 'Voucher baru berhasil disimpan!';

    } elseif ($action === 'update' && !empty($voucherId)) {
        $sql = "UPDATE vouchers SET deskripsi = ?, tipe_diskon = ?, nilai_diskon = ?, maks_diskon = ?, min_pembelian = ?, kuota = ?, tanggal_berakhir = ? 
                WHERE id = ? AND toko_id = ?"; // Keamanan: Pastikan seller hanya edit vouchernya sendiri
        
        $stmt = $koneksi->prepare($sql);
        // PERBAIKAN: Mengganti '...' dengan variabel yang benar
        $stmt->bind_param('ssddisii', $deskripsi, $tipe_diskon, $nilai_diskon, $maks_diskon, $min_pembelian, $kuota, $tanggal_berakhir, $voucherId, $toko_id);
        $pesanSukses = 'Voucher berhasil diperbarui!';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
        exit;
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => $pesanSukses]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database: ' . $stmt->error]);
    }
    $stmt->close();
    
} elseif (isset($_GET['hapus'])) {
    $voucher_id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM vouchers WHERE id = ? AND toko_id = ?");
    $stmt->bind_param("ii", $voucher_id, $toko_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ../app_seller/voucher.php?status=hapus_sukses");
    exit;
}

$koneksi->close();
?>