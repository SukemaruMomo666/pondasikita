<?php
session_start();
require_once '../../config/koneksi.php'; // Path disesuaikan, naik 2 level dari actions

// --- PENGAMANAN & VALIDASI ---
// Pastikan hanya admin yang login yang bisa mengakses
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    // Jika bukan admin, bisa diarahkan ke halaman utama atau halaman error
    header("Location: /index.php");
    exit;
}

// Pastikan parameter yang dibutuhkan ada
if (!isset($_GET['toko_id']) || !isset($_GET['action'])) {
    die("Aksi atau ID Toko tidak valid.");
}

$toko_id = (int)$_GET['toko_id'];
$action = $_GET['action'];

// --- PROSES AKSI ---
if ($action === 'setujui') {
    // Ubah status toko menjadi 'active'
    $new_status = 'active';
    $sql = "UPDATE tb_toko SET status = ? WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("si", $new_status, $toko_id);
    
    if ($stmt->execute()) {
        // Jika berhasil, set pesan sukses
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Toko berhasil disetujui.'];
    } else {
        // Jika gagal, set pesan error
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menyetujui toko: ' . $stmt->error];
    }
    $stmt->close();

} elseif ($action === 'tolak') {
    // Untuk aksi 'tolak', kita bisa menghapus data toko yang masih pending
    // Alternatif lain adalah mengubah statusnya menjadi 'rejected' jika ada di database
    $sql = "DELETE FROM tb_toko WHERE id = ? AND status = 'pending'";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $toko_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Pengajuan toko berhasil ditolak.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Toko tidak ditemukan atau sudah tidak dalam status pending.'];
        }
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menolak toko: ' . $stmt->error];
    }
    $stmt->close();

} else {
    // Jika aksi tidak dikenali
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Aksi tidak valid.'];
}

// Kembalikan admin ke halaman kelola toko
header("Location: ../kelola_toko.php?status=pending");
exit;
?>
