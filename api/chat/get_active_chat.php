<?php
// api/chat/get_active_chat.php
header('Content-Type: application/json');
require_once('config.php');// Menggunakan koneksi dan fungsi dari config.php

$response = ['success' => false, 'chat_id' => null, 'last_message_id' => 0, 'message' => ''];

$user_id = getLoggedInUserId($koneksi); // Mengakses dari $_SESSION['user']['id']

if (empty($user_id)) {
    $response['message'] = 'User ID tidak ditemukan atau belum login.';
    echo json_encode($response);
    exit();
}

// PERUBAHAN UNTUK AI SATU ARAH: Hanya cari chat yang ditangani oleh BOT_USER_ID atau status 'open' yang akan segera beralih ke bot.
// Asumsi 'open' jika ada admin manusia, 'in_progress_bot' jika ditangani bot.
// Karena kita mau 1 arah AI, kita fokus ke in_progress_bot.
$stmt = $koneksi->prepare("SELECT id FROM chats WHERE customer_id = ? AND (admin_id = ? AND status = 'in_progress_bot') ORDER BY start_time DESC LIMIT 1");
if (!$stmt) {
    error_log("Prepare statement gagal di get_active_chat: " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$bot_id = BOT_USER_ID; // Pastikan BOT_USER_ID didefinisikan di config.php
$stmt->bind_param("ii", $user_id, $bot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $chat = $result->fetch_assoc();
    $response['chat_id'] = $chat['id'];

    // Ambil ID pesan terakhir untuk chat ini
    $stmt_msg = $koneksi->prepare("SELECT MAX(id) as last_id FROM messages WHERE chat_id = ?");
    if (!$stmt_msg) {
        error_log("Prepare statement gagal di get_active_chat (last_message_id): " . $koneksi->error);
        $response['message'] = 'Internal server error.';
        echo json_encode($response);
        exit();
    }
    $stmt_msg->bind_param("i", $chat['id']);
    $stmt_msg->execute();
    $result_msg = $stmt_msg->get_result();
    $last_msg = $result_msg->fetch_assoc();
    $response['last_message_id'] = $last_msg['last_id'] ?? 0;
    $stmt_msg->close();

    $response['success'] = true;
} else {
    $response['message'] = 'Tidak ada chat aktif.';
}

$stmt->close();
echo json_encode($response);
exit();