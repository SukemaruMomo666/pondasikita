<?php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// --- PROSES TAMBAH ZONA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_zona'])) {
    $nama_zona = trim($_POST['nama_zona']);
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($nama_zona)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Nama zona tidak boleh kosong.'];
    } else {
        $sql = "INSERT INTO tb_zona_pengiriman (nama_zona, deskripsi) VALUES (?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ss", $nama_zona, $deskripsi);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Zona pengiriman berhasil ditambahkan.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menambahkan zona: ' . $stmt->error];
        }
        $stmt->close();
    }
}

// --- PROSES HAPUS ZONA ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'hapus') {
    if (!isset($_GET['id'])) {
        die("ID Zona tidak valid.");
    }
    $zona_id = (int)$_GET['id'];

    // Di aplikasi nyata, tambahkan validasi apakah zona ini sedang digunakan
    
    $sql = "DELETE FROM tb_zona_pengiriman WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $zona_id);

    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Zona berhasil dihapus.'];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menghapus zona.'];
    }
    $stmt->close();
}

header("Location: ../pengaturan_zona.php");
exit;
?>
