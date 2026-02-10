<?php
session_start();
// Pastikan path ini TIDAK DIUBAH sesuai request
include '../../config/koneksi.php';

// 1. Keamanan: Cek Login
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../auth/signin.php");
    exit;
}

$id_user = $_SESSION['user']['id'];

// 2. Filter & Status Logic
$display_statuses_map = [
    'Semua' => 'Semua',
    'Menunggu Pembayaran' => 'menunggu_pembayaran',
    'Diproses' => 'diproses',
    'Selesai' => 'selesai',
    'Dibatalkan' => 'dibatalkan'
];

$current_display_filter = $_GET['status'] ?? 'Semua';
// Cari value DB berdasarkan display name, default 'Semua'
$status_filter_db = $display_statuses_map[$current_display_filter] ?? 'Semua';

// =========================================================================
// == LOGIKA OPTIMASI: AUTO-CANCEL EXPIRED TRANSACTIONS ==
// =========================================================================
// Kita lakukan pengecekan expired HANYA jika user membuka tab 'Menunggu Pembayaran' atau 'Semua'
// untuk menghemat resource database.
if ($status_filter_db === 'Semua' || $status_filter_db === 'menunggu_pembayaran') {
    // Ambil ID transaksi yang expired sekaligus
    $sql_expired = "SELECT id FROM tb_transaksi WHERE user_id = ? AND status_pembayaran = 'pending' AND payment_deadline < NOW()";
    $stmt_exp = $koneksi->prepare($sql_expired);
    $stmt_exp->bind_param("i", $id_user);
    $stmt_exp->execute();
    $res_exp = $stmt_exp->get_result();
    
    $expired_ids = [];
    while($row = $res_exp->fetch_assoc()) {
        $expired_ids[] = $row['id'];
    }
    $stmt_exp->close();

    // Eksekusi pembatalan massal jika ada data
    if (!empty($expired_ids)) {
        $koneksi->begin_transaction();
        try {
            // Buat string placeholder (?,?,?)
            $placeholders = implode(',', array_fill(0, count($expired_ids), '?'));
            $types = str_repeat('i', count($expired_ids));

            // 1. Kembalikan Stok (Query yang sedikit kompleks tapi efisien)
            // Loop tetap dibutuhkan untuk update stok per item karena logic decrement stok berbeda tiap barang
            // Tapi kita hanya loop itemnya saja, bukan transaksinya.
            $sql_items = "SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id IN ($placeholders)";
            $stmt_items = $koneksi->prepare($sql_items);
            $stmt_items->bind_param($types, ...$expired_ids);
            $stmt_items->execute();
            $res_items = $stmt_items->get_result();
            
            $stmt_update_stok = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - ? WHERE id = ?");
            while($item = $res_items->fetch_assoc()) {
                $stmt_update_stok->bind_param("ii", $item['jumlah'], $item['barang_id']);
                $stmt_update_stok->execute();
            }
            $stmt_items->close();
            $stmt_update_stok->close();

            // 2. Update Status Transaksi (Sekali query untuk semua ID)
            $sql_update_trx = "UPDATE tb_transaksi SET status_pesanan_global = 'dibatalkan', status_pembayaran = 'expired', snap_token = NULL WHERE id IN ($placeholders)";
            $stmt_update = $koneksi->prepare($sql_update_trx);
            $stmt_update->bind_param($types, ...$expired_ids);
            $stmt_update->execute();
            $stmt_update->close();

            $koneksi->commit();
        } catch (Exception $e) {
            $koneksi->rollback();
            error_log("Auto-cancel error: " . $e->getMessage());
        }
    }
}

// =========================================================================
// == PENGAMBILAN DATA TRANSAKSI ==
// =========================================================================

// Filter Query
$sql_where = "";
$params = [$id_user];
$types = "i";

if ($status_filter_db !== 'Semua') {
    $sql_where = "AND p.status_pesanan_global = ?";
    $params[] = $status_filter_db;
    $types .= "s";
}

$query_string = "
    SELECT 
        p.id AS transaksi_id, 
        p.kode_invoice, 
        p.status_pesanan_global, 
        p.status_pembayaran,
        p.total_final AS total_transaksi,
        p.tanggal_transaksi,
        p.tipe_pengambilan,
        pd.jumlah,
        pd.harga_saat_transaksi AS harga_satuan,
        b.nama_barang,
        b.gambar_utama
    FROM tb_transaksi p 
    JOIN tb_detail_transaksi pd ON p.id = pd.transaksi_id
    JOIN tb_barang b ON pd.barang_id = b.id
    WHERE p.user_id = ? $sql_where
    ORDER BY p.tanggal_transaksi DESC, p.id DESC
";

$stmt = $koneksi->prepare($query_string);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Grouping Data (Biar rapi strukturnya)
$transaksi_grouped = [];
while ($row = $result->fetch_assoc()) {
    $tid = $row['transaksi_id'];
    if (!isset($transaksi_grouped[$tid])) {
        // Tentukan Label Status untuk UI
        $display_status = ucwords(str_replace('_', ' ', $row['status_pesanan_global']));
        
        // Override label jika kondisi khusus
        if ($row['status_pembayaran'] == 'expired') $display_status = 'Kedaluwarsa';
        elseif ($row['status_pembayaran'] == 'cancelled') $display_status = 'Dibatalkan';
        elseif ($row['tipe_pengambilan'] == 'ambil_di_toko' && $row['status_pesanan_global'] == 'diproses') $display_status = 'Siap Diambil';

        $transaksi_grouped[$tid] = [
            'info' => $row, // Simpan info header transaksi
            'display_status' => $display_status,
            'items' => []
        ];
    }
    $transaksi_grouped[$tid]['items'][] = $row;
}

// Helper CSS Class
function getStatusClass($status_global, $status_bayar) {
    $sg = strtolower($status_global);
    $sb = strtolower($status_bayar);
    if ($sg == 'dibatalkan' || $sb == 'expired' || $sb == 'cancelled') return 'status-failed';
    if ($sg == 'selesai') return 'status-completed';
    if ($sg == 'diproses' || $sg == 'dikirim' || $sg == 'siap_diambil') return 'status-processing';
    if ($sg == 'menunggu_pembayaran') return 'status-pending';
    return '';
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
        <h1>Riwayat Pesanan</h1>

        <nav class="nav-tabs">
            <?php foreach($display_statuses_map as $label => $val): ?>
                <a href="?status=<?= urlencode($label) ?>" class="<?= ($current_display_filter == $label) ? 'active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($transaksi_grouped)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                <p>Belum ada pesanan di status "<?= htmlspecialchars($current_display_filter) ?>".</p>
                <a href="produk.php" class="btn btn-primary" style="margin-top:15px;">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <?php foreach ($transaksi_grouped as $id => $data): 
                $trx = $data['info'];
            ?>
                <article class="order-card">
                    <div class="order-header">
                        <span><i class="fas fa-receipt"></i> #<?= htmlspecialchars($trx['kode_invoice']) ?></span>
                        <span class="order-status <?= getStatusClass($trx['status_pesanan_global'], $trx['status_pembayaran']) ?>">
                            <?= htmlspecialchars($data['display_status']) ?>
                        </span>
                    </div>
                    
                    <div class="order-body">
                        <?php foreach ($data['items'] as $item): ?>
                            <div class="product-item">
                                <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar_utama']) ?>" 
                                     alt="Produk" class="product-image" 
                                     onerror="this.src='https://via.placeholder.com/80';">
                                
                                <div class="product-details">
                                    <h4><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                    <p><?= $item['jumlah'] ?> x Rp<?= number_format($item['harga_satuan'], 0, ',', '.') ?></p>
                                </div>
                                
                                <div class="product-price">
                                    <span>Rp<?= number_format($item['jumlah'] * $item['harga_satuan'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-footer">
                        <div class="total-price">
                            <span>Total Bayar: </span>
                            Rp<?= number_format($trx['total_transaksi'], 0, ',', '.') ?>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="detail_pesanan.php?id=<?= $id ?>" class="btn btn-outline">Lihat Detail</a>
                            
                            <?php if ($trx['status_pesanan_global'] == 'selesai'): ?>
                                <a href="produk.php" class="btn btn-primary" style="margin-left:5px;">Beli Lagi</a>
                            <?php endif; ?>
                            
                            <?php if ($trx['status_pesanan_global'] == 'menunggu_pembayaran' && $trx['status_pembayaran'] == 'pending'): ?>
                                <a href="detail_pesanan.php?id=<?= $id ?>" class="btn btn-primary" style="margin-left:5px;">Bayar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>
</html>