<?php
session_start();
require_once '../../config/koneksi.php';

// --- PENGAMANAN & VALIDASI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Siapkan query yang efisien untuk update atau insert
    $sql = "INSERT INTO tb_pengaturan (setting_nama, setting_nilai) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_nilai = VALUES(setting_nilai)";
    $stmt = $koneksi->prepare($sql);

    if ($stmt === false) {
        die("Error preparing statement: " . $koneksi->error);
    }

    // Loop melalui semua data yang dikirim dari form
    foreach ($_POST as $nama_setting => $nilai_setting) {
        // Trim nilai untuk membersihkan spasi yang tidak perlu
        $nilai_bersih = trim($nilai_setting);
        
        // Bind parameter dan eksekusi untuk setiap setting
        $stmt->bind_param("ss", $nama_setting, $nilai_bersih);
        $stmt->execute();
    }

    $stmt->close();
    
    $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Pengaturan website berhasil diperbarui.'];

} else {
    $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Akses tidak sah.'];
}

// Kembalikan admin ke halaman pengaturan
header("Location: ../pengaturan_website.php");
exit;
?>
