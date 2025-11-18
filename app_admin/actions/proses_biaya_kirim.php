<?php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$zona_id = $_POST['zona_id'] ?? $_GET['zona_id'] ?? null;
if (!$zona_id) {
    die("ID Zona tidak ditemukan.");
}

// --- PROSES TAMBAH BIAYA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_biaya'])) {
    $tipe_biaya = $_POST['tipe_biaya'];
    $biaya = $_POST['biaya'];
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($biaya)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Biaya tidak boleh kosong.'];
    } else {
        $sql = "INSERT INTO tb_biaya_pengiriman (zona_id, tipe_biaya, biaya, deskripsi) VALUES (?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("isds", $zona_id, $tipe_biaya, $biaya, $deskripsi);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Tarif pengiriman berhasil ditambahkan.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menambahkan tarif: ' . $stmt->error];
        }
        $stmt->close();
    }
}

// --- PROSES HAPUS BIAYA (bisa ditambahkan nanti) ---
// ...

header("Location: ../pengaturan_biaya_kirim.php?zona_id=" . $zona_id);
exit;
?>
