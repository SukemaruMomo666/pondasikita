<?php
// cek-model.php
header('Content-Type: text/plain');

// ==========================================
// MASUKKAN API KEY ANDA DI SINI
$apiKey = 'AIzaSyAyTq5l4RXTB8q153DpPPVJ8Eyr7StKers';
// ==========================================

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Bypass SSL agar jalan di Localhost
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Koneksi Error: " . $error;
    exit;
}

$data = json_decode($response, true);

if (isset($data['models'])) {
    echo "=== DAFTAR MODEL YANG TERSEDIA UNTUK API KEY ANDA ===\n\n";
    $found = false;
    foreach ($data['models'] as $model) {
        // Kita cari model yang bisa 'generateContent' (untuk chat)
        if (in_array("generateContent", $model['supportedGenerationMethods'])) {
            echo "Nama Model (Copy bagian ini): " . str_replace('models/', '', $model['name']) . "\n";
            echo "Deskripsi: " . $model['description'] . "\n";
            echo "------------------------------------------------\n";
            $found = true;
        }
    }
    if (!$found) echo "Tidak ada model chat yang ditemukan. Cek izin API Key Anda.";
} else {
    echo "Gagal mengambil data. Response dari Google:\n";
    print_r($data);
}
?>