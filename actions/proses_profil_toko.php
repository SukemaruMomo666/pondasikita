<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php"); exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id, logo_toko, banner_toko FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id); $stmt_toko->execute();
$toko = $stmt_toko->get_result()->fetch_assoc();
$toko_id = $toko['id'];
if (!$toko_id) die("Toko tidak valid.");

// Proses jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_toko = $_POST['nama_toko'];
    $deskripsi_toko = $_POST['deskripsi_toko'];
    $telepon_toko = $_POST['telepon_toko'];
    $alamat_toko = $_POST['alamat_toko'];
    // Tambahkan field lain jika ada (kota, provinsi, dll)

    // Fungsi untuk upload file
    function uploadGambar($file_input, $toko_id, $prefix, $folder, $gambar_lama) {
        if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] == 0) {
            $target_dir = "../assets/uploads/$folder/";
            // Hapus gambar lama jika ada
            if ($gambar_lama && file_exists($target_dir . $gambar_lama)) {
                unlink($target_dir . $gambar_lama);
            }
            // Buat nama file baru yang unik
            $ext = strtolower(pathinfo($_FILES[$file_input]['name'], PATHINFO_EXTENSION));
            $nama_file_baru = "{$prefix}_{$toko_id}_" . time() . ".{$ext}";
            move_uploaded_file($_FILES[$file_input]['tmp_name'], $target_dir . $nama_file_baru);
            return $nama_file_baru;
        }
        return null;
    }

    $logo_baru = uploadGambar('logo_toko', $toko_id, 'logo', 'logos', $toko['logo_toko']);
    $banner_baru = uploadGambar('banner_toko', $toko_id, 'banner', 'banners', $toko['banner_toko']);

    // Bangun query UPDATE secara dinamis
    $sql = "UPDATE tb_toko SET nama_toko=?, deskripsi_toko=?, telepon_toko=?, alamat_toko=?";
    $params = [$nama_toko, $deskripsi_toko, $telepon_toko, $alamat_toko];
    $types = 'ssss';

    if ($logo_baru) {
        $sql .= ", logo_toko=?";
        $types .= 's';
        $params[] = $logo_baru;
    }
    if ($banner_baru) {
        $sql .= ", banner_toko=?";
        $types .= 's';
        $params[] = $banner_baru;
    }

    $sql .= " WHERE id = ?";
    $types .= 'i';
    $params[] = $toko_id;

    $stmt_update = $koneksi->prepare($sql);
    $stmt_update->bind_param($types, ...$params);
    $stmt_update->execute();

    header("Location: ../app_seller/profil.php?status=sukses");
    exit;
}
?>