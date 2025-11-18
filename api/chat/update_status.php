<?php
// api/chat/update_status.php
header('Content-Type: application/json');
require_once('config.php');// Menggunakan koneksi dan fungsi dari config.php

$response = ['success' => false, 'message' => ''];

$input = json_decode(file_get_contents('php://input'), true);
$user_id_from_client = $input['user_id'] ?? null; // ID user yang dikirim dari client
$status_type = $input['status_type'] ?? 'online'; 

$logged_in_user_id = getLoggedInUserId($koneksi); // ID user yang benar-benar login dari sesi

// Keamanan: Pastikan user_id yang dikirim dari client sesuai dengan user yang login
// Atau jika ini untuk BOT_USER_ID, maka perbolehkan jika user yang login adalah admin (misalnya dari panel admin yang update status bot)
if (empty($user_id_from_client) || ($user_id_from_client != $logged_in_user_id && $user_id_from_client != BOT_USER_ID) ) {
    $response['message'] = 'User ID tidak valid atau mismatch dengan sesi login.';
    header('HTTP/1.1 403 Forbidden'); 
    echo json_encode($response);
    exit();
}

// Dapatkan info user dari DB untuk memverifikasi role
$user_info = getUserInfo($koneksi, $user_id_from_client);
if (!$user_info) {
    $response['message'] = 'Pengguna tidak ditemukan di database.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

// PERBAIKAN: Fungsi updateOnlineStatus di config.php tidak lagi memiliki logic admin khusus
if (updateOnlineStatus($koneksi, $user_id_from_client, $status_type)) {
    $response['success'] = true;
    $response['message'] = 'Status berhasil diperbarui.';
} else {
    $response['message'] = 'Gagal memperbarui status di database.';
}

echo json_encode($response);
exit();