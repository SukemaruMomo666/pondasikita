<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$alertType = '';
$pesan = '';
$sisa_detik = 0;

if (isset($_SESSION['blocked_until'])) {
    $now = time();
    if ($_SESSION['blocked_until'] > $now) {
        $sisa_detik = $_SESSION['blocked_until'] - $now;
        $alertType = 'danger';
        $pesan = "Terlalu banyak percobaan login. Silakan coba lagi dalam <span id='timer'>{$sisa_detik}</span> detik.";
    }
}

// Kalau ada error lewat GET, tampilkan seperti biasa (optional, bisa digabung dengan di atas)
if (isset($_GET['error']) && $sisa_detik === 0) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'kolom_kosong':
            $pesan = 'Username atau password tidak boleh kosong.';
            break;
        case 'password_salah':
            $pesan = 'Password yang Anda masukkan salah.';
            break;
        case 'user_tidak_ditemukan':
            $pesan = 'Akun tidak ditemukan.';
            break;
        case 'akun_diblokir':
            $pesan = 'Akun Anda diblokir. Hubungi admin.';
            break;
        default:
            $pesan = 'Terjadi kesalahan login.';
    }
}

if (isset($_GET['success']) && $_GET['success'] === 'reset') {
    $alertType = 'success';
    $pesan = 'Password berhasil direset. Silakan login.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login Pelanggan - Pondasikita</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/auth_style_customer.css" />
</head>
<body>
    <div class="auth-container customer-theme">
        <div class="auth-sidebar">
            <div>
                <img src="../assets/image/Pondasikita.com.png" alt="Logo Pondasikita" class="logo"  style="border-radius: 10px;"/>
                <h1>Lebih Hemat, Lebih Cepat</h1>
                <p>Temukan semua kebutuhan proyek Anda di sini.</p>
            </div>
            <span>© Pondasikita 2025</span>
        </div>
        <div class="auth-main">
            <div class="form-wrapper">
                <h2>Log in</h2>
                <p>Baru di Pondasikita? <a href="register_customer.php">Daftar</a></p>

                <?php if (!empty($pesan)): ?>
                    <div class="alert alert-<?= htmlspecialchars($alertType) ?>" style="margin-bottom: 15px;">
                        <?= $pesan ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/proses_login.php" method="POST">
                    <div class="form-group">
                        <input type="text" id="username" name="username" placeholder="Email atau Username" required <?= $sisa_detik > 0 ? 'disabled' : '' ?> />
                    </div>
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder="Kata Sandi" required <?= $sisa_detik > 0 ? 'disabled' : '' ?> />
                    </div>
                    <input type="hidden" name="user_type" value="customer" />
                    <button type="submit" class="btn-submit" <?= $sisa_detik > 0 ? 'disabled' : '' ?>>LOG IN</button>

                    <center>
                        <a href="../login-google/login_google.php">
                            <img src="https://developers.google.com/identity/images/btn_google_signin_dark_normal_web.png" alt="Login with Google" />
                        </a>
                    </center>
                    <a href="../app_customer/pages/lupa_password.php" class="forgot-password">Lupa Password</a>
                </form>
            </div>
        </div>
    </div>

    <?php if ($sisa_detik > 0): ?>
    <script>
        let countdown = <?= $sisa_detik ?>;
        const timerElem = document.getElementById('timer');

        if (timerElem) {
            const interval = setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(interval);
                    // Reload halaman tanpa query parameter supaya timer reset
                    location.href = location.pathname;
                } else {
                    timerElem.innerText = countdown;
                }
            }, 1000);
        }
    </script>
    <?php endif; ?>
</body>
</html>
