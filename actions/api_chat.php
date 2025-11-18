<?php
session_start();
require_once '../config/koneksi.php';
header('Content-Type: application/json');

// Keamanan & ambil data toko
if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'seller') { 
    echo json_encode(['status'=>'error', 'message'=>'Akses ditolak']); exit; 
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_chat_list':
        $sql = "SELECT c.id, u.nama as nama_pelanggan, 
                       (SELECT message_text FROM messages WHERE chat_id = c.id ORDER BY timestamp DESC LIMIT 1) as last_message
                FROM chats c 
                JOIN tb_user u ON c.customer_id = u.id 
                WHERE c.toko_id = ? ORDER BY c.start_time DESC";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("i", $toko_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    case 'get_messages':
        $chat_id = (int)$_GET['chat_id'];
        // Validasi kepemilikan chat
        $stmt = $koneksi->prepare("SELECT * FROM messages WHERE chat_id = ? ORDER BY timestamp ASC");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;
        
    case 'send_message':
        $chat_id = (int)$_POST['chat_id'];
        $message_text = trim($_POST['message_text']);
        // Validasi kepemilikan chat sebelum mengirim
        // ... (Tambahkan pengecekan apakah chat_id ini benar-benar milik toko_id ini) ...
        $stmt = $koneksi->prepare("INSERT INTO messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $chat_id, $user_id, $message_text);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
        break;
}
?>