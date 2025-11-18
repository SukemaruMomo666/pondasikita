<?php
session_start();
include '../../config/koneksi.php';

// Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../auth/signin.php");
    exit;
}

$id_user = $_SESSION['user']['id'];

// Menyesuaikan filter agar cocok dengan nilai ENUM di database `status_pesanan_global`
// ENUM Anda: 'menunggu_pembayaran', 'diproses', 'selesai', 'dibatalkan'
$statuses_db = ['menunggu_pembayaran', 'diproses', 'selesai', 'dibatalkan'];

// Untuk tampilan, kita bisa punya label yang lebih user-friendly
$display_statuses_map = [
    'Semua' => 'Semua',
    'menunggu_pembayaran' => 'Menunggu Pembayaran',
    'diproses' => 'Diproses',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan'
];

// Dapatkan status filter dari URL. Default ke 'Semua' jika tidak ada atau tidak valid.
$status_filter_raw = $_GET['status'] ?? 'Semua';

// Konversi status filter dari display name ke DB value, jika perlu.
// Contoh: 'Menunggu Pembayaran' (display) -> 'menunggu_pembayaran' (DB)
$status_filter_db = array_search($status_filter_raw, $display_statuses_map);
if ($status_filter_db === false) { // Jika display name tidak ditemukan, gunakan raw value jika valid
    if (in_array($status_filter_raw, $statuses_db)) {
        $status_filter_db = $status_filter_raw;
    } else {
        $status_filter_db = 'Semua'; // Fallback jika raw value juga tidak valid
    }
}
// Untuk menentukan kelas 'active' pada nav-tabs, kita bandingkan dengan display_name dari URL
$current_display_filter = $status_filter_raw;

// =========================================================================
// == LOGIKA BARU: Cek & Batalkan Semua Transaksi yang Kedaluwarsa Milik User Ini ==
// =========================================================================
$query_cek_expired = "SELECT id FROM tb_transaksi WHERE user_id = ? AND status_pembayaran = 'pending' AND payment_deadline < NOW()";
$stmt_cek_expired = $koneksi->prepare($query_cek_expired);
if ($stmt_cek_expired) {
    $stmt_cek_expired->bind_param("i", $id_user);
    $stmt_cek_expired->execute();
    $result_expired = $stmt_cek_expired->get_result();

    while ($expired_row = $result_expired->fetch_assoc()) {
        $id_transaksi_expired = $expired_row['id'];
        $koneksi->begin_transaction();
        try {
            // 1. Ambil detail item & kembalikan stok
            $stmt_items_cancel = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = ?");
            if ($stmt_items_cancel) {
                $stmt_items_cancel->bind_param("i", $id_transaksi_expired);
                $stmt_items_cancel->execute();
                $result_items_cancel = $stmt_items_cancel->get_result();
                $stmt_stok_cancel = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
                if ($stmt_stok_cancel) {
                    while($item = $result_items_cancel->fetch_assoc()) {
                        $stmt_stok_cancel->bind_param("ii", $item['jumlah'], $item['barang_id']);
                        $stmt_stok_cancel->execute();
                    }
                    $stmt_stok_cancel->close();
                } else {
                    throw new Exception("Failed to prepare stock update statement: " . $koneksi->error);
                }
                $stmt_items_cancel->close();
            } else {
                throw new Exception("Failed to prepare detail items statement: " . $koneksi->error);
            }

            // 2. Update status transaksi menjadi Expired
            // Mengubah status_pesanan_global ke 'dibatalkan' dan status_pembayaran ke 'expired'
            $stmt_batal_auto = $koneksi->prepare("UPDATE tb_transaksi SET status_pesanan_global = 'dibatalkan', status_pembayaran = 'expired', snap_token = NULL WHERE id = ?");
            if ($stmt_batal_auto) {
                $stmt_batal_auto->bind_param("i", $id_transaksi_expired);
                $stmt_batal_auto->execute();
                $stmt_batal_auto->close();
            } else {
                throw new Exception("Failed to prepare auto cancel statement: " . $koneksi->error);
            }

            $koneksi->commit();
        } catch (Exception $e) {
            $koneksi->rollback();
            error_log("Gagal membatalkan transaksi otomatis ID " . $id_transaksi_expired . ": " . $e->getMessage());
        }
    }
    $stmt_cek_expired->close();
} else {
    error_log("Failed to prepare expired transaction check statement: " . $koneksi->error);
}
// =========================================================================
// =================== AKHIR DARI BLOK BARU ================================
// =========================================================================

$sql_where = '';
if ($status_filter_db !== 'Semua') { // Menggunakan $status_filter_db untuk query
    $sql_where = "AND p.status_pesanan_global = ?"; // Mengacu pada status_pesanan_global
}

$query_string = "
    SELECT 
        p.id AS transaksi_id, 
        p.kode_invoice, 
        p.status_pesanan_global, -- Menggunakan status_pesanan_global
        p.status_pembayaran,
        p.payment_deadline,
        p.total_final AS total_transaksi, -- Menggunakan total_final
        p.tanggal_transaksi, 
        p.metode_pembayaran,
        p.tipe_pengambilan,
        pd.jumlah,
        pd.harga_saat_transaksi AS harga_satuan,
        b.nama_barang,
        b.gambar_utama -- Menggunakan gambar_utama dari tb_barang
    FROM tb_transaksi p 
    JOIN tb_detail_transaksi pd ON p.id = pd.transaksi_id
    JOIN tb_barang b ON pd.barang_id = b.id
    WHERE p.user_id = ? $sql_where
    ORDER BY p.tanggal_transaksi DESC, p.id DESC
";

$stmt = $koneksi->prepare($query_string);
if (!$stmt) {
    die("Database error: Gagal menyiapkan query pesanan: " . $koneksi->error);
}

if ($status_filter_db !== 'Semua') {
    $stmt->bind_param("is", $id_user, $status_filter_db); // Menggunakan $status_filter_db
} else {
    $stmt->bind_param("i", $id_user);
}
$stmt->execute();
$result = $stmt->get_result();

// --- Fungsi Helper untuk class CSS status ---
// Input: $status_pesanan_global dari DB, $status_pembayaran dari DB
function getStatusClass($status_pesanan_global, $status_pembayaran = '') {
    $status_global_lower = strtolower($status_pesanan_global);
    $status_pembayaran_lower = strtolower($status_pembayaran);

    if ($status_global_lower == 'dibatalkan' || $status_pembayaran_lower == 'expired' || $status_pembayaran_lower == 'cancelled') {
        return 'status-failed';
    }
    
    switch ($status_global_lower) {
        case 'selesai':
            return 'status-completed';
        case 'diproses':
            return 'status-processing';
        case 'menunggu_pembayaran':
            return 'status-pending';
        default:
            return ''; // Untuk status lain yang tidak terdefinisi
    }
}

// Mengelompokkan hasil query berdasarkan ID Transaksi
$transaksi_grouped = [];
while ($row = $result->fetch_assoc()) {
    $transaksi_id = $row['transaksi_id'];
    if (!isset($transaksi_grouped[$transaksi_id])) {
        
        // Menentukan status yang akan ditampilkan
        $display_status_text = ucwords(str_replace('_', ' ', $row['status_pesanan_global'])); // Default display text

        // Override display text berdasarkan status_pembayaran khusus
        if ($row['status_pembayaran'] == 'expired') {
            $display_status_text = 'Pembayaran Kedaluwarsa';
        } elseif ($row['status_pembayaran'] == 'cancelled') {
            $display_status_text = 'Dibatalkan oleh Anda';
        } 
        // Tambahan untuk pickup yang sudah dibayar tapi masih diproses
        elseif ($row['tipe_pengambilan'] == 'ambil_di_toko' && $row['status_pembayaran'] == 'paid' && $row['status_pesanan_global'] == 'diproses') {
            $display_status_text = 'Siap Diambil';
        } 
        // Tambahan untuk pengiriman yang sudah dibayar tapi masih diproses
        elseif ($row['tipe_pengambilan'] == 'pengiriman' && $row['status_pembayaran'] == 'paid' && $row['status_pesanan_global'] == 'diproses') {
            $display_status_text = 'Sedang Diproses'; // Atau 'Diproses Penjual'
        }
        
        // Di sini kita tidak perlu lagi 'Siap Diambil' dan 'Dikirim' di $statuses_db
        // karena itu adalah tahapan pesanan, bukan status global utama.
        // Status global: menunggu_pembayaran, diproses, selesai, dibatalkan.
        // Detail per item bisa memiliki status lebih rinci di tb_detail_transaksi.


        $transaksi_grouped[$transaksi_id] = [
            'kode_invoice' => $row['kode_invoice'],
            'status_pesanan_global_db' => $row['status_pesanan_global'], // Simpan status DB asli
            'status_pembayaran' => $row['status_pembayaran'],
            'display_status' => $display_status_text, // Status untuk ditampilkan di UI
            'tipe_pengambilan' => $row['tipe_pengambilan'],
            'total_transaksi' => $row['total_transaksi'],
            'tanggal_transaksi' => $row['tanggal_transaksi'],
            'items' => []
        ];
    }
    
    $transaksi_grouped[$transaksi_id]['items'][] = [
        'nama_barang' => $row['nama_barang'],
        'gambar_barang' => $row['gambar_utama'], // Pastikan ini 'gambar_utama'
        'jumlah' => $row['jumlah'],
        'harga_satuan' => $row['harga_satuan']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan Saya</title>
    <link rel="stylesheet" href="../../assets/css/navbar_style.css">
    <link rel="stylesheet" href="../../assets/css/pesanan_customer.css">
    <link href="../assets/css/navbar.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include('partials/navbar.php'); ?>

    <div class="container">
        <h1>Riwayat Pesanan Saya</h1>

        <nav class="nav-tabs">
            <?php foreach($display_statuses_map as $db_val => $display_name): ?>
                <a href="?status=<?= urlencode($display_name) ?>" class="<?= ($current_display_filter == $display_name) ? 'active' : '' ?>">
                    <?= htmlspecialchars($display_name) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($transaksi_grouped)): ?>
            <div class="empty-state">
                <p>Anda belum memiliki pesanan dengan status "<?= htmlspecialchars($current_display_filter) ?>".</p>
            </div>
        <?php else: ?>
            <?php foreach ($transaksi_grouped as $id_transaksi => $transaksi): ?>
                <article class="order-card">
                    <div class="order-header">
                        <span>Transaksi: #<?= htmlspecialchars($transaksi['kode_invoice']) ?></span>
                        <span class="order-status <?= getStatusClass($transaksi['status_pesanan_global_db'], $transaksi['status_pembayaran']) ?>">
                            <?= htmlspecialchars($transaksi['display_status']) ?>
                        </span>
                    </div>
                    <div class="order-body">
                        <?php foreach ($transaksi['items'] as $item): ?>
                            <div class="product-item">
                                <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar_barang']) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>" class="product-image" onerror="this.src='https://via.placeholder.com/80';">
                                <div class="product-details">
                                    <h4><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                    <p>x<?= htmlspecialchars($item['jumlah']) ?></p>
                                </div>
                                <div class="product-price">
                                    <span>Rp<?= number_format($item['harga_satuan'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-footer">
                        <div class="total-price">
                            <span>Total Pesanan: </span>
                            Rp<?= number_format($transaksi['total_transaksi'], 0, ',', '.') ?>
                        </div>
                        <a href="detail_pesanan.php?id=<?= $id_transaksi ?>" class="btn btn-outline">Lihat Detail</a>
                        <?php if ($transaksi['status_pesanan_global_db'] == 'selesai'): ?>
                            <a href="#" class="btn btn-primary" style="margin-left:10px;">Beli Lagi</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>
</html>