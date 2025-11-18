<?php
session_start();
require_once '../../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    die("Aksi atau ID Produk Flash Sale tidak valid.");
}

$fsp_id = (int)$_GET['id']; // ID dari tabel tb_flash_sale_produk
$action = $_GET['action'];

// Ambil event_id untuk redirect kembali ke halaman yang benar
$stmt_get_event = $koneksi->prepare("SELECT event_id FROM tb_flash_sale_produk WHERE id = ?");
$stmt_get_event->bind_param("i", $fsp_id);
$stmt_get_event->execute();
$result_event = $stmt_get_event->get_result();
if ($result_event->num_rows === 0) {
    die("Produk Flash Sale tidak ditemukan.");
}
$event_id = $result_event->fetch_assoc()['event_id'];
$stmt_get_event->close();

$redirect_url = "../detail_flash_sale.php?id=" . $event_id;

// --- PROSES AKSI ---
$new_status = '';
if ($action === 'approve') {
    $new_status = 'approved';
    $pesan_sukses = 'Produk berhasil disetujui untuk Flash Sale.';
} elseif ($action === 'reject') {
    $new_status = 'rejected';
    $pesan_sukses = 'Produk berhasil ditolak dari Flash Sale.';
} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Aksi tidak valid.'];
    header("Location: " . $redirect_url);
    exit;
}

$sql = "UPDATE tb_flash_sale_produk SET status_moderasi = ? WHERE id = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("si", $new_status, $fsp_id);

if ($stmt->execute()) {
    $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => $pesan_sukses];
} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal memproses moderasi: ' . $stmt->error];
}
$stmt->close();

header("Location: " . $redirect_url);
exit;
?>
