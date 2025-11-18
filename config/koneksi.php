<?php
/**
 * File koneksi database
 * Sesuaikan nama host, user, password, dan nama database.
 */


$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = '';     
$db_name = 'pondasikita_db';

$koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
}

// Set karakter set ke utf8mb4 untuk mendukung berbagai karakter
$koneksi->set_charset("utf8mb4");

?>