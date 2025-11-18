<?php
// FILE: pengaturan_website.php (Versi Final dengan API Komerce)
session_start();
require_once '../config/koneksi.php'; // Menggunakan koneksi database Anda

// --- PENGAMANAN HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK RAJAONGKIR KOMERCE ---

// 1. Logika untuk menyimpan pengaturan kurir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rajaongkir_couriers'])) {
    $selectedCouriers = isset($_POST['couriers']) ? $_POST['couriers'] : [];
    $couriersJson = json_encode($selectedCouriers);
    
    $stmt = $koneksi->prepare("INSERT INTO tb_pengaturan (setting_nama, setting_nilai) VALUES ('rajaongkir_active_couriers', ?) ON DUPLICATE KEY UPDATE setting_nilai = ?");
    $stmt->bind_param("ss", $couriersJson, $couriersJson);
    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['tipe' => 'success', 'pesan' => 'Pengaturan kurir berhasil diperbarui.'];
    } else {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Gagal menyimpan pengaturan kurir.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 2. Logika untuk sinkronisasi data wilayah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_rajaongkir_data'])) {
    $apiKeyResult = $koneksi->query("SELECT setting_nilai FROM tb_pengaturan WHERE setting_nama = 'rajaongkir_api_key'");
    $apiKey = ($apiKeyResult->num_rows > 0) ? $apiKeyResult->fetch_assoc()['setting_nilai'] : '';

    if (empty($apiKey)) {
        $_SESSION['feedback'] = ['tipe' => 'error', 'pesan' => 'Sinkronisasi gagal. Harap masukkan Kunci API terlebih dahulu.'];
    } else {
        // Menggunakan fungsi sync yang sudah diperbarui untuk Komerce
        $syncResult = syncKomerceData($apiKey, $koneksi); 
        if ($syncResult['success']) {
            $newSyncTime = date('Y-m-d H:i:s');
            $stmt = $koneksi->prepare("INSERT INTO tb_pengaturan (setting_nama, setting_nilai) VALUES ('rajaongkir_last_sync', ?) ON DUPLICATE KEY UPDATE setting_nilai = ?");
            $stmt->bind_param("ss", $newSyncTime, $newSyncTime);
            $stmt->execute();
            $_SESSION['feedback'] = ['tipe' => 'success', 'pesan' => "Sinkronisasi berhasil! {$syncResult['province_count']} provinsi dan {$syncResult['city_count']} kota telah disimpan."];
        } else {
            $_SESSION['feedback'] = [
                'tipe' => 'error', 
                'pesan' => "<strong>Sinkronisasi Gagal.</strong><br>Pesan error dari sistem: <strong>" . htmlspecialchars($syncResult['message']) . "</strong>"
            ];
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/**
 * FUNGSI BARU: Berkomunikasi dengan API Komerce dan menyimpan data.
 * @param string $apiKey Kunci API dari Komerce.
 * @param mysqli $db Objek koneksi database.
 * @return array Hasil proses sinkronisasi.
 */
function syncKomerceData($apiKey, $db) {
    // --- PERUBAHAN 1: URL API diubah ke milik Komerce ---
    $provinceUrl = "https://rajaongkir.komerce.id/api/v1/destination/province";
    $cityUrl = "https://rajaongkir.komerce.id/api/v1/destination/city";

    $fetchData = function($url, $apiKey) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            // --- PERUBAHAN 2: Header disesuaikan dengan dokumentasi Komerce ---
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "key: " . $apiKey
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) return ['error' => true, 'message' => "cURL Error: " . $err];
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) return ['error' => true, 'message' => 'Gagal mem-parsing respons JSON dari Komerce.'];
        
        // --- PERUBAHAN 3: Struktur response Komerce mungkin berbeda ---
        // Kita asumsikan response sukses memiliki 'data'
        if (isset($data['status']) && $data['status'] == 'success' && isset($data['data'])) {
             return ['error' => false, 'data' => $data['data']];
        } else {
            // Menangkap pesan error dari Komerce, seperti 'Unauthenticated.'
            $errorMessage = $data['message'] ?? 'Terjadi error tidak diketahui dari API Komerce.';
            return ['error' => true, 'message' => $errorMessage];
        }
    };

    $db->begin_transaction();
    try {
        // 1. Ambil dan simpan data provinsi
        $provincesResult = $fetchData($provinceUrl, $apiKey);
        if ($provincesResult['error']) throw new Exception('Gagal mengambil data provinsi. ' . $provincesResult['message']);
        
        // --- PERUBAHAN 4: Sesuaikan nama kolom dengan response API Komerce ---
        $stmtProv = $db->prepare("INSERT INTO rajaongkir_provinces (province_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        foreach ($provincesResult['data'] as $province) {
            $stmtProv->bind_param("is", $province['id'], $province['name']); // Menggunakan 'id' dan 'name'
            $stmtProv->execute();
        }
        $provinceCount = count($provincesResult['data']);

        // 2. Ambil dan simpan data kota
        $citiesResult = $fetchData($cityUrl, $apiKey);
        if ($citiesResult['error']) throw new Exception('Gagal mengambil data kota. ' . $citiesResult['message']);
        
        $stmtCity = $db->prepare("INSERT INTO rajaongkir_cities (city_id, province_id, name, type, postal_code) VALUES (?, ?, ?, ?, ?)");
        // Hapus data kota lama untuk menghindari duplikat
        $db->query("TRUNCATE TABLE rajaongkir_cities"); 
        foreach ($citiesResult['data'] as $city) {
            $type = $city['type'] ?? '';
            $postal_code = $city['postal_code'] ?? '';
            $stmtCity->bind_param("iisss", $city['id'], $city['province_id'], $city['name'], $type, $postal_code);
            $stmtCity->execute();
        }
        $cityCount = count($citiesResult['data']);

        $db->commit();
        return ['success' => true, 'province_count' => $provinceCount, 'city_count' => $cityCount];
    } catch (Exception $e) {
        $db->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// --- AMBIL SEMUA PENGATURAN DARI DATABASE (seperti kode asli Anda) ---
$sql = "SELECT setting_nama, setting_nilai FROM tb_pengaturan";
$result = $koneksi->query($sql);
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_nama']] = $row['setting_nilai'];
}

// Daftar kurir (bisa disesuaikan jika Komerce punya daftar sendiri)
$all_rajaongkir_couriers = [
    'jne' => 'JNE', 'pos' => 'POS Indonesia', 'tiki' => 'TIKI',
    'sicepat' => 'SiCepat', 'jnt' => 'J&T' // Contoh tambahan
];
$activeCouriers = json_decode($settings['rajaongkir_active_couriers'] ?? '[]', true);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Website & Integrasi - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'pengaturan_website.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header"><h1 class="page-title">Pengaturan Website & Integrasi</h1></div>

                <?php if (isset($_SESSION['feedback'])): ?>
                <div class="feedback-alert <?= $_SESSION['feedback']['tipe'] ?>" style="margin-bottom: 1.5rem;">
                    <span><?= $_SESSION['feedback']['pesan'] ?></span>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['feedback']); ?>
                <?php endif; ?>

                <!-- Form Utama untuk Pengaturan Umum -->
                <form action="actions/proses_pengaturan.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Pengaturan API & Keuangan</h4>
                                     <div class="form-group mt-4">
                                        <label>Persentase Komisi Platform (%)</label>
                                        <input type="number" name="persentase_komisi" class="form-control" value="<?= htmlspecialchars($settings['persentase_komisi'] ?? '0') ?>" step="0.1" min="0" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Midtrans Server Key</label>
                                        <input type="password" name="midtrans_server_key" class="form-control" value="<?= htmlspecialchars($settings['midtrans_server_key'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>RajaOngkir API Key (dari Komerce)</label>
                                        <input type="password" name="rajaongkir_api_key" class="form-control" value="<?= htmlspecialchars($settings['rajaongkir_api_key'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan API</button>
                    </div>
                </form>

                <hr class="my-5">

                <!-- Bagian Khusus untuk RajaOngkir -->
                <div class="page-header"><h2 class="page-title">Pengaturan Ekspedisi (Komerce)</h2></div>
                <div class="row">
                    <!-- Pengaturan Kurir -->
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Aktifkan Kurir</h4>
                                <p class="card-description">Pilih kurir yang ingin ditampilkan kepada pelanggan.</p>
                                <form action="pengaturan_website.php" method="POST">
                                    <fieldset class="mt-4">
                                        <?php foreach ($all_rajaongkir_couriers as $code => $name): ?>
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input" name="couriers[]" value="<?= $code ?>" <?= in_array($code, $activeCouriers) ? 'checked' : '' ?>>
                                                <?= $name ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <button type="submit" name="save_rajaongkir_couriers" class="btn btn-info mt-4">Simpan Pengaturan Kurir</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Sinkronisasi Wilayah -->
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Sinkronisasi Wilayah</h4>
                                <p class="card-description">Ambil data provinsi & kota terbaru dari API Komerce.</p>
                                <div class="bg-light p-3 rounded mt-4">
                                    <p class="mb-1"><strong>Sinkronisasi Terakhir:</strong></p>
                                    <p class="mb-0 text-muted"><?= !empty($settings['rajaongkir_last_sync']) ? date('d F Y H:i', strtotime($settings['rajaongkir_last_sync'])) : 'Belum pernah' ?></p>
                                </div>
                                <form id="sync-form" action="pengaturan_website.php" method="POST" class="mt-4">
                                    <button type="submit" name="sync_rajaongkir_data" id="sync-button" class="btn btn-success">
                                        <i class="mdi mdi-sync"></i> Mulai Sinkronisasi
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#sync-form').on('submit', function() {
        var btn = $('#sync-button');
        btn.prop('disabled', true);
        btn.html('<i class="mdi mdi-sync mdi-spin"></i> Sedang menyinkronkan...');
    });
</script>
</body>
</html>
