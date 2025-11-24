<?php
// api-chat.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$message = $input['message'] ?? '';

if (!$message) {
    echo json_encode(['reply' => 'Error: Pesan kosong.']);
    exit;
}

// =======================================================
// MASUKKAN API KEY ANDA DI SINI
$apiKey = 'AIzaSyANopyj7PZSefp9n8merXzeOaTRAJZPFec'; 
// =======================================================

// PENTING: Kita pakai model 'gemini-2.5-flash' sesuai log akun Anda
$modelName = 'gemini-2.5-flash';

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

// Instruksi agar AI berperan sebagai CS Pondasikita
$systemInstruction = "Kamu adalah asisten CS untuk marketplace bahan bangunan 'Pondasikita'. Jawablah pertanyaan pelanggan dengan singkat, ramah, dan membantu dalam Bahasa Indonesia. Jangan gunakan format markdown (seperti bold/italic).";

$data = [
    'contents' => [
        [
            'parts' => [['text' => $systemInstruction . "\n\nUser: " . $message . "\nAI:"]]
        ]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// BYPASS SSL (Agar lancar di Laragon/XAMPP Localhost)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['reply' => 'Koneksi Error: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

// Cek Error dari Google
if (isset($result['error'])) {
    echo json_encode(['reply' => "Google Error: " . $result['error']['message']]);
    exit;
}

// Ambil jawaban
$reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, sistem sedang sibuk.';
$cleanReply = str_replace(['*', '#', '**'], '', $reply);

echo json_encode(['reply' => $cleanReply]);
?>