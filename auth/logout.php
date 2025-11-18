<?php
// Mulai session
session_start();

// Hapus semua data session
session_unset();

// Hancurkan session
session_destroy();

// Langsung arahkan kembali ke halaman login
header("Location: login_customer.php");
exit;
?>