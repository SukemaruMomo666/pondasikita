<?php
// api/chat/start_chat.php
header('Content-Type: application/json');
require_once('config.php'); // Menggunakan koneksi dan fungsi dari config.php

$response = ['success' => false, 'message' => '', 'chat_id' => null];

$input = json_decode(file_get_contents('php://input'), true);
$customer_id = $input['customer_id'] ?? null;

$logged_in_user_id = getLoggedInUserId($koneksi);

// Security Check: Pastikan customer_id yang dikirim sesuai dengan user yang login
if (empty($customer_id) || $customer_id != $logged_in_user_id) {
    $response['message'] = 'Customer ID tidak valid atau mismatch dengan sesi login.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

// 1. Cek apakah ada chat aktif yang sudah ada untuk customer ini yang ditangani BOT
$stmt_check_active = $koneksi->prepare("SELECT id, status FROM chats WHERE customer_id = ? AND admin_id = ? AND status = 'in_progress_bot' ORDER BY start_time DESC LIMIT 1");
if (!$stmt_check_active) {
    error_log("Prepare statement gagal di start_chat (check active chat): " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$bot_id = BOT_USER_ID;
$stmt_check_active->bind_param("ii", $customer_id, $bot_id);
$stmt_check_active->execute();
$result_active = $stmt_check_active->get_result();

if ($result_active->num_rows > 0) {
    // Jika ada chat aktif dengan bot, gunakan chat yang sudah ada
    $active_chat = $result_active->fetch_assoc();
    $chat_id = $active_chat['id'];
    $response['success'] = true;
    $response['chat_id'] = $chat_id;
    $response['message'] = 'Menggunakan chat yang sudah aktif dengan bot.';
    echo json_encode($response);
    exit();
}
$stmt_check_active->close();

// PERUBAHAN UNTUK AI SATU ARAH: Selalu alihkan ke bot jika tidak ada chat aktif.
$admin_id = BOT_USER_ID; 
$chat_status = 'in_progress_bot'; // Status selalu ke bot

// 2. Buat sesi chat baru dengan bot
$stmt = $koneksi->prepare("INSERT INTO chats (customer_id, admin_id, status) VALUES (?, ?, ?)");
if (!$stmt) {
    error_log("Prepare statement gagal di start_chat (insert chat): " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt->bind_param("iis", $customer_id, $admin_id, $chat_status);

if ($stmt->execute()) {
    $chat_id = $stmt->insert_id;
    $response['success'] = true;
    $response['chat_id'] = $chat_id;
    $response['message'] = 'Chat baru dengan bot berhasil dimulai.';

    // Tambahkan pesan pembuka dari bot
    $welcome_message = "Halo! Selamat datang di Pondasikita. Saya Dodo, asisten virtual Anda. Saat ini admin sedang offline, jadi saya akan membantu Anda. Silakan ketik pertanyaan Anda.";
    
    $sender_id_welcome = BOT_USER_ID; // Pengirim pesan pembuka adalah bot
    
    $stmt_msg = $koneksi->prepare("INSERT INTO messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
    if (!$stmt_msg) {
        error_log("Prepare statement gagal di start_chat (insert welcome message): " . $koneksi->error);
    } else {
        $stmt_msg->bind_param("iis", $chat_id, $sender_id_welcome, $welcome_message);
        $stmt_msg->execute();
        $stmt_msg->close();
    }

} else {
    $response['message'] = 'Gagal memulai chat: ' . $koneksi->error;
}
$stmt->close();

echo json_encode($response);
exit();