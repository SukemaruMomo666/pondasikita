<?php
session_start();

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../keranjang.php");
    exit;
}

$id_item = intval($_GET['id']);

// Cek & hapus dari session
if (isset($_SESSION['keranjang'][$id_item])) {
    unset($_SESSION['keranjang'][$id_item]);
}

// Kembali ke keranjang
header("Location: ../keranjang.php");
exit;
