<?php
// api/chat/send_message.php (Versi Disesuaikan untuk Bot dengan Memori)
header('Content-Type: application/json');
require_once('config.php');

$response = ['success' => false, 'message' => ''];

$input = json_decode(file_get_contents('php://input'), true);
$chat_id = $input['chat_id'] ?? null;
$sender_id = $input['sender_id'] ?? null; 
$message_text = trim($input['message_text'] ?? '');

$logged_in_user_id = getLoggedInUserId($koneksi); 
$logged_in_user_level = getLoggedInUserRole($koneksi);

if (empty($chat_id) || empty($message_text) || empty($sender_id)) {
    $response['message'] = 'Data tidak lengkap.';
    header('HTTP/1.1 400 Bad Request');
    echo json_encode($response);
    exit();
}

$sender_info = getUserInfo($koneksi, $sender_id);

// PERUBAHAN UNTUK AI SATU ARAH: Pengirim hanya bisa customer yang login atau BOT_USER_ID
if (!$sender_info || ($sender_id != $logged_in_user_id && $sender_id != BOT_USER_ID)) {
    $response['message'] = 'Pengirim tidak valid atau mismatch dengan sesi login.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

$stmt_check_chat = $koneksi->prepare("SELECT customer_id, admin_id, status FROM chats WHERE id = ?");
$stmt_check_chat->bind_param("i", $chat_id);
$stmt_check_chat->execute();
$chat_info = $stmt_check_chat->get_result()->fetch_assoc();
$stmt_check_chat->close();

if (!$chat_info || 
    ($sender_info['role'] === 'customer' && $chat_info['customer_id'] != $sender_id) ||
    // PERUBAHAN UNTUK AI SATU ARAH: Jika pengirim adalah admin, hanya izinkan jika itu BOT_USER_ID
    ($sender_info['role'] === 'admin' && $chat_info['admin_id'] != BOT_USER_ID) ||
    ($chat_info['status'] === 'closed') 
   ) {
    $response['message'] = 'Akses ditolak untuk chat ini.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

$stmt = $koneksi->prepare("INSERT INTO messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $chat_id, $sender_id, $message_text);

if ($stmt->execute()) {
    $response['success'] = true;
    
    // LOGIKA UTAMA: Cek apakah BOT harus merespons
    // PERUBAHAN UNTUK AI SATU ARAH: Bot selalu merespons jika customer mengirim pesan
    if ($sender_info['role'] === 'customer') { // Bot merespons HANYA jika pengirim adalah customer
        
        // =====================================================================================
        // === INI SATU-SATUNYA PERUBAHAN YANG DIPERLUKAN UNTUK MENGAKTIFKAN MEMORI ===
        $bot_response_text = getBotResponse($koneksi, $chat_id, $message_text);
        // =====================================================================================
        
        if (!empty($bot_response_text)) {
            $stmt_bot_msg = $koneksi->prepare("INSERT INTO messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
            if ($stmt_bot_msg) {
                $bot_id = BOT_USER_ID;
                $stmt_bot_msg->bind_param("iis", $chat_id, $bot_id, $bot_response_text);
                $stmt_bot_msg->execute();
                $stmt_bot_msg->close();
            } else {
                error_log("Failed to prepare bot message insert statement: " . $koneksi->error);
            }
        } else {
             error_log("Bot did not return a response for chat_id: " . $chat_id . " message: " . $message_text);
        }
    }
} else {
    $response['message'] = 'Gagal mengirim pesan: ' . $koneksi->error;
}
$stmt->close();
// $koneksi->close(); // Jangan tutup koneksi di sini, karena mungkin dibutuhkan oleh fungsi lain di config.php atau include lainnya

echo json_encode($response);
exit();