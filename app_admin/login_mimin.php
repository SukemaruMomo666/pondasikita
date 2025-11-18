<?php
session_start();

// Jika admin sudah login, langsung arahkan ke dashboard admin
// Ganti 'dashboard_admin.php' jika nama filenya berbeda
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
    <link rel="stylesheet" href="../assets/css/seller_style.css"> 
    
    <style>
        /* Style khusus untuk halaman login admin */
        body {
            /* Menggunakan warna background utama dari tema */
            background-color: var(--main-bg, #F9FAFB);
        }
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            background-color: var(--surface-color, #FFFFFF);
            padding: 2.5rem 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color, #E5E7EB);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .brand-link {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary, #1F2937);
            text-decoration: none;
        }
        .login-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: var(--text-secondary, #6B7280);
        }

        /* Styling untuk form input dengan ikon */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 15px;
            font-size: 1.25rem;
            color: var(--text-secondary, #6B7280);
        }
        .form-control.with-icon {
            padding-left: 45px; /* Beri ruang untuk ikon */
        }
        
        /* Tombol lihat/sembunyikan password */
        .password-toggle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 15px;
            cursor: pointer;
            color: var(--text-secondary, #6B7280);
            background: none;
            border: none;
            font-size: 1.25rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 600;
        }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.85rem;
            color: var(--text-secondary, #6B7280);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <a href="#" class="brand-link">Pondasikita</a>
                <h2>Admin Panel Login</h2>
                <p>Silakan masuk untuk mengelola website.</p>
            </div>

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
        // Skrip untuk toggle lihat/sembunyikan password
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