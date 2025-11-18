<?php
session_start();
require_once '../../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID Toko tidak valid.");
}

$toko_id = (int)$_GET['id'];

// --- PROSES AKSI ---
// 1. Cek dulu status toko saat ini
$stmt_check = $koneksi->prepare("SELECT status FROM tb_toko WHERE id = ?");
$stmt_check->bind_param("i", $toko_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $current_status = $result_check->fetch_assoc()['status'];
    
    // 2. Tentukan status baru berdasarkan status saat ini
    $new_status = '';
    if ($current_status === 'active') {
        $new_status = 'suspended';
        $pesan_sukses = 'Toko berhasil ditangguhkan (suspended).';
    } elseif ($current_status === 'suspended') {
        $new_status = 'active';
        $pesan_sukses = 'Toko berhasil diaktifkan kembali.';
    } else {
        // Jika statusnya 'pending', jangan lakukan apa-apa dari halaman ini
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Aksi tidak dapat dilakukan pada toko dengan status pending.'];
        header("Location: ../detail_toko.php?id=" . $toko_id);
        exit;
    }

    // 3. Update status toko di database
    $sql_update = "UPDATE tb_toko SET status = ? WHERE id = ?";
    $stmt_update = $koneksi->prepare($sql_update);
    $stmt_update->bind_param("si", $new_status, $toko_id);

    if ($stmt_update->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => $pesan_sukses];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal memperbarui status toko: ' . $stmt_update->error];
    }
    $stmt_update->close();

} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Toko tidak ditemukan.'];
}

$stmt_check->close();

// Kembalikan admin ke halaman detail toko yang sama
header("Location: ../detail_toko.php?id=" . $toko_id);
exit;
?>
