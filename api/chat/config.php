<?php
// api/chat/config.php (Versi FINAL v10 - Tanpa Fungsi Voucher)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/koneksi.php';
if (!isset($koneksi) || $koneksi === null) { die("Koneksi database belum diinisialisasi."); }

// --- KONFIGURASI UTAMA ---
define('BOT_USER_ID', 33); // Pastikan ID ini adalah ID user dengan level 'bot' di tb_user Anda
define('GEMINI_API_KEY', 'AIzaSyCF5Flq-xMYXVuXj4-iwRifKuN4J_rof8w'); // GANTI DENGAN KEY ANDA YANG ASLI
define('IDLE_TIMEOUT_SECONDS', 60);
define('CHAT_TIMEOUT_SECONDS', 600);

// --- FUNGSI HELPER ASLI ANDA (UTUH) ---
function getLoggedInUserId($koneksi) { return $_SESSION['user']['id'] ?? null; }
function getLoggedInUserRole($koneksi) { return $_SESSION['user']['level'] ?? 'customer'; }
function getUserInfo($koneksi, $user_id) {
    if (!$koneksi || $user_id === null) { return null; }
    $stmt = $koneksi->prepare("SELECT id, username, nama, level as role, status FROM tb_user WHERE id = ?");
    if (!$stmt) { error_log("Failed to prepare getUserInfo statement: " . $koneksi->error); return null; }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
    $stmt->close();
    return $user_info;
}

// PERUBAHAN UNTUK AI SATU ARAH: updateOnlineStatus tidak perlu lagi mengalihkan chat admin ke bot,
// karena chat selalu ditangani oleh bot sejak awal.
function updateOnlineStatus($koneksi, $user_id, $status_type) {
    if (!$user_id || !$koneksi) return false;
    $valid_statuses = ['online', 'offline', 'typing'];
    if (!in_array($status_type, $valid_statuses)) { return false; }

    // Hanya update status untuk user_id yang valid di tb_user (customer, admin, bot)
    $stmt = $koneksi->prepare("UPDATE tb_user SET status = ?, last_activity_at = CURRENT_TIMESTAMP WHERE id = ?");
    if (!$stmt) { error_log("Failed to prepare updateOnlineStatus statement: " . $koneksi->error); return false; }
    $stmt->bind_param("si", $status_type, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    // Admin offline logic is removed because chats are always bot-handled now.
    // If you still want a general 'admin offline' status for display purposes, keep the logic
    // in checkAndSetAdminOffline for *actual* admins, but it won't affect chat routing anymore.
    return $success;
}

// PERUBAHAN UNTUK AI SATU ARAH: checkAndSetAdminOffline mungkin tidak lagi relevan untuk alur chat,
// karena bot selalu aktif. Namun, Anda bisa mempertahankannya untuk tujuan monitoring admin jika perlu.
// Untuk tujuan chat 1-arah bot, fungsi ini bisa diabaikan atau disederhanakan.
function checkAndSetAdminOffline($koneksi) {
    // Fungsi ini bisa tetap ada jika Anda ingin memonitor status admin manusia terlepas dari bot.
    // Namun, tidak akan mempengaruhi routing chat ke bot karena routing sudah default ke bot.
    if (!$koneksi) { return; }
    $stmt = $koneksi->prepare("SELECT id FROM tb_user WHERE level = 'admin' AND status IN ('online', 'typing') AND last_activity_at < (NOW() - INTERVAL ? SECOND)");
    if (!$stmt) { error_log("Failed to prepare checkAndSetAdminOffline statement: " . $koneksi->error); return; }
    $timeout = IDLE_TIMEOUT_SECONDS;
    $stmt->bind_param("i", $timeout);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        updateOnlineStatus($koneksi, $row['id'], 'offline'); // Set admin manusia ke offline
    }
    $stmt->close();
}

// --- FUNGSI PENGAMBIL DATA & MEMORI (Tidak Berubah, Sudah Cukup Baik) ---
function getChatHistory($koneksi, $chat_id, $limit = 6) {
    $history = [];
    $stmt = $koneksi->prepare("SELECT sender_id, message_text FROM messages WHERE chat_id = ? ORDER BY timestamp DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("ii", $chat_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // PERBAIKAN: Gunakan BOT_USER_ID untuk menentukan pengirim sebagai "Asisten Virtual"
            $sender_role = ($row['sender_id'] == BOT_USER_ID) ? "Asisten Virtual" : "Customer";
            array_unshift($history, $sender_role . ": " . $row['message_text']);
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare getChatHistory statement: " . $koneksi->error);
    }
    return implode("\n", $history);
}

function handleProductListingIntent($koneksi, $message_text) {
    $message_lower = strtolower($message_text);
    $keyword = '';

    // Pola regex untuk menangkap keyword dari berbagai format pertanyaan
    if (preg_match('/(jenis|tipe|macam|model)\s+([a-zA-Z0-9\s.-]+)/i', $message_lower, $matches) || 
        preg_match('/([a-zA-Z0-9\s.-]+)\s+(apa saja|apa aja|jenisnya|aja|saja)/i', $message_lower, $matches) ||
        preg_match('/(punya|ada|jual)\s+([a-zA-Z0-9\s.-]+)\?/i', $message_lower, $matches)) {
        
        $keyword = trim($matches[2] ?? $matches[1]);
        $keyword = preg_replace('/\s+(apa saja|apa aja|jenisnya|aja|saja)$/i', '', $keyword);

    } else {
        return ""; // Tidak cocok pola
    }

    if (strlen($keyword) < 3) {
        return ""; // Hindari keyword terlalu pendek
    }

    $found_products = [];
    $stmt = $koneksi->prepare("SELECT nama_barang, harga, stok, stok_di_pesan FROM tb_barang WHERE nama_barang LIKE ? AND is_active = 1 LIMIT 5");
    if ($stmt) {
        $param_like = '%' . $koneksi->real_escape_string($keyword) . '%';
        $stmt->bind_param("s", $param_like);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($product = $result->fetch_assoc()) {
            $stok_tersedia = max(0, $product['stok'] - $product['stok_di_pesan']);
            $harga_formatted = "Rp " . number_format($product['harga'], 0, ',', '.');
            $found_products[] = "- **" . $product['nama_barang'] . "** (Harga: " . $harga_formatted . ", Stok: " . $stok_tersedia . ")";
        }
        $stmt->close();
    }

    if (!empty($found_products)) {
        $response = "Tentu, kami punya beberapa jenis produk terkait '" . ucfirst($keyword) . "':\n" . implode("\n", $found_products);
        $response .= "\n\nApakah ada salah satu dari produk ini yang ingin Anda ketahui lebih detail?";
        return $response;
    }

    return "";
}

function handleProductInfoIntent($koneksi, $message_text) {
    $stmt_dict = $koneksi->prepare("SELECT id, nama_barang FROM tb_barang WHERE is_active = 1");
    if (!$stmt_dict) { error_log("Failed to prepare product dictionary statement: " . $koneksi->error); return ""; }
    $stmt_dict->execute();
    $result_dict = $stmt_dict->get_result();
    $products_dictionary = [];
    while ($row = $result_dict->fetch_assoc()) { $products_dictionary[$row['id']] = strtolower($row['nama_barang']); }
    $stmt_dict->close();

    $found_product_id = null;
    $message_lower = strtolower($message_text);

    foreach ($products_dictionary as $id => $nama_barang) {
        // Use word boundary to avoid partial matches (e.g., 'cat' matching 'kategori')
        if (preg_match('/\b' . preg_quote($nama_barang, '/') . '\b/i', $message_lower)) {
            $found_product_id = $id;
            break;
        }
    }

    if ($found_product_id) {
        $stmt_detail = $koneksi->prepare("SELECT nama_barang, deskripsi, harga, stok, stok_di_pesan FROM tb_barang WHERE id = ?");
        if ($stmt_detail) {
            $stmt_detail->bind_param("i", $found_product_id);
            $stmt_detail->execute();
            $result_detail = $stmt_detail->get_result();
            if ($product = $result_detail->fetch_assoc()) {
                $stmt_detail->close();
                $stok_tersedia = max(0, $product['stok'] - $product['stok_di_pesan']);
                $harga_formatted = "Rp " . number_format($product['harga'], 0, ',', '.');
                return "Info Produk '" . $product['nama_barang'] . "': Harganya adalah " . $harga_formatted . ", stok yang benar-benar tersedia saat ini adalah " . $stok_tersedia . " buah. Deskripsi singkat: " . $product['deskripsi'];
            }
            $stmt_detail->close(); // Close even if no product found for the ID
        } else {
            error_log("Failed to prepare product detail statement: " . $koneksi->error);
        }
    }
    return "";
}

function handleCategoryInfoIntent($koneksi, $message_text) {
    $message_lower = strtolower($message_text);

    // Try to find specific category info
    $stmt = $koneksi->prepare("SELECT nama_kategori, deskripsi FROM tb_kategori WHERE LOWER(nama_kategori) LIKE ? LIMIT 1");
    if ($stmt) {
        $param_like = '%' . $message_lower . '%'; // Use a single LIKE for broad matching initially
        $stmt->bind_param("s", $param_like);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($category = $result->fetch_assoc()) {
            $stmt->close();
            return "Kategori **" . $category['nama_kategori'] . "**: " . $category['deskripsi'];
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare category info statement: " . $koneksi->error);
    }

    // If no specific category found, check for "list categories" intent
    if (preg_match('/\b(daftar|list|semua)\s+(kategori|jenis)\b/i', $message_lower)) {
        $stmt_all = $koneksi->query("SELECT nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
        if ($stmt_all) {
            $categories = [];
            while ($row = $stmt_all->fetch_assoc()) {
                $categories[] = $row['nama_kategori'];
            }
            $stmt_all->close();
            if (!empty($categories)) {
                return "Tentu! Kami memiliki kategori berikut: " . implode(", ", $categories) . ". Apakah Anda ingin tahu lebih banyak tentang salah satu kategori ini?";
            } else {
                return "Maaf, saat ini tidak ada daftar kategori yang tersedia.";
            }
        } else {
            error_log("Failed to query all categories: " . $koneksi->error);
        }
    }
    return "";
}

function handleOrderStatusIntent($koneksi, $user_id, $message_text) {
    $kode_invoice = null;
    // Regex for both INV-YYYYMMDD-XXXXXX and TRX-TIMESTAMP formats
    if (preg_match('/(INV|TRX)[-][0-9]{8}[-][A-Za-z0-9]{6}/i', $message_text, $matches) ||
        preg_match('/TRX-[0-9]{10,}/i', $message_text, $matches)) { // Adjusted for TRX-TIMESTAMP
        $kode_invoice = $matches[0];
    }

    if ($kode_invoice) {
        $stmt = $koneksi->prepare("SELECT kode_invoice, status_pesanan, status_pembayaran, tanggal_transaksi, total_harga, metode_pembayaran, tipe_pengambilan FROM tb_transaksi WHERE kode_invoice = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $kode_invoice);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($order = $result->fetch_assoc()) {
                $stmt->close();
                return "Status pesanan Anda dengan kode **" . $order['kode_invoice'] . "** adalah **" . $order['status_pesanan'] . "** dan status pembayarannya **" . $order['status_pembayaran'] . "** dengan metode **" . ($order['metode_pembayaran'] ?? 'N/A') . "**. Totalnya Rp " . number_format($order['total_harga'], 0, ',', '.') . ". Tanggal transaksi: " . date('d F Y H:i', strtotime($order['tanggal_transaksi'])) . ". Tipe pengambilan: **" . ($order['tipe_pengambilan'] ?? 'Tidak ditentukan') . "**.";
            } else {
                $stmt->close();
                return "Maaf, saya tidak dapat menemukan pesanan dengan kode invoice **" . $kode_invoice . "**. Mohon pastikan kode yang Anda masukkan sudah benar.";
            }
        } else {
             error_log("Failed to prepare order status statement by invoice: " . $koneksi->error);
        }
    }
    // If no explicit invoice code, and user is logged in, try to find their latest order
    else if ($user_id !== null) {
        $stmt = $koneksi->prepare("SELECT kode_invoice, status_pesanan, status_pembayaran, tanggal_transaksi, total_harga, metode_pembayaran, tipe_pengambilan FROM tb_transaksi WHERE user_id = ? ORDER BY tanggal_transaksi DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($order = $result->fetch_assoc()) {
                $stmt->close();
                return "Pesanan terakhir Anda dengan kode **" . $order['kode_invoice'] . "** memiliki status **" . $order['status_pesanan'] . "** dan status pembayarannya **" . $order['status_pembayaran'] . "** dengan metode **" . ($order['metode_pembayaran'] ?? 'N/A') . "**. Totalnya Rp " . number_format($order['total_harga'], 0, ',', '.') . ". Tanggal transaksi: " . date('d F Y H:i', strtotime($order['tanggal_transaksi'])) . ". Tipe pengambilan: **" . ($order['tipe_pengambilan'] ?? 'Tidak ditentukan') . "**.";
            } else {
                $stmt->close();
                return "Anda belum memiliki riwayat pesanan yang terdaftar di sistem kami. Apakah Anda ingin menanyakan pesanan dengan kode invoice tertentu?";
            }
        } else {
            error_log("Failed to prepare order status statement by user ID: " . $koneksi->error);
        }
    }

    return ""; // If no relevant data found or no invoice/user_id
}

// Gabungkan semua fungsi pengambilan data ke dalam getDataFromDBForAI
function getDataFromDBForAI($koneksi, $message_text, $user_id = null) {
    // Prioritas pencarian data
    // 1. Informasi Produk (Spesifik)
    $product_info = handleProductInfoIntent($koneksi, $message_text);
    if (!empty($product_info)) {
        return $product_info;
    }

    // 2. Daftar Produk (Umum)
    $product_listing_info = handleProductListingIntent($koneksi, $message_text);
    if (!empty($product_listing_info)) {
        return $product_listing_info;
    }

    // 3. Informasi Kategori
    $category_info = handleCategoryInfoIntent($koneksi, $message_text);
    if (!empty($category_info)) {
        return $category_info;
    }

    // 4. Status Pesanan (hanya jika user_id diberikan atau kode_invoice terdeteksi)
    $order_status_info = handleOrderStatusIntent($koneksi, $user_id, $message_text);
    if (!empty($order_status_info)) {
        return $order_status_info;
    }

    return ""; // Jika tidak ada data relevan yang ditemukan
}


// --- FUNGSI BOT UTAMA (DENGAN MEMORI) ---
function getBotResponse($koneksi, $chat_id, $message_text) {
    // Ambil user_id dari sesi untuk digunakan di getDataFromDBForAI
    $user_id_from_session = getLoggedInUserId($koneksi);

    // Cek keyword dari bot_configurations terlebih dahulu (prioritas tertinggi)
    $stmt_keyword = $koneksi->prepare("SELECT response_text FROM bot_configurations WHERE ? LIKE CONCAT('%', keyword, '%') LIMIT 1");
    if ($stmt_keyword) {
        $lower_msg = strtolower($message_text);
        $stmt_keyword->bind_param("s", $lower_msg);
        $stmt_keyword->execute();
        $result = $stmt_keyword->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt_keyword->close();
            return $row['response_text']; // Jika ada keyword match, langsung kembalikan
        }
        $stmt_keyword->close();
    } else {
        error_log("Failed to prepare bot_configurations statement: " . $koneksi->error);
    }

    // Ambil data dari DB yang lebih kompleks (sekarang meneruskan user_id)
    $data_from_db = getDataFromDBForAI($koneksi, $message_text, $user_id_from_session);

    // Ambil riwayat chat
    $chat_history = getChatHistory($koneksi, $chat_id);

    // Penentuan sapaan waktu
    date_default_timezone_set('Asia/Jakarta');
    $jam = (int)date('H');
    $sapaan_waktu = ""; // Default kosong
    if (empty($chat_history)) { // Hanya sapa jika ini pesan pertama dalam chat
        if ($jam >= 5 && $jam < 11) { $sapaan_waktu = "Selamat Pagi"; }
        elseif ($jam >= 11 && $jam < 15) { $sapaan_waktu = "Selamat Siang"; }
        elseif ($jam >= 15 && $jam < 19) { $sapaan_waktu = "Selamat Sore"; }
        else { $sapaan_waktu = "Selamat Malam"; }
        $sapaan_waktu .= "! "; // Tambahkan spasi setelah sapaan
    }

    $nama_hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    $nama_bulan = [1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April", 5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus", 9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"];
    $tanggal_lengkap = $nama_hari[date('w')] . ", " . date('d') . " " . $nama_bulan[date('n')] . " " . date('Y');

    // System instruction yang lebih kaya untuk AI Gemini
    $system_instruction = "
    PERAN ANDA: Anda adalah 'Dodo', asisten virtual yang ramah dan informatif dari Pondasikita di Subang. Anda adalah bot yang sangat membantu dan cerdas.

    ATURAN INTERAKSI:
    1.  **Baca dengan Seksama RIWAYAT PERCAKAPAN**: Selalu pahami konteks dari percakapan sebelumnya untuk memberikan jawaban yang relevan dan berkelanjutan. Jika customer menanyakan 'kalau yang itu', Anda harus tahu 'yang itu' merujuk ke produk atau kategori apa dari riwayat.
    2.  **PRIORITASKAN INFORMASI DARI DATABASE**: Jika ada bagian 'INFORMASI PENTING DARI DATABASE' yang relevan dengan pertanyaan customer, gunakan itu sebagai sumber utama jawaban Anda. Jangan mengarang informasi.
    3.  **SAPAAN AWAL**: Jika RIWAYAT PERCAKAPAN KOSONG (ini adalah pesan pertama dari sesi baru), WAJIB memulai dengan sapaan waktu yang sesuai ('{$sapaan_waktu}'). Jika sudah ada riwayat, lanjutkan percakapan tanpa sapaan ulang, langsung ke inti jawaban.
    4.  **KEKREATIFAN & EMPATI**: Respon Anda harus kreatif, menarik, dan sedikit empati. Contoh: Jika tidak ada informasi produk, Anda bisa bilang 'Mohon maaf, Dodo belum menemukan informasi untuk produk tersebut, mungkin ada nama lain yang bisa Dodo bantu cari?'
    5.  **JANGAN MENGULANG**: Hindari mengulang informasi yang sudah jelas ada di pertanyaan atau riwayat, kecuali untuk penegasan yang membantu.
    6.  **PENUTUP SUGESTIF**: Setelah menjawab, selalu tawarkan bantuan lebih lanjut atau pertanyaan terkait lainnya. Contoh: 'Ada hal lain yang bisa Dodo bantu?' atau 'Apakah Anda ingin tahu tentang produk atau kategori lain?'
    7.  **JANGAN MENGAKU ADMIN**: Tegaskan bahwa Anda adalah bot, bukan admin manusia.
    8.  **FORMAT**: Gunakan **bold (\*\*)** untuk menyoroti nama produk, kode invoice, atau informasi penting lainnya agar mudah dibaca.
    9.  **INFORMASI DEFAULT/FALLBACK**: Jika tidak ada info spesifik yang ditemukan di database untuk pertanyaan produk/kategori/status pesanan, informasikan bahwa informasi tersebut tidak ditemukan atau tidak ada informasi spesifik, dan sarankan untuk mencoba nama lain atau menghubungi admin jika diperlukan.
    10. **TANGGAPAN UMUM**: Untuk pertanyaan umum seperti sapaan, perkenalan bot, jam operasional, atau lokasi toko, Dodo harus bisa merespons dengan informasi yang sudah ia ketahui dari instruksi ini atau `bot_configurations`.

    KONTEKS STATIS SAAT INI:
    - Nama Toko: Agung Jaya
    - Lokasi Toko: Subang
    - Tanggal Hari Ini: " . $tanggal_lengkap . "
    ";

    if (!empty($chat_history)) {
        $system_instruction .= "\n\nRIWAYAT PERCAKAPAN SEBELUMNYA:\n" . $chat_history;
    }
    if (!empty($data_from_db)) {
        $system_instruction .= "\n\nINFORMASI PENTING DARI DATABASE:\n" . $data_from_db . "\n**Pastikan Anda menggunakan informasi ini secara AKURAT.**";
    }

    $full_prompt = $system_instruction . "\n\nPertanyaan terakhir dari Customer: \"" . $message_text . "\"\nJAWABAN ANDA SEBAGAI DODO (Fokus pada jawaban yang singkat, padat, informatif, kreatif, dan selalu menawarkan bantuan lebih lanjut):";

    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . GEMINI_API_KEY;

    $data = [
        'contents' => [
            ['parts' => [['text' => $full_prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.7, // Sedikit lebih tinggi untuk kreativitas
            'topK' => 50, // Meningkatkan rentang token yang dipertimbangkan
            'topP' => 0.95, // Meningkatkan probabilitas kumulatif untuk variasi
            'maxOutputTokens' => 300, // Batasi panjang respons
        ]
    ];

    $jsonData = json_encode($data);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log("Gemini API cURL error: " . $error_msg);
        curl_close($ch);
        return "Maaf, Dodo sedang ada sedikit gangguan teknis. Mohon coba lagi nanti ya!";
    }
    curl_close($ch);

    if ($httpCode != 200) {
        error_log("Gemini API call failed with HTTP code " . $httpCode . ": " . $response);
        return "Mohon maaf, Dodo sedang mengalami kesulitan untuk merespon. Ada masalah di sistem AI kami. Apakah ada hal lain yang bisa Dodo bantu?";
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        error_log("Gemini API response missing text: " . $response);
        return "Maaf, Dodo sedikit bingung. Bisakah Anda mengulangi pertanyaan Anda atau bertanya hal lain?";
    }
}
?>

**2. `api/chat/end_chat.php`**
(Fungsi ini lebih untuk admin. Untuk chat 1 arah bot, kita bisa sederhanakan atau tidak menggunakannya sama sekali dari frontend customer. Jika tetap ada, hanya izinkan jika `admin_id` adalah `BOT_USER_ID` atau hapus validasi admin.)

```php
<?php
// api/chat/end_chat.php
header('Content-Type: application/json');
require_once('config.php');

$response = ['success' => false, 'message' => ''];

$input = json_decode(file_get_contents('php://input'), true);
$chat_id = $input['chat_id'] ?? null;
// $admin_id_from_client = $input['admin_id'] ?? null; // ID admin yang dikirim dari client (tidak relevan lagi)

$logged_in_user_id = getLoggedInUserId($koneksi); // ID user yang benar-benar login (customer)
$logged_in_user_level = getLoggedInUserRole($koneksi);

if (!$chat_id) { // Tidak perlu cek admin_id dari client jika hanya bot yang terlibat
    $response['message'] = 'Data tidak lengkap.';
    echo json_encode($response);
    exit();
}

// PERUBAHAN UNTUK AI SATU ARAH: Hanya izinkan customer menutup chatnya sendiri.
// Atau jika admin memang ingin menutup chat bot dari panel admin, sesuaikan.
// Untuk Customer, biarkan dia menutup chatnya sendiri.
// Jika ini dari panel admin untuk menutup chat yang ditangani bot, maka logged_in_user_level harus 'admin'.
// Kita asumsikan ini dipanggil oleh customer untuk mengakhiri chat dengan bot.
$stmt_check_owner = $koneksi->prepare("SELECT customer_id FROM chats WHERE id = ?");
if (!$stmt_check_owner) {
    error_log("Prepare statement gagal di end_chat (owner check): " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt_check_owner->bind_param("i", $chat_id);
$stmt_check_owner->execute();
$chat_owner = $stmt_check_owner->get_result()->fetch_assoc();
$stmt_check_owner->close();

if (!$chat_owner || $chat_owner['customer_id'] != $logged_in_user_id) {
    $response['message'] = 'Akses ditolak. Anda tidak berhak menutup chat ini.';
    header('HTTP/1.1 403 Forbidden');
    echo json_encode($response);
    exit();
}


$stmt = $koneksi->prepare("UPDATE chats SET status = 'closed', end_time = CURRENT_TIMESTAMP WHERE id = ?");
if (!$stmt) {
    error_log("Prepare statement gagal di end_chat: " . $koneksi->error);
    $response['message'] = 'Internal server error.';
    echo json_encode($response);
    exit();
}
$stmt->bind_param("i", $chat_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Chat berhasil ditutup.';
    } else {
        $response['message'] = 'Chat tidak ditemukan atau sudah ditutup.';
    }
} else {
    $response['message'] = 'Gagal menutup chat: ' . $koneksi->error;
}
$stmt->close();

echo json_encode($response);
exit();