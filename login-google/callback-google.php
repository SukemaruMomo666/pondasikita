<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/koneksi.php';

use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

// Konfigurasi Google OAuth
$clientID = '92626258010-dtctpraoq0vv750119lofe4o36j80shl.apps.googleusercontent.com';
$clientSecret = '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';

// Langsung set redirect URI karena domainnya sama untuk lokal dan produksi
$redirectUri = 'http://pondasikita.com/login-google/callback-google.php';
 

// Inisialisasi Google Client
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri); // Variabel ini sekarang selalu benar
$client->addScope('email');
$client->addScope('profile');

try {
    // Saat kembali dari Google dengan code
    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);

            $googleService = new Google_Service_Oauth2($client);
            $info = $googleService->userinfo->get();

            $email = $info->email;
            $nama = $info->name;
            $username = explode('@', $email)[0];
            $default_level = 'customer';

            // Cek apakah email sudah ada
            $stmt = $koneksi->prepare("SELECT id, username, nama, level, is_banned FROM tb_user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if ($user['is_banned'] == 1) {
                    header('Location: ../auth/login_customer.php?error=akun_diblokir');
                    exit;
                }

                // Login: Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['logged_in'] = true;

            } else {
                // Belum ada → Daftarkan otomatis
                $stmt_insert = $koneksi->prepare("INSERT INTO tb_user (username, email, nama, password, level) VALUES (?, ?, ?, '', ?)");
                $stmt_insert->bind_param("ssss", $username, $email, $nama, $default_level);
                $stmt_insert->execute();

                $_SESSION['user_id'] = $stmt_insert->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['nama'] = $nama;
                $_SESSION['level'] = $default_level;
                $_SESSION['logged_in'] = true;
            }

            // Redirect sesuai level
            switch ($_SESSION['level']) {
                case 'admin':
                    header('Location: ../app_admin/dashboard_admin.php');
                    break;
                case 'seller':
                    header('Location: ../app_seller/index.php');
                    break;
                default:
                    header('Location: ../index.php');
                    break;
            }
            exit;
        } else {
            header('Location: ../auth/login_customer.php?error=token_invalid');
            exit;
        }
    } else {
        header('Location: ../auth/login_customer.php?error=kode_tidak_ada');
        exit;
    }
} catch (Exception $e) {
    // Tangani error internal
    error_log('Google login error: ' . $e->getMessage());
    header('Location: ../auth/login_customer.php?error=google_exception');
    exit;
}
