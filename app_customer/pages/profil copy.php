<?php
session_start();
require '../config/koneksi.php';

// Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../auth/signin.php");
    exit;
}

$id_user = $_SESSION['user']['id'];

// PERBAIKAN KEAMANAN: Menggunakan Prepared Statement
$stmt = $koneksi->prepare("SELECT * FROM tb_user WHERE id = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya</title>
    <link rel="stylesheet" href="../assets/css/navbar.css"> 
    <link rel="stylesheet" href="../assets/css/profil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-profil">
        <h2><i class="fa-solid fa-user-circle"></i> Profil Saya</h2>
        <div class="profil-box">
            
            <p><strong>Nama Pengguna:</strong> <?= htmlspecialchars($user['username']) ?></p>
            
            <p><strong>Nama Lengkap:</strong> <?= htmlspecialchars($user['nama']) ?? '-' ?></p>
            
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>No HP:</strong> <?= htmlspecialchars($user['no_telepon']) ?? '-' ?></p>
            
            <p><strong>Jenis Kelamin:</strong> <?= htmlspecialchars($user['jenis_kelamin'] ?? '-') ?></p>
            
            <p><strong>Tanggal Lahir:</strong> <?= !empty($user['tanggal_lahir']) ? date('d M Y', strtotime($user['tanggal_lahir'])) : '-' ?></p>
            <p><strong>Alamat:</strong> <?= htmlspecialchars($user['alamat']) ?? '-' ?></p>

            <p><strong>Tanggal Bergabung:</strong> <?= date('d M Y', strtotime($user['created_at'])) ?></p>

            <div class="profil-actions">
                <a href="crud_profil/edit_profil.php" class="btn-edit"><i class="fa-solid fa-pen-to-square"></i> Edit Profil</a>
                <a href="crud_profil/ganti_password.php" class="btn-password"><i class="fa-solid fa-key"></i> Ganti Password</a>
            </div>
        </div>
    </div>
</body>
</html>