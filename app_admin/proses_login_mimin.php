<?php
// Selalu mulai session di baris paling atas
session_start();

// Sertakan file koneksi database
require_once '../config/koneksi.php';

// Cek apakah form sudah disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Ambil data dari form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi dasar agar tidak kosong
    if (empty($username) || empty($password)) {
        // Jika kosong, kembalikan ke login dengan pesan error
        $_SESSION['login_error'] = "Username dan Password wajib diisi.";
        header("Location: login_mimin.php");
        exit;
    }

    // 2. Siapkan query untuk mencari admin
    // Ini bagian paling penting: WHERE username = ? AND level = 'admin'
    $sql = "SELECT id, username, password, nama, level FROM tb_user WHERE username = ? AND level = 'admin'";
    
    $stmt = $koneksi->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $koneksi->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. Cek apakah admin ditemukan
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // 4. Verifikasi password
        if (password_verify($password, $admin['password'])) {
            // Jika password cocok, login berhasil
            
            // Hapus session error jika ada
            unset($_SESSION['login_error']);

            // Set session untuk admin
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['nama'] = $admin['nama'];
            $_SESSION['level'] = $admin['level']; // Levelnya pasti 'admin'

            // Arahkan ke dashboard admin
            header("Location: dashboard_admin.php");
            exit;
        }
    }

    // 5. Jika username tidak ditemukan atau password salah
    $_SESSION['login_error'] = "Username atau Password salah.";
    header("Location: login_mimin.php");
    exit;

} else {
    // Jika halaman diakses langsung tanpa POST, tendang ke halaman login
    header("Location: login_mimin.php");
    exit;
}
?>
