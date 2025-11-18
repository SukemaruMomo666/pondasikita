<?php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $event_id = $_POST['event_id'] ?? null;
    $nama_event = trim($_POST['nama_event']);
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_berakhir = $_POST['tanggal_berakhir'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validasi dasar
    if (empty($nama_event) || empty($tanggal_mulai) || empty($tanggal_berakhir)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Nama event dan periode wajib diisi.'];
        header("Location: ../form_flash_sale.php" . ($event_id ? "?id=$event_id" : ""));
        exit;
    }

    // --- LOGIKA UPLOAD BANNER ---
    $banner_filename = $_POST['banner_lama'] ?? ''; // Untuk mode edit
    if (isset($_FILES['banner_event']) && $_FILES['banner_event']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['banner_event'];
        $upload_dir = '../../assets/uploads/flash_sale/';
        $allowed_types = ['image/jpeg', 'image/png'];
        
        if (in_array($file['type'], $allowed_types)) {
            $banner_filename = "fs_" . time() . "_" . basename($file['name']);
            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $banner_filename)) {
                $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal mengupload banner.'];
                header("Location: ../form_flash_sale.php" . ($event_id ? "?id=$event_id" : ""));
                exit;
            }
        } else {
            $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Format file banner tidak valid (hanya .jpg atau .png).'];
            header("Location: ../form_flash_sale.php" . ($event_id ? "?id=$event_id" : ""));
            exit;
        }
    }

    // --- PROSES INSERT ATAU UPDATE ---
    if ($event_id) { // Mode Edit
        $sql = "UPDATE tb_flash_sale_events SET nama_event = ?, banner_event = ?, tanggal_mulai = ?, tanggal_berakhir = ?, is_active = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ssssii", $nama_event, $banner_filename, $tanggal_mulai, $tanggal_berakhir, $is_active, $event_id);
    } else { // Mode Tambah
        $sql = "INSERT INTO tb_flash_sale_events (nama_event, banner_event, tanggal_mulai, tanggal_berakhir, is_active) VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ssssi", $nama_event, $banner_filename, $tanggal_mulai, $tanggal_berakhir, $is_active);
    }

    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'sukses', 'pesan' => 'Event Flash Sale berhasil disimpan.'];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menyimpan event: ' . $stmt->error];
    }
    $stmt->close();
}

header("Location: ../kelola_flash_sale.php");
exit;
?>
