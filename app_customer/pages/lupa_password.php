<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lupa Password - Pondasikita</title>
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
                <h2>Lupa Password</h2>
                <p>Masukkan alamat email Anda yang terdaftar. Kami akan mengirimkan tautan untuk mereset password Anda.</p>

                <?php
                if (isset($_SESSION['message'])) {
                    echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['message']).'</div>';
                    unset($_SESSION['message']);
                }
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
                    unset($_SESSION['error']);
                }
                ?>

                <form action="../../proses/proses_lupa_password.php" method="POST" novalidate>
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Masukkan email Anda">
                    </div>
                    <button type="submit" class="btn-submit">Kirim Tautan Reset</button>
                </form>

                <p class="mt-3">
                    <a href="../../auth/login_customer.php">Kembali ke halaman Login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
