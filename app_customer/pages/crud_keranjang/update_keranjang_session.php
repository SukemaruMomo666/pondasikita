<?php
session_start();
$barang_id = $_POST['barang_id'];
$jumlah = $_POST['jumlah'];

if (isset($_SESSION['keranjang'][$barang_id])) {
    $_SESSION['keranjang'][$barang_id] = $jumlah;
}

header("Location: ../keranjang.php");
