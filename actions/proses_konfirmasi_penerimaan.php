<?php
session_start();
include '../config/koneksi.php';

// Atur header untuk output JSON
header('Content-Type: application/json');

// Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

// Keamanan: Pastikan request adalah POST dan pesanan_id dikirim
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['transaksi_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Request tidak valid.']);
    exit;
}

$id_user_login = (int)$_SESSION['user']['id'];
$id_transaksi  = (int)$_POST['transaksi_id'];

// PERBAIKAN UTAMA ADA DI SINI:
// Gunakan 'Dikirim' bukan 'sedang di kirim' di klausa WHERE.
// Query ini akan mengubah status menjadi 'Selesai' HANYA JIKA status saat ini adalah 'Dikirim'
// dan pesanan tersebut milik user yang sedang login.
// Query diubah untuk mengupdate tb_transaksi dan juga mencatat tanggal selesai
$query = "UPDATE tb_transaksi 
          SET status_pesanan = 'Selesai', tanggal_selesai = NOW()
          WHERE id = ? 
            AND user_id = ? 
            AND status_pesanan = 'Dikirim'";

$stmt = $koneksi->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement database.']);
    exit;
}

$stmt->bind_param("ii", $id_transaksi, $id_user_login);
$stmt->execute();

// Periksa apakah query UPDATE berhasil mempengaruhi baris di database
if ($stmt->affected_rows > 0) {
    // Jika berhasil (ada baris yang diubah), kirim pesan sukses
    echo json_encode(['status' => 'success', 'message' => 'Terima kasih! Pesanan Anda telah ditandai sebagai Selesai.']);
} else {
    // Jika gagal (tidak ada baris yang cocok/diubah), kirim pesan error
    // Ini terjadi jika pesanan bukan milik user atau statusnya BUKAN 'Dikirim'
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status. Pesanan mungkin sudah selesai atau belum dikirim.']);
}

$stmt->close();
$koneksi->close();