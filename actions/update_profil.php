<?php
// Pastikan error reporting diaktifkan untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Aktifkan ini untuk debugging, matikan di production!

session_start();
require_once __DIR__ . '/../config/koneksi.php'; // Sesuaikan path

// Fungsi untuk mengirim feedback dan redirect
function redirect_with_feedback($tipe, $pesan, $url) {
    $_SESSION['feedback'] = ['tipe' => $tipe, 'pesan' => $pesan];
    header("Location: " . $url);
    exit();
}

// Keamanan: Pastikan user sudah login sebagai customer
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['level'] !== 'customer') {
    redirect_with_feedback('gagal', 'Akses ditolak. Anda harus login sebagai pelanggan.', '/auth/login_customer.php');
}

$user_id = $_SESSION['user']['id'];
$upload_dir = __DIR__ . '/../assets/uploads/avatars/'; // Path folder upload
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Pastikan direktori bisa ditulis
}

$profile_picture_url = ''; // Inisialisasi default

// --- LOGIKA PEMROSESAN GAMBAR BARU (DALAM TRY-CATCH) ---
try {
    if (isset($_POST['profile_picture_base64']) && !empty($_POST['profile_picture_base64'])) {
        $base64_image = $_POST['profile_picture_base64'];
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_image));
        $file_extension = 'jpg';

        if (strpos($base64_image, 'data:image/png') === 0) {
            $file_extension = 'png';
        } elseif (strpos($base64_image, 'data:image/jpeg') === 0) {
            $file_extension = 'jpg';
        } else {
            throw new Exception("Format gambar tidak didukung.");
        }

        $new_file_name = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $new_file_name;

        if (file_put_contents($file_path, $data)) {
            $profile_picture_url = $new_file_name;

            $stmt_old_avatar = $koneksi->prepare("SELECT profile_picture_url FROM tb_user WHERE id = ?");
            if (!$stmt_old_avatar) { throw new Exception("Prepare old avatar query failed: " . $koneksi->error); }
            $stmt_old_avatar->bind_param("i", $user_id);
            $stmt_old_avatar->execute();
            $result_old_avatar = $stmt_old_avatar->get_result();
            $old_avatar = $result_old_avatar->fetch_assoc();
            $stmt_old_avatar->close();

            if ($old_avatar && !empty($old_avatar['profile_picture_url']) && $old_avatar['profile_picture_url'] !== 'default-avatar.png') {
                $old_file_path = $upload_dir . $old_avatar['profile_picture_url'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
        } else {
            throw new Exception("Gagal menyimpan foto profil yang di-crop ke server.");
        }
    } 
    // Jika tidak ada Base64 dan ada file upload biasa (fallback)
    elseif (isset($_FILES['profile_picture_input']) && $_FILES['profile_picture_input']['error'] === UPLOAD_ERR_OK) {
        // Implementasi upload file standar di sini jika diperlukan, atau lewati saja
        // Untuk saat ini, kita anggap cropper yang dipakai. Jika ada file biasa, bisa dikasih warning
        $_SESSION['warning_message'] = "Foto profil diunggah tanpa cropping, pastikan ukurannya sesuai.";
        // Anda mungkin perlu menambahkan logika upload file $_FILES secara standar di sini jika ingin fitur ini.
    }
} catch (Exception $e) {
    // Tangkap error spesifik dari proses gambar dan redirect dengan feedback
    redirect_with_feedback('gagal', 'Error Foto Profil: ' . $e->getMessage(), '../app_customer/pages/profil/crud_profil/edit_profil.php');
}
// --- AKHIR LOGIKA PEMROSESAN GAMBAR BARU ---


// Ambil data dari form (selain gambar)
$nama = trim($_POST['nama'] ?? '');
$no_telepon = trim($_POST['no_telepon'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? null);
$tanggal_lahir = trim($_POST['tanggal_lahir'] ?? null);

// Transaksi database untuk update profil dan alamat
$koneksi->begin_transaction();

try {
    // Update data user di tb_user
    $update_user_sql = "UPDATE tb_user SET nama = ?, no_telepon = ?, jenis_kelamin = ?, tanggal_lahir = ?";
    $params_user_types = "ssss";
    $params_user_values = [&$nama, &$no_telepon, &$jenis_kelamin, &$tanggal_lahir];

    if (!empty($profile_picture_url)) {
        $update_user_sql .= ", profile_picture_url = ?";
        $params_user_types .= "s";
        $params_user_values[] = &$profile_picture_url;
    }
    $update_user_sql .= " WHERE id = ?";
    $params_user_types .= "i";
    $params_user_values[] = &$user_id;

    $stmt_update_user = $koneksi->prepare($update_user_sql);
    if (!$stmt_update_user) {
        throw new Exception("Prepare user update failed: " . $koneksi->error);
    }
    call_user_func_array([$stmt_update_user, 'bind_param'], array_merge([$params_user_types], $params_user_values));
    $stmt_update_user->execute();
    $stmt_update_user->close();

    // Ambil data alamat dari form
    $alamat_id = trim($_POST['alamat_id'] ?? '');
    $label_alamat = trim($_POST['label_alamat'] ?? '');
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $telepon_penerima = trim($_POST['telepon_penerima'] ?? '');
    $alamat_lengkap = trim($_POST['alamat_lengkap'] ?? '');
    $province_id = trim($_POST['province_id'] ?? null);
    $city_id = trim($_POST['city_id'] ?? null);
    $district_id = trim($_POST['district_id'] ?? null);
    $kode_pos = trim($_POST['kode_pos'] ?? null);

    // Validasi input alamat yang paling penting, di sini di backend
    if (empty($label_alamat) || empty($nama_penerima) || empty($telepon_penerima) || empty($alamat_lengkap) || empty($province_id) || empty($city_id) || empty($district_id)) {
        throw new Exception("Data alamat tidak lengkap. Pastikan semua kolom wajib diisi.");
    }

    // Update atau Insert alamat utama
    if (!empty($alamat_id)) {
        // Update alamat yang sudah ada
        $update_alamat_sql = "
            UPDATE tb_user_alamat SET
            label_alamat = ?, nama_penerima = ?, telepon_penerima = ?,
            alamat_lengkap = ?, province_id = ?, city_id = ?, district_id = ?, kode_pos = ?, is_utama = 1
            WHERE id = ? AND user_id = ?";
        $stmt_update_alamat = $koneksi->prepare($update_alamat_sql);
        if (!$stmt_update_alamat) {
            throw new Exception("Prepare alamat update failed: " . $koneksi->error);
        }
        // String tipe: (s)label, (s)nama, (s)telepon, (s)alamat_lengkap, (i)province, (i)city, (i)district, (s)kode_pos, (i)alamat_id, (i)user_id
        $stmt_update_alamat->bind_param("sssiisiiii", 
            $label_alamat, $nama_penerima, $telepon_penerima, $alamat_lengkap,
            $province_id, $city_id, $district_id, $kode_pos,
            $alamat_id, $user_id
        );
        $stmt_update_alamat->execute();
        $stmt_update_alamat->close();
    } else {
        // Insert alamat baru sebagai alamat utama
        // Nonaktifkan alamat utama sebelumnya
        $koneksi->query("UPDATE tb_user_alamat SET is_utama = 0 WHERE user_id = $user_id");

        $insert_alamat_sql = "
            INSERT INTO tb_user_alamat
            (user_id, label_alamat, nama_penerima, telepon_penerima,
             alamat_lengkap, province_id, city_id, district_id, kode_pos, is_utama)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_alamat = $koneksi->prepare($insert_alamat_sql);
        if (!$stmt_insert_alamat) {
            throw new Exception("Prepare alamat insert failed: " . $koneksi->error);
        }
        $is_utama_flag = 1; 
        // String tipe: (i)user_id, (s)label, (s)nama, (s)telepon, (s)alamat_lengkap, (i)province, (i)city, (i)district, (s)kode_pos, (i)is_utama
        $stmt_insert_alamat->bind_param("issssiisii", 
            $user_id, $label_alamat, $nama_penerima, $telepon_penerima,
            $alamat_lengkap, $province_id, $city_id, $district_id, $kode_pos, $is_utama_flag
        );
        $stmt_insert_alamat->execute();
        $stmt_insert_alamat->close();
    }

    $koneksi->commit(); // Commit transaksi jika semua berhasil
    $_SESSION['success_message'] = "Profil dan alamat utama berhasil diperbarui.";
    redirect_with_feedback('sukses', 'Profil dan alamat utama berhasil diperbarui.', '../app_customer/pages/profil/crud_profil/edit_profil.php');

} catch (Exception $e) {
    $koneksi->rollback(); // Rollback transaksi jika ada error
    error_log("Update Profil Error: " . $e->getMessage() . " - SQLSTATE: " . $koneksi->sqlstate . " - MySQL Error: " . $koneksi->error);
    redirect_with_feedback('gagal', 'Gagal memperbarui profil: ' . $e->getMessage(), '../app_customer/pages/profil/crud_profil/edit_profil.php');
}

?>