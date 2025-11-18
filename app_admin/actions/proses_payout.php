<?php
session_start();
require_once '../../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['payout_id']) || !isset($_POST['action'])) {
    die("Akses tidak sah.");
}

$payout_id = (int)$_POST['payout_id'];
$action = $_POST['action'];
$redirect_url = '../kelola_payout.php?status=pending'; // Default redirect

// --- PROSES AKSI ---
if ($action === 'approve') {
    $new_status = 'completed';
    $tanggal_proses = date('Y-m-d H:i:s'); // Waktu saat ini
    $sql = "UPDATE tb_payouts SET status = ?, tanggal_proses = ? WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ssi", $new_status, $tanggal_proses, $payout_id);
    
    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Payout berhasil diproses.'];
        $redirect_url = '../kelola_payout.php?status=completed';
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal memproses payout: ' . $stmt->error];
    }
    $stmt->close();

} elseif ($action === 'reject') {
    $catatan = trim($_POST['catatan_admin'] ?? '');
    if (empty($catatan)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Alasan penolakan wajib diisi.'];
        header("Location: " . $redirect_url);
        exit;
    }

    $new_status = 'rejected';
    $tanggal_proses = date('Y-m-d H:i:s');
    $sql = "UPDATE tb_payouts SET status = ?, tanggal_proses = ?, catatan_admin = ? WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("sssi", $new_status, $tanggal_proses, $catatan, $payout_id);
    
    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Payout berhasil ditolak.'];
        $redirect_url = '../kelola_payout.php?status=rejected';
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menolak payout: ' . $stmt->error];
    }
    $stmt->close();

} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Aksi tidak valid.'];
}

header("Location: " . $redirect_url);
exit;
?>
