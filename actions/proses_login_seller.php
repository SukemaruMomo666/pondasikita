<?php
// actions/proses_login_seller.php
session_start();
require_once '../config/koneksi.php'; // Pastikan path ini benar

// Validasi dasar: Pastikan request POST dan input tidak kosong
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['username']) || empty($_POST['password'])) {
    header('Location: ../auth/login_seller.php?error=kolom_kosong');
    exit;
}

$username_or_email = $_POST['username'];
$password = $_POST['password'];

// --- Proteksi Brute-Force (khusus untuk login seller) ---
// Menggunakan variabel sesi terpisah agar tidak mengganggu login customer
$_SESSION['seller_login_attempts'] = $_SESSION['seller_login_attempts'] ?? 0;
$_SESSION['seller_blocked_until'] = $_SESSION['seller_blocked_until'] ?? 0;

$max_attempts = 3; // Maksimal percobaan login
$block_duration = 120; // Durasi blokir dalam detik (2 menit)
$now = time();

// Cek apakah sedang diblokir
if ($now < $_SESSION['seller_blocked_until']) {
    $sisa = $_SESSION['seller_blocked_until'] - $now;
    header("Location: ../auth/login_seller.php?error=terkunci&sisa={$sisa}");
    exit;
}
// --- Akhir Proteksi Brute-Force ---

// Persiapan query untuk mencari user dengan level 'seller'
$stmt = $koneksi->prepare(
    "SELECT id, username, password, nama, email, level, is_banned 
     FROM tb_user 
     WHERE (username = ? OR email = ?) AND level = 'seller'"
);

// Periksa jika prepare statement gagal
if ($stmt === false) {
    error_log('MySQL prepare statement failed: ' . $koneksi->error);
    header('Location: ../auth/login_seller.php?error=internal_server_error');
    exit;
}

$stmt->bind_param("ss", $username_or_email, $username_or_email);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah ada 1 user ditemukan
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password
    if (password_verify($password, $user['password'])) {
        // Cek jika akun diblokir (banned)
        if ($user['is_banned'] == 1) {
            header('Location: ../auth/login_seller.php?error=akun_diblokir');
            exit;
        }

        // Login sukses, reset sesi percobaan brute-force
        unset($_SESSION['seller_login_attempts'], $_SESSION['seller_blocked_until']);

        // Simpan data user ke sesi
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'nama'  => $user['nama'],
            'email' => $user['email'],
            'level' => $user['level'] // Pastikan level adalah 'seller'
        ];
        
        // Redirect ke dashboard seller
        header('Location: ../app_seller/dashboard_seller.php'); // Sesuaikan path ini
        exit;

    } else {
        // Password salah
        $_SESSION['seller_login_attempts']++; // Tambah counter percobaan gagal
        $sisa_percobaan = $max_attempts - $_SESSION['seller_login_attempts'];

        if ($_SESSION['seller_login_attempts'] >= $max_attempts) {
            $_SESSION['seller_blocked_until'] = $now + $block_duration;
            unset($_SESSION['seller_login_attempts']); // Reset counter setelah diblokir
            header("Location: ../auth/login_seller.php?error=terkunci&sisa={$block_duration}");
            exit;
        } else {
            header("Location: ../auth/login_seller.php?error=invalid_credentials&attempts_left={$sisa_percobaan}");
            exit;
        }
    }
} else {
    // User tidak ditemukan
    $_SESSION['seller_login_attempts']++; // Tambah counter percobaan gagal
    $sisa_percobaan = $max_attempts - $_SESSION['seller_login_attempts'];

    if ($_SESSION['seller_login_attempts'] >= $max_attempts) {
        $_SESSION['seller_blocked_until'] = $now + $block_duration;
        unset($_SESSION['seller_login_attempts']); // Reset counter setelah diblokir
        header("Location: ../auth/login_seller.php?error=terkunci&sisa={$block_duration}");
        exit;
    } else {
        header("Location: ../auth/login_seller.php?error=invalid_credentials&attempts_left={$sisa_percobaan}"); // Pesan umum untuk keamanan
        exit;
    }
}

// Tutup statement
$stmt->close();
// Tutup koneksi database (opsional, PHP akan menutupnya secara otomatis di akhir script)
// $koneksi->close();
?>