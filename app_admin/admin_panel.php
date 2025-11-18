<?php
// FILE: db_connect.php
// Letakkan file ini di direktori yang sama dengan admin_panel.php

// --- PENGATURAN KONEKSI DATABASE ---
// Ganti nilai-nilai ini sesuai dengan konfigurasi server database Anda.
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Ganti dengan username database Anda
define('DB_PASS', ''); // Ganti dengan password database Anda
define('DB_NAME', 'pondasikita_db');

/**
 * Membuat koneksi ke database menggunakan MySQLi.
 * Mengatur charset ke utf8mb4 untuk mendukung berbagai karakter.
 * @return mysqli|false Objek koneksi mysqli jika berhasil, atau false jika gagal.
 */
function connect_db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Periksa koneksi
    if ($conn->connect_error) {
        // Jangan tampilkan error detail di produksi, cukup catat log.
        // Untuk pengembangan, die() akan menghentikan script dan menampilkan error.
        die("Koneksi Gagal: " . $conn->connect_error);
    }

    // Set charset
    $conn->set_charset("utf8mb4");

    return $conn;
}

/**
 * Fungsi helper untuk mengambil nilai pengaturan dari tabel tb_pengaturan.
 * @param mysqli $db Objek koneksi database.
 * @param string $setting_nama Nama kunci pengaturan.
 * @param mixed $default_value Nilai default jika kunci tidak ditemukan.
 * @return mixed Nilai dari pengaturan.
 */
function get_setting($db, $setting_nama, $default_value = '') {
    $stmt = $db->prepare("SELECT setting_nilai FROM tb_pengaturan WHERE setting_nama = ?");
    $stmt->bind_param("s", $setting_nama);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['setting_nilai'];
    }
    return $default_value;
}

/**
 * Fungsi helper untuk menyimpan atau memperbarui nilai pengaturan di tabel tb_pengaturan.
 * Menggunakan INSERT ... ON DUPLICATE KEY UPDATE.
 * @param mysqli $db Objek koneksi database.
 * @param string $setting_nama Nama kunci pengaturan.
 * @param string $setting_nilai Nilai yang akan disimpan.
 * @return bool True jika berhasil, false jika gagal.
 */
function set_setting($db, $setting_nama, $setting_nilai) {
    $stmt = $db->prepare("INSERT INTO tb_pengaturan (setting_nama, setting_nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_nilai = ?");
    $stmt->bind_param("sss", $setting_nama, $setting_nilai, $setting_nilai);
    return $stmt->execute();
}

?>

<?php
// FILE: admin_panel.php
// Pastikan file db_connect.php ada di direktori yang sama.

require_once '../config/koneksi.php';

// --- KONEKSI & INISIALISASI ---
$db = connect_db();

$feedbackMessage = '';
$feedbackType = ''; // 'success' atau 'error'

// Muat konfigurasi dari database
$apiKey = get_setting($db, 'rajaongkir_api_key', '');
$activeCouriersJson = get_setting($db, 'rajaongkir_active_couriers', '[]');
$activeCouriers = json_decode($activeCouriersJson, true);
$lastSync = get_setting($db, 'rajaongkir_last_sync');


// --- LOGIKA PEMROSESAN FORM ---

// 1. Logika untuk menyimpan API Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_key'])) {
    $newApiKey = trim($_POST['api_key']);
    if (!empty($newApiKey)) {
        if (set_setting($db, 'rajaongkir_api_key', $newApiKey)) {
            $apiKey = $newApiKey; // Update variabel untuk ditampilkan
            $feedbackMessage = 'Kunci API berhasil disimpan ke database.';
            $feedbackType = 'success';
        } else {
            $feedbackMessage = 'Gagal menyimpan Kunci API ke database.';
            $feedbackType = 'error';
        }
    } else {
        $feedbackMessage = 'Kunci API tidak boleh kosong.';
        $feedbackType = 'error';
    }
}

// 2. Logika untuk menyimpan pengaturan kurir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_couriers'])) {
    $selectedCouriers = isset($_POST['couriers']) ? $_POST['couriers'] : [];
    $couriersJson = json_encode($selectedCouriers);
    if (set_setting($db, 'rajaongkir_active_couriers', $couriersJson)) {
        $activeCouriers = $selectedCouriers; // Update variabel untuk ditampilkan
        $feedbackMessage = 'Pengaturan kurir berhasil diperbarui di database.';
        $feedbackType = 'success';
    } else {
        $feedbackMessage = 'Gagal menyimpan pengaturan kurir ke database.';
        $feedbackType = 'error';
    }
}

// 3. Logika untuk sinkronisasi data wilayah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_data'])) {
    if (empty($apiKey)) {
        $feedbackMessage = 'Sinkronisasi gagal. Harap masukkan Kunci API terlebih dahulu.';
        $feedbackType = 'error';
    } else {
        // Panggil fungsi sinkronisasi dengan koneksi DB
        $syncResult = syncRajaOngkirData($apiKey, $db);
        if ($syncResult['success']) {
            $newSyncTime = date('Y-m-d H:i:s');
            set_setting($db, 'rajaongkir_last_sync', $newSyncTime);
            $lastSync = $newSyncTime; // Update variabel untuk ditampilkan
            $feedbackMessage = "Sinkronisasi berhasil! {$syncResult['province_count']} provinsi dan {$syncResult['city_count']} kota telah disimpan ke database.";
            $feedbackType = 'success';
        } else {
            $feedbackMessage = "Sinkronisasi gagal: " . $syncResult['message'];
            $feedbackType = 'error';
        }
    }
}


/**
 * Fungsi untuk berkomunikasi dengan API RajaOngkir dan menyimpan data ke DATABASE.
 *
 * @param string $apiKey Kunci API RajaOngkir Anda.
 * @param mysqli $db Objek koneksi database.
 * @return array Hasil dari proses sinkronisasi.
 */
function syncRajaOngkirData($apiKey, $db) {
    // Pastikan tabel rajaongkir_provinces dan rajaongkir_cities sudah ada.

    // URL endpoint RajaOngkir
    $provinceUrl = "https://api.rajaongkir.com/starter/province";
    $cityUrl = "https://api.rajaongkir.com/starter/city";

    // Fungsi helper untuk cURL
    $fetchData = function($url, $apiKey) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 30, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => ["key: " . $apiKey],
        ]);
        $response = curl_exec($curl); $err = curl_error($curl); curl_close($curl);
        if ($err) return ['error' => true, 'message' => "cURL Error #:" . $err];
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) return ['error' => true, 'message' => 'Gagal mem-parsing respons JSON.'];
        if ($data['rajaongkir']['status']['code'] != 200) return ['error' => true, 'message' => $data['rajaongkir']['status']['description']];
        return ['error' => false, 'data' => $data['rajaongkir']['results']];
    };

    $db->begin_transaction(); // Mulai transaksi

    try {
        // 1. Ambil dan simpan data provinsi
        $provincesResult = $fetchData($provinceUrl, $apiKey);
        if ($provincesResult['error']) throw new Exception('Gagal mengambil data provinsi. ' . $provincesResult['message']);

        $stmtProv = $db->prepare("INSERT INTO rajaongkir_provinces (province_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        foreach ($provincesResult['data'] as $province) {
            $stmtProv->bind_param("is", $province['province_id'], $province['province']);
            $stmtProv->execute();
        }
        $provinceCount = count($provincesResult['data']);

        // 2. Ambil dan simpan data kota
        $citiesResult = $fetchData($cityUrl, $apiKey);
        if ($citiesResult['error']) throw new Exception('Gagal mengambil data kota. ' . $citiesResult['message']);

        $stmtCity = $db->prepare("INSERT INTO rajaongkir_cities (city_id, province_id, name, type, postal_code) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE province_id = VALUES(province_id), name = VALUES(name), type = VALUES(type), postal_code = VALUES(postal_code)");
        foreach ($citiesResult['data'] as $city) {
            $cityName = $city['type'] . " " . $city['city_name'];
            $stmtCity->bind_param("iisss", $city['city_id'], $city['province_id'], $cityName, $city['type'], $city['postal_code']);
            $stmtCity->execute();
        }
        $cityCount = count($citiesResult['data']);

        $db->commit(); // Jika semua berhasil, commit transaksi
        return ['success' => true, 'province_count' => $provinceCount, 'city_count' => $cityCount];

    } catch (Exception $e) {
        $db->rollback(); // Jika ada error, batalkan semua perubahan
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Daftar kurir yang didukung (sesuaikan dengan tipe akun RajaOngkir Anda)
$all_couriers = [
    'jne' => 'JNE',
    'pos' => 'POS Indonesia',
    'tiki' => 'TIKI'
];

$db->close(); // Tutup koneksi setelah semua logika selesai
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Pengaturan RajaOngkir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-button.active {
            border-color: #3B82F6; /* blue-500 */
            color: #3B82F6;
            background-color: #EFF6FF; /* blue-50 */
        }
        .feedback-enter {
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .feedback-enter-active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Panel Admin RajaOngkir</h1>
        <p class="text-gray-600 mb-6">Atur integrasi RajaOngkir untuk toko Anda di sini.</p>

        <!-- Feedback Message -->
        <?php if ($feedbackMessage): ?>
        <div id="feedback-message" class="mb-6 p-4 rounded-lg text-sm <?php echo $feedbackType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedbackMessage); ?>
        </div>
        <?php endif; ?>


        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <!-- Tab Buttons -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-1 sm:space-x-4 px-4" aria-label="Tabs">
                    <button onclick="changeTab('api')" class="tab-button active whitespace-nowrap py-4 px-2 sm:px-4 border-b-2 font-medium text-sm text-gray-500 hover:text-blue-600 hover:border-blue-300">
                        Pengaturan API
                    </button>
                    <button onclick="changeTab('courier')" class="tab-button whitespace-nowrap py-4 px-2 sm:px-4 border-b-2 font-medium text-sm text-gray-500 hover:text-blue-600 hover:border-blue-300">
                        Pengaturan Kurir
                    </button>
                    <button onclick="changeTab('sync')" class="tab-button whitespace-nowrap py-4 px-2 sm:px-4 border-b-2 font-medium text-sm text-gray-500 hover:text-blue-600 hover:border-blue-300">
                        Sinkronisasi Wilayah
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Tab Content: API -->
                <div id="tab-api" class="tab-content">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Kunci API RajaOngkir</h2>
                    <p class="text-gray-600 text-sm mb-4">Masukkan kunci API dari akun RajaOngkir Anda.</p>
                    <form method="POST" action="admin_panel.php">
                        <div class="max-w-md">
                            <label for="api_key" class="sr-only">Kunci API</label>
                            <input type="password" name="api_key" id="api_key" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="<?php echo htmlspecialchars($apiKey); ?>">
                        </div>
                        <button type="submit" name="save_api_key" class="mt-4 inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Simpan Kunci API
                        </button>
                    </form>
                </div>

                <!-- Tab Content: Courier -->
                <div id="tab-courier" class="tab-content hidden">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Kurir Pengiriman</h2>
                    <p class="text-gray-600 text-sm mb-4">Pilih kurir yang ingin Anda aktifkan untuk pelanggan.</p>
                    <form method="POST" action="admin_panel.php">
                        <fieldset>
                            <legend class="sr-only">Pilihan Kurir</legend>
                            <div class="space-y-3">
                                <?php foreach ($all_couriers as $code => $name): ?>
                                <div class="relative flex items-start">
                                    <div class="flex h-5 items-center">
                                        <input id="courier_<?php echo $code; ?>" name="couriers[]" type="checkbox" value="<?php echo $code; ?>" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo in_array($code, $activeCouriers) ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="courier_<?php echo $code; ?>" class="font-medium text-gray-700"><?php echo $name; ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <button type="submit" name="save_couriers" class="mt-6 inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Simpan Pengaturan Kurir
                        </button>
                    </form>
                </div>

                <!-- Tab Content: Sync -->
                <div id="tab-sync" class="tab-content hidden">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Sinkronisasi Data Wilayah</h2>
                    <p class="text-gray-600 text-sm mb-4">Ambil data provinsi dan kota terbaru dari RajaOngkir ke database lokal Anda.</p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-medium text-gray-700">Status Sinkronisasi Terakhir:</p>
                        <p class="text-sm text-gray-500">
                            <?php echo $lastSync ? date('d F Y \p\u\k\u\l H:i', strtotime($lastSync)) : 'Belum pernah disinkronisasi'; ?>
                        </p>
                    </div>
                    <form id="sync-form" method="POST" action="admin_panel.php" class="mt-4">
                         <button type="submit" name="sync_data" id="sync-button" class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            <svg id="sync-icon" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="sync-text">Mulai Sinkronisasi</span>
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-gray-500">Proses ini akan memasukkan/memperbarui data ke tabel `rajaongkir_provinces` dan `rajaongkir_cities`.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(tb => tb.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            document.querySelector(`button[onclick="changeTab('${tabId}')"]`).classList.add('active');
        }

        const syncForm = document.getElementById('sync-form');
        if (syncForm) {
            syncForm.addEventListener('submit', function() {
                const syncButton = document.getElementById('sync-button');
                syncButton.disabled = true;
                syncButton.classList.add('opacity-75', 'cursor-not-allowed');
                document.getElementById('sync-icon').classList.remove('hidden');
                document.getElementById('sync-text').textContent = 'Menyinkronkan...';
            });
        }
        
        const feedbackMessage = document.getElementById('feedback-message');
        if(feedbackMessage) {
            feedbackMessage.classList.add('feedback-enter');
            setTimeout(() => { feedbackMessage.classList.add('feedback-enter-active'); }, 10);
        }
    </script>

</body>
</html>
