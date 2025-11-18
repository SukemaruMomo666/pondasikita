<?php
include '../../config/koneksi.php';
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../../auth/signin.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Validasi parameter ID keranjang
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../keranjang.php");
    exit;
}

$id_keranjang = intval($_GET['id']);

// Cek apakah item keranjang milik user
$query_cek = "SELECT * FROM tb_keranjang WHERE id = $id_keranjang AND user_id = $id_user";
$result_cek = mysqli_query($koneksi, $query_cek);

if ($result_cek && mysqli_num_rows($result_cek) > 0) {
    // Hapus item dari database
    $query_hapus = "DELETE FROM tb_keranjang WHERE id = $id_keranjang";
    if (!mysqli_query($koneksi, $query_hapus)) {
        die("Gagal menghapus item: " . mysqli_error($koneksi));
    }
}

// Kembali ke keranjang
header("Location: ../keranjang.php");
exit;
