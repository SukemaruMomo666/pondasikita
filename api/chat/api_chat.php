<?php
error_reporting(0); 
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

require_once '../../config/koneksi.php'; 

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$history = $input['history'] ?? []; 

if (!$message) { echo json_encode(['reply' => 'Halo! POTA siap membantu.']); exit; }

// ============================================================
// 1. LOGIKA "INGATAN" KOTA (SESSION)
// ============================================================
$qKota = mysqli_query($koneksi, "SELECT DISTINCT c.name FROM tb_toko t JOIN cities c ON t.city_id = c.id WHERE t.status = 'active'");
$detectedCity = "";
$allCities = [];

while($r = mysqli_fetch_assoc($qKota)) {
    $cleanName = str_ireplace(['KABUPATEN ', 'KOTA ', 'ADM. '], '', $r['name']);
    $allCities[] = $cleanName;
    if (stripos($message, $cleanName) !== false) {
        $detectedCity = $cleanName;
    }
}

if (!empty($detectedCity)) {
    $_SESSION['last_city_context'] = $detectedCity;
    $cityContext = $detectedCity;
} else {
    $cityContext = $_SESSION['last_city_context'] ?? '';
}

// ============================================================
// 2. CEK APAKAH USER TANYA "TERLARIS/POPULER"?
// ============================================================
$isBestSellerRequest = preg_match('/(terlaris|paling laku|populer|best seller|juara|laku keras)/i', $message);
$dataProdukString = "";
$labelProduk = "";

if ($isBestSellerRequest) {
    // --- LOGIKA BEST SELLER (GRID PRODUK) ---
    // Kita ambil: ID, Nama, Harga, Gambar, Slug Toko, Jumlah Terjual
    
    if (!empty($cityContext)) {
        // A. TERLARIS LOKAL
        $labelProduk = "PRODUK TERLARIS DI " . strtoupper($cityContext);
        $term = "%" . $cityContext . "%";
        $stmtBS = $koneksi->prepare("
            SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug as toko_slug, SUM(dt.jumlah) as total_terjual
            FROM tb_detail_transaksi dt
            JOIN tb_barang b ON dt.barang_id = b.id
            JOIN tb_toko t ON b.toko_id = t.id
            LEFT JOIN cities c ON t.city_id = c.id
            WHERE b.is_active = 1 AND c.name LIKE ?
            GROUP BY b.id
            ORDER BY total_terjual DESC LIMIT 6
        ");
        $stmtBS->bind_param("s", $term);
    } else {
        // B. TERLARIS GLOBAL
        $labelProduk = "PRODUK TERLARIS NASIONAL";
        $stmtBS = $koneksi->prepare("
            SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug as toko_slug, SUM(dt.jumlah) as total_terjual
            FROM tb_detail_transaksi dt
            JOIN tb_barang b ON dt.barang_id = b.id
            JOIN tb_toko t ON b.toko_id = t.id
            WHERE b.is_active = 1
            GROUP BY b.id
            ORDER BY total_terjual DESC LIMIT 6
        ");
    }
    
    $stmtBS->execute();
    $resBS = $stmtBS->get_result();
    
    // FORMAT DATA SPESIFIK UNTUK AI (Agar bisa jadi Link)
    // Format: [ID|Nama|Harga|Gambar|TokoSlug|Terjual]
    if ($resBS->num_rows > 0) {
        while ($row = $resBS->fetch_assoc()) {
            $img = $row['gambar_utama'] ? $row['gambar_utama'] : 'default.jpg';
            $dataProdukString .= "[{$row['id']}|{$row['nama_barang']}|{$row['harga']}|{$img}|{$row['toko_slug']}|{$row['total_terjual']}]\n";
        }
    } else {
        $dataProdukString = "Data transaksi belum tersedia.";
    }

} else {
    // --- PENCARIAN BIASA (KEYWORD) ---
    // Sama seperti sebelumnya, tapi kita upgrade outputnya jadi format Grid juga jika memungkinkan
    $labelProduk = "HASIL PENCARIAN";
    $searchKeywords = [$message];
    if (preg_match('/(cat|dinding)/i', $message)) array_push($searchKeywords, 'cat tembok', 'kuas');
    if (preg_match('/(bata|semen)/i', $message)) array_push($searchKeywords, 'bata', 'semen');
    
    $foundProducts = [];
    $stmtProd = $koneksi->prepare("
        SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug as toko_slug 
        FROM tb_barang b JOIN tb_toko t ON b.toko_id = t.id 
        WHERE b.is_active = 1 AND b.status_moderasi = 'approved' 
        AND (b.nama_barang LIKE ? OR b.deskripsi LIKE ?) LIMIT 4
    ");
    
    foreach (array_unique($searchKeywords) as $kw) {
        if (strlen($kw) < 3) continue;
        $term = "%" . $kw . "%";
        $stmtProd->bind_param("ss", $term, $term);
        $stmtProd->execute();
        $resProd = $stmtProd->get_result();
        while ($row = $resProd->fetch_assoc()) {
            $key = $row['id'];
            if (!isset($foundProducts[$key])) {
                $img = $row['gambar_utama'] ? $row['gambar_utama'] : 'default.jpg';
                // Format: [ID|Nama|Harga|Gambar|TokoSlug|0]
                $dataProdukString .= "[{$row['id']}|{$row['nama_barang']}|{$row['harga']}|{$img}|{$row['toko_slug']}|0]\n";
                $foundProducts[$key] = true;
            }
        }
    }
}

// ============================================================
// 3. AMBIL DATA TOKO (GRID TOKO)
// ============================================================
$finalSearchTerm = !empty($cityContext) ? $cityContext : $message;
$sqlSearchTerm = "%" . $finalSearchTerm . "%";

$dataTokoString = "";
$stmtToko = $koneksi->prepare("
    SELECT t.nama_toko, t.alamat_toko, t.slug, c.name as kota 
    FROM tb_toko t LEFT JOIN cities c ON t.city_id = c.id 
    WHERE t.status = 'active' AND (t.nama_toko LIKE ? OR c.name LIKE ? OR t.alamat_toko LIKE ?) LIMIT 6
");
$stmtToko->bind_param("sss", $sqlSearchTerm, $sqlSearchTerm, $sqlSearchTerm);
$stmtToko->execute();
$resToko = $stmtToko->get_result();
while ($row = $resToko->fetch_assoc()) {
    $dataTokoString .= "[{$row['nama_toko']}|{$row['kota']}|{$row['slug']}]\n";
}

// ============================================================
// 4. SUSUN PROMPT POTA
// ============================================================
$contextData = "DATA DATABASE PONDASIKITA:\n";
$contextData .= "[AREA LAYANAN]: " . implode(", ", array_unique($allCities)) . "\n";
if ($dataProdukString) $contextData .= "[DATA " . $labelProduk . " (Format: ID|Nama|Harga|Gambar|TokoSlug|Terjual)]:\n" . $dataProdukString . "\n";
if ($dataTokoString) $contextData .= "[DATA TOKO (Format: Nama|Kota|Slug)]:\n" . $dataTokoString . "\n";

<<<<<<< HEAD
$apiKey = 'AIzaSyDXWCPfhMuLmiMKyHJbHdriYfE8KpqQBF8'; 
=======
$apiKey = 'AIzaSyBJEhT0qmIU41oVtEaPA_rj5xOh55BBnYo'; 
>>>>>>> 9995fac48aff199a995d17ae9f60076ca5704e4b
$modelName = 'gemini-2.5-flash'; 
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

$systemInstruction = "
Kamu adalah 'POTA' (Pondasikita AI).

ATURAN RENDER HTML (WAJIB DIPATUHI):

1. JIKA MENAMPILKAN TOKO:
Gunakan format:
<div class='chat-store-grid'>
    <a href='pages/toko.php?slug={SLUG}' class='chat-store-card'>
        <div class='chat-store-icon'>🏪</div>
        <div class='chat-store-name'>{NAMA}</div>
        <div class='chat-store-loc'>{KOTA}</div>
    </a>
</div>

2. JIKA MENAMPILKAN PRODUK (Terlaris/Pencarian):
Gunakan format ini persis:
<div class='chat-product-grid'>
    <a href='pages/detail_produk.php?id={ID}&toko_slug={TOKOSLUG}' class='chat-product-card'>
        <div class='chat-product-img-box'>
             <img src='assets/uploads/products/{GAMBAR}' class='chat-product-img' onerror=\"this.style.display='none';this.parentNode.innerText='📦'\">
        </div>
        <div class='chat-product-info'>
            <div class='chat-product-name'>{NAMA}</div>
            <div class='chat-product-price'>Rp {HARGA}</div>
            <div class='chat-product-sold'>Terjual {TERJUAL}x</div>
        </div>
    </a>
</div>

3. JANGAN campur teks biasa dengan Grid jika tidak perlu. Langsung tampilkan Gridnya agar rapi.

Gaya bahasa: Santai & Ramah.
";

$contents = [];
$contents[] = ['role' => 'user', 'parts' => [['text' => $systemInstruction]]];
$contents[] = ['role' => 'model', 'parts' => [['text' => 'Siap. Saya akan merender Grid Produk dan Toko dalam HTML.']]];

foreach ($history as $chat) {
    $role = ($chat['sender'] == 'bot') ? 'model' : 'user';
    $cleanText = strip_tags($chat['text']);
    $contents[] = ['role' => $role, 'parts' => [['text' => $cleanText]]];
}
$contents[] = ['role' => 'user', 'parts' => [['text' => "User: " . $message . "\n" . $contextData]]];

$data = ['contents' => $contents];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$result = json_decode($response, true);
$reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, POTA loading...';

// Formatting Bold (untuk teks biasa)
$formattedReply = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $reply);
echo json_encode(['reply' => $formattedReply]);
?>