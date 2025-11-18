<?php
// api/chat/register_guest.php
// File ini tidak lagi digunakan karena fungsionalitas guest dihapus.
// Semua interaksi chat memerlukan pengguna yang terdaftar dan login.

header('Content-Type: application/json');
// Mengembalikan error jika file ini diakses
$response = ['success' => false, 'message' => 'Akses tidak diizinkan. Mohon login atau daftar untuk memulai chat.'];
echo json_encode($response);
exit();