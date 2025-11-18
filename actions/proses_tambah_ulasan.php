<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/koneksi.php';

// Atur header untuk merespon sebagai JSON
header('Content-Type: application/json');

// --- Fungsi untuk mengirim respon JSON dan menghentikan skrip ---
function send_json_response($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// --- 1. Validasi Keamanan & Input Dasar ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Metode request tidak valid.');
}

if (!isset($_SESSION['user']['id'])) {
    send_json_response('error', 'Akses ditolak. Silakan login terlebih dahulu.');
}

$id_user = (int)$_SESSION['user']['id'];
$id_transaksi = isset($_POST['transaksi_id']) ? (int)$_POST['transaksi_id'] : 0;
$id_barang = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$ulasan = isset($_POST['ulasan']) ? trim($_POST['ulasan']) : '';

// Validasi input yang wajib diisi
if (empty($id_transaksi) || empty($id_barang) || empty($rating)) {
    send_json_response('error', 'Data tidak lengkap. Gagal menambahkan ulasan.');
}

if ($rating < 1 || $rating > 5) {
    send_json_response('error', 'Rating harus antara 1 sampai 5.');
}

// --- 2. Verifikasi Kepemilikan & Status Pesanan ---
// Cek apakah pesanan ini benar milik user yang login dan statusnya sudah 'Selesai'
$stmt_check = $koneksi->prepare("SELECT id FROM tb_transaksi WHERE id = ? AND user_id = ? AND status_pesanan = 'Selesai'");
$stmt_check->bind_param("ii", $id_transaksi, $id_user);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    send_json_response('error', 'Anda tidak dapat memberikan ulasan untuk pesanan ini.');
}

// Cek apakah user sudah pernah memberikan ulasan untuk item ini di pesanan ini
// CATATAN: Pastikan tabel tb_review Anda sudah diubah kolomnya dari pesanan_id menjadi transaksi_id
$stmt_reviewed = $koneksi->prepare("SELECT id FROM tb_review WHERE transaksi_id = ? AND barang_id = ? AND user_id = ?");
$stmt_reviewed->bind_param("iii", $id_transaksi, $id_barang, $id_user);
$stmt_reviewed->execute();
$result_reviewed = $stmt_reviewed->get_result();

if ($result_reviewed->num_rows > 0) {
    send_json_response('error', 'Anda sudah memberikan ulasan untuk produk ini.');
}


// --- 3. Proses Upload Gambar (Jika Ada) ---
$nama_file_gambar = null; // Defaultnya null
if (isset($_FILES['gambar_ulasan']) && $_FILES['gambar_ulasan']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar_ulasan'];
    $target_dir = "../assets/uploads/ulasan/"; // Pastikan folder ini ada dan writable
    
    // Buat nama file yang unik untuk menghindari penimpaan file
    $nama_file_unik = uniqid() . '-' . basename($file['name']);
    $target_file = $target_dir . $nama_file_unik;
    
    // Validasi tipe file (hanya izinkan gambar)
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (!in_array($imageFileType, $allowed_types)) {
        send_json_response('error', 'Hanya file JPG, JPEG, & PNG yang diizinkan.');
    }
    
    // Validasi ukuran file (misal, maks 2MB)
    if ($file['size'] > 2 * 1024 * 1024) { // 2 Megabytes
        send_json_response('error', 'Ukuran file terlalu besar. Maksimal 2MB.');
    }
    
    // Pindahkan file ke folder tujuan
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $nama_file_gambar = $nama_file_unik; // Simpan nama file jika berhasil di-upload
    } else {
        send_json_response('error', 'Gagal mengunggah gambar. Silakan coba lagi.');
    }
}

// --- 4. Simpan Ulasan ke Database ---
// --- 4. Simpan Ulasan ke Database ---
// Query disederhanakan. Kita hapus 'created_at' karena database akan mengisinya secara otomatis
// sesuai pengaturan DEFAULT current_timestamp() yang sudah Anda buat. Ini lebih aman dan bersih.
// Ganti kolom pesanan_id menjadi transaksi_id
$stmt_insert = $koneksi->prepare(
    "INSERT INTO tb_review (transaksi_id, barang_id, user_id, rating, ulasan, gambar_ulasan) 
     VALUES (?, ?, ?, ?, ?, ?)"
);
// Ganti variabel $id_pesanan menjadi $id_transaksi
$stmt_insert->bind_param("iiiiss", $id_transaksi, $id_barang, $id_user, $rating, $ulasan, $nama_file_gambar);

// ...
if ($stmt_insert->execute()) {
    send_json_response('success', 'Terima kasih! Ulasan Anda telah berhasil dikirim.');
} else {
    // Jika gagal, dan ada file yang sudah ter-upload, hapus file tersebut
    if ($nama_file_gambar && file_exists($target_dir . $nama_file_gambar)) {
        unlink($target_dir . $nama_file_gambar);
    }
    // PERUBAHAN DI SINI: Tampilkan error asli dari MySQL
    send_json_response('error', 'Database Error: ' . $stmt_insert->error);
}
// ...

$stmt_check->close();
$stmt_reviewed->close();
$stmt_insert->close();
$koneksi->close();
?>