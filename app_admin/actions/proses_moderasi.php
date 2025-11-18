<?php
session_start();
require_once '../../config/koneksi.php'; // Path disesuaikan, naik 2 level dari actions

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// --- PROSES AKSI BERDASARKAN METODE (GET untuk setujui, POST untuk tolak) ---

// Proses Aksi Setujui (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
    if (!isset($_GET['id'])) {
        die("ID Produk tidak valid.");
    }
    $produk_id = (int)$_GET['id'];
    
    $new_status = 'approved';
    $sql = "UPDATE tb_barang SET status_moderasi = ? WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("si", $new_status, $produk_id);
    
    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Produk berhasil disetujui.'];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menyetujui produk: ' . $stmt->error];
    }
    $stmt->close();

    // Kembalikan ke halaman moderasi
    header("Location: ../moderasi_produk.php?status=pending");
    exit;
}

// Proses Aksi Tolak (via POST dari modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    if (!isset($_POST['produk_id']) || !isset($_POST['alasan_penolakan'])) {
        die("Data penolakan tidak lengkap.");
    }
    $produk_id = (int)$_POST['produk_id'];
    $alasan = trim($_POST['alasan_penolakan']);
    
    if(empty($alasan)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Alasan penolakan wajib diisi.'];
        header("Location: ../moderasi_produk.php?status=pending");
        exit;
    }

    $new_status = 'rejected';
    $sql = "UPDATE tb_barang SET status_moderasi = ?, alasan_penolakan = ? WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ssi", $new_status, $alasan, $produk_id);

    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Produk berhasil ditolak.'];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menolak produk: ' . $stmt->error];
    }
    $stmt->close();

    // Kembalikan ke halaman moderasi
    header("Location: ../moderasi_produk.php?status=pending");
    exit;
}

// Jika tidak ada aksi yang cocok, arahkan kembali
header("Location: ../moderasi_produk.php");
exit;
?>
