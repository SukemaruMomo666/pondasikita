<?php
session_start();
include_once '../config/koneksi.php'; 
require_once __DIR__ . '/../api/chat/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username_or_email = trim($_POST['username']); 
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $_SESSION['error'] = 'Username/Email dan password wajib diisi.';
        header('Location: ../auth/signin.php');
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id, username, password, nama, level, status FROM tb_user WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $username_or_email, $username_or_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'nama'     => $user['nama'],
                'level'    => $user['level'],
            ];
            
            $update_last_login_stmt = mysqli_prepare($koneksi, "UPDATE tb_user SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            mysqli_stmt_bind_param($update_last_login_stmt, 'i', $user['id']);
            mysqli_stmt_execute($update_last_login_stmt);
            mysqli_stmt_close($update_last_login_stmt);

            updateOnlineStatus($koneksi, $user['id'], 'online');

            // --- PERBAIKAN UTAMA DI SINI ---
            if ($user['level'] === 'admin') {
                // Arahkan LANGSUNG ke halaman live chat admin
                header('Location: ../admin/kelola_data/data_barang/kelola_data_barang.php'); // Pastikan path ini benar!
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $_SESSION['error'] = 'Password salah.';
        }
    } else {
        $_SESSION['error'] = 'Username atau Email tidak ditemukan.';
    }

    mysqli_stmt_close($stmt);

} else {
    $_SESSION['error'] = 'Akses tidak sah.';
}

header('Location: ../auth/signin.php');
exit;
?>