<?php
/**
 * File ini HANYA untuk memproses pendaftaran seller.
 * Menerima data dari form, upload file, dan menyimpan ke 2 tabel (user & toko).
 * Menggunakan transaksi database untuk keamanan data.
 */

require_once '../config/koneksi.php';
header('Content-Type: application/json');

// Validasi dasar
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

// Ambil semua data dari POST
$username = $_POST['username'] ?? '';
$nama_pemilik = $_POST['nama_pemilik'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$nama_toko = $_POST['nama_toko'] ?? '';
$telepon_toko = $_POST['telepon_toko'] ?? '';
$alamat_toko = $_POST['alamat_toko'] ?? '';

// ✅ [DIUBAH] Ambil ID lokasi dari dropdown, bukan teks dari input biasa.
$province_id = $_POST['province_id'] ?? null;
$city_id = $_POST['city_id'] ?? null;
$district_id = $_POST['district_id'] ?? null;

// ✅ [DIUBAH] Cek data penting, termasuk ID lokasi.
if (empty($username) || empty($nama_pemilik) || empty($email) || empty($password) || empty($nama_toko) || empty($alamat_toko) || empty($province_id) || empty($city_id) || empty($district_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom informasi, termasuk alamat lengkap dan lokasi, wajib diisi.']);
    exit;
}

// --- Proses Upload File Logo ---
$logo_path = null;
if (isset($_FILES['logo_toko']) && $_FILES['logo_toko']['error'] == 0) {
    $target_dir = "../assets/uploads/logos/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Buat folder jika belum ada
    }
    $file_extension = strtolower(pathinfo($_FILES['logo_toko']['name'], PATHINFO_EXTENSION));
    // Pastikan ekstensi file gambar
    if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        echo json_encode(['status' => 'error', 'message' => 'Format logo tidak valid. Gunakan JPG, PNG, atau GIF.']);
        exit;
    }
    $logo_filename = "logo_" . time() . "_" . uniqid() . "." . $file_extension;
    $target_file = $target_dir . $logo_filename;

    if (!move_uploaded_file($_FILES['logo_toko']['tmp_name'], $target_file)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah logo.']);
        exit;
    }
    $logo_path = $logo_filename;
}

// --- Transaksi Database ---
$koneksi->begin_transaction();

try {
    // 1. Cek duplikasi username/email
    $stmt_check = $koneksi->prepare("SELECT id FROM tb_user WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception('Username atau email sudah terdaftar.');
    }
    $stmt_check->close();

    // 2. Insert ke tb_user sebagai 'seller'
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt_user = $koneksi->prepare("INSERT INTO tb_user (username, password, nama, email, level) VALUES (?, ?, ?, ?, 'seller')");
    $stmt_user->bind_param("ssss", $username, $hashed_password, $nama_pemilik, $email);
    $stmt_user->execute();
    $user_id = $koneksi->insert_id;
    if ($user_id == 0) {
        throw new Exception('Gagal membuat akun pengguna.');
    }
    $stmt_user->close();

    // 3. Insert ke tb_toko
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $nama_toko)));
    
    // ✅ [DIUBAH] Query INSERT disesuaikan untuk menyimpan ID lokasi.
    $stmt_toko = $koneksi->prepare(
        "INSERT INTO tb_toko (user_id, nama_toko, slug, logo_toko, alamat_toko, province_id, city_id, district_id, telepon_toko, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    // ✅ [DIUBAH] Tipe data dan variabel di bind_param disesuaikan. `s` untuk string, `i` untuk integer.
    $stmt_toko->bind_param("issssiiis", $user_id, $nama_toko, $slug, $logo_path, $alamat_toko, $province_id, $city_id, $district_id, $telepon_toko);
    $stmt_toko->execute();
    $stmt_toko->close();

    // Jika semua berhasil, commit
    $koneksi->commit();
    echo json_encode(['status' => 'success', 'message' => 'Pendaftaran toko berhasil! Akun Anda akan segera diverifikasi oleh Admin.']);

} catch (Exception $e) {
    // Jika ada error, batalkan semua
    $koneksi->rollback();
    
    // Hapus file logo yang sudah terupload jika transaksi gagal
    if ($logo_path && file_exists($target_dir . $logo_path)) {
        unlink($target_dir . $logo_path);
    }
    
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$koneksi->close();
?>