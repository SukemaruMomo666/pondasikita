<?php
session_start();
require_once '../config/koneksi.php'; // Path ke koneksi.php

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'] ?? null;
$stmt_toko->close();

if (!$toko_id) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => 'Data toko tidak ditemukan. Silakan login kembali.']);
        exit;
    } else {
        header("Location: ../auth/login.php?error=toko_tidak_valid");
        exit;
    }
}

// Pastikan direktori target ada dan memiliki izin tulis
$target_dir = "../assets/uploads/products/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // Buat direktori jika belum ada, dengan izin penuh
}

// ===========================================
// --- Aksi dari Form POST (Tambah / Update / Toggle Status) ---
// ===========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // --- Aksi Toggle Status Produk (khusus untuk AJAX) ---
    if ($_POST['action'] == 'toggle_status') {
        header('Content-Type: application/json');
        $product_id = (int)$_POST['product_id'];
        $is_active = (int)$_POST['is_active'];

        $update_stmt = $koneksi->prepare("UPDATE tb_barang SET is_active = ? WHERE id = ? AND toko_id = ?");
        $update_stmt->bind_param("iii", $is_active, $product_id, $toko_id);

        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Status produk berhasil diperbarui.']);
        } else {
            error_log("Error updating product status: " . $update_stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status produk: ' . $update_stmt->error]);
        }
        $update_stmt->close();
        exit;
    }
    // --- Aksi Tambah atau Update Produk dari Form ---
    else {
        // Ambil data form utama
        $nama_barang = $_POST['nama_barang'];
        $kategori_id = (int)$_POST['kategori_id'];
        // BARU: Ambil data kode_barang, set ke null jika kosong
        $kode_barang = (!empty($_POST['kode_barang'])) ? $_POST['kode_barang'] : null;
        $deskripsi = $_POST['deskripsi'];
        $harga = (float)$_POST['harga'];
        $stok = (int)$_POST['stok'];
        $berat_kg = (float)$_POST['berat_kg'];
        $satuan_unit = $_POST['satuan_unit'];
        $merk_barang = (!empty($_POST['merk_barang'])) ? $_POST['merk_barang'] : null;

        // Ambil data form diskon
        $tipe_diskon = (!empty($_POST['tipe_diskon'])) ? $_POST['tipe_diskon'] : null;
        $nilai_diskon = (isset($_POST['nilai_diskon']) && $_POST['nilai_diskon'] !== '') ? (float)$_POST['nilai_diskon'] : null;
        $diskon_mulai = (!empty($_POST['diskon_mulai'])) ? $_POST['diskon_mulai'] : null;
        $diskon_berakhir = (!empty($_POST['diskon_berakhir'])) ? $_POST['diskon_berakhir'] : null;

        $gambar_nama = '';
        if (isset($_FILES['gambar_utama']) && $_FILES['gambar_utama']['error'] == 0) {
            $file_extension = strtolower(pathinfo($_FILES['gambar_utama']['name'], PATHINFO_EXTENSION));
            $gambar_nama = "produk_" . $toko_id . "_" . time() . "." . $file_extension;

            if ($_POST['action'] === 'update' && isset($_POST['produk_id'])) {
                $produk_id_old = (int)$_POST['produk_id'];
                $q_old_img = $koneksi->prepare("SELECT gambar_utama FROM tb_barang WHERE id = ? AND toko_id = ?");
                $q_old_img->bind_param("ii", $produk_id_old, $toko_id);
                $q_old_img->execute();
                $old_img_result = $q_old_img->get_result()->fetch_assoc();
                $old_img = $old_img_result['gambar_utama'] ?? null;
                $q_old_img->close();

                if ($old_img && file_exists($target_dir . $old_img)) {
                    unlink($target_dir . $old_img);
                }
            }

            if (!move_uploaded_file($_FILES['gambar_utama']['tmp_name'], $target_dir . $gambar_nama)) {
                error_log("Failed to move uploaded file. Check permissions for: " . $target_dir);
                header("Location: ../app_seller/produk.php?status=gagal&error=" . urlencode("Gagal upload gambar. Periksa izin folder."));
                exit;
            }
        }

        if ($_POST['action'] === 'tambah') {
            // BARU: Tambahkan `kode_barang` ke dalam query
            $sql = "INSERT INTO tb_barang (toko_id, kategori_id, kode_barang, nama_barang, merk_barang, deskripsi, gambar_utama, harga, tipe_diskon, nilai_diskon, diskon_mulai, diskon_berakhir, satuan_unit, stok, berat_kg) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($sql);

            // BARU: Sesuaikan tipe (tambah 's' untuk kode_barang) dan jumlah jadi 15
            $types = "iisssssdsdsssid";
            $params_values = [
                &$toko_id, &$kategori_id, &$kode_barang, &$nama_barang, &$merk_barang, &$deskripsi, &$gambar_nama, &$harga,
                &$tipe_diskon, &$nilai_diskon, &$diskon_mulai, &$diskon_berakhir, &$satuan_unit, &$stok, &$berat_kg
            ];

            call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params_values));

        }
        elseif ($_POST['action'] === 'update') {
            $produk_id = (int)$_POST['produk_id'];
            if ($gambar_nama) { // Jika ada gambar baru
                // BARU: Tambahkan `kode_barang` ke dalam query
                $sql = "UPDATE tb_barang SET kategori_id=?, kode_barang=?, nama_barang=?, merk_barang=?, deskripsi=?, gambar_utama=?, harga=?, tipe_diskon=?, nilai_diskon=?, diskon_mulai=?, diskon_berakhir=?, satuan_unit=?, stok=?, berat_kg=? WHERE id=? AND toko_id=?";
                $stmt = $koneksi->prepare($sql);

                // BARU: Sesuaikan tipe dan jumlah jadi 16
                $types = "isssssdsdsssidii";
                $params_values = [
                    &$kategori_id, &$kode_barang, &$nama_barang, &$merk_barang, &$deskripsi, &$gambar_nama, &$harga,
                    &$tipe_diskon, &$nilai_diskon, &$diskon_mulai, &$diskon_berakhir, &$satuan_unit, &$stok, &$berat_kg,
                    &$produk_id, &$toko_id
                ];

                call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params_values));

            } else { // Update tanpa mengubah gambar
                // BARU: Tambahkan `kode_barang` ke dalam query
                $sql = "UPDATE tb_barang SET kategori_id=?, kode_barang=?, nama_barang=?, merk_barang=?, deskripsi=?, harga=?, tipe_diskon=?, nilai_diskon=?, diskon_mulai=?, diskon_berakhir=?, satuan_unit=?, stok=?, berat_kg=? WHERE id=? AND toko_id=?";
                $stmt = $koneksi->prepare($sql);
                
                // BARU: Sesuaikan tipe dan jumlah jadi 15
                $types = "issssdsdsssiidii";
                $params_values = [
                    &$kategori_id, &$kode_barang, &$nama_barang, &$merk_barang, &$deskripsi, &$harga,
                    &$tipe_diskon, &$nilai_diskon, &$diskon_mulai, &$diskon_berakhir, &$satuan_unit, &$stok, &$berat_kg,
                    &$produk_id, &$toko_id
                ];

                call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params_values));
            }
        }

        if ($stmt->execute()) {
             header("Location: ../app_seller/produk.php?status=sukses");
        } else {
            error_log("Error processing product (add/update): " . $stmt->error . " | SQL: " . $sql);
            header("Location: ../app_seller/produk.php?status=gagal&error=" . urlencode($stmt->error));
        }
        $stmt->close();
        exit;
    }
}

// =======================
// --- Aksi Hapus dari GET ---
// =======================
if (isset($_GET['hapus'])) {
    $produk_id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("SELECT gambar_utama FROM tb_barang WHERE id = ? AND toko_id = ?");
    $stmt->bind_param("ii", $produk_id, $toko_id);
    $stmt->execute();
    $gambar_result = $stmt->get_result()->fetch_assoc();
    $gambar = $gambar_result['gambar_utama'] ?? null;
    $stmt->close();

    if ($gambar && file_exists($target_dir . $gambar)) {
        unlink($target_dir . $gambar);
    }

    $stmt_delete = $koneksi->prepare("DELETE FROM tb_barang WHERE id = ? AND toko_id = ?");
    $stmt_delete->bind_param("ii", $produk_id, $toko_id);

    if ($stmt_delete->execute()) {
        header("Location: ../app_seller/produk.php?status=hapus_sukses");
    } else {
        error_log("Error deleting product: " . $stmt_delete->error);
        header("Location: ../app_seller/produk.php?status=hapus_gagal&error=" . urlencode($stmt_delete->error));
    }
    $stmt_delete->close();
    exit;
}

// Redirect default jika tidak ada aksi yang cocok atau request GET
header("Location: ../app_seller/produk.php");
exit;