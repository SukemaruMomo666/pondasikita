<?php
require '../config/koneksi.php'; // Sesuaikan path jika perlu

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>--- TES FINAL VALIDASI TOKEN ---</h1>";

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("<p style='color:red;'><b>ERROR:</b> Tidak ada token di URL. Pastikan URL Anda benar.</p>");
}

$token_asli_dari_url = $_GET['token'];
$hash_yang_dicari = hash('sha256', $token_asli_dari_url);

echo "<p><b>Token yang diterima dari URL:</b><br>" . htmlspecialchars($token_asli_dari_url) . "</p>";
echo "<p><b>Hash yang dicari di Database:</b><br><b style='color:blue;'>" . htmlspecialchars($hash_yang_dicari) . "</b></p>";
echo "<hr>";

// Set timezone
// DENGAN BARIS INI
$koneksi->query("SET time_zone = '+07:00'");

// Persiapkan dan jalankan query
$stmt = $koneksi->prepare("SELECT id, email, reset_token, reset_token_expires_at FROM tb_user WHERE reset_token = ?");
$stmt->bind_param("s", $hash_yang_dicari);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<h2>Hasil Pencarian di Database:</h2>";

if ($user) {
    echo "<h3 style='color:green;'>BERHASIL! Pengguna Ditemukan.</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    print_r($user);
    echo "</pre>";
    echo "<p>Ini membuktikan query Anda bekerja. Jika halaman reset masih error, berarti ada masalah di file `reset_password.php` itu sendiri.</p>";
} else {
    echo "<h3 style='color:red;'>GAGAL! Pengguna Tidak Ditemukan.</h3>";
    echo "<p>Ini adalah akar masalahnya. Database tidak dapat menemukan baris mana pun yang memiliki `reset_token` yang sama dengan hash berwarna biru di atas.</p>";
    echo "<p><b>Solusi:</b> Buka phpMyAdmin, bandingkan hash biru di atas dengan hash yang tersimpan di database. Pastikan keduanya 100% sama persis, tanpa ada spasi atau karakter tersembunyi.</p>";
}

echo "</div>";

$stmt->close();
$koneksi->close();
?>