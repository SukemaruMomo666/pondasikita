<?php
// api/chat/get_chat_info.php
header('Content-Type: application/json');
require_once('config.php');

$response = ['success' => false, 'chat_info' => null, 'message' => ''];

$chat_id = $_GET['chat_id'] ?? null;
$logged_in_user_id = getLoggedInUserId($koneksi);
$logged_in_user_level = getLoggedInUserRole($koneksi);

if (empty($chat_id)) {
    $response['message'] = 'Chat ID tidak valid.';
    echo json_encode($response);
    exit();
}

$stmt = $koneksi->prepare("SELECT id, customer_id, admin_id, status FROM chats WHERE id = ?");
if (!$stmt) {
    error_log("Prepare statement gagal di get_chat_info: " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $chat_info = $result->fetch_assoc();
    // PERUBAHAN UNTUK AI SATU ARAH: Hanya customer yang berhak melihat info chatnya sendiri.
    // Admin manusia tidak lagi secara langsung 'memiliki' chat. Admin bot adalah admin_id.
    if ($logged_in_user_level === 'customer' && $chat_info['customer_id'] != $logged_in_user_id) {
        $response['message'] = 'Akses ditolak ke info chat ini.';
        header('HTTP/1.1 403 Forbidden');
        echo json_encode($response);
        exit();
    }
    // Jika user adalah admin (misalnya dari panel admin yang ingin melihat semua chat bot)
    if ($logged_in_user_level === 'admin' && ($chat_info['admin_id'] != $logged_in_user_id && $chat_info['admin_id'] != BOT_USER_ID)) {
         $response['message'] = 'Akses ditolak ke info chat ini.'; // Admin hanya bisa melihat chat yang ditanganinya atau chat bot
         header('HTTP/1.1 403 Forbidden');
         echo json_encode($response);
         exit();
    }


    $response['success'] = true;
    $response['chat_info'] = $chat_info;
} else {
    $response['message'] = 'Chat tidak ditemukan.';
}
$stmt->close();

echo json_encode($response);
exit();