<?php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengguna'])) {
    
    // Ambil data dari form
    $user_id = $_POST['user_id'] ?? null;
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $level = $_POST['level'];

    // --- MODE EDIT ---
    if ($user_id) {
        $sql = "UPDATE tb_user SET nama = ?, username = ?, email = ?, level = ?";
        $types = "ssss";
        $params = [$nama, $username, $email, $level];

        // Jika password diisi, tambahkan ke query update
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql .= ", password = ?";
            $types .= "s";
            $params[] = $hashed_password;
        }

        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Data pengguna berhasil diperbarui.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal memperbarui data: ' . $stmt->error];
        }
        $stmt->close();
    } 
    // --- MODE TAMBAH ---
    else {
        if (empty($password)) {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Password wajib diisi untuk pengguna baru.'];
            header("Location: ../form_pengguna.php");
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO tb_user (nama, username, email, password, level, is_verified) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssss", $nama, $username, $email, $hashed_password, $level);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Pengguna baru berhasil ditambahkan.'];
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menambahkan pengguna: ' . $stmt->error];
        }
        $stmt->close();
    }
}

header("Location: ../kelola_pengguna.php");
exit;
?>
