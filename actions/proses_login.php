<?php
// Pastikan session_start() ada di baris paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file koneksi database
require_once '../config/koneksi.php';

// Pastikan request datang dari metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/login_customer.php');
    exit;
}

// Validasi kolom tidak boleh kosong
if (empty($_POST['username']) || empty($_POST['password'])) {
    header('Location: ../auth/login_customer.php?error=kolom_kosong');
    exit;
}

$username_or_email = $_POST['username'];
$password = $_POST['password'];

// Konfigurasi pembatasan percobaan login
$max_attempts = 3;
$block_duration = 120; // dalam detik (2 menit)

// Inisialisasi atau ambil jumlah percobaan login dan waktu blokir dari sesi
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['blocked_until'])) {
    $_SESSION['blocked_until'] = 0;
}

$now = time(); // Waktu saat ini

// Cek apakah user masih dalam masa blokir
if ($now < $_SESSION['blocked_until']) {
    $sisa = $_SESSION['blocked_until'] - $now;
    header("Location: ../auth/login_customer.php?error=terkunci&sisa={$sisa}");
    exit;
}

// Siapkan query untuk mencari user berdasarkan username atau email
$stmt = $koneksi->prepare("SELECT id, username, password, nama, level, is_banned FROM tb_user WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username_or_email, $username_or_email);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah user ditemukan
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password
    if (!password_verify($password, $user['password'])) {
        // Password salah
        $_SESSION['login_attempts']++; // Tambah jumlah percobaan gagal
        if ($_SESSION['login_attempts'] >= $max_attempts) {
            // Jika percobaan melebihi batas, blokir user
            $_SESSION['blocked_until'] = $now + $block_duration;
            $_SESSION['login_attempts'] = 0; // Reset counter percobaan setelah diblokir
            header("Location: ../auth/login_customer.php?error=terkunci&sisa={$block_duration}");
            exit;
        }
        header('Location: ../auth/login_customer.php?error=password_salah');
        exit;
    }

    // Cek status banned
    if ($user['is_banned'] == 1) {
        header('Location: ../auth/login_customer.php?error=akun_diblokir');
        exit;
    }

    // Login sukses:
    // 1. Reset counter percobaan dan waktu blokir
    $_SESSION['login_attempts'] = 0;
    $_SESSION['blocked_until'] = 0;

    // 2. Simpan detail user ke dalam array $_SESSION['user']
    // INI ADALAH PERUBAHAN UTAMA YANG MEMPERBAIKI MASALAH SESI LIVE CHAT
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'nama' => $user['nama'],
        'level' => $user['level'],
        'logged_in' => true // Penanda bahwa user sedang login
    ];

    // 3. Redirect user ke halaman dashboard sesuai levelnya
    if ($user['level'] == 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($user['level'] == 'seller') {
        header('Location: ../app_seller/dashboard.php');
    } else { // Default untuk 'customer'
        header('Location: ../index.php');
    }
    exit;

} else {
    // User tidak ditemukan (username atau email salah)
    $_SESSION['login_attempts']++; // Tambah jumlah percobaan gagal
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        // Jika percobaan melebihi batas, blokir user
        $_SESSION['blocked_until'] = $now + $block_duration;
        $_SESSION['login_attempts'] = 0;
        header("Location: ../auth/login_customer.php?error=terkunci&sisa={$block_duration}");
        exit;
    }
    header('Location: ../auth/login_customer.php?error=user_tidak_ditemukan');
    exit;
}
?>