<?php
session_start(); // SELALU INI PERTAMA
include_once '../config/koneksi.php'; // Sesuaikan path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan Anda menangkap nama_lengkap dari form signup
    $username     = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']); // Tambahkan ini
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];
    $no_telepon   = trim($_POST['no_telepon']);
    $terms        = isset($_POST['terms']); // Dari form signup

    // Validasi input dasar
    if (empty($username) || empty($nama_lengkap) || empty($email) || empty($password) || empty($no_telepon) || !$terms) {
        echo "<script>alert('Semua field wajib diisi dan Anda harus menyetujui Syarat & Ketentuan.'); window.location.href = '../auth/signup.php';</script>";
        exit;
    }

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid.'); window.location.href = '../auth/signup.php';</script>";
        exit;
    }

    // Validasi nomor HP
    if (!preg_match('/^[0-9]{10,15}$/', $no_telepon)) {
        echo "<script>alert('Nomor HP harus berupa angka 10–15 digit.'); window.location.href = '../auth/signup.php';</script>";
        exit;
    }

    // Cek apakah username atau email sudah digunakan
    $cek_stmt = mysqli_prepare($koneksi, "SELECT id FROM tb_user WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($cek_stmt, 'ss', $username, $email);
    mysqli_stmt_execute($cek_stmt);
    $cek_result = mysqli_stmt_get_result($cek_stmt);
    if (mysqli_num_rows($cek_result) > 0) {
        echo "<script>alert('Username atau email sudah terdaftar.'); window.location.href = '../auth/signup.php';</script>";
        exit;
    }
    mysqli_stmt_close($cek_stmt);

    // Enkripsi password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert ke database
    // Tambahkan 'nama' dan 'is_verified' ke query INSERT
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_user (username, password, nama, email, no_telepon, level, status, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $level = 'customer';
    $status_default = 'offline';
    $is_verified_default = 0; // Anda bisa mengubah ini jadi 1 jika ingin otomatis terverifikasi

    mysqli_stmt_bind_param($stmt, 'sssssssi', $username, $hashed_password, $nama_lengkap, $email, $no_telepon, $level, $status_default, $is_verified_default);

    if (mysqli_stmt_execute($stmt)) {
        $new_user_id = mysqli_insert_id($koneksi); // Dapatkan ID user yang baru terdaftar

        // SET VARIABEL SESI UNTUK PENGGUNA YANG BARU MENDAFTAR
        $_SESSION['id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['nama'] = $nama_lengkap;
        $_SESSION['level'] = $level; // Akan selalu 'customer' untuk pendaftaran biasa

        // Jika Anda ingin user yang baru daftar langsung 'online' di live chat
        // Maka panggil updateOnlineStatus atau set langsung di sini
        // updateOnlineStatus($koneksi, $new_user_id, 'online'); // Jika fungsi ini tersedia dan config di-include

        echo "<script>alert('Registrasi berhasil. Anda otomatis masuk.'); window.location.href = '../index.php';</script>"; // Redirect ke halaman utama
    } else {
        echo "<script>alert('Registrasi gagal. Silakan coba lagi. Error: " . mysqli_error($koneksi) . "'); window.location.href = '../auth/signup.php';</script>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<script>alert('Akses tidak sah.'); window.location.href = '../index.php';</script>";
}
?>