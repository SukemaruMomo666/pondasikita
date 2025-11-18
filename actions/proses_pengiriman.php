<?php
session_start();
require_once '../config/koneksi.php'; // Path ke koneksi.php

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php"); 
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); 
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'] ?? null; // Gunakan null coalescing operator
$stmt_toko->close();

if (!$toko_id) {
    // Jika toko tidak ditemukan, kirim respons error JSON jika ini adalah AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => 'Data toko tidak ditemukan. Silakan login kembali.']);
        exit;
    } else {
        // Jika bukan AJAX, arahkan ke login atau tampilkan pesan error
        header("Location: ../auth/login.php?error=toko_tidak_valid");
        exit;
    }
}

// ===========================================
// --- Aksi dari Form POST (Tambah / Update) ---
// ===========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // --- Aksi Toggle Status (khusus untuk AJAX dari toggle switch) ---
    if ($_POST['action'] == 'toggle_status') {
        header('Content-Type: application/json'); // Penting untuk respons JSON
        $kurir_id = (int)$_POST['kurir_id'];
        $is_active = (int)$_POST['is_active']; // 1 or 0

        $update_stmt = $koneksi->prepare("UPDATE tb_kurir_toko SET is_active = ? WHERE id = ? AND toko_id = ?");
        $update_stmt->bind_param("iii", $is_active, $kurir_id, $toko_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Status layanan berhasil diperbarui.']);
        } else {
            error_log("Error updating courier status: " . $update_stmt->error); // Log error untuk debugging
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status: ' . $update_stmt->error]);
        }
        $update_stmt->close();
        exit; // Hentikan eksekusi setelah AJAX request
    } 
    // --- Aksi Tambah atau Update dari Modal Form ---
    else {
        $nama_kurir = $_POST['nama_kurir'];
        $tipe_kurir = $_POST['tipe_kurir']; // Tambah kolom tipe_kurir
        $estimasi = $_POST['estimasi_waktu'];
        $biaya = (float)$_POST['biaya'];
        $is_active = isset($_POST['is_active']) ? 1 : 0; // Dari checkbox modal

        if ($_POST['action'] == 'tambah') {
            $stmt = $koneksi->prepare("INSERT INTO tb_kurir_toko (toko_id, nama_kurir, tipe_kurir, estimasi_waktu, biaya, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdi", $toko_id, $nama_kurir, $tipe_kurir, $estimasi, $biaya, $is_active);
        } elseif ($_POST['action'] == 'update') {
            $kurir_id = (int)$_POST['kurir_id'];
            $stmt = $koneksi->prepare("UPDATE tb_kurir_toko SET nama_kurir=?, tipe_kurir=?, estimasi_waktu=?, biaya=?, is_active=? WHERE id=? AND toko_id=?");
            $stmt->bind_param("sssdiii", $nama_kurir, $tipe_kurir, $estimasi, $biaya, $is_active, $kurir_id, $toko_id);
        }
        
        if ($stmt->execute()) {
             // Redirect ke halaman pengaturan pengiriman
            header("Location: ../pengaturan/pengiriman.php?status=sukses"); // Sesuaikan path redirect
        } else {
            // Jika ada error, Anda bisa arahkan kembali dengan pesan error
            header("Location: ../pengaturan/pengiriman.php?status=gagal&error=" . urlencode($stmt->error));
        }
        $stmt->close();
        exit;
    }
}

// =======================
// --- Aksi Hapus dari GET ---
// =======================
if (isset($_GET['hapus'])) {
    $kurir_id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM tb_kurir_toko WHERE id = ? AND toko_id = ?");
    $stmt->bind_param("ii", $kurir_id, $toko_id);
    
    if ($stmt->execute()) {
        header("Location: ../pengaturan/pengiriman.php?status=hapus_sukses"); // Sesuaikan path redirect
    } else {
        header("Location: ../pengaturan/pengiriman.php?status=hapus_gagal&error=" . urlencode($stmt->error));
    }
    $stmt->close();
    exit;
}

// Redirect default jika tidak ada aksi yang cocok
header("Location: ../pengaturan/pengiriman.php"); // Sesuaikan path redirect
exit;
?>