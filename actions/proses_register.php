<?php
/**
 * File ini HANYA untuk memproses data. Tidak ada HTML di sini.
 * Menerima data dari form, memvalidasi, dan menyimpan ke database.
 */

require_once '../config/koneksi.php';

// Atur header untuk merespons sebagai JSON
header('Content-Type: application/json');

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

// Validasi input
if (empty($_POST['username']) || empty($_POST['nama_lengkap']) || empty($_POST['email']) || empty($_POST['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
    exit;
}

$username = $_POST['username'];
$nama = $_POST['nama_lengkap'];
$email = $_POST['email'];
$password = $_POST['password'];

// Cek apakah username atau email sudah ada
$stmt = $koneksi->prepare("SELECT id FROM tb_user WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username atau email sudah terdaftar.']);
    $stmt->close();
    $koneksi->close();
    exit;
}
$stmt->close();

// Hash password sebelum disimpan
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert user baru dengan level 'customer'
$stmt = $koneksi->prepare("INSERT INTO tb_user (username, password, nama, email, level) VALUES (?, ?, ?, ?, 'customer')");
$stmt->bind_param("ssss", $username, $hashed_password, $nama, $email);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil! Anda akan diarahkan ke halaman login.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat pendaftaran.']);
}

$stmt->close();
$koneksi->close();
?>