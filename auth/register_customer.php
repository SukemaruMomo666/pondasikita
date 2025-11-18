<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar sebagai Pembeli - Pondasikita</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/auth_style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-sidebar">
            <div>
                <h1>Selamat Datang di Pondasikita.</h1>
                <p>Platform terpercaya untuk semua kebutuhan bahan bangunan Anda.</p>
            </div>
            <span>© Pondasikita 2025</span>
        </div>
        <div class="auth-main">
            <div class="form-wrapper">
                <h2>Buat Akun Pembeli</h2>
                <p>Sudah punya akun? <a href="login_customer.php">Masuk di sini</a></p>
                
                <form id="registerForm" method="POST">
                    <div id="message" class="message-box" style="display:none;"></div>
                    <div class="form-group">
                        <label for="username">Nama Pengguna</label>
                        <input type="text" id="username" name="username" placeholder="cth: budikeren" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Sesuai KTP" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="email@anda.com" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Kata Sandi</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-submit">Daftar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');
            const submitButton = form.querySelector('.btn-submit');

            submitButton.disabled = true;
            submitButton.textContent = 'Memproses...';

            // PERUBAHAN KRUSIAL: Mengirim data ke file /actions/proses_register.php
            fetch('../actions/proses_register.php', { 
                method: 'POST', 
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.textContent = data.message;
                messageDiv.className = 'message-box ' + (data.status === 'success' ? 'success' : 'error');
                messageDiv.style.display = 'block';

                if (data.status === 'success') {
                    form.reset();
                    // Arahkan ke halaman login setelah berhasil
                    setTimeout(() => { window.location.href = 'login_customer.php?status=reg_success'; }, 2000);
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Daftar';
                }
            })
            .catch(error => {
                messageDiv.textContent = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
                messageDiv.className = 'message-box error';
                messageDiv.style.display = 'block';
                submitButton.disabled = false;
                submitButton.textContent = 'Daftar';
            });
        });
    </script>
</body>
</html>