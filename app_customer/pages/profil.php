<?php
// Selalu mulai dengan session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sesuaikan path ke file koneksi Anda
require_once __DIR__ . '/../../config/koneksi.php';

// Keamanan: Pastikan user sudah login sebagai customer
// PERBAIKAN DI SINI: Akses data login dari $_SESSION['user']
if (!isset($_SESSION['user']['logged_in']) || !$_SESSION['user']['logged_in'] || $_SESSION['user']['level'] !== 'customer') {
    header("Location: /auth/login_customer.php");
    exit;
}

// Ambil user_id dari array $_SESSION['user']
$user_id = $_SESSION['user']['id'];

// 1. Ambil data personal dari tb_user
$stmt_user = $koneksi->prepare("SELECT * FROM tb_user WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

if (!$user) {
    // Jika user tidak ditemukan di DB padahal session ada, mungkin data korup atau dihapus.
    // Hancurkan sesi dan redirect ke login.
    session_unset();
    session_destroy();
    header("Location: /auth/login_customer.php?error=akun_tidak_ditemukan");
    exit;
}

// 2. Query baru untuk mengambil alamat utama yang terstruktur
$alamat_lengkap_formatted = "-"; // Nilai default jika tidak ada alamat
$stmt_alamat = $koneksi->prepare("
    SELECT
        ua.alamat_lengkap,
        ua.kode_pos,
        d.name AS district_name,
        c.name AS city_name,
        p.name AS province_name
    FROM
        tb_user_alamat ua
    LEFT JOIN
        districts d ON ua.district_id = d.id
    LEFT JOIN
        cities c ON ua.city_id = c.id
    LEFT JOIN
        provinces p ON ua.province_id = p.id
    WHERE
        ua.user_id = ? AND ua.is_utama = 1
");
$stmt_alamat->bind_param("i", $user_id);
$stmt_alamat->execute();
$result_alamat = $stmt_alamat->get_result();
if ($alamat_utama = $result_alamat->fetch_assoc()) {
    // Format alamat menjadi satu string yang rapi
    $alamat_lengkap_formatted = 
        htmlspecialchars($alamat_utama['alamat_lengkap']) . '<br>' .
        'Kec. ' . htmlspecialchars($alamat_utama['district_name'] ?? 'Tidak Diketahui') . ', ' . // Tambahkan null coalescing operator untuk jaga-jaga
        htmlspecialchars($alamat_utama['city_name'] ?? 'Tidak Diketahui') . ',<br>' .
        htmlspecialchars($alamat_utama['province_name'] ?? 'Tidak Diketahui') .
        (!empty($alamat_utama['kode_pos']) ? ', ' . htmlspecialchars($alamat_utama['kode_pos']) : '');
}
$stmt_alamat->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - <?= htmlspecialchars($user['nama']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/navbar_style.css">
    
    <link rel="stylesheet" href="/assets/css/profil_style.css">
</head>
<body>
    
<<<<<<< HEAD
<?php include __DIR__ . '../partials/navbar.php'; // Path disesuaikan ?>
=======
<?php include __DIR__ . '/navbar.php'; // Path disesuaikan ?>
>>>>>>> 28078367120c440b1a4e1aa2a6445ba8e3061cf5

<div class="profile-container">
    <div class="profile-header">
        <h2><i class="fa-solid fa-user-circle"></i> Profil Saya</h2>
        <p>Kelola informasi profil Anda untuk mengontrol, melindungi, dan mengamankan akun.</p>
    </div>

    <div class="profile-card">
        <div class="profile-picture-section">
            <img src="/assets/uploads/avatars/<?= htmlspecialchars($user['profile_picture_url'] ?? 'default-avatar.png') ?>" 
                 alt="Foto Profil" 
                 class="profile-picture"
                 onerror="this.onerror=null;this.src='/assets/uploads/avatars/default-avatar.png';">
        </div>
        <div class="profile-details-section">
            <dl class="profile-details-list">
                <dt>Username</dt>
                <dd><?= htmlspecialchars($user['username']) ?></dd>

                <dt>Nama Lengkap</dt>
                <dd><?= htmlspecialchars($user['nama'] ?? '-') ?></dd>

                <dt>Email</dt>
                <dd><?= htmlspecialchars($user['email'] ?? '-') ?></dd>

                <dt>Nomor Telepon</dt>
                <dd><?= htmlspecialchars($user['no_telepon'] ?? '-') ?></dd>

                <dt>Jenis Kelamin</dt>
                <dd><?= htmlspecialchars($user['jenis_kelamin'] ?? '-') ?></dd>

                <dt>Tanggal Lahir</dt>
                <dd><?= !empty($user['tanggal_lahir']) ? date('d F Y', strtotime($user['tanggal_lahir'])) : '-' ?></dd>

                <dt>Alamat Utama</dt>
                <dd><?= $alamat_lengkap_formatted ?></dd>
                
                <dt>Tanggal Bergabung</dt>
                <dd><?= date('d F Y', strtotime($user['created_at'])) ?></dd>
            </dl>
            <div class="profile-actions">
                <a href="crud_profil/edit_profil.php" class="btn btn-primary"><i class="fa-solid fa-pen-to-square"></i> Edit Profil</a>
                <a href="crud_profil/ganti_password.php" class="btn btn-secondary"><i class="fa-solid fa-key"></i> Ganti Password</a>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/navbar.js"></script>
</body>
</html>