<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_POST['token'], $_POST['password'], $_POST['password_confirm'])) {
    $_SESSION['error'] = 'Data tidak lengkap.';
    header('Location: ../pages/lupa_password.php');
    exit;
}

$token = $_POST['token'];
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

if ($password !== $password_confirm) {
    $_SESSION['error'] = 'Password dan konfirmasi password tidak cocok.';
    header("Location: ../pages/reset_password.php?token=" . urlencode($token));
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password minimal 6 karakter.';
    header("Location: ../pages/reset_password.php?token=" . urlencode($token));
    exit;
}

$token_hash = hash('sha256', $token);

$stmt = $koneksi->prepare("SELECT id, reset_token_expires_at FROM tb_user WHERE reset_token = ?");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = 'Tautan reset password tidak valid.';
    header('Location: ../pages/lupa_password.php');
    exit;
}

$now = new DateTime();
$expire = new DateTime($user['reset_token_expires_at']);
if ($expire < $now) {
    $_SESSION['error'] = 'Tautan reset password sudah kedaluwarsa.';
    header('Location: ../pages/lupa_password.php');
    exit;
}

// Hash password baru dengan password_hash
$new_password_hash = password_hash($password, PASSWORD_DEFAULT);

$update_stmt = $koneksi->prepare("UPDATE tb_user SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
$update_stmt->bind_param("si", $new_password_hash, $user['id']);
$update_stmt->execute();

$_SESSION['message'] = 'Password berhasil diubah. Silakan login dengan password baru Anda.';
header('Location: ../auth/login_customer.php');
exit;
