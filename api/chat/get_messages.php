<?php
// api/chat/get_messages.php
header('Content-Type: application/json');
require_once('config.php');// Menggunakan koneksi dan fungsi dari config.php

$response = ['success' => false, 'messages' => [], 'agent_status' => 'offline', 'typing_status' => null, 'message' => ''];

$chat_id = $_GET['chat_id'] ?? null;
$last_message_id = $_GET['last_message_id'] ?? 0; // last_message_id dari client

$logged_in_user_id = getLoggedInUserId($koneksi);
$logged_in_user_level = getLoggedInUserRole($koneksi);

if (empty($chat_id)) {
    $response['message'] = 'Chat ID tidak valid.';
    echo json_encode($response);
    exit();
}

// Security check: Pastikan user yang meminta pesan adalah customer di chat ini,
// atau admin (dari panel admin) yang melihat chat bot.
$stmt_check_chat = $koneksi->prepare("SELECT customer_id, admin_id, status FROM chats WHERE id = ?");
if (!$stmt_check_chat) {
    error_log("Prepare statement gagal di get_messages (chat check): " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt_check_chat->bind_param("i", $chat_id);
$stmt_check_chat->execute();
$chat_info = $stmt_check_chat->get_result()->fetch_assoc();
$stmt_check_chat->close();

if (!$chat_info || 
    ($logged_in_user_level === 'customer' && $chat_info['customer_id'] != $logged_in_user_id) || 
    // PERUBAHAN UNTUK AI SATU ARAH: Admin hanya bisa melihat jika admin_id di chat adalah BOT_USER_ID
    ($logged_in_user_level === 'admin' && $chat_info['admin_id'] != BOT_USER_ID) 
    ) {
    $response['message'] = 'Akses ditolak ke chat ini.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

// 1. Ambil pesan baru
$stmt = $koneksi->prepare("SELECT m.id, m.message_text, m.timestamp, u.level as sender_role FROM messages m JOIN tb_user u ON m.sender_id = u.id WHERE m.chat_id = ? AND m.id > ? ORDER BY m.timestamp ASC");
if (!$stmt) {
    error_log("Prepare statement gagal di get_messages (messages query): " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt->bind_param("ii", $chat_id, $last_message_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    // PERBAIKAN: Gunakan BOT_USER_ID untuk menentukan sender_role sebagai 'bot' jika sender_id == BOT_USER_ID
    if ($row['sender_role'] === 'bot') { // Jika level di DB adalah 'bot'
        $row['sender_role'] = 'bot';
    } else if ($row['sender_role'] === 'customer') {
        $row['sender_role'] = 'customer';
    } else { // Jika level adalah 'admin' (jarang terjadi di sini, tapi untuk keamanan)
        $row['sender_role'] = 'admin';
    }
    $messages[] = $row;
}
$stmt->close();
$response['messages'] = $messages;

// 2. Ambil status agen/admin yang bertanggung jawab atas chat ini, yang sekarang adalah bot
$agent_id = $chat_info['admin_id']; // Ini seharusnya selalu BOT_USER_ID jika chat_info berasal dari start_chat
$agent_info = getUserInfo($koneksi, $agent_id);

if ($agent_info) {
    $response['agent_status'] = $agent_info['status']; // Status bot (online/offline/typing)
    if ($agent_info['status'] === 'typing') {
        $response['typing_status'] = 'typing';
    }
} else {
    $response['agent_status'] = 'offline'; // Fallback jika info bot tidak ditemukan
}

$response['success'] = true;
echo json_encode($response);
exit();