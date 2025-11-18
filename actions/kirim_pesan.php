<?php
session_start();
include '../config/koneksi.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($name) || empty($email) || empty($message)) {
    $_SESSION['error'] = "Semua bidang harus diisi!";
    header("Location: ../auth/contact_admin.php");
    exit;
}

$query = "INSERT INTO tb_pesan (name, email, message) VALUES (?, ?, ?)";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("sss", $name, $email, $message);

if ($stmt->execute()) {
    $_SESSION['message'] = "Pesan Anda telah dikirim!";
} else {
    $_SESSION['error'] = "Terjadi kesalahan saat mengirim pesan.";
}

$stmt->close();
$koneksi->close();

header("Location: ../auth/contact_admin.php");
exit;
?>
