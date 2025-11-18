<?php
// api/chat/check_user_session.php
// AKTIFKAN ERROR REPORTING UNTUK DEBUGGING (HAPUS DI LINGKUNGAN PRODUKSI!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // <<< PASTIKAN INI ADA DI BARIS PERTAMA, TANPA SPASI ATAU BARIS KOSONG SEBELUMNYA

header('Content-Type: application/json');
require_once('config.php'); // Include config chat yang berisi helper functions

$response = ['success' => false, 'user_id' => null, 'user_role' => 'customer', 'message' => 'Not logged in'];

// DEBUGGING: Tampilkan isi dari $_SESSION
error_log("DEBUG check_user_session.php: Session data: " . print_r($_SESSION, true));

if (isset($_SESSION['user']['id'])) { // MENGGUNAKAN $_SESSION['user']['id'] yang sudah diperbaiki di proses_login.php
    $response['success'] = true;
    $response['user_id'] = $_SESSION['user']['id'];
    $response['user_role'] = $_SESSION['user']['level'] ?? 'customer'; // Mengambil 'level' dari sesi
    $response['message'] = 'User is logged in';
    
    // Panggil updateOnlineStatus hanya jika koneksi valid
    if (isset($koneksi) && $koneksi instanceof mysqli) {
        updateOnlineStatus($koneksi, $_SESSION['user']['id'], 'online');
    } else {
        error_log("ERROR check_user_session.php: Koneksi database tidak tersedia untuk updateOnlineStatus.");
    }
}
// Bagian guest sudah dikomentari, biarkan seperti itu
/*
else if (isset($_SESSION['guest_id'])) { // Fallback untuk guest (jika diaktifkan)
    $response['success'] = true;
    $response['user_id'] = $_SESSION['guest_id'];
    $response['user_role'] = 'customer';
    $response['message'] = 'Guest is logged in';
    if (isset($koneksi) && $koneksi instanceof mysqli) {
        updateOnlineStatus($koneksi, $_SESSION['guest_id'], 'online');
    } else {
        error_log("ERROR check_user_session.php: Koneksi database tidak tersedia untuk updateOnlineStatus (guest).");
    }
}
*/

echo json_encode($response);
exit();