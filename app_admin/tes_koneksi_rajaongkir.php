<?php
// FILE: tes_koneksi_rajaongkir.php (Versi Multi-Akun)
// Simpan di folder admin Anda dan buka melalui browser.

$apiKey = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
$accountType = isset($_POST['account_type']) ? $_POST['account_type'] : 'starter'; // Default ke starter

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tes Koneksi Lanjutan ke RajaOngkir</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 2em; background-color: #f4f4f4; color: #333; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #0056b3; }
        pre { background: #eee; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: .5rem; font-weight: bold; }
        input[type="text"], select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tes Koneksi Lanjutan ke RajaOngkir</h1>
        <p>Skrip ini akan membantu Anda memeriksa validitas Kunci API untuk berbagai jenis akun RajaOngkir.</p>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="api_key">Masukkan Kunci API Anda:</label>
                <input type="text" id="api_key" name="api_key" value="<?= htmlspecialchars($apiKey) ?>" required>
            </div>
            <div class="form-group">
                <label for="account_type">Pilih Jenis Akun Anda:</label>
                <select id="account_type" name="account_type">
                    <option value="starter" <?= $accountType == 'starter' ? 'selected' : '' ?>>Starter</option>
                    <option value="basic" <?= $accountType == 'basic' ? 'selected' : '' ?>>Basic</option>
                    <option value="pro" <?= $accountType == 'pro' ? 'selected' : '' ?>>Pro</option>
                </select>
            </div>
            <button type="submit">Jalankan Tes</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <hr>
            <h2>Hasil Tes</h2>
            <?php
            if (empty($apiKey)) {
                echo "<p class='error'>Kunci API tidak boleh kosong.</p>";
            } else {
                // 1. Cek apakah ekstensi cURL aktif
                if (!function_exists('curl_init')) {
                    echo "<p class='error'>GAGAL: Ekstensi <strong>cURL</strong> tidak aktif di server PHP Anda. Harap hubungi penyedia hosting Anda untuk mengaktifkannya.</p>";
                } else {
                    echo "<p class='success'>✔️ Ekstensi cURL aktif.</p>";

                    // 2. Tentukan URL berdasarkan jenis akun
                    $baseUrl = "https://api.rajaongkir.com/{$accountType}/province";
                    echo "<p>Mencoba menghubungi URL: <strong>{$baseUrl}</strong></p>";

                    // 3. Lakukan panggilan cURL
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $baseUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_HTTPHEADER => ["key: " . $apiKey],
                    ]);

                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);

                    if ($err) {
                        echo "<h3 class='error'>GAGAL TERHUBUNG</h3>";
                        echo "<p>Terjadi error saat koneksi cURL. Ini biasanya masalah jaringan atau firewall di server Anda.</p>";
                        echo "<p><strong>Pesan Error:</strong> " . htmlspecialchars($err) . "</p>";
                    } else {
                        echo "<h3 class='success'>BERHASIL TERHUBUNG</h3>";
                        echo "<p><strong>Kode Status HTTP dari RajaOngkir:</strong> " . $httpcode . "</p>";
                        
                        $data = json_decode($response, true);
                        if (isset($data['rajaongkir']['status'])) {
                            $rajaOngkirStatus = $data['rajaongkir']['status'];
                            echo "<p><strong>Status dari API RajaOngkir:</strong></p>";
                            echo "<pre>";
                            echo "Kode: " . htmlspecialchars($rajaOngkirStatus['code']) . "\n";
                            echo "Deskripsi: " . htmlspecialchars($rajaOngkirStatus['description']);
                            echo "</pre>";

                            if ($rajaOngkirStatus['code'] == 200) {
                                echo "<p class='success'>Selamat! Kunci API Anda valid untuk akun tipe '{$accountType}'. Sinkronisasi seharusnya sudah bisa dilakukan.</p>";
                            } else {
                                echo "<p class='error'>Peringatan: Kunci API Anda TIDAK VALID untuk akun tipe '{$accountType}'. Coba pilih jenis akun lain atau periksa kembali kunci API Anda.</p>";
                            }
                        } else {
                            echo "<p class='error'>Peringatan: Respons dari RajaOngkir tidak terduga.</p>";
                            echo "<pre>" . htmlspecialchars($response) . "</pre>";
                        }
                    }
                }
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>
