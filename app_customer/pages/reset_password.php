<?php
// app_customer/pages/reset_password.php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Tautan tidak valid.");
}

$token = $_GET['token'];
$token_hash = hash('sha256', $token);

$stmt = $koneksi->prepare("SELECT id, reset_token_expires_at FROM tb_user WHERE reset_token = ?");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Tautan tidak valid atau sudah digunakan.");
}

$now = new DateTime();
$expire = new DateTime($user['reset_token_expires_at']);
if ($expire < $now) {
    die("Tautan reset password sudah kedaluwarsa.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password Baru - Pondasikita</title>
    <link rel="stylesheet" type="text/css" href="../../assets/css/auth_style.css">
</head>
<body>
    <div class="auth-container customer-theme">
        <div class="auth-sidebar">
            <div>
                <img src="../../assets/images/logo-putih.png" alt="Logo Pondasikita" class="logo">
                <h1>Lebih Hemat, Lebih Cepat</h1>
                <p>Temukan semua kebutuhan proyek Anda di sini.</p>
            </div>
            <span>© Pondasikita 2025</span>
        </div>
        <div class="auth-main">
            <div class="form-wrapper">
                <h2>Atur Password Baru</h2>

                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['message'])) {
                    echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['message']).'</div>';
                    unset($_SESSION['message']);
                }
                ?>

                <form action="../../proses/proses_reset_password.php" method="POST" novalidate>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6" placeholder="Masukkan password baru">
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Konfirmasi Password Baru</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="6" placeholder="Konfirmasi password baru">
                    </div>
                    <button type="submit" class="btn-submit">Reset Password</button>
                </form>
                <p class="mt-3">
                    Sudah ingat password? <a href="../../auth/login_customer.php">Login di sini</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
