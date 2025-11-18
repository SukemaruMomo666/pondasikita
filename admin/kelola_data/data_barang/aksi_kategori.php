<?php
// kelola_kategori/aksi_kategori.php
header('Content-Type: application/json');
require_once '../../../config/koneksi.php'; // Pastikan path ini benar

$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];

if (isset($_GET['act'])) {
    $action = $_GET['act'];

    switch ($action) {
        case 'insert':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nama_kategori = trim($_POST['nama_kategori'] ?? '');
                $deskripsi = trim($_POST['deskripsi'] ?? '');
                // $gambar = null; // No longer needed

                if (empty($nama_kategori)) {
                    $response = ['status' => 'error', 'message' => 'Nama kategori tidak boleh kosong.'];
                    echo json_encode($response);
                    exit();
                }

                // Cek apakah kategori sudah ada
                $check_query = "SELECT COUNT(*) FROM tb_kategori WHERE nama_kategori = ?";
                $stmt_check = $koneksi->prepare($check_query);
                if (!$stmt_check) {
                    $response = ['status' => 'error', 'message' => 'Gagal menyiapkan cek kategori: ' . $koneksi->error];
                    echo json_encode($response);
                    exit();
                }
                $stmt_check->bind_param("s", $nama_kategori);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $response = ['status' => 'error', 'message' => 'Kategori dengan nama tersebut sudah ada.'];
                    echo json_encode($response);
                    exit();
                }

                // Hapus semua logika upload gambar
                
                // Insert data (sesuaikan query jika kolom 'gambar' dihapus dari DB)
                // Jika kolom 'gambar' masih ada di DB tapi tidak ingin diisi, maka biarkan null/default
                $insert_query = "INSERT INTO tb_kategori (nama_kategori, deskripsi) VALUES (?, ?)"; // Updated query
                $stmt_insert = $koneksi->prepare($insert_query);
                if (!$stmt_insert) {
                    $response = ['status' => 'error', 'message' => 'Gagal menyiapkan insert: ' . $koneksi->error];
                    echo json_encode($response);
                    exit();
                }
                $stmt_insert->bind_param("ss", $nama_kategori, $deskripsi); // Updated bind_param
                if ($stmt_insert->execute()) {
                    $response = ['status' => 'success', 'message' => 'Kategori berhasil ditambahkan.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal menambahkan kategori: ' . $stmt_insert->error];
                }
                $stmt_insert->close();
            } else {
                $response = ['status' => 'error', 'message' => 'Metode request tidak valid untuk insert.'];
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;
                $nama_kategori = trim($_POST['nama_kategori'] ?? '');
                $deskripsi = trim($_POST['deskripsi'] ?? '');
                // $gambar_lama = $_POST['gambar_lama'] ?? null; // No longer needed
                // $gambar_baru = $gambar_lama; // No longer needed

                if (empty($id) || empty($nama_kategori)) {
                    $response = ['status' => 'error', 'message' => 'ID dan nama kategori tidak boleh kosong.'];
                    echo json_encode($response);
                    exit();
                }

                // Cek apakah kategori dengan nama yang sama sudah ada (kecuali kategori itu sendiri)
                $check_query = "SELECT COUNT(*) FROM tb_kategori WHERE nama_kategori = ? AND id != ?";
                $stmt_check = $koneksi->prepare($check_query);
                if (!$stmt_check) {
                    $response = ['status' => 'error', 'message' => 'Gagal menyiapkan cek kategori update: ' . $koneksi->error];
                    echo json_encode($response);
                    exit();
                }
                $stmt_check->bind_param("si", $nama_kategori, $id);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $response = ['status' => 'error', 'message' => 'Kategori dengan nama tersebut sudah ada.'];
                    echo json_encode($response);
                    exit();
                }

                // Hapus semua logika upload gambar baru

                // Update data (sesuaikan query jika kolom 'gambar' dihapus dari DB)
                $update_query = "UPDATE tb_kategori SET nama_kategori = ?, deskripsi = ? WHERE id = ?"; // Updated query
                $stmt_update = $koneksi->prepare($update_query);
                if (!$stmt_update) {
                    $response = ['status' => 'error', 'message' => 'Gagal menyiapkan update: ' . $koneksi->error];
                    echo json_encode($response);
                    exit();
                }
                $stmt_update->bind_param("ssi", $nama_kategori, $deskripsi, $id); // Updated bind_param
                if ($stmt_update->execute()) {
                    $response = ['status' => 'success', 'message' => 'Kategori berhasil diperbarui.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal memperbarui kategori: ' . $stmt_update->error];
                }
                $stmt_update->close();
            } else {
                $response = ['status' => 'error', 'message' => 'Metode request tidak valid untuk update.'];
            }
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;

                if (empty($id)) {
                    $response = ['status' => 'error', 'message' => 'ID kategori tidak boleh kosong.'];
                    echo json_encode($response);
                    exit();
                }

                // Cek apakah ada produk yang terkait dengan kategori ini
                $check_produk_query = "SELECT COUNT(*) FROM tb_barang WHERE kategori_id = ?";
                $stmt_produk_check = $koneksi->prepare($check_produk_query);
                if (!$stmt_produk_check) {
                    $response = ['status' => 'error', 'message' => 'Gagal menyiapkan cek produk: ' . $koneksi->error];
                    echo json_encode($response);
                    exit();
                }
                $stmt_produk_check->bind_param("i", $id);
                $stmt_produk_check->execute();
                $stmt_produk_check->bind_result($jumlah_produk);
                $stmt_produk_check->fetch();
                $stmt_produk_check->close();

                if ($jumlah_produk > 0) {
                    $response = ['status' => 'error', 'message' => 'Tidak dapat menghapus kategori karena masih ada ' . $jumlah_produk . ' produk yang terkait.'];
                    echo json_encode($response);
                    exit();
                }

                // Hapus logika untuk ambil dan hapus gambar lama
                // $get_gambar_query = "SELECT gambar FROM tb_kategori WHERE id = ?";
                // ... (seluruh blok kode ini dihapus)

                // Hapus kategori
                $delete_query = "DELETE FROM tb_kategori WHERE id = ?";
                $stmt_delete = $koneksi->prepare($delete_query);
                if (!$stmt_delete) {
                    $response = ['status' => 'error', 'message' => 'Gagal menyiapkan delete: ' . $koneksi->error];
                    echo json_encode($response);
                    exit();
                }
                $stmt_delete->bind_param("i", $id);
                if ($stmt_delete->execute()) {
                    // Hapus file gambar jika ada (logika ini juga dihapus)
                    // if ($gambar_to_delete && file_exists("uploads/kategori/" . $gambar_to_delete)) {
                    //     unlink("uploads/kategori/" . $gambar_to_delete);
                    // }
                    $response = ['status' => 'success', 'message' => 'Kategori berhasil dihapus.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal menghapus kategori: ' . $stmt_delete->error];
                }
                $stmt_delete->close();
            } else {
                $response = ['status' => 'error', 'message' => 'Metode request tidak valid untuk delete.'];
            }
            break;

        default:
            $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];
            break;
    }
}

echo json_encode($response);
$koneksi->close();
?>