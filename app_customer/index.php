<?php
require_once '../config/koneksi.php'; // Sesuaikan path jika berbeda
session_start();

// Cek apakah pengguna sudah login
$is_logged_in = isset($_SESSION['user']['id']); 
$nama_user = $is_logged_in ? $_SESSION['user']['nama'] : 'Tamu'; 

$id_user = $is_logged_in ? $_SESSION['user']['id'] : null;
$customer_city_id = null;
$customer_district_id = null; 

// Jika user login, ambil alamat utama
if ($is_logged_in) {
    $stmt_alamat = $koneksi->prepare("SELECT city_id, district_id FROM tb_user_alamat WHERE user_id = ? AND is_utama = 1");
    if ($stmt_alamat) {
        $stmt_alamat->bind_param("i", $id_user);
        $stmt_alamat->execute();
        $result_alamat = $stmt_alamat->get_result();
        if ($result_alamat->num_rows > 0) {
            $alamat_utama = $result_alamat->fetch_assoc();
            $customer_city_id = $alamat_utama['city_id'];
            $customer_district_id = $alamat_utama['district_id'];
        }
        $stmt_alamat->close();
    }
}

// Logika Toko Populer
$query_toko = "";
$toko_params = [];
$toko_types = "";
$toko_section_title = "Toko Populer Nasional"; 

if ($customer_city_id) {
    $query_toko = "
        SELECT t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota,
        (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif
        FROM tb_toko t JOIN cities c ON t.city_id = c.id
        WHERE t.status = 'active' AND t.status_operasional = 'Buka' AND (t.city_id = ? OR t.district_id = ?)
        ORDER BY jumlah_produk_aktif DESC, t.nama_toko ASC LIMIT 4";
    $toko_params = [&$customer_city_id, &$customer_district_id];
    $toko_types = "ii";
    $toko_section_title = "Toko di Wilayah Anda"; 
} else {
    $query_toko = "
        SELECT t.id, t.nama_toko, t.slug, t.logo_toko, t.banner_toko, c.name as kota,
        (SELECT COUNT(id) FROM tb_barang WHERE toko_id = t.id AND is_active = 1 AND status_moderasi = 'approved') as jumlah_produk_aktif
        FROM tb_toko t JOIN cities c ON t.city_id = c.id
        WHERE t.status = 'active' AND t.status_operasional = 'Buka'
        ORDER BY jumlah_produk_aktif DESC LIMIT 4";
}

$stmt_toko = $koneksi->prepare($query_toko);
if ($stmt_toko) {
    if (!empty($toko_params)) {
        $stmt_toko->bind_param($toko_types, ...$toko_params);
    }
    $stmt_toko->execute();
    $result_toko = $stmt_toko->get_result();
    $list_toko = [];
    while ($row = $result_toko->fetch_assoc()) {
        $list_toko[] = $row;
    }
    $stmt_toko->close();
} else {
    $list_toko = [];
}

// Logika Produk Terlaris Lokal
$list_produk_terlaris_lokal = [];
if ($customer_city_id) {
    $query_produk_lokal_sql = "
        SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug AS slug_toko 
        FROM tb_barang b JOIN tb_toko t ON b.toko_id = t.id
        WHERE b.is_active = 1 AND b.status_moderasi = 'approved' AND (t.city_id = ? OR t.district_id = ?)
        ORDER BY (SELECT SUM(jumlah) FROM tb_detail_transaksi WHERE barang_id = b.id) DESC LIMIT 8";

    $stmt_produk_lokal = $koneksi->prepare($query_produk_lokal_sql);
    if ($stmt_produk_lokal) {
        $stmt_produk_lokal->bind_param("ii", $customer_city_id, $customer_district_id);
        $stmt_produk_lokal->execute();
        $result_produk_lokal = $stmt_produk_lokal->get_result();
        while ($row = $result_produk_lokal->fetch_assoc()) {
            $list_produk_terlaris_lokal[] = $row;
        }
        $stmt_produk_lokal->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondasikita - Marketplace Bahan Bangunan Terlengkap</title>
    
    <link rel="stylesheet" type="text/css" href="../assets/css/theme.css"> 
    <link rel="stylesheet" type="text/css" href="../assets/css/navbar_style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* === STYLING KHUSUS LIVE CHAT AI === */
        .live-chat-toggle {
            position: fixed; bottom: 20px; right: 20px;
            background: #007bff; color: white; border: none;
            padding: 15px 20px; border-radius: 50px;
            font-size: 16px; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 9999; display: flex; align-items: center; gap: 8px;
            transition: transform 0.3s;
        }
        .live-chat-toggle:hover { transform: scale(1.05); }
        
        .live-chat-window {
            position: fixed; bottom: 90px; right: 20px;
            width: 350px; height: 450px;
            background: white; border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none; /* Default Hidden */
            flex-direction: column; overflow: hidden;
            z-index: 9999; border: 1px solid #eee;
        }
        .live-chat-window.active { display: flex; }
        
        .chat-header {
            background: #007bff; color: white; padding: 15px;
            display: flex; justify-content: space-between; align-items: center;
            font-weight: bold;
        }
        .close-chat-btn { background: none; border: none; color: white; font-size: 18px; cursor: pointer; }
        
        .chat-messages {
            flex: 1; padding: 15px; overflow-y: auto;
            background: #f9f9f9; display: flex; flex-direction: column; gap: 10px;
        }
        
        .chat-message {
            max-width: 80%; padding: 10px 14px; border-radius: 10px;
            font-size: 14px; line-height: 1.4; word-wrap: break-word;
        }
        .chat-message.bot { background: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 0; }
        .chat-message.user { background: #007bff; color: white; align-self: flex-end; border-bottom-right-radius: 0; }
        .chat-message.loading { background: transparent; color: #888; font-style: italic; font-size: 12px; padding: 0; margin-left: 5px; }

        .chat-input-area {
            padding: 10px; border-top: 1px solid #eee; background: white;
            display: flex; align-items: center; gap: 8px;
        }
        .chat-input-area input {
            flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px;
            outline: none; font-size: 14px;
        }
        .chat-input-area button {
            background: #007bff; color: white; border: none;
            width: 35px; height: 35px; border-radius: 50%;
            cursor: pointer; display: flex; justify-content: center; align-items: center;
        }
        .chat-input-area button:hover { background: #0056b3; }
    </style>
</head>
<body>
    
    <?php include './partials/navbar.php'; ?>

    <section class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h2>Cari Bahan Bangunan?</h2>
                <h3>Temukan semua kebutuhan proyek Anda dari toko-toko terpercaya di seluruh Indonesia.</h3>
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
                    $kategori_query = mysqli_query($koneksi, "SELECT * FROM tb_kategori LIMIT 8");
                    if ($kategori_query && mysqli_num_rows($kategori_query) > 0) {
                        while ($row = mysqli_fetch_assoc($kategori_query)) {
                            echo '
                            <a href="pages/produk.php?kategori=' . $row['id'] . '" class="category-item">
                                <div class="category-icon"><i class="' . htmlspecialchars($row['icon_class'] ?? 'fas fa-tools') . '"></i></div>
                                <p>' . htmlspecialchars($row['nama_kategori'] ?? '') . '</p>
                            </a>';
                        }
                    } else {
                        echo "<p>Tidak ada kategori untuk ditampilkan.</p>";
                    }
                    ?>
                </div>
            </section>

            <section class="featured-stores">
                <div class="section-header">
                    <h2 class="section-title"><span><?= htmlspecialchars($toko_section_title) ?></span></h2>
                    <a href="pages/semua_toko.php" class="see-all">Lihat Semua Toko <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="store-grid">
                    <?php
                    if (!empty($list_toko)) {
                        foreach ($list_toko as $toko) {
                            $logo_path = !empty($toko['logo_toko']) ? '/assets/uploads/logos/' . htmlspecialchars($toko['logo_toko']) : '/assets/images/default-store-logo.png';
                            $banner_style = !empty($toko['banner_toko']) ? 'background-image: url(/assets/uploads/banners/' . htmlspecialchars($toko['banner_toko']) . ');' : 'background-color: #f0f0f0;';
                            $jarak_display = isset($toko['jarak_km']) ? '~' . number_format($toko['jarak_km'], 1) . ' km' : '';

                            echo '
                            <a href="pages/toko.php?slug=' . htmlspecialchars($toko['slug']) . '" class="store-card">
                                <div class="store-banner" style="' . $banner_style . '"></div>
                                <div class="store-info">
                                    <img src="' . $logo_path . '" alt="Logo ' . htmlspecialchars($toko['nama_toko']) . '" class="store-logo">
                                    <h4>' . htmlspecialchars($toko['nama_toko']) . '</h4>
                                    <p><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($toko['kota']) . ' ' . $jarak_display . '</p>
                                    <p class="product-count">' . htmlspecialchars($toko['jumlah_produk_aktif']) . ' Produk</p>
                                </div>
                            </a>';
                        }
                    } else {
                        echo "<p>Belum ada toko yang tersedia di wilayah Anda atau secara nasional.</p>";
                    }
                    ?>
                </div>
            </section>

            <?php if (!empty($list_produk_terlaris_lokal)): ?>
            <section class="products">
                <div class="section-header">
                    <h2 class="section-title"><span>Produk Terlaris di Wilayah Anda</span></h2>
                    <a href="pages/produk.php?city_id=<?= htmlspecialchars($customer_city_id) ?>" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    <?php foreach ($list_produk_terlaris_lokal as $row): ?>
                        <?php
                        $gambar_produk_path = !empty($row['gambar_utama']) ? '/assets/uploads/products/' . htmlspecialchars($row['gambar_utama']) : '/assets/uploads/products/default.jpg';
                        ?>
                        <a href="pages/detail_produk.php?id=<?= htmlspecialchars($row['id']) ?>&toko_slug=<?= htmlspecialchars($row['slug_toko']) ?>" class="product-link">
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?= $gambar_produk_path ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>" onerror="this.onerror=null; this.src='/assets/uploads/products/default.jpg';">
                                </div>
                                <div class="product-details">
                                    <h3><?= htmlspecialchars(substr($row['nama_barang'], 0, 45)) . (strlen($row['nama_barang']) > 45 ? '...' : '') ?></h3>
                                    <p class="price">Rp<?= number_format($row['harga'], 0, ',', '.') ?></p>
                                    <div class="product-store-info">
                                        <i class="fas fa-store-alt"></i>
                                        <span><?= htmlspecialchars($row['nama_toko']) ?></span>
                                    </div>
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
                <?php
                    $query_terlaris_sql = "
                        SELECT b.id, b.nama_barang, b.harga, b.gambar_utama, t.nama_toko, t.slug AS slug_toko
                        FROM tb_barang b JOIN tb_toko t ON b.toko_id = t.id
                        WHERE b.is_active = 1 AND b.status_moderasi = 'approved'
                        ORDER BY (SELECT SUM(jumlah) FROM tb_detail_transaksi WHERE barang_id = b.id) DESC LIMIT 8";
                    
                    $result_terlaris = $koneksi->query($query_terlaris_sql);
                    
                    if ($result_terlaris && $result_terlaris->num_rows > 0) {
                        while ($row = $result_terlaris->fetch_assoc()) {
                            $gambar_produk_path = !empty($row['gambar_utama']) ? '/assets/uploads/products/' . htmlspecialchars($row['gambar_utama']) : '/assets/uploads/products/default.jpg';
                            echo '
                            <a href="pages/detail_produk.php?id=' . $row['id'] . '&toko_slug=' . htmlspecialchars($row['slug_toko']) . '" class="product-link">
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="' . $gambar_produk_path . '" alt="' . htmlspecialchars($row['nama_barang']) . '" onerror="this.onerror=null; this.src=\'/assets/uploads/products/default.jpg\';">
                                    </div>
                                    <div class="product-details">
                                        <h3>' . htmlspecialchars(substr($row['nama_barang'], 0, 45)) . (strlen($row['nama_barang']) > 45 ? '...' : '') . '</h3>
                                        <p class="price">Rp' . number_format($row['harga'], 0, ',', '.') . '</p>
                                        <div class="product-store-info">
                                            <i class="fas fa-store-alt"></i>
                                            <span>' . htmlspecialchars($row['nama_toko']) . '</span>
                                        </div>
                                    </div>
                                </div>
                            </a>';
                        }
                    } else {
                        echo "<p>Belum ada produk terlaris saat ini.</p>";
                    }
                ?>
                </div>
            </section>
        </div>
    </main>
    
    <?php include 'partials/footer.php'; ?>
    <script src="/assets/js/navbar.js"></script>

    <button id="live-chat-toggle" class="live-chat-toggle" onclick="toggleChat()">
        <i class="fas fa-robot"></i>
        <span class="chat-toggle-text">Tanya AI</span>
    </button>
    
    <div id="live-chat-window" class="live-chat-window">
        <div class="chat-header">
            <span id="chat-header-title">Asisten Pondasikita</span>
            <button id="close-chat" class="close-chat-btn" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            <div class="chat-message bot">Halo <?= htmlspecialchars($nama_user) ?>! Saya AI Asisten Pondasikita. Ada yang bisa saya bantu cari bahan bangunan hari ini?</div>
        </div>
        
        <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Tanya sesuatu..." onkeypress="handleEnter(event)">
            <button id="send-chat-btn" onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
        const chatWindow = document.getElementById('live-chat-window');
        const messagesContainer = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const toggleBtn = document.getElementById('live-chat-toggle');

        function toggleChat() {
            chatWindow.classList.toggle('active');
            if (chatWindow.classList.contains('active')) {
                chatInput.focus();
                // Sembunyikan tombol toggle saat chat terbuka jika di mobile
                if (window.innerWidth < 768) toggleBtn.style.display = 'none';
            } else {
                toggleBtn.style.display = 'flex';
            }
        }

        function handleEnter(e) {
            if (e.key === 'Enter') sendChat();
        }

        function appendMessage(text, sender) {
            const div = document.createElement('div');
            div.classList.add('chat-message', sender);
            div.innerText = text;
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function sendChat() {
            const text = chatInput.value.trim();
            if (!text) return;

            // 1. Tampilkan pesan user
            appendMessage(text, 'user');
            chatInput.value = '';

            // 2. Indikator loading
            const loadingDiv = document.createElement('div');
            loadingDiv.classList.add('chat-message', 'loading');
            loadingDiv.innerText = 'AI sedang mengetik...';
            loadingDiv.id = 'loading-indicator';
            messagesContainer.appendChild(loadingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            try {
                // 3. Request ke API (PENTING: Pastikan file api-chat.php sudah dibuat)
                const response = await fetch('/api/chat/api_chat.php', { // Path relatif, pastikan file ada di folder yg sama
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });

                const data = await response.json();
                
                // 4. Hapus loading & tampilkan balasan
                const loader = document.getElementById('loading-indicator');
                if(loader) loader.remove();
                
                if (data.reply) {
                    appendMessage(data.reply, 'bot');
                } else {
                    appendMessage("Maaf, terjadi kesalahan pada server.", 'bot');
                }

            } catch (error) {
                const loader = document.getElementById('loading-indicator');
                if(loader) loader.remove();
                appendMessage("Gagal terhubung ke AI. Cek koneksi internet Anda.", 'bot');
                console.error(error);
            }
        }
    </script>

</body>
</html>