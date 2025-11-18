<?php
session_start();
require '../../../config/koneksi.php';

// --- BAGIAN DEBUGGING (JANGAN DIHAPUS DULU SAAT UJI COBA) ---
// Perhatikan bahwa print_r($_SESSION) akan menampilkan seluruh isi SESSION
// Ini termasuk $_SESSION['user']['level']
echo '<pre>SESSION: ';
print_r($_SESSION);
echo '</pre>';

// Debugging yang benar untuk level admin
echo 'Is isset($_SESSION[\'user\'][\'level\'])? ' . (isset($_SESSION['user']['level']) ? 'Yes' : 'No') . '<br>';
echo 'Value of $_SESSION[\'user\'][\'level\']: ' . ($_SESSION['user']['level'] ?? 'N/A') . '<br>';
echo 'Comparison $_SESSION[\'user\'][\'level\'] !== \'admin\': ' . (($_SESSION['user']['level'] ?? '') !== 'admin' ? 'True' : 'False') . '<br>';
// -------------------------------------------------------------

// Cek apakah user memiliki akses admin
// Perubahan di sini: Menggunakan $_SESSION['user']['level']
if (!isset($_SESSION['user']['level']) || $_SESSION['user']['level'] !== 'admin') {
    die('Akses ditolak! Anda tidak memiliki izin admin.');
}

// Ambil data ID dan status dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$aksi = isset($_GET['status']) ? (int)$_GET['status'] : null;

// --- BAGIAN DEBUGGING (JANGAN DIHAPUS DULU SAAT UJI COBA) ---
echo '<pre>GET: ';
print_r($_GET);
echo '</pre>';
// -------------------------------------------------------------

// Validasi aksi
if (!in_array($aksi, [0, 1], true)) {
    die('Aksi tidak valid! Status harus 0 (non-banned) atau 1 (banned).');
}

// Debug koneksi database
if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal atau variabel $koneksi tidak tersedia. Periksa koneksi.php');
}

// Cek apakah ID ada di database
$sql_check = "SELECT id FROM tb_user WHERE id = ?";
$stmt_check = $koneksi->prepare($sql_check);
if (!$stmt_check) {
    die('Error preparing statement (check ID): ' . $koneksi->error);
}
$stmt_check->bind_param('i', $id);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows === 0) {
    die('ID pengguna tidak ditemukan di database.');
}

// Update status banned di database
$is_banned = $aksi;
$sql_update = "UPDATE tb_user SET is_banned = ? WHERE id = ?";
$stmt_update = $koneksi->prepare($sql_update);
if (!$stmt_update) {
    die('Error preparing statement (update status): ' . $koneksi->error);
}
$stmt_update->bind_param('ii', $is_banned, $id);

if (!$stmt_update->execute()) {
    die('Error saat memperbarui status: ' . $stmt_update->error);
}

// Tutup koneksi
$stmt_check->close();
$stmt_update->close();
$koneksi->close();

// Redirect kembali ke daftar pelanggan setelah berhasil
header('Location: data_customer.php?status=success_update');
exit;
?>