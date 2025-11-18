<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller' || !isset($_POST['action'])) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];
$toko_id = 0;

// Ambil toko_id berdasarkan user_id
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();
if ($toko = $result_toko->fetch_assoc()) {
    $toko_id = $toko['id'];
}
$stmt_toko->close();

if ($toko_id === 0) {
    $_SESSION['error_message'] = "Toko tidak ditemukan.";
    header("Location: ../app_seller/dashboard.php");
    exit;
}

switch ($action) {
    case 'simpan_profil':
        $nama_toko = trim($_POST['nama_toko']);
        $deskripsi_toko = trim($_POST['deskripsi_toko']);
        $redirect_tab = 'profil';

        // Logika Upload Logo
        $logo_toko = null;
        if (isset($_FILES['logo_toko']) && $_FILES['logo_toko']['error'] == 0) {
            $target_dir = "../assets/uploads/logos/";
            $file_extension = pathinfo($_FILES["logo_toko"]["name"], PATHINFO_EXTENSION);
            $new_filename = "logo_" . $toko_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Validasi file
            $allowed_types = ['jpg', 'jpeg', 'png'];
            if (in_array(strtolower($file_extension), $allowed_types) && $_FILES["logo_toko"]["size"] <= 2000000) {
                if (move_uploaded_file($_FILES["logo_toko"]["tmp_name"], $target_file)) {
                    $logo_toko = $new_filename;
                }
            }
        }

        if ($logo_toko) {
            $stmt = $koneksi->prepare("UPDATE tb_toko SET nama_toko = ?, deskripsi_toko = ?, logo_toko = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama_toko, $deskripsi_toko, $logo_toko, $toko_id);
        } else {
            $stmt = $koneksi->prepare("UPDATE tb_toko SET nama_toko = ?, deskripsi_toko = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nama_toko, $deskripsi_toko, $toko_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profil toko berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui profil toko.";
        }
        $stmt->close();
        break;

    case 'simpan_pengaturan_dasar':
        $status_operasional = $_POST['status_operasional'];
        $redirect_tab = 'pengaturan';
        
        $koneksi->begin_transaction();
        try {
            $stmt_status = $koneksi->prepare("UPDATE tb_toko SET status_operasional = ? WHERE id = ?");
            $stmt_status->bind_param("si", $status_operasional, $toko_id);
            $stmt_status->execute();
            $stmt_status->close();
            
            // Proses Jam Operasional
            $stmt_jam = $koneksi->prepare("INSERT INTO tb_toko_jam_operasional (toko_id, hari, is_buka, jam_buka, jam_tutup) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_buka=VALUES(is_buka), jam_buka=VALUES(jam_buka), jam_tutup=VALUES(jam_tutup)");
            for ($i = 1; $i <= 7; $i++) {
                $is_buka = isset($_POST['is_buka'][$i]) ? 1 : 0;
                $jam_buka = !empty($_POST['jam_buka'][$i]) ? $_POST['jam_buka'][$i] : null;
                $jam_tutup = !empty($_POST['jam_tutup'][$i]) ? $_POST['jam_tutup'][$i] : null;
                $stmt_jam->bind_param("iisss", $toko_id, $i, $is_buka, $jam_buka, $jam_tutup);
                $stmt_jam->execute();
            }
            $stmt_jam->close();
            
            $koneksi->commit();
            $_SESSION['success_message'] = "Pengaturan dasar berhasil disimpan.";
        } catch (Exception $e) {
            $koneksi->rollback();
            $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        }
        break;
        
    case 'simpan_alamat':
        $alamat_toko = trim($_POST['alamat_toko']);
        $province_id = (int)$_POST['province_id'];
        $city_id = (int)$_POST['city_id'];
        $district_id = (int)$_POST['district_id'];
        $kode_pos = trim($_POST['kode_pos']);
        $telepon_toko = trim($_POST['telepon_toko']);
        $redirect_tab = 'alamat';

        $stmt = $koneksi->prepare("UPDATE tb_toko SET alamat_toko = ?, province_id = ?, city_id = ?, district_id = ?, kode_pos = ?, telepon_toko = ? WHERE id = ?");
        $stmt->bind_param("siiissi", $alamat_toko, $province_id, $city_id, $district_id, $kode_pos, $telepon_toko, $toko_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Alamat toko berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui alamat.";
        }
        $stmt->close();
        break;
    
    // Anda bisa tambahkan case untuk 'simpan_pengiriman' dan 'simpan_notifikasi' di sini
    // ...

    default:
        $_SESSION['error_message'] = "Aksi tidak valid.";
        $redirect_tab = 'profil';
        break;
}

header("Location: ../app_seller/pengaturan_toko.php?tab=" . $redirect_tab);
exit();
?>