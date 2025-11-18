<?php
// File ini akan menjadi halaman login default untuk seller.pondasikita.com
// Kita bisa copy-paste dari file login utama dan sedikit modifikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Masuk</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/auth_style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-sidebar">
            <div>
                <h1>Pondasikita Seller Center</h1>
                <p>Platform untuk mengelola dan mengembangkan bisnis bahan bangunan Anda.</p>
            </div>
            <span>© Pondasikita 2025</span>
        </div>
        <div class="auth-main">
            <div class="form-wrapper">
                <h2>Masuk ke Akun Toko Anda</h2>
                <p>Belum punya toko? <a href="../auth/register_seller.php">Daftar di sini</a></p>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="message-box error">
                        <?php
                            $errors = [
                                'password_salah' => 'Kata sandi salah.',
                                'user_tidak_ditemukan' => 'Username atau email tidak ditemukan.',
                                'akun_diblokir' => 'Akun Anda telah diblokir.',
                                'not_seller' => 'Akun ini bukan akun penjual.',
                                'kolom_kosong' => 'Semua kolom wajib diisi.'
                            ];
                            echo $errors[$_GET['error']] ?? 'Terjadi kesalahan.';
                        ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/proses_login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username atau Email</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Kata Sandi</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-submit">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>