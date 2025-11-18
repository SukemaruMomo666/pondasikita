<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Jalur yang benar untuk koneksi.php relatif dari detail_pesanan.php
// Jika detail_pesanan.php ada di app_customer/pages/, maka naik dua level ke root 'pondasikita'
// lalu masuk ke folder 'config'
include '../../config/koneksi.php';

// --- BAGIAN 1: VALIDASI & KEAMANAN ---
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../auth/signin.php");
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<h1>Akses Ditolak</h1><p>ID Pesanan tidak valid.</p>");
}

$id_user_login = (int)$_SESSION['user']['id'];
$id_transaksi = (int)$_GET['id'];

// --- BAGIAN 2: PENGAMBILAN DATA TRANSAKSI & ITEM ---
// Query disesuaikan dengan tabel tb_transaksi dan kolom-kolom baru
// Mengambil kolom yang benar: status_pesanan_global, shipping_nama_penerima, shipping_telepon_penerima, shipping_alamat_lengkap, total_harga_produk, total_final
$query_transaksi_string = "SELECT p.*, u.username, u.email, u.no_telepon
                            FROM tb_transaksi p
                            JOIN tb_user u ON p.user_id = u.id
                            WHERE p.id = ? AND p.user_id = ?";
$stmt_transaksi = $koneksi->prepare($query_transaksi_string);
$stmt_transaksi->bind_param("ii", $id_transaksi, $id_user_login);
$stmt_transaksi->execute();
$result_transaksi = $stmt_transaksi->get_result();

if ($result_transaksi->num_rows === 0) {
    die("<h1>Akses Ditolak</h1><p>Transaksi tidak ditemukan atau Anda tidak memiliki hak akses untuk melihatnya.</p>");
}
$transaksi = $result_transaksi->fetch_assoc();

// Menentukan 'tipe_pengambilan' secara heuristik untuk transaksi keseluruhan
// Ini diperlukan karena tidak ada kolom 'tipe_pengambilan' langsung di tb_transaksi.
// Asumsi: Jika status global adalah 'siap_diambil', maka ini adalah pengambilan di toko.
// Jika tidak, diasumsikan sebagai 'pengiriman'.
$tipe_pengambilan_transaksi = 'pengiriman'; // Default
if (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'siap_diambil') {
    $tipe_pengambilan_transaksi = 'ambil_di_toko';
}


// Ambil data item/produk dalam transaksi
// Memastikan kolom 'gambar_utama' diambil dari tb_barang
$query_items_string = "SELECT d.jumlah, d.harga_saat_transaksi AS harga_satuan, b.nama_barang, b.gambar_utama, b.id as barang_id
                       FROM tb_detail_transaksi d
                       JOIN tb_barang b ON d.barang_id = b.id
                       WHERE d.transaksi_id = ?";
$stmt_items = $koneksi->prepare($query_items_string);
$stmt_items->bind_param("i", $id_transaksi);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$items_pesanan = [];
while ($row = $result_items->fetch_assoc()) {
    $items_pesanan[] = $row;
}

// Ambil data item yang sudah diulas
$reviewed_items_ids = [];
// Menggunakan 'status_pesanan_global' dan nama tabel 'tb_review_produk'
if (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'selesai') {
    $query_ulasan = $koneksi->prepare("SELECT barang_id FROM tb_review_produk WHERE transaksi_id = ? AND user_id = ?");
    $query_ulasan->bind_param("ii", $id_transaksi, $id_user_login);
    $query_ulasan->execute();
    $result_ulasan = $query_ulasan->get_result();
    while ($row_ulasan = $result_ulasan->fetch_assoc()) {
        $reviewed_items_ids[] = (int)$row_ulasan['barang_id'];
    }
}

// --- BAGIAN 3: LOGIKA STATUS & PERSIAPAN TAMPILAN ---
// =========================================================================
// == LOGIKA BARU: PEMBATALAN OTOMATIS JIKA WAKTU HABIS (SERVER-SIDE) ==
// =========================================================================
// Menggunakan 'pending' untuk status_pembayaran
if (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'pending' && !empty($transaksi['payment_deadline'])) {
    $deadline_check = new DateTime($transaksi['payment_deadline']);
    $now_check = new DateTime();

    if ($now_check > $deadline_check) {
        // Waktu sudah habis, jalankan proses pembatalan di sini
        $koneksi->begin_transaction();
        try {
            // 1. Ambil detail item untuk kembalikan stok
            $stmt_items_cancel = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = ?");
            $stmt_items_cancel->bind_param("i", $id_transaksi);
            $stmt_items_cancel->execute();
            $result_items_cancel = $stmt_items_cancel->get_result();
            $items_to_restore = [];
            while ($item = $result_items_cancel->fetch_assoc()) {
                $items_to_restore[] = $item;
            }

            // 2. Kembalikan stok yang di-tahan (stok_di_pesan)
            if (!empty($items_to_restore)) {
                $stmt_stok_cancel = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
                foreach ($items_to_restore as $item) {
                    $stmt_stok_cancel->bind_param("ii", $item['jumlah'], $item['barang_id']);
                    $stmt_stok_cancel->execute();
                }
            }

            // 3. Kembalikan kuota voucher jika digunakan (nama tabel 'vouchers')
            if (!empty($transaksi['voucher_digunakan'])) {
                $stmt_voucher_cancel = $koneksi->prepare("UPDATE vouchers SET kuota_terpakai = kuota_terpakai - 1 WHERE kode_voucher = ? AND kuota_terpakai > 0");
                $stmt_voucher_cancel->bind_param("s", $transaksi['voucher_digunakan']);
                $stmt_voucher_cancel->execute();
            }

            // 4. Update status transaksi menjadi Dibatalkan/Expired
            // Menggunakan 'status_pesanan_global' dan 'expired' untuk status_pembayaran
            $stmt_batal_auto = $koneksi->prepare("UPDATE tb_transaksi SET status_pesanan_global = 'dibatalkan', status_pembayaran = 'expired', snap_token = NULL WHERE id = ?");
            $stmt_batal_auto->bind_param("i", $id_transaksi);
            $stmt_batal_auto->execute();

            $koneksi->commit();
            // Muat ulang halaman agar menampilkan status terbaru
            header("Location: detail_pesanan.php?id=" . $id_transaksi);
            exit();

        } catch (Exception $e) {
            $koneksi->rollback();
            // Jika gagal, tampilkan pesan error
            die("Gagal membatalkan transaksi secara otomatis: " . $e->getMessage());
        }
    }
}
// =========================================================================
// =================== AKHIR DARI BLOK BARU ================================
// =========================================================================

// --- BAGIAN 3: LOGIKA STATUS & PERSIAPAN TAMPILAN (VERSI FINAL BERSIH) ---
$sisa_waktu_detik = 0;

// Menggunakan 'pending' untuk status_pembayaran
if (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'pending' && !empty($transaksi['payment_deadline'])) {
    try {
        $deadline = new DateTime($transaksi['payment_deadline']);
        $now = new DateTime();
        if ($deadline > $now) {
            $sisa_waktu_detik = $deadline->getTimestamp() - $now->getTimestamp();
        }
    } catch (Exception $e) { /* Abaikan jika format tanggal salah */ }
}

// Menggunakan 'pending' dan 'status_pesanan_global'
$is_waktu_habis = (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'pending' && $sisa_waktu_detik <= 0);
$bisa_bayar = (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'pending' && !$is_waktu_habis);
// Menggunakan 'status_pesanan_global' dan 'cancelled'
$is_dibatalkan = (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'dibatalkan' || (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'cancelled'));

// Ambil token dari database
$snapToken = $transaksi['snap_token'] ?? null; // Menggunakan null coalescing operator

// Cek notifikasi dari URL
$pickup_success_message = isset($_GET['pickup_success']) && $_GET['pickup_success'] == '1';
$cancel_success_message = isset($_GET['cancel_success']) && $_GET['cancel_success'] == '1';

// Persiapan untuk status tracker di HTML
// Menggunakan nilai enum huruf kecil yang sesuai dengan database
$statuses_delivery = ['menunggu_pembayaran', 'diproses', 'dikirim', 'selesai'];
$statuses_pickup   = ['menunggu_pembayaran', 'diproses', 'siap_diambil', 'selesai'];

// 1. Tentukan array statusnya berdasarkan $tipe_pengambilan_transaksi yang sudah ditentukan
$current_statuses = ($tipe_pengambilan_transaksi == 'ambil_di_toko') ? $statuses_pickup : $statuses_delivery;

// 2. Jika dibatalkan, modifikasi statusnya untuk keperluan visual
if ($is_dibatalkan) {
    // Ubah status terakhir menjadi 'dibatalkan' untuk keperluan visual
    $current_statuses[count($current_statuses) - 1] = 'dibatalkan';
}

// Menggunakan 'status_pesanan_global'
$current_status_index = array_search(strtolower($transaksi['status_pesanan_global'] ?? ''), $current_statuses);
if ($current_status_index === false) {
    // Jika statusnya "dibatalkan", kita set indexnya ke langkah terakhir yang sudah kita ubah
    if ($is_dibatalkan) {
        $current_status_index = count($current_statuses) - 1;
    } else {
        $current_status_index = -1; // Status lain yang tidak ditemukan
    }
}

// Set alamat display berdasarkan $tipe_pengambilan_transaksi
$display_alamat_label = "Alamat Pengiriman:";
// Menggunakan 'shipping_alamat_lengkap' dan null coalescing operator
$display_alamat_value = nl2br(htmlspecialchars($transaksi['shipping_alamat_lengkap'] ?? ''));
if ($tipe_pengambilan_transaksi == 'ambil_di_toko') {
    $display_alamat_label = "Lokasi Pengambilan:";
    $display_alamat_value = "Toko Bangunan Agung Jaya<br>Jl. Raya Pagaden No. 123, Pagaden, Subang, Jawa Barat";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi #<?= htmlspecialchars($transaksi['kode_invoice'] ?? '') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/navbar_style.css"> <!-- Pastikan ini path yang benar -->
    <link rel="stylesheet" href="/assets/css/checkout_page_style.css"> <!-- Pastikan ini path yang benar -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-ejWh_qCa9cClgKRm"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* (CSS Anda tetap sama, kecuali ada perubahan spesifik di bawah) */
        :root { --primary-color: #5e1914; --text-color: #333; --light-gray: #F5F5F5; --border-color: #E0E0E0; --success-color: #28a745; --gray-text: #757575; --danger-color:rgb(145, 29, 20); --star-color: #ffc107; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-gray); color: var(--text-color); margin: 0; padding: 0px; }
        .container { max-width: 800px; margin: auto; }
        .card { background-color: #fff; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .card-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { margin: 0; font-size: 1.2em; }
        .card-header span { font-size: 0.9em; color: var(--gray-text); }
        .card-body { padding: 20px; }
        h4 { font-size: 1em; font-weight: 600; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; }
        .payment-card { text-align: center; }
        .payment-card p { color: var(--gray-text); font-size: 0.9em; }
        .payment-card .timer { font-size: 1.5em; font-weight: 600; color: var(--danger-color); margin: 10px 0; }
        .btn-pay { display: inline-block; background-color: var(--primary-color); color: #fff; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; transition: background-color 0.3s; width: 100%; font-size: 1.1em; }
        .btn-pay:hover { background-color:rgb(161, 25, 15); }
        .btn-pay:disabled { background-color: #ccc; cursor: not-allowed; }
        .status-tracker { display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; }
        .status-tracker::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 4px; background-color: var(--border-color); transform: translateY(-50%); z-index: 1; }
        .status-tracker .line { position: absolute; top: 50%; left: 0; height: 4px; background-color: var(--success-color); transform: translateY(-50%); z-index: 2; transition: width 0.5s ease; }
        .status-step { display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; z-index: 3; width: 100px; }
        .status-step .icon { width: 30px; height: 30px; border-radius: 50%; background-color: #fff; border: 4px solid var(--border-color); transition: all 0.3s ease; }
        .status-step .label { font-size: 0.8em; margin-top: 10px; color: var(--gray-text); text-transform: capitalize; }
        .status-step.completed .icon { border-color: var(--success-color); }
        .status-step.active .icon { border-color: var(--success-color); background-color: var(--success-color); transform: scale(1.2); }
        .status-step.active .label { font-weight: 600; color: var(--success-color); }
        .status-cancelled, .status-expired { text-align: center; padding: 20px; background-color: #fff8f8; border: 1px solid #ffe0e0; border-radius: 8px; }
        .status-cancelled h3, .status-expired h3 { margin: 0; color: var(--danger-color); }
        .status-tracker.failed .line { background-color: var(--danger-color); }
        .status-step.failed .icon { border-color: var(--danger-color); }
        .status-step.failed.active .icon { background-color: var(--danger-color); }
        .status-step.failed.active .label { color: var(--danger-color); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .product-item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); }
        .product-item:last-child { border-bottom: none; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
        .product-details { flex-grow: 1; }
        .product-details h5 { margin: 0; font-size: 0.95em; font-weight: 500; }
        .product-details p { margin: 0; font-size: 0.85em; color: var(--gray-text); }
        .product-price { text-align: right; font-size: 0.9em; }
        .payment-summary div { display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.9em; }
        .payment-summary .total { font-weight: 600; font-size: 1.1em; border-top: 1px solid var(--border-color); margin-top: 10px; padding-top: 10px; }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); } .btn-primary:hover{ background-color: #5e1914; border-color: #5e1914;}
        .btn-ulasan {
            background-color: #ff9800;
            color: white;
            padding: 8px 14px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-ulasan:hover {
            background-color: #e68900;
        }

        .btn-ulasan i {
            font-size: 14px;
        }
        .btn-sudah-diulas {
            background-color: #c8e6c9;
            color: #388e3c;
            padding: 8px 14px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            opacity: 0.9;
            cursor: not-allowed;
        }

        .btn-sudah-diulas i {
            font-size: 14px;
        }
        /* KODE BARU UNTUK TOMBOL BATAL & NOTIFIKASI */
.btn-cancel {
    display: block;
    width: 100%;
    background-color: transparent;
    color: var(--danger-color);
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    border: 1px solid var(--danger-color);
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}
.btn-cancel:hover {
    background-color: var(--danger-color);
    color: #fff;
}
.alert-box {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: .25rem;
}
.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}


        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; }
        .modal-close { position: absolute; top: 10px; right: 15px; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa; }
        .modal-product { display: flex; align-items: center; margin-bottom: 20px; }
        .modal-product img { width: 50px; height: 50px; border-radius: 4px; margin-right: 15px; }
        .star-rating { margin-bottom: 20px; }
        .star-rating .stars { font-size: 2rem; color: var(--gray-text); cursor: pointer; }
        .star-rating .stars span:hover, .star-rating .stars span.selected { color: var(--star-color); }
        .modal-content textarea { width: 100%; min-height: 100px; padding: 10px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 20px; }
        @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php
    // Memastikan jalur include untuk navbar.php sudah benar
    include 'partials/navbar.php';
?>


<div class="container">
    <?php if ($cancel_success_message): ?>
        <div class="alert-box alert-success">Pesanan Anda telah berhasil dibatalkan.</div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header">
            <h2>Detail Pesanan</h2>
            <span>#<?= htmlspecialchars($transaksi['kode_invoice'] ?? '') ?></span>
        </div>
        <div class="card-body">
<?php if (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'cancelled'): ?>
    <div class="status-cancelled"><h3>Transaksi Dibatalkan</h3><p>Anda telah membatalkan transaksi ini.</p></div>
<?php elseif ($is_waktu_habis || (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'expired')): ?>
    <div class="status-expired"><h3>Pembayaran Gagal</h3><p>Waktu pembayaran untuk transaksi ini telah habis. Stok telah dikembalikan.</p></div>
<?php else: ?>
    <div class="status-tracker">
        <?php
            $line_width_divisor = count($current_statuses) > 1 ? (count($current_statuses) - 1) : 1;
            $line_width_percentage = $current_status_index >= 0 ? ($current_status_index / $line_width_divisor) * 100 : 0;
        ?>
        <div class="line" style="width: <?= $line_width_percentage ?>%;"></div>
        <?php foreach ($current_statuses as $index => $status): ?>
            <div class="status-step <?= ($index < $current_status_index) ? 'completed' : (($index == $current_status_index) ? 'active' : '') ?>">
                <div class="icon"></div>
                <div class="label"><?= str_replace('_', ' ', $status) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
        </div>
    </div>

    <?php if ($pickup_success_message): ?>
        <div class="card payment-card" style="background-color: #e6ffe6; border-color: #b3ffb3;">
            <div class="card-body">
                <h4 style="color: #28a745;">Pesanan Berhasil Dibuat!</h4>
                <p>Pesanan Anda akan disiapkan. Setelah pembayaran terverifikasi, Anda dapat mengambil pesanan di toko kami. Silakan tunjukkan bukti pesanan ini.</p>
                <div style="margin-top: 20px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($transaksi['kode_invoice'] . '|' . ($transaksi['shipping_nama_penerima'] ?? '') . '|' . ($transaksi['total_final'] ?? '')) ?>" alt="QR Code Transaksi"  style="border: 1px solid #eee; padding: 5px;">
                </div>
            </div>
        </div>
    <?php elseif ($bisa_bayar && $snapToken): ?>
        <div class="card payment-card">
            <div class="card-body"><h4>Lanjutkan Pembayaran</h4><p>Anda memiliki waktu untuk menyelesaikan pembayaran.</p><div id="countdown-timer" class="timer">--:--</div><button id="bayarSekarang" class="btn-pay">Bayar Sekarang</button></div>
<form action="/actions/proses_batal_pesanan.php" method="POST" onsubmit="return confirm('Anda yakin ingin membatalkan transaksi ini? Stok dan voucher akan dikembalikan.');">
    <input type="hidden" name="transaksi_id" value="<?= $id_transaksi ?>">
    <button type="submit" class="btn-cancel">Batalkan Transaksi</button>
</form>
        </div>
    <?php elseif ($is_waktu_habis): ?>
        <div class="card payment-card status-expired"><h3>Waktu Pembayaran Habis</h3><p>Batas waktu pembayaran pesanan ini telah berakhir.</p></div>
    <?php elseif (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'dikirim'): // Untuk metode pengiriman barang (BUKAN pickup) ?>
        <div class="card">
            <div class="card-body">
                <h4>Konfirmasi Penerimaan</h4>
                <p>Apakah Anda sudah menerima pesanan ini? Klik tombol di bawah ini jika semua barang sudah Anda terima dengan baik.</p>
                <form id="konfirmasi-form"><input type="hidden" name="transaksi_id" value="<?= $id_transaksi ?>"><button type="submit" class="btn-pay">Pesanan Sudah Diterima</button></form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h4>Informasi Pengiriman</h4>
            <div class="info-grid">
                <div class="info-block"><p><strong>Nama Penerima:</strong></p><p><?= htmlspecialchars($transaksi['shipping_nama_penerima'] ?? '') ?></p></div>
<div class="info-block"><p><strong>Kontak:</strong></p><p><?= htmlspecialchars($transaksi['shipping_telepon_penerima'] ?? '') ?></p></div>
                <div class="info-block" style="grid-column: 1 / -1;"><p><strong><?= $display_alamat_label ?></strong></p><p><?= $display_alamat_value ?></p></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h4>Rincian Produk</h4>
            <?php foreach ($items_pesanan as $item): ?>
            <div class="product-item">
                <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar_utama'] ?? '') ?>" alt="Gambar Produk" class="product-image">
                <div class="product-details">
                    <h5><?= htmlspecialchars($item['nama_barang'] ?? '') ?></h5>
                    <p><?= htmlspecialchars($item['jumlah'] ?? 0) ?> barang x Rp<?= number_format($item['harga_satuan'] ?? 0, 0, ',', '.') ?></p>
                </div>
                <div class="product-price">
                    <span>Rp<?= number_format(($item['jumlah'] ?? 0) * ($item['harga_satuan'] ?? 0), 0, ',', '.') ?></span>

                    <?php // Tampilkan tombol ulasan hanya jika pesanan sudah Selesai ?>
                    <?php if (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'selesai'): ?>
                        <?php // Cek apakah ID barang ini ada di dalam array barang yang sudah diulas ?>
                        <?php if(in_array($item['barang_id'], $reviewed_items_ids)): ?>
                            <?php // JIKA SUDAH DIULAS: Tampilkan tombol non-aktif "Sudah Diulas" ?>
                            <button class="btn-sudah-diulas btn-sm mt-2" disabled>
                                <i class="fa-solid fa-check-circle"></i> Sudah Diulas
                            </button>
                        <?php else: ?>
                            <?php // JIKA BELUM DIULAS: Tampilkan tombol aktif "Beri Ulasan" ?>
                            <button class="btn-ulasan btn-sm mt-2 tombol-ulasan"
                                data-barang-id="<?= $item['barang_id'] ?>"
                                data-nama-barang="<?= htmlspecialchars($item['nama_barang'] ?? '') ?>"
                                data-gambar-barang="<?= htmlspecialchars($item['gambar_utama'] ?? '') ?>">
                                <i class="fa-solid fa-star"></i> Beri Ulasan
                            </button>
                        <?php endif; // Akhir dari if/else in_array ?>
                    <?php endif; // Akhir dari if status pesanan ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h4>Rincian Pembayaran</h4>
            <div class="payment-summary">
                <div><span>Metode Pembayaran</span><span><?= htmlspecialchars(str_replace('_', ' ', $transaksi['metode_pembayaran'] ?? '')) ?></span></div>
                <div><span>Subtotal Produk</span><span>Rp<?= number_format($transaksi['total_harga_produk'] ?? 0, 0, ',', '.') ?></span></div>
                <div><span>Ongkos Kirim</span><span>Rp<?= number_format($transaksi['biaya_pengiriman'] ?? 0, 0, ',', '.') ?></span></div>

<?php if (isset($transaksi['total_diskon']) && $transaksi['total_diskon'] > 0): ?>
    <div class="discount-row">
        <span>Diskon (<?= htmlspecialchars($transaksi['voucher_digunakan'] ?? '') ?>)</span>
        <span style="color: #28a745;">-Rp<?= number_format($transaksi['total_diskon'] ?? 0, 0, ',', '.') ?></span>
    </div>
<?php endif; ?>
               <div class="total"><span>Total Pembayaran</span><span>Rp<?= number_format($transaksi['total_final'] ?? 0, 0, ',', '.') ?></span></div>
            </div>
        </div>
    </div>

    <div id="review-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close">×</span>
            <h4>Beri Ulasan untuk Produk</h4>
            <div class="modal-product">
                <img id="modal-product-img" src="" alt="Produk">
                <h5 id="modal-product-name">Nama Produk</h5>
            </div>
            <form id="review-form" enctype="multipart/form-data">
                <input type="hidden" name="transaksi_id" value="<?= $id_transaksi ?>">
                <input type="hidden" id="modal-barang-id" name="barang_id" value="">
                <input type="hidden" id="modal-rating" name="rating" value="0">
                <div class="star-rating">
                    <label>Rating Anda:</label>
                    <div class="stars" data-rating="0">
                        <span data-value="1">★</span><span data-value="2">★</span><span data-value="3">★</span><span data-value="4">★</span><span data-value="5">★</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ulasan">Komentar Anda:</label>
                    <textarea name="ulasan" id="ulasan" placeholder="Bagaimana pendapat Anda tentang produk ini?"></textarea>
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label for="gambar_ulasan">Unggah Foto (Opsional):</label>
                    <input type="file" name="gambar_ulasan" id="gambar_ulasan" accept="image/png, image/jpeg, image/jpg" style="width: 100%; margin-top: 5px;">
                </div>
                <button type="submit" class="btn-pay" style="margin-top:20px;">Kirim Ulasan</button>
            </form>
        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const countdownElement = document.getElementById('countdown-timer');
    const payButton = document.getElementById('bayarSekarang');

    // Hanya jalankan timer jika memang ada waktu yang tersisa
<?php if ($bisa_bayar && $sisa_waktu_detik > 0): ?>
    let sisaWaktu = <?= $sisa_waktu_detik ?>;

    const timerInterval = setInterval(() => {
        if (sisaWaktu <= 0) {
            clearInterval(timerInterval);
            countdownElement.textContent = "Memproses...";
            if (payButton) { payButton.disabled = true; }

            // Reload halaman setelah 1.5 detik.
            // Saat halaman reload, logika PHP di atas akan berjalan dan membatalkan pesanan.
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            return;
        }

        sisaWaktu--;

        const jam = Math.floor(sisaWaktu / 3600);
        const menit = Math.floor((sisaWaktu % 3600) / 60);
        const detik = sisaWaktu % 60;

        countdownElement.textContent = `${jam.toString().padStart(2, '0')}:${menit.toString().padStart(2, '0')}:${detik.toString().padStart(2, '0')}`;
    }, 1000);
<?php endif; ?>

<?php if ($bisa_bayar && $snapToken): ?>
    if (payButton) {
        payButton.addEventListener('click', function() {
            // Hentikan timer saat popup Midtrans dibuka
            if (typeof timerInterval !== 'undefined') {
                clearInterval(timerInterval);
            }
            window.snap.pay('<?= $snapToken ?>', {
                onSuccess: function(result){
                    window.location.href = `?id=<?= $id_transaksi ?>&status=success`;
                },
                onPending: function(result){
                    window.location.href = `?id=<?= $id_transaksi ?>&status=pending`;
                },
                onError: function(result){
                    // Mengganti alert() dengan pesan di konsol atau UI kustom
                    console.error("Pembayaran gagal!", result);
                    // alert("Pembayaran gagal!"); // Hindari alert()
                    // Mungkin mulai lagi timer di sini jika diperlukan
                },
                onClose: function(){
                    // Biarkan user mencoba lagi. Timer akan terus berjalan jika belum habis.
                }
            });
        });
    }
<?php endif; ?>

    // --- LOGIKA KONFIRMASI PENERIMAAN ---
    const konfirmasiForm = document.getElementById('konfirmasi-form');
    if(konfirmasiForm) {
        konfirmasiForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Mengganti confirm() dengan UI kustom jika diperlukan
            if(confirm('Apakah Anda yakin sudah menerima semua barang dalam pesanan ini?')) { // Untuk saat ini tetap pakai confirm
                const formData = new FormData(konfirmasiForm);
                fetch('/actions/proses_konfirmasi_penerimaan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Mengganti alert() dengan pesan di konsol atau UI kustom
                    // alert(data.message); // Hindari alert()
                    console.log(data.message);
                    if(data.status === 'success') {
                        window.location.reload();
                    }
                })
                .catch(err => {
                    // Mengganti alert() dengan pesan di konsol atau UI kustom
                    console.error('Terjadi kesalahan. Silakan coba lagi.', err);
                    // alert('Terjadi kesalahan. Silakan coba lagi.'); // Hindari alert()
                });
            }
        });
    }

    // --- LOGIKA POPUP/MODAL UNTUK ULASAN ---
    const modal = document.getElementById('review-modal');
    const modalCloseBtn = modal.querySelector('.modal-close');
    const reviewForm = document.getElementById('review-form');
    const stars = modal.querySelectorAll('.stars span');

    document.querySelectorAll('.tombol-ulasan').forEach(button => {
        button.addEventListener('click', function() {
            const barangId = this.dataset.barangId;
            const namaBarang = this.dataset.namaBarang;
            const gambarBarang = this.dataset.gambarBarang;

            document.getElementById('modal-product-name').textContent = namaBarang;
            document.getElementById('modal-product-img').src = `../assets/uploads/${gambarBarang}`;
            document.getElementById('modal-barang-id').value = barangId;

            reviewForm.reset();
            document.getElementById('ulasan').value = ''; // Pastikan textarea direset
            document.getElementById('modal-rating').value = 0;
            stars.forEach(s => s.classList.remove('selected'));
            stars.forEach(s => {
                s.style.color = 'var(--gray-text)';
            });

            modal.style.display = 'flex';
        });
    });

    modalCloseBtn.addEventListener('click', () => { modal.style.display = 'none'; });
    window.addEventListener('click', (e) => { if (e.target === modal) { modal.style.display = 'none'; } });

    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const rating = this.dataset.value;
            stars.forEach(s => { s.style.color = s.dataset.value <= rating ? 'var(--star-color)' : 'var(--gray-text)'; });
        });
        star.addEventListener('mouseout', function() {
            const currentRating = document.getElementById('modal-rating').value;
            stars.forEach(s => { s.style.color = s.dataset.value <= currentRating ? 'var(--star-color)' : 'var(--gray-text)'; });
        });
        star.addEventListener('click', function() {
            const rating = this.dataset.value;
            document.getElementById('modal-rating').value = rating;
        });
    });

    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const ratingValue = document.getElementById('modal-rating').value;
        if(ratingValue == 0) {
            // Mengganti alert() dengan pesan di konsol atau UI kustom
            // alert('Harap berikan rating bintang terlebih dahulu.'); // Hindari alert()
            console.warn('Harap berikan rating bintang terlebih dahulu.');
            return;
        }

        const formData = new FormData(reviewForm);
        fetch('/actions/proses_tambah_ulasan.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Mengganti alert() dengan pesan di konsol atau UI kustom
            // alert(data.message); // Hindari alert()
            console.log(data.message);
            if(data.status === 'success') {
                modal.style.display = 'none';
                window.location.reload();
            }
        })
        .catch(err => {
            // Mengganti alert() dengan pesan di konsol atau UI kustom
            console.error('Terjadi kesalahan. Silakan coba lagi.', err);
            // alert('Terjadi kesalahan. Silakan coba lagi.'); // Hindari alert()
        });
    });
});
</script>
</body>
</html>
