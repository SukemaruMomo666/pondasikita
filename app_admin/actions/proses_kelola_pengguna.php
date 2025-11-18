<?php
session_start();
require_once '../../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if (!isset($_GET['user_id']) || !isset($_GET['action'])) {
    die("Aksi atau ID Pengguna tidak valid.");
}

$user_id_to_change = (int)$_GET['user_id'];
$action = $_GET['action'];
$current_admin_id = $_SESSION['user_id'];

// Keamanan tambahan: Admin tidak bisa mem-banned dirinya sendiri
if ($user_id_to_change === $current_admin_id) {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Anda tidak dapat mengubah status akun Anda sendiri.'];
    header("Location: ../kelola_pengguna.php");
    exit;
}

// --- PROSES AKSI ---
$new_banned_status = -1; // Default value

if ($action === 'ban') {
    $new_banned_status = 1; // 1 = Dibanned
} elseif ($action === 'unban') {
    $new_banned_status = 0; // 0 = Tidak dibanned / Aktif
} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Aksi tidak valid.'];
    header("Location: ../kelola_pengguna.php");
    exit;
}

// Update status 'is_banned' di database
$sql = "UPDATE tb_user SET is_banned = ? WHERE id = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ii", $new_banned_status, $user_id_to_change);

if ($stmt->execute()) {
    $pesan_sukses = ($action === 'ban') ? 'Pengguna berhasil dibanned.' : 'Pengguna berhasil diaktifkan kembali.';
    $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => $pesan_sukses];
} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal memperbarui status pengguna: ' . $stmt->error];
}
$stmt->close();

// Kembalikan admin ke halaman kelola pengguna
header("Location: ../kelola_pengguna.php");
exit;
?>
