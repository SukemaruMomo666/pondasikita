<?php
// === DEBUG MODE (Matikan saat Production) ===
// error_reporting(E_ALL);
// ini_set('display_errors', 1); 

session_start();

// =================================================================
// 1. CONFIG & HELPER FUNCTIONS
// =================================================================

$base_path = __DIR__; 
// Cek file koneksi ada di mana
if (file_exists($base_path . '/config/koneksi.php')) {
    require_once $base_path . '/config/koneksi.php';
} elseif (file_exists($base_path . '/../config/koneksi.php')) {
    require_once $base_path . '/../config/koneksi.php';
} else {
    die("Error: File koneksi database tidak ditemukan. Cek jalur file.");
}

// --- FUNGSI HELPER UNTUK INISIAL TOKO ---
function getStoreInitials($nama_toko) {
    if (empty($nama_toko)) return "TK"; // Default jika kosong
    $words = explode(" ", $nama_toko);
    $acronym = "";
    foreach ($words as $w) {
        $acronym .= mb_substr($w, 0, 1);
    }
    return strtoupper(substr($acronym, 0, 2)); // Ambil maksimal 2 huruf
}

function getStoreColor($nama_toko) {
    // Palet warna material design yang cerah
    $colors = ['#e53935', '#d81b60', '#8e24aa', '#5e35b1', '#3949ab', '#1e88e5', '#039be5', '#00acc1', '#00897b', '#43a047', '#7cb342', '#c0ca33', '#fdd835', '#ffb300', '#fb8c00', '#f4511e'];
    // Hash nama toko ke index angka agar warnanya konsisten per toko
    $index = crc32($nama_toko) % count($colors);
    return $colors[$index];
}

// =================================================================
// 2. LOGIKA DATA USER & LOKASI
// =================================================================
$is_logged_in = isset($_SESSION['user']['id']); 
$nama_user = $is_logged_in ? $_SESSION['user']['nama'] : 'Tamu'; 
$id_user = $is_logged_in ? $_SESSION['user']['id'] : null;

// Default 0 biar query SQL tidak error
$customer_city_id = 0;
$customer_district_id = 0; 

if ($is_logged_in && $koneksi) {
    // Ambil alamat utama user
    $stmt = $koneksi->prepare("SELECT city_id, district_id FROM tb_user_alamat WHERE user_id = ? AND is_utama = 1");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $d = $res->fetch_assoc();
        $customer_city_id = intval($d['city_id']);
        $customer_district_id = intval($d['district_id']);
    }
    $stmt->close();
}

// =================================================================
// 3. QUERY DATA (TOKO & PRODUK)
// =================================================================
$list_toko = [];
$toko_section_title = "Toko Populer Nasional";

// Query Base Toko
$sql_toko_select = "SELECT t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota, 
                    (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif 
                    FROM tb_toko t JOIN cities c ON t.city_id = c.id 
                    WHERE t.status = 'active' AND t.status_operasional = 'Buka'";

if ($customer_city_id > 0) {
    $toko_section_title = "Toko di Wilayah Anda";
    $q_toko = $sql_toko_select . " AND (t.city_id = $customer_city_id OR t.district_id = $customer_district_id) 
              ORDER BY jumlah_produk_aktif DESC, t.nama_toko ASC LIMIT 4";
} else {
    $q_toko = $sql_toko_select . " ORDER BY jumlah_produk_aktif DESC LIMIT 4";
}

$res_toko = $koneksi->query($q_toko);
if ($res_toko) { while($r = $res_toko->fetch_assoc()) { $list_toko[] = $r; } }

// B. PRODUK TERLARIS LOKAL
$list_produk_lokal = [];
if ($customer_city_id) {
    $q_lokal = "SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug AS slug_toko 
               FROM tb_barang b JOIN tb_toko t ON b.toko_id = t.id 
               WHERE b.is_active = 1 AND b.status_moderasi = 'approved' 
               AND (t.city_id = $customer_city_id OR t.district_id = ".($customer_district_id ?? 0).") 
               ORDER BY (SELECT SUM(jumlah) FROM tb_detail_transaksi WHERE barang_id = b.id) DESC LIMIT 8";
    $res_lokal = $koneksi->query($q_lokal);
    if ($res_lokal) { while($r = $res_lokal->fetch_assoc()) { $list_produk_lokal[] = $r; } }
}

// C. PRODUK TERLARIS NASIONAL
$list_produk_nasional = [];
$q_nasional = "SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug AS slug_toko
              FROM tb_barang b JOIN tb_toko t ON b.toko_id = t.id
              WHERE b.is_active = 1 AND b.status_moderasi = 'approved'
              ORDER BY (SELECT SUM(jumlah) FROM tb_detail_transaksi WHERE barang_id = b.id) DESC LIMIT 8";
$res_nasional = $koneksi->query($q_nasional);
if ($res_nasional) { while($r = $res_nasional->fetch_assoc()) { $list_produk_nasional[] = $r; } }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondasikita - Marketplace Bahan Bangunan</title>
    
    <link rel="stylesheet" type="text/css" href="../assets/css/theme.css"> 
    <link rel="stylesheet" type="text/css" href="../assets/css/navbar_style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* === TYPEWRITER EFFECT (HERO BANNER) === */
        .typing-wrapper { display: inline-block; }
        .typing-text { font-weight: bold; color: #fff; border-bottom: 2px solid transparent; }
        .typing-cursor { display: inline-block; width: 3px; background-color: #fff; animation: blink 0.7s infinite; margin-left: 2px; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0; } 100% { opacity: 1; } }

        /* === STYLE POTA CHATBOT (BAWAAN) === */
        .live-chat-toggle { position: fixed; bottom: 20px; right: 20px; background: #007bff; color: white; border: none; padding: 15px 20px; border-radius: 50px; font-size: 16px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 9999; display: flex; align-items: center; gap: 8px; transition: transform 0.3s; }
        .live-chat-toggle:hover { transform: scale(1.05); }
        .live-chat-window { position: fixed; bottom: 90px; right: 20px; width: 350px; height: 450px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; z-index: 9999; border: 1px solid #eee; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); opacity: 0; transform: translateY(20px) scale(0.95); }
        .live-chat-window.active { display: flex; opacity: 1; transform: translateY(0) scale(1); }
        .live-chat-window.expanded { width: 90% !important; height: 90% !important; bottom: 5% !important; right: 5% !important; border-radius: 15px; z-index: 10000; }
        .chat-header { background: #007bff; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
        .chat-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
        .chat-message { max-width: 80%; padding: 10px 14px; border-radius: 10px; font-size: 14px; line-height: 1.4; word-wrap: break-word; position: relative; }
        .chat-message.bot { background: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 0; padding-bottom: 25px; }
        .chat-message.user { background: #007bff; color: white; align-self: flex-end; border-bottom-right-radius: 0; }
        .chat-message.loading { background: transparent; color: #888; font-style: italic; font-size: 12px; padding: 0; margin-left: 5px; }
        .speak-icon { position: absolute; bottom: 5px; right: 10px; font-size: 12px; color: #888; cursor: pointer; padding: 2px 5px; }
        .chat-input-area { padding: 10px; border-top: 1px solid #eee; background: white; display: flex; align-items: center; gap: 8px; }
        .chat-input-area input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; font-size: 14px; }
        .chat-input-area button { background: #007bff; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; }
        #voice-btn { background: #f8f9fa; border: 1px solid #ccc; color: #555; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        #voice-btn.recording { background: #dc3545; color: white; border-color: #dc3545; animation: pulse 1.5s infinite; }
        #voice-call-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); z-index: 10001; display: none; flex-direction: column; align-items: center; justify-content: center; color: white; }
        .voice-visualizer { width: 80px; height: 80px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; margin-bottom: 30px; transition: all 0.3s; }
        .voice-visualizer.speaking { animation: pulse-blue 1.5s infinite; background: #4facfe; }
        .voice-visualizer.listening { animation: pulse-white 1.5s infinite; background: #ff416c; }
        .voice-btn-hangup { background: #ff416c; color: white; border: none; padding: 12px 25px; border-radius: 30px; font-weight: bold; cursor: pointer; display: flex; gap: 8px; align-items: center; }
        .chat-store-grid, .chat-product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 10px; width: 100%; box-sizing: border-box; }
        .chat-store-card, .chat-product-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 4px; text-decoration: none; color: #333; font-size: 11px; text-align: center; align-items: center; min-width: 0; width: 100%; box-sizing: border-box; transition: all 0.2s; }
        .chat-product-img-box { width: 100%; height: 80px; background: #f4f4f4; display: flex; align-items: center; justify-content: center; margin-bottom: 5px; }
        .chat-product-img { width: 100%; height: 100%; object-fit: cover; }
        
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        @keyframes pulse-white { 0% { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); transform: scale(1); } 70% { box-shadow: 0 0 0 20px rgba(255,255,255,0); transform: scale(1.1); } 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); transform: scale(1); } }
        @keyframes pulse-blue { 0% { box-shadow: 0 0 0 0 rgba(79,172,254,0.7); transform: scale(1); } 70% { box-shadow: 0 0 0 20px rgba(79,172,254,0); transform: scale(1.1); } 100% { box-shadow: 0 0 0 0 rgba(79,172,254,0); transform: scale(1); } }

        /* === STYLE TAMBAHAN: INISIAL TOKO === */
        .store-card { position: relative; overflow: hidden; display: block; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: white; transition: transform 0.2s; text-decoration: none; color: inherit; }
        .store-card:hover { transform: translateY(-5px); }
        .store-banner { height: 100px; background-size: cover; background-position: center; position: relative; }
        .store-info { padding: 35px 15px 15px 15px; /* Padding atas lebih besar agar teks tidak kena logo */ position: relative; }
        
        /* Gambar Logo Asli */
        .store-logo {
            width: 60px; height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: absolute;
            bottom: -30px; left: 20px;
            z-index: 2;
            background: white;
        }

        /* Inisial Pengganti Logo */
        .store-logo-initial {
            width: 60px; height: 60px;
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 22px;
            text-transform: uppercase;
            border: 3px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: absolute;
            bottom: -30px; left: 20px;
            z-index: 2;
        }

        @media (max-width: 768px) { .live-chat-window.expanded { width: 100% !important; height: 100% !important; bottom: 0 !important; right: 0 !important; border-radius: 0; } }
    </style>
</head>
<body>
    
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>

    <section class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h2>
                    <span class="typing-text"></span><span class="typing-cursor">&nbsp;</span>
                </h2>
                <h3>Temukan semua kebutuhan proyek Anda dari toko-toko terpercaya.</h3>
                <a href="pages/produk.php" class="btn-primary">Jelajahi Produk</a>
            </div>
        </div>
    </section>

    <main class="main-content">
        <div class="container">
            
            <section class="categories">
                <h2 class="section-title"><span>Kategori Populer</span></h2>
                <div class="category-grid">
                    <?php
                    $kat_q = mysqli_query($koneksi, "SELECT * FROM tb_kategori LIMIT 8");
                    if ($kat_q && mysqli_num_rows($kat_q) > 0) {
                        while ($r = mysqli_fetch_assoc($kat_q)) {
                            echo '<a href="pages/produk.php?kategori='.$r['id'].'" class="category-item">
                                <div class="category-icon"><i class="'.($r['icon_class'] ?? 'fas fa-tools').'"></i></div>
                                <p>'.htmlspecialchars($r['nama_kategori']).'</p>
                            </a>';
                        }
                    } else { echo "<p>Kategori kosong.</p>"; }
                    ?>
                </div>
            </section>

            <section class="featured-stores">
                <div class="section-header">
                    <h2 class="section-title"><span><?= htmlspecialchars($toko_section_title) ?></span></h2>
                    <a href="pages/semua_toko.php" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="store-grid">
                    <?php if (!empty($list_toko)): ?>
                        <?php foreach ($list_toko as $t): 
                            // 1. Tentukan Background Banner (Gambar atau Warna Polos)
                            $bannerUrl = '/assets/uploads/banners/'.$t['banner_toko'];
                            $hasBanner = !empty($t['banner_toko']) && file_exists(__DIR__ . $bannerUrl);
                            
                            $bgStyle = $hasBanner 
                                ? 'background-image: url('.$bannerUrl.');' 
                                : 'background-color: '.getStoreColor($t['nama_toko']).'; opacity: 0.8;';

                            // 2. Tentukan Logo (Gambar atau Inisial)
                            $logoUrl = '/assets/uploads/logos/'.$t['logo_toko'];
                            $hasLogo = !empty($t['logo_toko']) && file_exists(__DIR__ . $logoUrl);
                        ?>
                        <a href="pages/toko.php?slug=<?= $t['slug'] ?>" class="store-card">
                            <div class="store-banner" style="<?= $bgStyle ?>">
                                <?php if ($hasLogo): ?>
                                    <img src="<?= $logoUrl ?>" class="store-logo" alt="Logo">
                                <?php else: ?>
                                    <div class="store-logo-initial" style="background-color: <?= getStoreColor($t['nama_toko']) ?>;">
                                        <?= getStoreInitials($t['nama_toko']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="store-info">
                                <h4><?= htmlspecialchars($t['nama_toko']) ?></h4>
                                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($t['kota']) ?></p>
                                <p class="product-count"><?= $t['jumlah_produk_aktif'] ?> Produk</p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Belum ada toko tersedia.</p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!empty($list_produk_lokal)): ?>
            <section class="products">
                <div class="section-header">
                    <h2 class="section-title"><span>Produk Terlaris di Wilayah Anda</span></h2>
                    <a href="pages/produk.php" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    <?php foreach ($list_produk_lokal as $p): 
                        $img = !empty($p['gambar_utama']) ? '/assets/uploads/products/'.$p['gambar_utama'] : '/assets/uploads/products/default.jpg';
                    ?>
                    <a href="pages/detail_produk.php?id=<?= $p['id'] ?>&toko_slug=<?= $p['slug_toko'] ?>" class="product-link">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= $img ?>" onerror="this.onerror=null; this.src='/assets/uploads/products/default.jpg';">
                            </div>
                            <div class="product-details">
                                <h3><?= substr($p['nama_barang'],0,40) ?>...</h3>
                                <p class="price">Rp<?= number_format($p['harga'],0,',','.') ?></p>
                                <div class="product-store-info"><i class="fas fa-store-alt"></i> <span><?= $p['nama_toko'] ?></span></div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section id="nasional-content" class="products">
                <div class="section-header">
                    <h2 class="section-title"><span>Produk Terlaris Nasional</span></h2>
                    <a href="pages/produk.php" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    <?php if (!empty($list_produk_nasional)): ?>
                        <?php foreach ($list_produk_nasional as $p): 
                            $img = !empty($p['gambar_utama']) ? '/assets/uploads/products/'.$p['gambar_utama'] : '/assets/uploads/products/default.jpg';
                        ?>
                        <a href="pages/detail_produk.php?id=<?= $p['id'] ?>&toko_slug=<?= $p['slug_toko'] ?>" class="product-link">
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?= $img ?>" onerror="this.onerror=null; this.src='/assets/uploads/products/default.jpg';">
                                </div>
                                <div class="product-details">
                                    <h3><?= substr($p['nama_barang'],0,40) ?>...</h3>
                                    <p class="price">Rp<?= number_format($p['harga'],0,',','.') ?></p>
                                    <div class="product-store-info"><i class="fas fa-store-alt"></i> <span><?= $p['nama_toko'] ?></span></div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Belum ada produk terlaris.</p>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>
    
    <?php include 'partials/footer.php'; ?>
    <script src="/assets/js/navbar.js"></script>

    <button id="live-chat-toggle" class="live-chat-toggle" onclick="toggleChat()">
        <i class="fas fa-robot"></i> <span class="chat-toggle-text">Tanya POTA</span>
    </button>
    
    <div id="live-chat-window" class="live-chat-window">
        <div class="chat-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <span id="chat-header-title">POTA (Mandor AI)</span>
            </div>
            <div class="header-controls">
                <button onclick="startVoiceCallMode()" title="Mode Telepon" style="background:none; border:none; color:white; cursor:pointer; margin-right:8px;">
                    <i class="fas fa-phone-volume"></i>
                </button>
                <button onclick="toggleFullScreen()" title="Perbesar" style="background:none; border:none; color:white; cursor:pointer;">
                    <i id="icon-resize" class="fas fa-expand"></i>
                </button>
                <button id="close-chat" class="close-chat-btn" onclick="toggleChat()" style="margin-left:10px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            <div class="chat-message bot">Halo <?= htmlspecialchars($nama_user) ?>! Saya POTA. Tekan tombol telepon 📞 di atas untuk ngobrol langsung, atau ketik di bawah ya!</div>
        </div>
        
        <div class="chat-input-area">
            <button id="voice-btn" onclick="toggleVoice()" title="Tekan untuk bicara"><i class="fas fa-microphone"></i></button>
            <input type="text" id="chat-input" placeholder="Ketik pesan..." onkeypress="handleEnter(event)">
            <button id="send-chat-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>

        <div id="voice-call-overlay">
            <div class="voice-status" id="voice-status-text">Menghubungkan...</div>
            <div class="voice-visualizer" id="voice-visualizer"><i class="fas fa-microphone"></i></div>
            <button class="voice-btn-hangup" onclick="endVoiceCallMode()"><i class="fas fa-phone-slash"></i> Tutup</button>
        </div>
    </div>

    <script>
        const chatWindow = document.getElementById('live-chat-window');
        const messagesContainer = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const toggleBtn = document.getElementById('live-chat-toggle');
        const callOverlay = document.getElementById('voice-call-overlay');
        const voiceStatus = document.getElementById('voice-status-text');
        const voiceVisualizer = document.getElementById('voice-visualizer');
        
        let chatHistory = []; 
        let isCallMode = false;
        let recognition = null;
        let voices = []; 

        function loadVoices() { voices = window.speechSynthesis.getVoices(); }
        window.speechSynthesis.onvoiceschanged = loadVoices;

        if (window.SpeechRecognition || window.webkitSpeechRecognition) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.onresult = (event) => {
                const text = event.results[0][0].transcript;
                if(isCallMode) {
                    voiceStatus.innerText = "Memproses: " + text;
                    voiceVisualizer.className = "voice-visualizer";
                    voiceVisualizer.innerHTML = '<i class="fas fa-brain"></i>';
                    sendMessage(text);
                } else {
                    chatInput.value = text;
                    document.getElementById('voice-btn').classList.remove('recording');
                }
            };

            recognition.onerror = (event) => {
                document.getElementById('voice-btn').classList.remove('recording');
                if(isCallMode) {
                    if(event.error === 'no-speech') speakText("Halo? Ada orang?", true);
                    else { voiceStatus.innerText = "Gagal mendengar."; setTimeout(startListening, 2000); }
                }
            };
            
            recognition.onend = () => { 
                if(!isCallMode) document.getElementById('voice-btn').classList.remove('recording'); 
            };
        }

        function toggleChat() {
            chatWindow.classList.toggle('active');
            if(!chatWindow.classList.contains('active')) {
                toggleBtn.style.display = 'flex';
                endVoiceCallMode();
            } else {
                if(window.innerWidth < 768) toggleBtn.style.display = 'none';
                chatInput.focus();
            }
        }

        function toggleFullScreen() {
            chatWindow.classList.toggle('expanded');
            const icon = document.getElementById('icon-resize');
            icon.className = chatWindow.classList.contains('expanded') ? 'fas fa-compress' : 'fas fa-expand';
        }

        function handleEnter(e) { if(e.key === 'Enter') sendMessage(); }

        function toggleVoice() {
            if(!recognition) { alert("Browser tidak support suara."); return; }
            const btn = document.getElementById('voice-btn');
            if(btn.classList.contains('recording')) {
                recognition.stop();
                btn.classList.remove('recording');
            } else {
                recognition.start();
                btn.classList.add('recording');
            }
        }

        function startVoiceCallMode() {
            if(!recognition) { alert("Browser tidak support suara."); return; }
            isCallMode = true;
            callOverlay.style.display = 'flex';
            chatWindow.classList.add('expanded');
            voiceStatus.innerText = "POTA Bicara...";
            voiceVisualizer.className = "voice-visualizer speaking";
            speakText("Halo! POTA siap mendengarkan.", true);
        }

        function endVoiceCallMode() {
            isCallMode = false;
            callOverlay.style.display = 'none';
            window.speechSynthesis.cancel();
            if(recognition) recognition.stop();
            chatWindow.classList.remove('expanded');
        }

        function startListening() {
            if(!isCallMode) return;
            try {
                recognition.start();
                voiceStatus.innerText = "Silakan bicara...";
                voiceVisualizer.className = "voice-visualizer listening";
                voiceVisualizer.innerHTML = '<i class="fas fa-microphone"></i>';
            } catch(e) { console.log("Mic busy"); }
        }

        function appendMessage(text, sender) {
            const div = document.createElement('div');
            div.classList.add('chat-message', sender);
            if(sender === 'bot') {
                div.innerHTML = text;
                const cleanText = text.replace(/"/g, "'").replace(/\n/g, " ").replace(/<[^>]*>?/gm, '');
                div.innerHTML += `<i class="fas fa-volume-up speak-icon" onclick="speakText('${cleanText}')"></i>`;
            } else {
                div.innerText = text;
            }
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function sendMessage(textOverride = null) {
            const text = textOverride || chatInput.value.trim();
            if(!text) return;

            if(!textOverride) { appendMessage(text, 'user'); chatInput.value = ''; }
            chatHistory.push({sender:'user', text:text});
            if(chatHistory.length > 6) chatHistory.shift();

            if(!isCallMode) {
                const loadDiv = document.createElement('div');
                loadDiv.id = 'loading-indicator';
                loadDiv.className = 'chat-message loading';
                loadDiv.innerText = 'POTA mengetik...';
                messagesContainer.appendChild(loadDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            try {
                // Pastikan path ke api_chat.php benar
                const res = await fetch('/api/chat/api_chat.php', { 
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: text, history: chatHistory})
                });
                
                if (!res.ok) throw new Error("HTTP Error: " + res.status);
                const data = await res.json();
                
                if(!isCallMode) {
                    const loader = document.getElementById('loading-indicator');
                    if(loader) loader.remove();
                }
                
                appendMessage(data.reply, 'bot');
                let cleanText = data.reply.replace(/<[^>]*>?/gm, '');
                chatHistory.push({sender:'bot', text: cleanText});
                if(isCallMode) speakText(data.reply, true);

            } catch(e) {
                const loader = document.getElementById('loading-indicator');
                if(loader) loader.remove();
                if(isCallMode) {
                    voiceStatus.innerText = "Error koneksi...";
                    speakText("Maaf, koneksi terputus.", false);
                } else {
                    appendMessage("Maaf, gagal terhubung ke server.", 'bot');
                }
            }
        }

        function speakText(text, autoListen = false) {
            window.speechSynthesis.cancel();
            const clean = text.replace(/<[^>]*>?/gm, '').replace(/[*_#]/g, '');
            const u = new SpeechSynthesisUtterance(clean);
            u.lang = 'id-ID';
            u.pitch = 0.8; 
            u.rate = 1.1;
            
            if (voices.length === 0) loadVoices();
            const indoVoice = voices.find(v => v.lang === 'id-ID' && v.name.includes('Google')); 
            if (indoVoice) u.voice = indoVoice;

            u.onstart = () => { 
                if(isCallMode) { 
                    voiceVisualizer.className="voice-visualizer speaking"; 
                    voiceVisualizer.innerHTML='<i class="fas fa-volume-up"></i>'; 
                    voiceStatus.innerText = "POTA Menjawab...";
                }
            };
            
            u.onend = () => { 
                if(isCallMode) {
                    voiceVisualizer.className="voice-visualizer";
                    if(autoListen) setTimeout(startListening, 500); 
                }
            };
            window.speechSynthesis.speak(u);
        }
    </script>
    
    <script>
        const typingText = document.querySelector(".typing-text");
        const phrases = [
            "Cari Bahan Bangunan?", 
            "Renovasi Rumah Impian?", 
            "Solusi Material Terlengkap", 
            "Harga Terbaik & Terpercaya",
            "Belanja Mudah dari Rumah"
        ];

        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        let typeSpeed = 100;

        function typeEffect() {
            const currentPhrase = phrases[phraseIndex];

            if (isDeleting) {
                typingText.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
                typeSpeed = 50; 
            } else {
                typingText.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
                typeSpeed = 100;
            }

            if (!isDeleting && charIndex === currentPhrase.length) {
                isDeleting = true;
                typeSpeed = 2000; 
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typeSpeed = 500;
            }

            setTimeout(typeEffect, typeSpeed);
        }

        document.addEventListener("DOMContentLoaded", typeEffect);
    </script>
</body>
</html>