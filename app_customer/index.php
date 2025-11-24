<?php
// === DEBUG MODE ===
error_reporting(E_ALL);
ini_set('display_errors', 0); 

session_start();

// 1. CEK KONEKSI & NAVBAR
$path_koneksi = '../config/koneksi.php';
$path_navbar  = './partials/navbar.php';

// Fallback path checking
if (!file_exists($path_koneksi)) {
    $paths = ['../config/koneksi.php', 'config/koneksi.php', '../koneksi.php'];
    foreach ($paths as $p) { if(file_exists($p)) { $path_koneksi = $p; break; } }
}
require_once $path_koneksi;

// 2. LOGIKA USER
$is_logged_in = isset($_SESSION['user']['id']); 
$nama_user = $is_logged_in ? $_SESSION['user']['nama'] : 'Tamu'; 
$id_user = $is_logged_in ? $_SESSION['user']['id'] : null;
$customer_city_id = null;
$customer_district_id = null; 

if ($is_logged_in) {
    $stmt = $koneksi->prepare("SELECT city_id, district_id FROM tb_user_alamat WHERE user_id = ? AND is_utama = 1");
    if ($stmt) {
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $d = $res->fetch_assoc();
            $customer_city_id = $d['city_id'];
            $customer_district_id = $d['district_id'];
        }
        $stmt->close();
    }
}

// 3. LOGIKA QUERY DATABASE

// A. TOKO POPULER
$list_toko = [];
$toko_section_title = "Toko Populer Nasional";

if ($customer_city_id) {
    $toko_section_title = "Toko di Wilayah Anda";
    $q_toko = "SELECT t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota, 
              (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif 
              FROM tb_toko t JOIN cities c ON t.city_id = c.id 
              WHERE t.status = 'active' AND t.status_operasional = 'Buka' 
              AND (t.city_id = $customer_city_id OR t.district_id = ".($customer_district_id ?? 0).")
              ORDER BY jumlah_produk_aktif DESC, t.nama_toko ASC LIMIT 4";
} else {
    $q_toko = "SELECT t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota, 
              (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif 
              FROM tb_toko t JOIN cities c ON t.city_id = c.id 
              WHERE t.status = 'active' AND t.status_operasional = 'Buka' 
              ORDER BY jumlah_produk_aktif DESC LIMIT 4";
}
$res_toko = $koneksi->query($q_toko);
if ($res_toko) { while($r = $res_toko->fetch_assoc()) { $list_toko[] = $r; } }

// B. PRODUK TERLARIS LOKAL (Jika ada lokasi)
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

// C. PRODUK TERLARIS NASIONAL (Global - Wajib Ada)
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
        /* === STYLE POTA CHATBOT === */
        .live-chat-toggle { position: fixed; bottom: 20px; right: 20px; background: #007bff; color: white; border: none; padding: 15px 20px; border-radius: 50px; font-size: 16px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 9999; display: flex; align-items: center; gap: 8px; transition: transform 0.3s; }
        .live-chat-toggle:hover { transform: scale(1.05); }
        .live-chat-window { position: fixed; bottom: 90px; right: 20px; width: 350px; height: 450px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; z-index: 9999; border: 1px solid #eee; }
        .live-chat-window.active { display: flex; }
        .live-chat-window.expanded { width: 90% !important; height: 90% !important; bottom: 5% !important; right: 5% !important; border-radius: 15px; z-index: 10000; }
        
        .chat-header { background: #007bff; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
        .chat-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
        .chat-message { max-width: 80%; padding: 10px 14px; border-radius: 10px; font-size: 14px; line-height: 1.4; word-wrap: break-word; position: relative; }
        .chat-message.bot { background: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 0; padding-bottom: 25px; }
        .chat-message.user { background: #007bff; color: white; align-self: flex-end; border-bottom-right-radius: 0; }
        .chat-message.loading { background: transparent; color: #888; font-style: italic; font-size: 12px; padding: 0; margin-left: 5px; }
        
        .speak-icon { position: absolute; bottom: 5px; right: 10px; font-size: 12px; color: #888; cursor: pointer; padding: 2px 5px; }
        .speak-icon.speaking { color: #dc3545; animation: pulse 1s infinite; }

        .chat-input-area { padding: 10px; border-top: 1px solid #eee; background: white; display: flex; align-items: center; gap: 8px; }
        .chat-input-area input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; font-size: 14px; }
        .chat-input-area button { background: #007bff; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; }

        #voice-btn { background: #f8f9fa; border: 1px solid #ccc; color: #555; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        #voice-btn.recording { background: #dc3545; color: white; border-color: #dc3545; animation: pulse 1.5s infinite; }

        /* Overlay Telepon */
        #voice-call-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); z-index: 10001; display: none; flex-direction: column; align-items: center; justify-content: center; color: white; }
        .voice-visualizer { width: 80px; height: 80px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; margin-bottom: 30px; transition: all 0.3s; }
        .voice-visualizer i { font-size: 30px; color: #1e3c72; }
        .voice-visualizer.speaking { animation: pulse-blue 1.5s infinite; background: #4facfe; }
        .voice-visualizer.listening { animation: pulse-white 1.5s infinite; background: #ff416c; }
        .voice-btn-hangup { background: #ff416c; color: white; border: none; padding: 12px 25px; border-radius: 30px; font-weight: bold; cursor: pointer; display: flex; gap: 8px; align-items: center; }

        /* Grid Style POTA */
        .chat-store-grid, .chat-product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 10px; width: 100%; box-sizing: border-box; }
        .chat-store-card, .chat-product-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px 4px; text-decoration: none; color: #333; font-size: 11px; text-align: center; align-items: center; min-width: 0; width: 100%; box-sizing: border-box; transition: all 0.2s; }
        .chat-store-card:hover, .chat-product-card:hover { border-color: #007bff; transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .chat-product-img-box { width: 100%; height: 80px; background: #f4f4f4; display: flex; align-items: center; justify-content: center; margin-bottom: 5px; }
        .chat-product-img { width: 100%; height: 100%; object-fit: cover; }
        .chat-product-price { color: #f36f21; font-weight: bold; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        @keyframes pulse-white { 0% { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); transform: scale(1); } 70% { box-shadow: 0 0 0 20px rgba(255,255,255,0); transform: scale(1.1); } 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); transform: scale(1); } }
        @keyframes pulse-blue { 0% { box-shadow: 0 0 0 0 rgba(79,172,254,0.7); transform: scale(1); } 70% { box-shadow: 0 0 0 20px rgba(79,172,254,0); transform: scale(1.1); } 100% { box-shadow: 0 0 0 0 rgba(79,172,254,0); transform: scale(1); } }
        
        @media (max-width: 768px) { .live-chat-window.expanded { width: 100% !important; height: 100% !important; bottom: 0 !important; right: 0 !important; border-radius: 0; } }
    </style>
</head>
<body>
    
    <?php if(file_exists($path_navbar)) include $path_navbar; ?>

    <section class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h2>Cari Bahan Bangunan?</h2>
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
                            $logo = !empty($t['logo_toko']) ? '/assets/uploads/logos/'.$t['logo_toko'] : '/assets/images/default-store-logo.png';
                            $bg = !empty($t['banner_toko']) ? 'background-image: url(/assets/uploads/banners/'.$t['banner_toko'].');' : 'background-color: #f0f0f0;';
                        ?>
                        <a href="pages/toko.php?slug=<?= $t['slug'] ?>" class="store-card">
                            <div class="store-banner" style="<?= $bg ?>"></div>
                            <div class="store-info">
                                <img src="<?= $logo ?>" class="store-logo">
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
        let voices = []; // Simpan data suara

        // Load Suara Browser (Agar bisa pilih suara cowok/cewek nanti)
        function loadVoices() { voices = window.speechSynthesis.getVoices(); }
        window.speechSynthesis.onvoiceschanged = loadVoices;

        // Cek Browser Support Speech
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
                console.log("Voice Error:", event.error);
                document.getElementById('voice-btn').classList.remove('recording');
                if(isCallMode) {
                    if(event.error === 'no-speech') speakText("Halo? Ada orang?", true);
                    else { voiceStatus.innerText = "Gagal mendengar. Coba lagi."; setTimeout(startListening, 2000); }
                } else {
                    if(event.error === 'not-allowed') alert("Izin mikrofon ditolak browser.");
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
                // Bersihkan teks untuk fitur baca suara
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
                // === PERBAIKAN JALUR FILE ===
                // Pastikan file api-chat.php ada di folder yang sama dengan index.php
                const res = await fetch('/api/chat/api_chat.php', { 
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: text, history: chatHistory})
                });
                
                // Cek jika response bukan JSON (Biasanya error PHP / 404)
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
                // Hapus loading jika error
                const loader = document.getElementById('loading-indicator');
                if(loader) loader.remove();

                if(isCallMode) {
                    voiceStatus.innerText = "Error koneksi...";
                    speakText("Maaf, koneksi terputus.", false);
                } else {
                    appendMessage("Maaf, gagal terhubung ke server. Cek koneksi atau nama file api-chat.php", 'bot');
                }
                console.error(e);
            }
        }

        function speakText(text, autoListen = false) {
            window.speechSynthesis.cancel();
            const clean = text.replace(/<[^>]*>?/gm, '').replace(/[*_#]/g, ''); // Bersihkan simbol
            
            const u = new SpeechSynthesisUtterance(clean);
            u.lang = 'id-ID';
            
            // === SETTING SUARA AGAR LEBIH BERAT (MANDOR) ===
            u.pitch = 0.8; // Sedikit ngebass
            u.rate = 1.1;  // Agak cepat
            
            // Coba cari suara Google Indo jika ada
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
</body>
</html>