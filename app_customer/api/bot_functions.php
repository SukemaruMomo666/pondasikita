<?php
// api/chat/bot_functions.php (Versi FINAL)

// Ini adalah file yang berisi konstanta dan fungsi-fungsi inti untuk bot.
// File ini TIDAK menginisialisasi sesi atau koneksi database.
// Asumsi: $pdo (koneksi database) akan disediakan oleh file yang menyertakan ini.

// --- KONFIGURASI UTAMA BOT ---
define('BOT_USER_ID', 33);
// GEMINI_API_KEY sekarang didefinisikan di sini sebagai konstanta.
// Pastikan nilai ini adalah API Key Gemini Anda yang valid.
define('GEMINI_API_KEY', 'AIzaSyAiviNKwqB4vxvHPJDD2l4_KF0gv9kNDqs'); 
define('IDLE_TIMEOUT_SECONDS', 60);
define('CHAT_TIMEOUT_SECONDS', 600);

// --- FUNGSI HELPER ASLI ANDA (UTUH) ---
// Catatan: Fungsi-fungsi ini sekarang menerima $pdo sebagai argumen.
function getLoggedInUserId($pdo) { 
    // Menggunakan $pdo sebagai argumen, meskipun tidak langsung digunakan di sini.
    // Ini mengasumsikan $_SESSION['user']['id'] sudah diatur di tempat lain.
    return $_SESSION['user']['id'] ?? null; 
}

function getLoggedInUserRole($pdo) { 
    // Menggunakan $pdo sebagai argumen, meskipun tidak langsung digunakan di sini.
    // Ini mengasumsikan $_SESSION['user']['level'] sudah diatur di tempat lain.
    return $_SESSION['user']['level'] ?? 'customer'; 
}

function getUserInfo($pdo, $user_id) {
    if (!$pdo || $user_id === null) { return null; }
    // Menggunakan $pdo untuk prepared statement
    $stmt = $pdo->prepare("SELECT id, username, nama, level as role, status FROM tb_user WHERE id = ?");
    if (!$stmt) { error_log("Failed to prepare getUserInfo statement: " . implode(" ", $pdo->errorInfo())); return null; }
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT); // Menggunakan bindValue untuk PDO
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC); // Menggunakan fetch untuk PDO
    $stmt->closeCursor(); // Untuk PDO, gunakan closeCursor() atau biarkan saja
    return $user_info;
}

function updateOnlineStatus($pdo, $user_id, $status_type) {
    if (!$user_id || !$pdo) return false;
    $valid_statuses = ['online', 'offline', 'typing'];
    if (!in_array($status_type, $valid_statuses)) { return false; }
    $stmt = $pdo->prepare("UPDATE tb_user SET status = ?, last_activity_at = CURRENT_TIMESTAMP WHERE id = ?");
    if (!$stmt) { error_log("Failed to prepare updateOnlineStatus statement: " . implode(" ", $pdo->errorInfo())); return false; }
    $stmt->bindValue(1, $status_type, PDO::PARAM_STR);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    $stmt->closeCursor();
    if ($success) {
        $user_info = getUserInfo($pdo, $user_id);
        if ($user_info && $user_info['role'] === 'admin') {
            if ($status_type === 'offline') {
                if (defined('BOT_USER_ID') && BOT_USER_ID !== 0) {
                    $stmt_chat = $pdo->prepare("UPDATE chats SET status = 'in_progress_bot', admin_id = ? WHERE admin_id = ? AND status = 'open'");
                    if ($stmt_chat) {
                        $bot_id = BOT_USER_ID;
                        $stmt_chat->bindValue(1, $bot_id, PDO::PARAM_INT);
                        $stmt_chat->bindValue(2, $user_id, PDO::PARAM_INT);
                        $stmt_chat->execute();
                        $stmt_chat->closeCursor();
                    } else {
                        error_log("Failed to prepare chat update statement for admin offline: " . implode(" ", $pdo->errorInfo()));
                    }
                }
            }
        }
    }
    return $success;
}

function checkAndSetAdminOffline($pdo) {
    if (!$pdo) { return; }
    $stmt = $pdo->prepare("SELECT id FROM tb_user WHERE level = 'admin' AND status IN ('online', 'typing') AND last_activity_at < (NOW() - INTERVAL ? SECOND)");
    if (!$stmt) { error_log("Failed to prepare checkAndSetAdminOffline statement: " . implode(" ", $pdo->errorInfo())); return; }
    $timeout = IDLE_TIMEOUT_SECONDS;
    $stmt->bindValue(1, $timeout, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results before closing cursor
    $stmt->closeCursor();
    foreach ($result as $row) {
        updateOnlineStatus($pdo, $row['id'], 'offline');
    }
}

// --- FUNGSI PENGAMBIL DATA & MEMORI ---

function getChatHistory($pdo, $chat_id, $limit = 6) {
    $history = [];
    $stmt = $pdo->prepare("SELECT sender_id, message_text FROM messages WHERE chat_id = ? ORDER BY timestamp DESC LIMIT ?");
    if ($stmt) {
        $stmt->bindValue(1, $chat_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        foreach ($result as $row) {
            $sender_role = ($row['sender_id'] == BOT_USER_ID) ? "Asisten Virtual" : "Customer";
            array_unshift($history, $sender_role . ": " . $row['message_text']);
        }
    } else {
        error_log("Failed to prepare getChatHistory statement: " . implode(" ", $pdo->errorInfo()));
    }
    return implode("\n", $history);
}

// --- FUNGSI BARU UNTUK MENANGANI PERTANYAAN UMUM ---
function handleProductListingIntent($pdo, $message_text) {
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
    $stmt = $pdo->prepare("SELECT nama_barang, harga, stok, stok_di_pesan FROM tb_barang WHERE nama_barang LIKE ? AND is_active = 1 LIMIT 5");
    if ($stmt) {
        $param_like = '%' . $keyword . '%'; // PDO handles escaping automatically
        $stmt->bindValue(1, $param_like, PDO::PARAM_STR);
        $stmt->execute();
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stok_tersedia = max(0, $product['stok'] - $product['stok_di_pesan']);
            $harga_formatted = "Rp " . number_format($product['harga'], 0, ',', '.');
            $found_products[] = "- **" . $product['nama_barang'] . "** (Harga: " . $harga_formatted . ", Stok: " . $stok_tersedia . ")";
        }
        $stmt->closeCursor();
    } else {
        error_log("Failed to prepare handleProductListingIntent statement: " . implode(" ", $pdo->errorInfo()));
    }

    if (!empty($found_products)) {
        $response = "Tentu, kami punya beberapa jenis produk terkait '" . ucfirst($keyword) . "':\n" . implode("\n", $found_products);
        $response .= "\n\nApakah ada salah satu dari produk ini yang ingin Anda ketahui lebih detail?";
        return $response;
    }

    return "";
}

function handleProductInfoIntent($pdo, $message_text) {
    $stmt_dict = $pdo->prepare("SELECT id, nama_barang FROM tb_barang WHERE is_active = 1");
    if (!$stmt_dict) { error_log("Failed to prepare product dictionary statement: " . implode(" ", $pdo->errorInfo())); return ""; }
    $stmt_dict->execute();
    $products_dictionary = [];
    while ($row = $stmt_dict->fetch(PDO::FETCH_ASSOC)) { $products_dictionary[$row['id']] = strtolower($row['nama_barang']); }
    $stmt_dict->closeCursor();

    $found_product_id = null;
    $message_lower = strtolower($message_text);

    foreach ($products_dictionary as $id => $nama_barang) {
        if (preg_match('/\b' . preg_quote($nama_barang, '/') . '\b/i', $message_lower)) {
            $found_product_id = $id;
            break;
        }
    }

    if ($found_product_id) {
        $stmt_detail = $pdo->prepare("SELECT nama_barang, deskripsi, harga, stok, stok_di_pesan FROM tb_barang WHERE id = ?");
        if ($stmt_detail) {
            $stmt_detail->bindValue(1, $found_product_id, PDO::PARAM_INT);
            $stmt_detail->execute();
            if ($product = $stmt_detail->fetch(PDO::FETCH_ASSOC)) {
                $stmt_detail->closeCursor();
                $stok_tersedia = max(0, $product['stok'] - $product['stok_di_pesan']);
                $harga_formatted = "Rp " . number_format($product['harga'], 0, ',', '.');
                return "Info Produk '" . $product['nama_barang'] . "': Harganya adalah " . $harga_formatted . ", stok yang benar-benar tersedia saat ini adalah " . $stok_tersedia . " buah. Deskripsi singkat: " . $product['deskripsi'];
            }
            $stmt_detail->closeCursor();
        } else {
            error_log("Failed to prepare product detail statement: " . implode(" ", $pdo->errorInfo()));
        }
    }
    return "";
}

function handleCategoryInfoIntent($pdo, $message_text) {
    $message_lower = strtolower($message_text);

    $stmt = $pdo->prepare("SELECT nama_kategori, deskripsi FROM tb_kategori WHERE LOWER(nama_kategori) LIKE ? LIMIT 1");
    if ($stmt) {
        $param_like = '%' . $message_lower . '%';
        $stmt->bindValue(1, $param_like, PDO::PARAM_STR);
        $stmt->execute();
        if ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stmt->closeCursor();
            return "Kategori **" . $category['nama_kategori'] . "**: " . $category['deskripsi'];
        }
        $stmt->closeCursor();
    } else {
        error_log("Failed to prepare category info statement: " . implode(" ", $pdo->errorInfo()));
    }

    if (preg_match('/\b(daftar|list|semua)\s+(kategori|jenis)\b/i', $message_lower)) {
        $stmt_all = $pdo->query("SELECT nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
        if ($stmt_all) {
            $categories = [];
            while ($row = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = $row['nama_kategori'];
            }
            $stmt_all->closeCursor();
            if (!empty($categories)) {
                return "Tentu! Kami memiliki kategori berikut: " . implode(", ", $categories) . ". Apakah Anda ingin tahu lebih banyak tentang salah satu kategori ini?";
            } else {
                return "Maaf, saat ini tidak ada daftar kategori yang tersedia.";
            }
        } else {
            error_log("Failed to query all categories: " . implode(" ", $pdo->errorInfo()));
        }
    }
    return "";
}

function handleOrderStatusIntent($pdo, $user_id, $message_text) {
    $kode_invoice = null;
    if (preg_match('/(INV|TRX)[-][0-9]{8}[-][A-Za-z0-9]{6}/i', $message_text, $matches) ||
        preg_match('/TRX-[0-9]{10,}/i', $message_text, $matches)) {
        $kode_invoice = $matches[0];
    }

    if ($kode_invoice) {
        $stmt = $pdo->prepare("SELECT kode_invoice, status_pesanan, status_pembayaran, tanggal_transaksi, total_harga, metode_pembayaran, tipe_pengambilan FROM tb_transaksi WHERE kode_invoice = ? LIMIT 1");
        if ($stmt) {
            $stmt->bindValue(1, $kode_invoice, PDO::PARAM_STR);
            $stmt->execute();
            if ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stmt->closeCursor();
                return "Status pesanan Anda dengan kode **" . $order['kode_invoice'] . "** adalah **" . $order['status_pesanan'] . "** dan status pembayarannya **" . $order['status_pembayaran'] . "** dengan metode **" . ($order['metode_pembayaran'] ?? 'N/A') . "**. Totalnya Rp " . number_format($order['total_harga'], 0, ',', '.') . ". Tanggal transaksi: " . date('d F Y H:i', strtotime($order['tanggal_transaksi'])) . ". Tipe pengambilan: **" . ($order['tipe_pengambilan'] ?? 'Tidak ditentukan') . "**.";
            } else {
                $stmt->closeCursor();
                return "Maaf, saya tidak dapat menemukan pesanan dengan kode invoice **" . $kode_invoice . "**. Mohon pastikan kode yang Anda masukkan sudah benar.";
            }
        } else {
            error_log("Failed to prepare order status statement by invoice: " . implode(" ", $pdo->errorInfo()));
        }
    }
    else if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT kode_invoice, status_pesanan, status_pembayaran, tanggal_transaksi, total_harga, metode_pembayaran, tipe_pengambilan FROM tb_transaksi WHERE user_id = ? ORDER BY tanggal_transaksi DESC LIMIT 1");
        if ($stmt) {
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stmt->closeCursor();
                return "Pesanan terakhir Anda dengan kode **" . $order['kode_invoice'] . "** memiliki status **" . $order['status_pesanan'] . "** dan status pembayarannya **" . $order['status_pembayaran'] . "** dengan metode **" . ($order['metode_pembayaran'] ?? 'N/A') . "**. Totalnya Rp " . number_format($order['total_harga'], 0, ',', '.') . ". Tanggal transaksi: " . date('d F Y H:i', strtotime($order['tanggal_transaksi'])) . ". Tipe pengambilan: **" . ($order['tipe_pengambilan'] ?? 'Tidak ditentukan') . "**.";
            } else {
                $stmt->closeCursor();
                return "Anda belum memiliki riwayat pesanan yang terdaftar di sistem kami. Apakah Anda ingin menanyakan pesanan dengan kode invoice tertentu?";
            }
        } else {
            error_log("Failed to prepare order status statement by user ID: " . implode(" ", $pdo->errorInfo()));
        }
    }

    return "";
}

// Gabungkan semua fungsi pengambilan data ke dalam getDataFromDBForAI
function getDataFromDBForAI($pdo, $message_text, $user_id = null) {
    // Prioritas pencarian data
    // 1. Informasi Produk (Spesifik)
    $product_info = handleProductInfoIntent($pdo, $message_text);
    if (!empty($product_info)) {
        return $product_info;
    }

    // 2. Daftar Produk (Umum)
    $product_listing_info = handleProductListingIntent($pdo, $message_text);
    if (!empty($product_listing_info)) {
        return $product_listing_info;
    }

    // 3. Informasi Kategori
    $category_info = handleCategoryInfoIntent($pdo, $message_text);
    if (!empty($category_info)) {
        return $category_info;
    }

    // 4. Status Pesanan (hanya jika user_id diberikan atau kode_invoice terdeteksi)
    $order_status_info = handleOrderStatusIntent($pdo, $user_id, $message_text);
    if (!empty($order_status_info)) {
        return $order_status_info;
    }

    return ""; // Jika tidak ada data relevan yang ditemukan
}

// --- FUNGSI BOT UTAMA (DENGAN MEMORI) ---
function getBotResponse($pdo, $chat_id, $message_text) {
    // Ambil user_id dari sesi untuk digunakan di getDataFromDBForAI
    $user_id_from_session = getLoggedInUserId($pdo);

    // Cek keyword dari bot_configurations terlebih dahulu (prioritas tertinggi)
    $stmt_keyword = $pdo->prepare("SELECT response_text FROM bot_configurations WHERE ? LIKE CONCAT('%', keyword, '%') LIMIT 1");
    if ($stmt_keyword) {
        $lower_msg = strtolower($message_text);
        $stmt_keyword->bindValue(1, $lower_msg, PDO::PARAM_STR);
        $stmt_keyword->execute();
        if ($row = $stmt_keyword->fetch(PDO::FETCH_ASSOC)) {
            $stmt_keyword->closeCursor();
            return $row['response_text']; // Jika ada keyword match, langsung kembalikan
        }
        $stmt_keyword->closeCursor();
    } else {
        error_log("Failed to prepare bot_configurations statement: " . implode(" ", $pdo->errorInfo()));
    }

    // Ambil data dari DB yang lebih kompleks (sekarang meneruskan user_id)
    $data_from_db = getDataFromDBForAI($pdo, $message_text, $user_id_from_session);

    // Ambil riwayat chat
    $chat_history = getChatHistory($pdo, $chat_id);

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
    PERAN ANDA: Anda adalah 'Dodo', asisten virtual yang ramah dan informatif dari Toko Bangunan Agung Jaya di Subang. Anda adalah bot yang sangat membantu dan cerdas.

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
