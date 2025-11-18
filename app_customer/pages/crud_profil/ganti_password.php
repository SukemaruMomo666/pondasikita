<?php
session_start();
require '../../config/koneksi.php'; // Sesuaikan path jika perlu

// Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../auth/signin.php");
    exit;
}

$id_user = $_SESSION['user']['id'];
$error = '';
$success = '';

// --- MODIFIKASI DIMULAI: Logika untuk Akun Google ---
// Ambil data user termasuk cara dia login (google_id)
// Asumsi Anda memiliki kolom 'google_id' di tabel 'tb_user'
// Nilainya bisa 'local' untuk pendaftaran biasa, atau 'google' untuk login Google.
$stmt_check = $koneksi->prepare("SELECT password, google_id FROM tb_user WHERE id = ?");
$stmt_check->bind_param("i", $id_user);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$user_data = $result_check->fetch_assoc();
$stmt_check->close();

// Jika user login dengan Google, jangan izinkan ganti password di sini
if ($user_data && $user_data['google_id'] === 'google') {
    // Set pesan error dan nonaktifkan proses ganti password
    $error = "Anda login menggunakan akun Google. Silakan ganti password melalui pengaturan akun Google Anda.";
} elseif (isset($_POST['ganti'])) {
    // --- MODIFIKASI SELESAI ---

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    // Gunakan data user yang sudah diambil tadi
    if ($user_data && password_verify($password_lama, $user_data['password'])) {
        if (strlen($password_baru) < 6) {
            $error = "Password baru minimal harus 6 karakter.";
        } elseif ($password_baru === $konfirmasi) {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            
            $stmt_update = $koneksi->prepare("UPDATE tb_user SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $password_hash, $id_user);
            
            if ($stmt_update->execute()) {
                header("Location: ../profil.php?status=password_sukses");
                exit;
            } else {
                $error = "Gagal memperbarui password. Silakan coba lagi.";
            }
            $stmt_update->close();
        } else {
            $error = "Konfirmasi password baru tidak cocok.";
        }
    } else {
        $error = "Password lama yang Anda masukkan salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/edit_profile.css">

    <style>
        .form-row {
            position: relative;
        }
        .form-row .toggle-password {
            position: absolute;
            right: 15px;
            top: 70%; /* Sesuaikan posisi vertikal ikon */
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <i class="fas fa-key"></i>
                <h1>Ganti Password</h1>
            </div>

            <?php if (!empty($error)) echo "<p class='message error'>$error</p>"; ?>
            
            <form method="post" class="profile-form">
                <div class="form-row">
                    <label for="password_lama">Password Lama</label>
                    <input type="password" id="password_lama" name="password_lama" required <?php if ($user_data['google_id'] === 'google') echo 'disabled'; ?>>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                
                <div class="form-row">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" id="password_baru" name="password_baru" required minlength="6" <?php if ($user_data['google_id'] === 'google') echo 'disabled'; ?>>
                    <i class="fas fa-eye toggle-password"></i>
                </div>

                <div class="form-row">
                    <label for="konfirmasi">Konfirmasi Password Baru</label>
                    <input type="password" id="konfirmasi" name="konfirmasi" required <?php if ($user_data['google_id'] === 'google') echo 'disabled'; ?>>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="ganti" class="btn" <?php if ($user_data['google_id'] === 'google') echo 'disabled'; ?>>
                        <i class="fas fa-save"></i>
                        Ganti Password
                    </button>
                    <a href="../profil.php" class="btn" style="background-color: #6c757d;">
                        <i class="fas fa-times"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleIcons = document.querySelectorAll('.toggle-password');

            toggleIcons.forEach(icon => {
                icon.addEventListener('click', function () {
                    const input = this.previousElementSibling;
                    // Ganti tipe input
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
</body>
</html>