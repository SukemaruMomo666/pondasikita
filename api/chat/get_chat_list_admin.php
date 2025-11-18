<?php
// api/chat/get_chat_list_admin.php
header('Content-Type: application/json');
require_once('config.php');

$response = ['success' => false, 'chats' => [], 'message' => ''];

$admin_id = getLoggedInUserId($koneksi); 
$admin_level = getLoggedInUserRole($koneksi);

if (empty($admin_id) || $admin_level !== 'admin') {
    $response['message'] = 'Akses ditolak. Anda bukan admin atau user ID tidak valid.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$sql_where = "";
$bind_params = [];
$bind_types = "";

error_log("DEBUG: get_chat_list_admin.php - Filter: " . $filter);
error_log("DEBUG: get_chat_list_admin.php - Admin ID: " . $admin_id);

// PERUBAHAN UNTUK AI SATU ARAH: Sesuaikan filter untuk admin agar fokus ke chat bot.
switch ($filter) {
    case 'open': // Chat 'open' tidak akan ada lagi jika langsung ke bot
        $sql_where = "WHERE c.status = 'open' AND c.admin_id = ?"; // Ini akan jarang ditemukan
        $bind_params = [$admin_id];
        $bind_types = "i";
        break;
    case 'pending': // Chat 'pending_admin' tidak akan ada lagi jika langsung ke bot
        $sql_where = "WHERE c.status = 'pending_admin'";
        break;
    case 'bot': // Filter untuk chat yang ditangani bot
        $sql_where = "WHERE c.admin_id = ? AND c.status = 'in_progress_bot'";
        $bind_params = [BOT_USER_ID]; // Filter khusus untuk BOT_USER_ID
        $bind_types = "i";
        break;
    case 'closed':
        // Admin bisa melihat chat yang ditanganinya (jika dulu ada) atau chat yang ditutup oleh bot
        $sql_where = "WHERE c.status = 'closed' AND (c.admin_id = ? OR c.admin_id = ?)"; 
        $bind_params = [$admin_id, BOT_USER_ID];
        $bind_types = "ii";
        break;
    case 'all':
    default:
        // Admin bisa melihat semua chat aktif (yang sekarang hanya bot) atau chat yang ditanganinya/bot yang sudah closed
        $sql_where = "WHERE (c.admin_id = ? AND c.status = 'in_progress_bot') OR (c.status = 'closed' AND (c.admin_id = ? OR c.admin_id = ?))";
        $bind_params = [BOT_USER_ID, $admin_id, BOT_USER_ID];
        $bind_types = "iii";
        break;
}

$query = "
    SELECT 
        c.id, 
        c.status, 
        c.start_time,
        tu.nama AS customer_name,
        (SELECT message_text FROM messages WHERE chat_id = c.id ORDER BY timestamp DESC LIMIT 1) AS last_message_text,
        (SELECT status FROM tb_user WHERE id = c.customer_id) AS customer_status,
        (SELECT level FROM tb_user WHERE id = c.admin_id) AS admin_role_assigned -- Tambahkan ini untuk debug siapa yang menangani
    FROM 
        chats c
    JOIN 
        tb_user tu ON c.customer_id = tu.id
    " . $sql_where . "
    ORDER BY 
        c.status ASC, c.start_time DESC
";

error_log("DEBUG: get_chat_list_admin.php - Final Query: " . $query);
error_log("DEBUG: get_chat_list_admin.php - Final Bind Params: " . print_r($bind_params, true));
error_log("DEBUG: get_chat_list_admin.php - Final Bind Types: " . $bind_types);

$stmt = $koneksi->prepare($query);
if (!$stmt) {
    error_log("ERROR: get_chat_list_admin.php - Prepare statement failed: " . $koneksi->error);
    $response['message'] = 'Internal server error (SQL Prepare).';
    header('HTTP/1.1 500 Internal Server Error'); 
    echo json_encode($response);
    exit();
}

if (!empty($bind_params)) {
    if (!mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params)) {
        error_log("ERROR: get_chat_list_admin.php - Bind param failed: " . $stmt->error);
        $response['message'] = 'Internal server error (SQL Bind Param).';
        header('HTTP/1.1 500 Internal Server Error'); 
        echo json_encode($response);
        exit();
    }
}

if (!mysqli_stmt_execute($stmt)) {
    error_log("ERROR: get_chat_list_admin.php - Execute statement failed: " . $stmt->error);
    $response['message'] = 'Internal server error (SQL Execute).';
    header('HTTP/1.1 500 Internal Server Error'); 
    echo json_encode($response);
    exit();
}

$result = $stmt->get_result();

$chats = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $chats[] = $row;
    }
} else {
    error_log("ERROR: get_chat_list_admin.php - Get result failed: " . $stmt->error);
    $response['message'] = 'Internal server error (SQL Get Result).';
}

error_log("DEBUG: get_chat_list_admin.php - Fetched Chats Count: " . count($chats));
error_log("DEBUG: get_chat_list_admin.php - Fetched Chats Data: " . print_r($chats, true));

$response['success'] = true;
$response['chats'] = $chats;

$stmt->close();
echo json_encode($response);
exit();