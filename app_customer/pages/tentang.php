<?php
$password = "admin123"; // Ganti dengan password yang kamu inginkan
$hashed = password_hash($password, PASSWORD_BCRYPT);

echo "Password hash: " . $hashed;
?>