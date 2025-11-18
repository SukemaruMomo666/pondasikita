<?php
// api/chat/end_chat.php
header('Content-Type: application/json');
require_once('config.php'); // <-- GANTI DENGAN require_once
// ...

$response = ['success' => false, 'message' => ''];

$input = json_decode(file_get_contents('php://input'), true);
$chat_id = $input['chat_id'] ?? null;
$admin_id_from_client = $input['admin_id'] ?? null; // ID admin yang dikirim dari client

$logged_in_user_id = getLoggedInUserId($koneksi); // ID admin yang benar-benar login
$logged_in_user_level = getLoggedInUserRole($koneksi);

if (!$chat_id || empty($admin_id_from_client)) { // Cek $admin_id_from_client juga
    $response['message'] = 'Data tidak lengkap.';
    echo json_encode($response);
    exit();
}

// PERBAIKAN: Pastikan admin yang menutup chat adalah yang sedang login dan levelnya admin
if ($admin_id_from_client != $logged_in_user_id || $logged_in_user_level !== 'admin') {
    $response['message'] = 'Akses ditolak. Anda tidak berhak menutup chat ini.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

$stmt = $koneksi->prepare("UPDATE chats SET status = 'closed', end_time = CURRENT_TIMESTAMP WHERE id = ?");
if (!$stmt) {
    error_log("Prepare statement gagal di end_chat: " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt->bind_param("i", $chat_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Chat berhasil ditutup.';
    } else {
        $response['message'] = 'Chat tidak ditemukan atau sudah ditutup.';
    }
} else {
    $response['message'] = 'Gagal menutup chat: ' . $koneksi->error;
}
$stmt->close();

echo json_encode($response);
exit();
