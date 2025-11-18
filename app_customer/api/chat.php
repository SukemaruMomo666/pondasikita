<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$env = parse_ini_file(__DIR__ . '/../../.env');

$DOMAIN = $env['BASE_DOMAIN'];

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../../config/koneksi.php';

if (!isset($pdo)) {
    error_log("PDO object not available in api/chat.php after including koneksi.php.");
    echo json_encode(['success' => false, 'message' => 'Internal server error: Database connection not initialized.']);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$action = $_GET['action'] ?? ($input['action'] ?? '');

function getBotUserId($pdo_unused) {
    return 99;
}

function callGeminiAPI($apiKey, $prompt)
{
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        error_log("Gemini API Error: HTTP Status " . $httpcode . " - " . $response);
        return null;
    }

    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
    error_log("Gemini API Response Format Error: " . print_r($result, true));
    return null;
}

try {
    switch ($action) {
        case 'get_or_create_session':
            $_SESSION['chat_session_id'] = session_id();
            echo json_encode(['success' => true, 'session_id' => session_id()]);
            break;

        case 'check_session':
            $session_id_from_js = $input['session_id'] ?? '';
            if (empty($session_id_from_js) || $session_id_from_js !== session_id()) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid atau telah berakhir.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Sesi valid.']);
            }
            break;

        case 'send_message':
            $message_text = $input['message'] ?? '';
            $session_id = $input['session_id'] ?? '';
            $sender_type = $input['sender_type'] ?? '';

            if (empty($message_text) || empty($session_id) || empty($sender_type)) {
                echo json_encode(['success' => false, 'message' => 'Pesan, ID sesi, atau tipe pengirim tidak boleh kosong.']);
                exit();
            }

            if ($session_id !== session_id()) {
                echo json_encode(['success' => false, 'message' => 'Sesi chat tidak cocok atau telah berakhir.']);
                exit();
            }

            $ai_responses = [];

            if ($sender_type === 'customer' || $sender_type === 'guest') {
                $gemini_api_key = $env["GEMINI_API_KEY"];

                $_SESSION['chat_history'] = $_SESSION['chat_history'] ?? [];

                $_SESSION['chat_history'][] = ['sender_type' => $sender_type, 'message_text' => $message_text];

                if (count($_SESSION['chat_history']) > 10) {
                    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);
                }

                $chat_history_for_prompt = "";
                foreach ($_SESSION['chat_history'] as $msg) {
                    $role = ($msg['sender_type'] === 'customer' || $msg['sender_type'] === 'guest') ? "user" : "model";
                    $chat_history_for_prompt .= "{$role}: {$msg['message_text']}\n";
                }

                $db_context = "";

                $stmt_products = $pdo->query("
                    SELECT
                        b.id AS product_id,
                        b.nama_barang,
                        b.merk_barang,
                        b.deskripsi,
                        b.harga,
                        b.gambar_utama,
                        k.nama_kategori,
                        t.nama_toko
                    FROM
                        tb_barang b
                    JOIN
                        tb_kategori k ON b.kategori_id = k.id
                    JOIN
                        tb_toko t ON b.toko_id = t.id
                    WHERE
                        b.is_active = 1 AND b.status_moderasi = 'approved'
                    ORDER BY
                        b.created_at DESC
                    LIMIT 5
                ");
                $products_data = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

                if ($products_data) {
                    $db_context .= "Berikut adalah data produk bahan bangunan yang tersedia di Pondasikita.com:\n";
                    foreach ($products_data as $product) {
                        $db_context .= "- Nama: " . $product['nama_barang'] . ", Merek: " . $product['merk_barang'] . ", Kategori: " . $product['nama_kategori'] . ", Harga: Rp" . number_format($product['harga'], 0, ',', '.') . ", Toko: " . $product['nama_toko'] . ", Deskripsi: " . $product['deskripsi'] . "\n";
                    }
                }

                $stmt_banks = $pdo->query("
                    SELECT
                        setting_nama, setting_nilai
                    FROM
                        tb_pengaturan
                    WHERE
                        setting_nama IN ('bank_rekening_platform', 'nama_rekening_platform', 'nomor_rekening_platform')
                ");
                $bank_settings = $stmt_banks->fetchAll(PDO::FETCH_KEY_PAIR);
                $bank_data = [
                    'nama_bank' => $bank_settings['bank_rekening_platform'] ?? 'N/A',
                    'atas_nama' => $bank_settings['nama_rekening_platform'] ?? 'N/A',
                    'nomor_rekening' => $bank_settings['nomor_rekening_platform'] ?? 'N/A',
                ];
                $db_context .= "\nInformasi rekening bank platform Pondasikita.com untuk transfer: " . json_encode($bank_data) . "\n";

                $current_date_obj = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
                $current_time = $current_date_obj->format('H:i');
                $hour = (int)$current_date_obj->format('H');

                $time_of_day = '';
                if ($hour >= 5 && $hour < 11) {
                    $time_of_day = 'pagi';
                } elseif ($hour >= 11 && $hour < 15) {
                    $time_of_day = 'siang';
                } elseif ($hour >= 15 && $hour < 18) {
                    $time_of_day = 'sore';
                } else {
                    $time_of_day = 'malam';
                }

                $prompt = "Kamu adalah 'SiPonda', asisten AI customer service untuk platform e-commerce bahan bangunan 'Pondasikita.com'. Jawab dengan ramah, informatif, dan dalam Bahasa Indonesia.

---

Aturan dan Pengetahuanmu:

1. Jawaban tidak boleh mengandung simbol bintang (*). Selalu gunakan tag HTML `<ul><li>...</li></ul>` untuk daftar atau paragraf rapi jika memberikan daftar. Jangan gunakan simbol bullet point non-HTML.
2. Nama kamu adalah SiPonda.
3. Jika disapa 'pagi', 'siang', 'sore', balas dengan ramah sesuai waktu saat ini (saat ini adalah {$current_time} WIB di Bandung, jadi ini {$time_of_day}).
4. Pondasikita.com adalah platform e-commerce multi-vendor berbasis model hiperlokal untuk industri bahan bangunan nasional dengan integrasi asisten AI cerdas. Ini berarti ada banyak toko bahan bangunan yang menjual produk mereka melalui platform kami.
5. Untuk pertanyaan terkait produk (misal: 'semen', 'batu', 'cat', 'harga produk', 'deskripsi'), cari informasi dari 'Konteks Data dari Database' jika relevan. Berikan informasi yang ringkas dan tawarkan untuk melihat detail lebih lanjut di website.
6. Jika pengguna bertanya tentang 'negosiasi', 'menawar', atau 'budget' terkait pembelian, balas dengan instruksi yang jelas:
    <ul>
        <li>Untuk melakukan negosiasi harga atau mencari produk sesuai budget, silakan gunakan fitur pencarian di website utama kami atau kunjungi halaman produk yang Anda inginkan, lalu hubungi langsung toko penjual melalui fitur chat yang tersedia di halaman toko/produk tersebut. Setiap toko memiliki kebijakan harga dan penawaran yang berbeda.</li>
    </ul>
    Jangan pernah mencoba melakukan negosiasi harga secara langsung sebagai AI, dan jangan pernah menyertakan `[TRIGGER_NEGOTIATION]` karena itu hanya pemicu internal frontend.
7. Jika pengguna menanyakan tentang 'Rekomendasi Produk Akhir Tahun' atau 'Promo', rekomendasikan beberapa produk dari 'Konteks Data dari Database' yang mungkin relevan, atau sebutkan bahwa promo bisa bervariasi per toko. Sajikan informasi dalam format daftar menggunakan tag HTML `<ul>` dan `<li>`. Contoh:
    <ul>
        <li>[Nama Produk 1]: [Deskripsi Singkat/Harga] - Tersedia di toko [Nama Toko]</li>
    </ul>
8. Jika ditanya 'cara pembayaran' atau 'metode pembayaran', jelaskan:
    Tersedia beberapa metode pembayaran: Transfer Bank (Virtual Account), Dompet Digital (e-wallet), dan Kartu Kredit melalui payment gateway pihak ketiga terpercaya di Indonesia. Untuk detail transfer bank platform Pondasikita, gunakan data dari 'Konteks Data dari Database'.
    <ul>
        <li>Nama Bank: [Nama Bank]<br>Nomor Rekening: [Nomor Rekening]<br>Atas Nama: [Atas Nama]</li>
    </ul>
9. Jika ditanya 'lokasi' atau 'pengiriman', jawab:
    Pondasikita.com adalah platform online yang menghubungkan Anda dengan toko-toko bahan bangunan terdekat. Pengiriman barang dapat dilakukan melalui opsi 'Pengiriman oleh Toko' (jika toko menyediakan armada sendiri untuk area hiperlokal) atau kurir pihak ketiga. Kami fokus pada wilayah Jabodetabek untuk pilot project. Ini memungkinkan pengiriman cepat dan terjangkau, bahkan *same-day delivery* untuk produk bervolume besar.
10. Jika kamu tidak tahu jawabannya atau pertanyaan terlalu spesifik tentang suatu transaksi/toko, cukup katakan: 'Maaf, untuk pertanyaan tersebut, silakan hubungi langsung toko penjual produk tersebut melalui chat di halaman toko/produk, atau hubungi customer service Pondasikita.com untuk bantuan lebih lanjut.'

---

Konteks Data dari Database (jika ada):

{$db_context}

---

Riwayat Percakapan Terakhir:

{$chat_history_for_prompt}

---

Pertanyaan Pengguna Sekarang:

user: {$message_text}

---

Jawabanmu (sebagai SiPonda):

model: ";

                $gemini_response = callGeminiAPI($gemini_api_key, $prompt);

                if ($gemini_response) {
                    $final_message_text = trim($gemini_response);
                    if (!preg_match('/<a\s+href=.*?>.*?<\/a>/i', $final_message_text) && !preg_match('/<ul.*?>.*?<\/ul>/is', $final_message_text) && !preg_match('/<ol.*?>.*?<\/ol>/is', $final_message_text) && !preg_match('/<strong>.*?<\/strong>/is', $final_message_text) && !preg_match('/<p>.*?<\/p>/is', $final_message_text)) {
                         $final_message_text = htmlspecialchars($final_message_text);
                    }
                    $_SESSION['chat_history'][] = ['sender_type' => 'bot', 'message_text' => $final_message_text];

                    if (!empty($final_message_text)) {
                        $ai_responses[] = [
                            'message_text' => $final_message_text,
                            'sender_type' => 'bot'
                        ];
                    }
                } else {
                    $error_message_ai = "Maaf, SiPonda sedang mengalami gangguan atau tidak dapat terhubung. Mohon coba beberapa saat lagi.";
                    $_SESSION['chat_history'][] = ['sender_type' => 'bot', 'message_text' => $error_message_ai];
                    $ai_responses[] = [
                        'message_text' => $error_message_ai,
                        'sender_type' => 'bot'
                    ];
                }
            }

            echo json_encode(['success' => true, 'message' => 'Pesan berhasil dikirim.', 'ai_messages' => $ai_responses]);

            break;

        case 'get_history':
            $messages = $_SESSION['chat_history'] ?? [];
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        case 'get_new_messages_customer':
            echo json_encode(['success' => true, 'new_messages' => []]);
            break;

        case 'get_unread_count':
            echo json_encode(['success' => true, 'unread_count' => 0]);
            break;

        case 'update_typing_status':
            echo json_encode(['success' => true]);
            break;

        case 'get_typing_status':
            echo json_encode(['success' => true, 'is_typing' => false]);
            break;

        case 'get_products':
            $stmt = $pdo->query("
                SELECT
                    b.id AS product_id,
                    b.nama_barang,
                    b.merk_barang,
                    b.deskripsi,
                    b.harga,
                    b.gambar_utama,
                    k.nama_kategori,
                    t.nama_toko,
                    t.id AS store_id
                FROM
                    tb_barang b
                JOIN
                    tb_kategori k ON b.kategori_id = k.id
                JOIN
                    tb_toko t ON b.toko_id = t.id
                WHERE
                    b.is_active = 1 AND b.status_moderasi = 'approved'
                ORDER BY
                    b.created_at DESC
                LIMIT 5
            ");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $base_image_url = $DOMAIN . '/assets/images/produk/';

            foreach ($products as &$product) {
                $product['display_name'] = htmlspecialchars($product['nama_barang']) . ' (' . htmlspecialchars($product['merk_barang'] ?? 'Tidak Ada Merek') . ')';

                if (!empty($product['gambar_utama'])) {
                    $product['image'] = $base_image_url . $product['gambar_utama'];
                } else {
                    $product['image'] = $base_image_url . 'default_product.jpg';
                }
            }
            unset($product);

            echo json_encode(['success' => true, 'products' => $products]);
            break;

        case 'submit_negotiation_offer':
            $session_id = $input['session_id'] ?? '';
            $product_id = $input['product_id'] ?? null;
            $offer_amount = $input['offer_amount'] ?? null;
            $customer_message_text = $input['customer_message_text'] ?? '';

            if (empty($session_id) || empty($product_id) || !isset($offer_amount) || !is_numeric($offer_amount) || $offer_amount < 0 || empty($customer_message_text)) {
                echo json_encode(['success' => false, 'message' => 'Gagal memproses penawaran: Data negosiasi tidak lengkap.']);
                exit();
            }

            if ($session_id !== session_id()) {
                echo json_encode(['success' => false, 'message' => 'Sesi chat tidak cocok atau telah berakhir.']);
                exit();
            }

            $stmt_product = $pdo->prepare("
                SELECT
                    b.harga AS price_displayed,
                    b.nama_barang,
                    t.nama_toko,
                    t.id AS toko_id
                FROM
                    tb_barang b
                JOIN
                    tb_kategori k ON b.kategori_id = k.id
                JOIN
                    tb_toko t ON b.toko_id = t.id
                WHERE
                    b.id = ?
            ");
            $stmt_product->execute([$product_id]);
            $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan.']);
                exit();
            }

            $response_message_customer_html = '';
            $full_product_name = htmlspecialchars($product['nama_barang']) . ' dari toko ' . htmlspecialchars($product['nama_toko']);

            $response_message_customer_html = '<strong>Terima kasih atas penawaran Anda!</strong><p>Penawaran sebesar Rp' . number_format($offer_amount, 0, ',', '.') . ' untuk ' . $full_product_name . ' telah kami catat. Untuk proses negosiasi lebih lanjut dan pembelian, silakan hubungi langsung toko penjual.</p><button class="negotiation-btn" data-action="contactStore" data-store-id="' . htmlspecialchars($product['toko_id']) . '">Hubungi Toko ' . htmlspecialchars($product['nama_toko']) . '</button><button class="negotiation-btn" data-action="selectOtherProduct">Cari Produk Lain</button>';

            $_SESSION['chat_history'][] = ['sender_type' => ($isCustomerLoggedIn ? 'customer' : 'guest'), 'message_text' => $customer_message_text];
            $_SESSION['chat_history'][] = ['sender_type' => 'bot', 'message_text' => $response_message_customer_html];

            echo json_encode([
                'success' => true,
                'message' => 'Penawaran berhasil diajukan dan diteruskan ke toko.',
                'customer_bot_response_html' => $response_message_customer_html
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
            break;
    }
} catch (\Exception $e) {
    error_log("Unhandled error in api/chat.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server yang tidak terduga.']);
}