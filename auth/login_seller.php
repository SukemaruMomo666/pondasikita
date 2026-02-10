<?php
// auth/login_seller.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Penjual - Pondasikita Seller Centre</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/auth_style.css">
</head>
<body>
    <div class="auth-container seller-theme">
        <div class="auth-sidebar">
            <div>
                 <img src="/assets/images/logo-oranye.png" alt="Logo Pondasikita" class="logo"> <h1>Jadilah Penjual Terbaik!</h1>
                <p>Kelola toko Anda secara efisien di Pondasikita Seller Centre.</p>
            </div>
            <span>© Pondasikita 2025</span>
        </div>
        <div class="auth-main">
            <div class="form-wrapper">
                <h2>Login Penjual</h2>
                <p>Ingin mulai berjualan? <a href="register_seller.php">Daftar sebagai Penjual</a></p>
                
                <?php include '_auth_messages.php'; ?>

                <form action="../actions/proses_login_seller.php" method="POST">
                    <div class="form-group">
                        <input type="text" id="username" name="username" placeholder="Email atau Username Toko" required>
                    </div>
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder="Kata Sandi" required>
                    </div>
                    <input type="hidden" name="user_type" value="seller">
                    <button type="submit" class="btn-submit">LOG IN</button>
                    <a href="#" class="forgot-password">Lupa Password</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>