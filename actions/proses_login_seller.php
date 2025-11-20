<?php
// actions/proses_login_seller.php
session_start();
require_once '../config/koneksi.php'; // Pastikan path ini benar

// Validasi dasar: Pastikan request POST dan input tidak kosong
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['username']) || empty($_POST['password'])) {
    header('Location: ../auth/login_seller.php?error=kolom_kosong');
    exit;
}

// PERBAIKAN 1: Pakai trim() biar spasi di awal/akhir tidak bikin error
$username_or_email = trim($_POST['username']); 
$password = $_POST['password'];

// --- Proteksi Brute-Force (khusus untuk login seller) ---
$_SESSION['seller_login_attempts'] = $_SESSION['seller_login_attempts'] ?? 0;
$_SESSION['seller_blocked_until'] = $_SESSION['seller_blocked_until'] ?? 0;

$max_attempts = 3; 
$block_duration = 120; 
$now = time();

if ($now < $_SESSION['seller_blocked_until']) {
    $sisa = $_SESSION['seller_blocked_until'] - $now;
    header("Location: ../auth/login_seller.php?error=terkunci&sisa={$sisa}");
    exit;
}
// --- Akhir Proteksi Brute-Force ---

// Persiapan query
$stmt = $koneksi->prepare(
    "SELECT id, username, password, nama, email, level, is_banned 
     FROM tb_user 
     WHERE (username = ? OR email = ?) AND level = 'seller'"
);

if ($stmt === false) {
    error_log('MySQL prepare statement failed: ' . $koneksi->error);
    header('Location: ../auth/login_seller.php?error=internal_server_error');
    exit;
}

$stmt->bind_param("ss", $username_or_email, $username_or_email);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah user ditemukan
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password
    if (password_verify($password, $user['password'])) {
        
        // Cek jika akun diblokir
        if ($user['is_banned'] == 1) {
            header('Location: ../auth/login_seller.php?error=akun_diblokir');
            exit;
        }

        // Login sukses, reset sesi percobaan brute-force
        unset($_SESSION['seller_login_attempts'], $_SESSION['seller_blocked_until']);

        // ============================================================
        // PERBAIKAN UTAMA: MENYESUAIKAN SESSION DENGAN DASHBOARD.PHP
        // ============================================================
        
        // 1. Wajib ada biar gak ditendang dashboard (baris 18 dashboard)
        $_SESSION['logged_in'] = true; 

        // 2. Wajib ada buat query toko (baris 22 dashboard)
        $_SESSION['user_id'] = $user['id']; 

        // 3. Wajib 'seller' (baris 18 dashboard)
        $_SESSION['level'] = $user['level']; 

        // 4. Tambahan buat tampilan nama
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        
        // Redirect ke dashboard seller
        header('Location: ../app_seller/dashboard.php'); 
        exit;

    } else {
        // Password salah
        $_SESSION['seller_login_attempts']++; 
        $sisa_percobaan = $max_attempts - $_SESSION['seller_login_attempts'];

        if ($_SESSION['seller_login_attempts'] >= $max_attempts) {
            $_SESSION['seller_blocked_until'] = $now + $block_duration;
            unset($_SESSION['seller_login_attempts']); 
            header("Location: ../auth/login_seller.php?error=terkunci&sisa={$block_duration}");
            exit;
        } else {
            header("Location: ../auth/login_seller.php?error=invalid_credentials&attempts_left={$sisa_percobaan}");
            exit;
        }
    }
} else {
    // User tidak ditemukan
    $_SESSION['seller_login_attempts']++; 
    $sisa_percobaan = $max_attempts - $_SESSION['seller_login_attempts'];

    if ($_SESSION['seller_login_attempts'] >= $max_attempts) {
        $_SESSION['seller_blocked_until'] = $now + $block_duration;
        unset($_SESSION['seller_login_attempts']);
        header("Location: ../auth/login_seller.php?error=terkunci&sisa={$block_duration}");
        exit;
    } else {
        header("Location: ../auth/login_seller.php?error=invalid_credentials&attempts_left={$sisa_percobaan}");
        exit;
    }
}

$stmt->close();
?>