<?php
/**
 * File koneksi database
 * Sesuaikan nama host, user, password, dan nama database.
 */


$db_host = 'localhost';
$db_user = 'root'; // Ganti dengan username database Anda
$db_pass = 'root';     // Ganti dengan password database Anda
$db_name = 'pondasikita_db5';

$koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
}

// Set karakter set ke utf8mb4 untuk mendukung berbagai karakter
$koneksi->set_charset("utf8mb4");

?>