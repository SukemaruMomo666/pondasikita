<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../config/koneksi.php';
$koneksi->query("SET time_zone = '+07:00'");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_POST['email']) || empty($_POST['email'])) {
    $_SESSION['error'] = 'Alamat email wajib diisi.';
    header('Location: ../app_customer/pages/lupa_password.php');
    exit;
}

$email = $_POST['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Format email tidak valid.';
    header('Location: ../app_customer/pages/lupa_password.php');
    exit;
}

// Cek email di database
$stmt = $koneksi->prepare("SELECT id FROM tb_user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Untuk keamanan, jangan beri tahu user apakah email ada atau tidak
    $_SESSION['message'] = 'Jika email Anda terdaftar, tautan reset password telah dikirim.';
    header('Location: ../app_customer/pages/lupa_password.php');
    exit;
}

// Generate token
$token = bin2hex(random_bytes(32)); // token asli dikirim via email
$token_hash = hash('sha256', $token); // token hash disimpan di DB

$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // kadaluarsa 1 jam

// Simpan token dan waktu kedaluwarsa
$update_stmt = $koneksi->prepare("UPDATE tb_user SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
$update_stmt->bind_param("sss", $token_hash, $expires_at, $email);
$update_stmt->execute();

// Kirim email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'prabualamxi@gmail.com'; // ganti email pengirim
    $mail->Password   = 'vwhnbrhfheqtjmav'; // ganti app password gmail Anda
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('prabualamxi@gmail.com', 'Admin Pondasi Kita');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Permintaan Reset Password Akun Pondasi Kita';
    $reset_link = "http://localhost/pondasikita/app_customer/pages/reset_password.php?token=" . $token;

    $mail->Body = <<<EOT
    <html>
    <head><title>Reset Password</title></head>
    <body>
        <p>Halo,</p>
        <p>Kami menerima permintaan reset password akun Anda. Klik tautan di bawah ini untuk mengatur ulang password Anda:</p>
        <p><a href="{$reset_link}">Reset Password Sekarang</a></p>
        <p>Tautan ini berlaku selama 1 jam.</p>
        <p>Jika Anda tidak mengajukan permintaan ini, abaikan email ini.</p>
        <br><p>Salam,</p><p>Tim Pondasi Kita</p>
    </body>
    </html>
    EOT;

    $mail->send();
    $_SESSION['message'] = 'Tautan reset password telah dikirim ke email Anda. Silakan cek inbox atau spam.';
} catch (Exception $e) {
    $_SESSION['error'] = "Gagal mengirim email. Kesalahan: {$mail->ErrorInfo}";
}

header('Location: ../app_customer/pages/lupa_password.php');
exit;
