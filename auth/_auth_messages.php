<?php
// auth/_auth_messages.php

// Daftar pesan error
$errors = [
    'password_salah' => 'Kata sandi salah.',
    'user_tidak_ditemukan' => 'Username atau email tidak ditemukan untuk peran ini.',
    'akun_diblokir' => 'Akun Anda telah diblokir. Hubungi admin.',
    'akun_belum_verif' => 'Akun Anda sedang dalam proses verifikasi oleh admin.',
    'kolom_kosong' => 'Username dan password wajib diisi.'
];

// Daftar pesan status sukses
$statuses = [
    'reg_success' => 'Pendaftaran berhasil! Silakan masuk.',
    'reg_seller_success' => 'Pendaftaran toko berhasil! Akun Anda akan diverifikasi oleh admin.'
];

// Tampilkan kotak pesan jika ada parameter di URL
if (isset($_GET['error']) && isset($errors[$_GET['error']])) {
    echo '<div class="message-box error">' . htmlspecialchars($errors[$_GET['error']]) . '</div>';
}

if (isset($_GET['status']) && isset($statuses[$_GET['status']])) {
    echo '<div class="message-box success">' . htmlspecialchars($statuses[$_GET['status']]) . '</div>';
}
?>