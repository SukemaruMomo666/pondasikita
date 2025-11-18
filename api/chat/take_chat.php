<?php
// api/chat/take_chat.php
// File ini tidak lagi relevan untuk mode chat Customer <-> AI Bot.
// Admin tidak lagi 'mengambil' chat karena semua chat ditangani bot secara default.

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Fungsi ini tidak diizinkan dalam mode chat Customer <-> AI Bot.'];
echo json_encode($response);
exit();