<?php
// FILE: add_product.php (VERSI AMAN YANG DISESUAIKAN)

header('Content-Type: application/json');
session_start();

// Ganti dengan path koneksi Anda yang benar (TIDAK DIUBAH)
include "../../../config/koneksi.php"; 

// Fungsi untuk mengirim response JSON (TIDAK DIUBAH)
function send_response($status, $message, $data = []) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Hanya proses jika metode adalah POST (TIDAK DIUBAH)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_response('error', 'Metode request tidak valid.');
}

// ===================================================================================
// VALIDASI DAN PENGAMBILAN DATA
// ===================================================================================

// Validasi gambar (TIDAK DIUBAH)
if (!isset($_FILES['gambar_barang']) || !is_array($_FILES['gambar_barang']['name']) || empty($_FILES['gambar_barang']['name'][0])) {
    send_response('error', 'Anda wajib mengunggah minimal 1 gambar.');
}
if (count($_FILES['gambar_barang']['name']) > 5) {
    send_response('error', 'Jumlah gambar tidak boleh lebih dari 5.');
}

// Ambil data produk dasar (TIDAK DIUBAH)
$admin_id = $_SESSION['user']['id'] ?? null; 
if (!$admin_id) {
    send_response('error', 'Sesi admin tidak ditemukan. Silakan login ulang.');
}

// --- PENYESUAIAN DIMULAI DI SINI ---
$kategori_id = filter_input(INPUT_POST, 'kategori_id', FILTER_VALIDATE_INT);
$nama_barang = trim($_POST['nama_barang']);
$deskripsi   = trim($_POST['deskripsi'] ?? '');
// Langsung ambil harga dan stok dari form
$harga       = filter_input(INPUT_POST, 'harga', FILTER_VALIDATE_FLOAT); 
$stok        = filter_input(INPUT_POST, 'stok', FILTER_VALIDATE_INT);
$berat       = filter_input(INPUT_POST, 'berat', FILTER_VALIDATE_FLOAT); 
$is_active   = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_INT);
$index_utama = isset($_POST['is_utama']) ? (int)$_POST['is_utama'] : 0;
$kode_barang = 'BRG-' . strtoupper(substr(uniqid(), -6));

// Hapus validasi variasi dan ganti dengan validasi sederhana
if (empty($nama_barang) || $kategori_id === false || $harga === null || $stok === null || $berat === null) {
    send_response('error', 'Semua field yang bertanda * wajib diisi dengan benar.');
}
// --- PENYESUAIAN SELESAI ---


// ===================================================================================
// PROSES DATABASE DENGAN TRANSAKSI (TIDAK DIUBAH)
// ===================================================================================

$koneksi->begin_transaction();

try {
    // --- PENYESUAIAN PADA SQL INSERT ---
    // Menambahkan kolom `harga` dan `stok`, menghapus `panjang`, `lebar`, `tinggi`
    $sql_barang = "INSERT INTO tb_barang 
                      (admin_id, kategori_id, kode_barang, nama_barang, deskripsi, harga, stok, berat, is_active, created_at, updated_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt_barang = $koneksi->prepare($sql_barang);
    // Menyesuaikan tipe data dan variabel yang di-bind
    $stmt_barang->bind_param(
        "iisssdidi", 
        $admin_id, $kategori_id, $kode_barang, $nama_barang, $deskripsi,
        $harga, $stok, $berat, $is_active
    );
    // --- PENYESUAIAN SELESAI ---
    
    $stmt_barang->execute();

    $last_barang_id = $koneksi->insert_id;
    if ($last_barang_id == 0) {
        throw new Exception("Gagal menyimpan data produk utama.");
    }
    $stmt_barang->close();

    // LANGKAH 2: PROSES SIMPAN GAMBAR (BAGIAN INI SAMA SEKALI TIDAK DIUBAH)
    $upload_folder = "/../../../assets/uploads/";
    $upload_dir = __DIR__ . $upload_folder;

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) {
             throw new Exception("Gagal membuat folder upload.");
        }
    }

    foreach ($_FILES['gambar_barang']['name'] as $index => $nama_file) {
        if ($_FILES['gambar_barang']['error'][$index] !== UPLOAD_ERR_OK) continue;
        
        $file_tmp = $_FILES['gambar_barang']['tmp_name'][$index];
        $ext = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $new_filename = 'BRG-' . $last_barang_id . '-' . uniqid() . '.' . $ext;
        $target_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($file_tmp, $target_path)) {
            throw new Exception("Gagal memindahkan file: $nama_file");
        }

        $is_utama_flag = ($index === $index_utama) ? 1 : 0;
        
        $sql_gambar = "INSERT INTO tb_gambar_barang (barang_id, nama_file, is_utama) VALUES (?, ?, ?)";
        $stmt_gambar = $koneksi->prepare($sql_gambar);
        $stmt_gambar->bind_param("isi", $last_barang_id, $new_filename, $is_utama_flag);
        $stmt_gambar->execute();
        $stmt_gambar->close();
    }
    
    // LANGKAH 3: LOGIKA VARIASI DIHAPUS KARENA TIDAK DIGUNAKAN LAGI

    // Commit transaksi (TIDAK DIUBAH)
    $koneksi->commit();
    send_response('success', 'Produk berhasil ditambahkan.');

} catch (Exception $e) {
    // Rollback jika error (TIDAK DIUBAH)
    $koneksi->rollback();
    send_response('error', 'Terjadi kesalahan: ' . $e->getMessage());
} finally {
    // Tutup koneksi (TIDAK DIUBAH)
    $koneksi->close();
}
?>