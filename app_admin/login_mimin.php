<?php
session_start();

// Jika admin sudah login, langsung ke dashboard
if (isset($_SESSION['logged_in']) && isset($_SESSION['level']) && $_SESSION['level'] === 'admin') {
    header("Location: dashboard_admin.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/login_admin.css"> 
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <a href="#" class="brand-link">Pondasikita</a>
                <h2>Admin Panel Login</h2>
                <p>Silakan masuk untuk mengelola website.</p>
            </div>

            <!-- PERBAIKAN: Menampilkan Alert Error -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger">
                    <i class="mdi mdi-alert-circle-outline"></i>
                    <span><?= $_SESSION['login_error']; ?></span>
                </div>
                <!-- Hapus session error agar tidak muncul terus saat direfresh -->
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <form action="proses_login_mimin.php" method="POST">
                <div class="form-group">
                    <i class="mdi mdi-account-outline input-icon"></i>
                    <input type="text" name="username" class="form-control with-icon" placeholder="Username atau Email" required>
                </div>
                <div class="form-group">
                    <i class="mdi mdi-lock-outline input-icon"></i>
                    <input type="password" name="password" id="password-field" class="form-control with-icon" placeholder="Password" required>
                    <button type="button" id="password-toggle-btn" class="password-toggle">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                </div>
                
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                        <label class="form-check-label" for="rememberMe" style="font-size: 0.9rem;">
                            Ingat Saya
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">Masuk</button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> Pondasikita. All Rights Reserved.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password-field');
            const toggleBtn = document.getElementById('password-toggle-btn');
            const icon = toggleBtn.querySelector('i');

            toggleBtn.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('mdi-eye-outline');
                    icon.classList.add('mdi-eye-off-outline');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('mdi-eye-off-outline');
                    icon.classList.add('mdi-eye-outline');
                }
            });
        });
    </script>
</body>
</html>