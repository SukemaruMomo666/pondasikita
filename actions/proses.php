<?php
session_start();
include '../config/koneksi.php';

// --- 1. Validasi Input Awal ---
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Username dan password wajib diisi.";
    header("Location: ../auth/signin.php");
    exit;
}

// --- 2. Ambil Data User dari Database ---
// Menggunakan prepared statement untuk keamanan
$query = "SELECT * FROM tb_user WHERE username = ? OR email = ?"; // Bisa login dengan username atau email
$stmt = $koneksi->prepare($query);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();


// --- 3. Pengecekan Akun (Ditemukan atau Tidak) ---
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // --- 4. Verifikasi Password dan Status Akun ---

    // A. Periksa Status Banned (Logika dari file kedua Anda)
    // Ini langkah pertama setelah user ditemukan.
    if ($user['is_banned'] == 1) {
        $_SESSION['error'] = "Akun Anda telah dinonaktifkan. Silakan hubungi admin.";
        header("Location: ../auth/signin.php");
        exit;
    }

    // B. Verifikasi Password
    if (password_verify($password, $user['password'])) {
        // --- JIKA LOLOS SEMUA PENGECEKAN, LANJUTKAN PROSES LOGIN SUKSES ---

        // 5. Atur Session (Versi lebih rapi menggunakan satu array)
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama'],
            'email'    => $user['email'],
            'level'    => $user['level']
        ];
        
        // 6. Update Waktu Login Terakhir (Fitur tambahan yang baik)
        $update_stmt = $koneksi->prepare("UPDATE tb_user SET last_login = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();

        // 7. Pindahkan Keranjang Guest ke Keranjang User (Logika dari file pertama Anda)
// 7. Pindahkan Keranjang Guest ke Keranjang User (Logika dari file pertama Anda)
if ($user['level'] === 'customer' && isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $kode_barang => $jumlah) {
        $kode_barang = (int)$kode_barang;
        $jumlah = max(1, (int)$jumlah); // Pastikan jumlah minimal 1

        // --- VALIDASI kode_barang SEBELUM INSERT/UPDATE ---
        $stmt_check_barang = $koneksi->prepare("SELECT id FROM tb_barang WHERE id = ?");
        $stmt_check_barang->bind_param("i", $kode_barang);
        $stmt_check_barang->execute();
        $res_check_barang = $stmt_check_barang->get_result();

        // Hanya proses jika kode_barang benar-benar ada di tabel tb_barang
        if ($res_check_barang->num_rows > 0) {

            // Cek apakah barang sudah ada di keranjang user
            $stmt_cek = $koneksi->prepare("SELECT id FROM tb_keranjang WHERE user_id = ? AND kode_barang = ?");
            $stmt_cek->bind_param("ii", $user['id'], $kode_barang);
            $stmt_cek->execute();
            $res_cek = $stmt_cek->get_result();

            if ($res_cek->num_rows > 0) {
                // Jika sudah ada, update jumlahnya
                // FIX: Nama kolom salah, seharusnya 'kode_barang'
                $stmt_upd = $koneksi->prepare("UPDATE tb_keranjang SET jumlah = jumlah + ? WHERE user_id = ? AND kode_barang = ?");
                $stmt_upd->bind_param("iii", $jumlah, $user['id'], $kode_barang);
                $stmt_upd->execute();
            } else {
                // Jika belum ada, insert baru
                // FIX: Nama kolom salah, seharusnya 'kode_barang'
                $stmt_ins = $koneksi->prepare("INSERT INTO tb_keranjang (user_id, kode_barang, jumlah) VALUES (?, ?, ?)");
                $stmt_ins->bind_param("iii", $user['id'], $kode_barang, $jumlah);
                $stmt_ins->execute();
            }
        }
        // Jika kode_barang tidak valid, item tersebut akan diabaikan dan tidak ditambahkan ke keranjang.
    }
    unset($_SESSION['keranjang']); // Kosongkan session keranjang setelah dipindahkan
}

        // 8. Logika Redirect (Logika dari file pertama Anda)
        // A. Cek apakah ada tujuan redirect spesifik
        $redirect_url = $_GET['redirect'] ?? $_SESSION['redirect_url'] ?? '';
        unset($_SESSION['redirect_url']);
        if (!empty($redirect_url)) {
            // Keamanan: Pastikan redirect hanya ke domain sendiri
            $parsed_url = parse_url(urldecode($redirect_url));
            if (isset($parsed_url['host']) && $parsed_url['host'] !== $_SERVER['HTTP_HOST']) {
                $redirect_url = '../index.php'; // Arahkan ke home jika domain berbeda
            }
            header("Location: " . $redirect_url);
            exit;
        }

        // B. Jika tidak ada, redirect sesuai level user
        switch ($user['level']) {
            case 'superadmin':
                header("Location: ../backend/admin/index_admin.php");
                break;
            case 'admin':
                header("Location: ../admin/kelola_data/data_barang/kelola_data_barang.php");
                break;
            case 'customer':
            default:
                header("Location: ../index.php");
                break;
        }
        exit;

    } else {
        // Jika password salah
        $_SESSION['error'] = "Username atau password salah.";
    }
} else {
    // Jika username tidak ditemukan
    $_SESSION['error'] = "Username atau password salah.";
}

// Default redirect jika login gagal karena alasan apapun
header("Location: ../auth/signin.php");
exit;
?>