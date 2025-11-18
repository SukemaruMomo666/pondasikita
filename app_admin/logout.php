<?php
// Selalu mulai session di baris paling atas
session_start();

// 1. Hapus semua variabel session
$_SESSION = array();

// 2. Hancurkan session
// Ini akan menghapus session di sisi server
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 3. Arahkan kembali ke halaman login admin
// Pastikan path ini benar sesuai lokasi file login admin Anda
header("Location: login_mimin.php");
exit;
?>
