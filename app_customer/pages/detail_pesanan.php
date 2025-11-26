<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. SET TIMEZONE (PENTING BIAR TIMER GAK NGACIO)
date_default_timezone_set('Asia/Jakarta'); 

// [PENTING] Jalur koneksi
include '../../config/koneksi.php';

// --- BAGIAN 1: VALIDASI & KEAMANAN ---
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../auth/signin.php");
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div style='text-align:center; margin-top:50px;'><h3>Akses Ditolak</h3><p>ID Pesanan tidak valid.</p><a href='../../index.php'>Kembali</a></div>");
}

$id_user_login = (int)$_SESSION['user']['id'];
$id_transaksi = (int)$_GET['id'];

// --- BAGIAN 2: PENGAMBILAN DATA ---
$query_transaksi_string = "SELECT p.*, u.username, u.email, u.no_telepon
                            FROM tb_transaksi p
                            JOIN tb_user u ON p.user_id = u.id
                            WHERE p.id = ? AND p.user_id = ?";
$stmt_transaksi = $koneksi->prepare($query_transaksi_string);
$stmt_transaksi->bind_param("ii", $id_transaksi, $id_user_login);
$stmt_transaksi->execute();
$result_transaksi = $stmt_transaksi->get_result();

if ($result_transaksi->num_rows === 0) {
    die("<div style='text-align:center; margin-top:50px;'><h3>Data Tidak Ditemukan</h3><p>Transaksi tidak ada.</p><a href='../../index.php'>Kembali</a></div>");
}
$transaksi = $result_transaksi->fetch_assoc();

// Tipe Pengambilan
$tipe_pengambilan_transaksi = 'pengiriman'; 
if (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'siap_diambil') {
    $tipe_pengambilan_transaksi = 'ambil_di_toko';
} elseif (strpos(strtoupper($transaksi['shipping_label_alamat'] ?? ''), 'AMBIL DI TOKO') !== false) {
     $tipe_pengambilan_transaksi = 'ambil_di_toko';
}

// Ambil Item
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

// Cek Ulasan
$reviewed_items_ids = [];
if (isset($transaksi['status_pesanan_global']) && strtolower($transaksi['status_pesanan_global']) == 'selesai') {
    $query_ulasan = $koneksi->prepare("SELECT barang_id FROM tb_review_produk WHERE transaksi_id = ? AND user_id = ?");
    $query_ulasan->bind_param("ii", $id_transaksi, $id_user_login);
    $query_ulasan->execute();
    $result_ulasan = $query_ulasan->get_result();
    while ($row_ulasan = $result_ulasan->fetch_assoc()) {
        $reviewed_items_ids[] = (int)$row_ulasan['barang_id'];
    }
}

// --- LOGIKA AUTO CANCEL (SERVER SIDE) ---
if (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'pending' && !empty($transaksi['payment_deadline'])) {
    $deadline_check = new DateTime($transaksi['payment_deadline']);
    $now_check = new DateTime(); // Ini sekarang ikut Asia/Jakarta

    if ($now_check > $deadline_check) {
        // Logic batalin transaksi...
        $koneksi->begin_transaction();
        try {
            // Restore Stok
            $stmt_items_cancel = $koneksi->prepare("SELECT barang_id, jumlah FROM tb_detail_transaksi WHERE transaksi_id = ?");
            $stmt_items_cancel->bind_param("i", $id_transaksi);
            $stmt_items_cancel->execute();
            $result_items_cancel = $stmt_items_cancel->get_result();
            while ($item = $result_items_cancel->fetch_assoc()) {
                $koneksi->query("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan - {$item['jumlah']} WHERE id = {$item['barang_id']}");
            }
            
            // Restore Voucher
            if (!empty($transaksi['voucher_digunakan'])) {
                $stmt_v = $koneksi->prepare("UPDATE vouchers SET kuota_terpakai = kuota_terpakai - 1 WHERE kode_voucher = ?");
                $stmt_v->bind_param("s", $transaksi['voucher_digunakan']);
                $stmt_v->execute();
            }

            // Update Status
            $koneksi->query("UPDATE tb_transaksi SET status_pesanan_global = 'dibatalkan', status_pembayaran = 'expired', snap_token = NULL WHERE id = $id_transaksi");
            
            $koneksi->commit();
            header("Location: detail_pesanan.php?id=" . $id_transaksi);
            exit();
        } catch (Exception $e) {
            $koneksi->rollback();
        }
    }
}

// --- LOGIKA STATUS TAMPILAN ---
$sisa_waktu_detik = 0;
if (isset($transaksi['status_pembayaran']) && strtolower($transaksi['status_pembayaran']) == 'pending' && !empty($transaksi['payment_deadline'])) {
    try {
        $deadline = new DateTime($transaksi['payment_deadline']);
        $now = new DateTime();
        if ($deadline > $now) {
            $sisa_waktu_detik = $deadline->getTimestamp() - $now->getTimestamp();
        }
    } catch (Exception $e) {}
}

$is_waktu_habis = (strtolower($transaksi['status_pembayaran'] ?? '') == 'pending' && $sisa_waktu_detik <= 0);
$bisa_bayar = (strtolower($transaksi['status_pembayaran'] ?? '') == 'pending' && !$is_waktu_habis);
$is_dibatalkan = (strtolower($transaksi['status_pesanan_global'] ?? '') == 'dibatalkan' || strtolower($transaksi['status_pembayaran'] ?? '') == 'cancelled');
$is_selesai = (strtolower($transaksi['status_pesanan_global'] ?? '') == 'selesai');
$snapToken = $transaksi['snap_token'] ?? null;

// Status Tracker Logic
$statuses_delivery = ['menunggu_pembayaran', 'diproses', 'dikirim', 'selesai'];
$statuses_pickup   = ['menunggu_pembayaran', 'diproses', 'siap_diambil', 'selesai'];
$current_statuses = ($tipe_pengambilan_transaksi == 'ambil_di_toko') ? $statuses_pickup : $statuses_delivery;

if ($is_dibatalkan) {
    $current_statuses = ['menunggu_pembayaran', 'dibatalkan'];
    $current_status_index = 1;
} else {
    $current_status_index = array_search(strtolower($transaksi['status_pesanan_global'] ?? ''), $current_statuses);
    if ($current_status_index === false) $current_status_index = 0; 
}

// Display Alamat
$display_alamat_label = "Alamat Pengiriman:";
$display_alamat_value = nl2br(htmlspecialchars($transaksi['shipping_alamat_lengkap'] ?? ''));
if ($tipe_pengambilan_transaksi == 'ambil_di_toko') {
    $display_alamat_label = "Lokasi Pengambilan:";
    $display_alamat_value = "<strong>Toko PondasiKita Pusat</strong><br>Jl. Raya Pagaden No. 123, Subang, Jawa Barat";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi #<?= htmlspecialchars($transaksi['kode_invoice'] ?? '') ?></title>
    
    <link rel="stylesheet" href="/assets/css/navbar_style.css"> 
    <link rel="stylesheet" href="/assets/css/css_detailPesanan.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-ejWh_qCa9cClgKRm"></script>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container">

    <?php if (isset($_GET['cancel_success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Pesanan berhasil dibatalkan.</div>
    <?php endif; ?>
    <?php if (isset($_GET['pickup_success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Pembayaran berhasil! Pesanan sedang diproses.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>Detail Pesanan</h2>
            <span>#<?= htmlspecialchars($transaksi['kode_invoice'] ?? '-') ?></span>
        </div>
        <div class="card-body">
            <?php if ($is_dibatalkan): ?>
                <div class="alert alert-danger"><strong><i class="fas fa-ban"></i> Transaksi Dibatalkan</strong></div>
            <?php elseif ($is_waktu_habis || (strtolower($transaksi['status_pembayaran'] ?? '') == 'expired')): ?>
                <div class="alert alert-danger"><strong><i class="fas fa-clock"></i> Waktu Pembayaran Habis</strong></div>
            <?php else: ?>
                <div class="status-tracker">
                    <?php 
                        $step_count = count($current_statuses);
                        $progress_width = ($current_status_index >= 0 && $step_count > 1) ? ($current_status_index / ($step_count - 1)) * 100 : 0;
                    ?>
                    <div class="line" style="width: <?= $progress_width ?>%;"></div>
                    <?php foreach ($current_statuses as $idx => $status): ?>
                        <div class="status-step <?= ($idx <= $current_status_index) ? 'completed' : '' ?> <?= ($idx == $current_status_index) ? 'active' : '' ?>">
                            <div class="icon">
                                <?php if($idx < $current_status_index): ?><i class="fas fa-check"></i>
                                <?php elseif($idx == $current_status_index): ?><i class="fas fa-circle-notch fa-spin"></i>
                                <?php else: ?><i class="fas fa-circle" style="font-size: 8px;"></i><?php endif; ?>
                            </div>
                            <div class="label"><?= ucwords(str_replace('_', ' ', $status)) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($bisa_bayar && $snapToken): ?>
    <div class="card" style="text-align: center;">
        <div class="card-body">
            <h4><i class="fas fa-hourglass-half"></i> Sisa Waktu Pembayaran</h4>
            <div id="countdown-timer" class="timer-box">--:--:--</div>
            
            <div style="margin-top: 20px;">
                <button id="bayarSekarang" class="btn btn-pay">
                    <i class="fas fa-wallet"></i> Bayar Sekarang
                </button>
                
                <form action="/actions/proses_batal_pesanan.php" method="POST" onsubmit="return confirm('Yakin batalkan pesanan?');" style="width: 100%;">
                    <input type="hidden" name="transaksi_id" value="<?= $id_transaksi ?>">
                    <button type="submit" class="btn btn-cancel">Batalkan Pesanan</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (strtolower($transaksi['status_pesanan_global'] ?? '') == 'dikirim'): ?>
    <div class="card" style="text-align: center;">
        <div class="card-body">
            <h4><i class="fas fa-shipping-fast"></i> Pesanan Sedang Dikirim</h4>
            <p>Klik tombol di bawah jika barang sudah Anda terima dengan baik.</p>
            <form id="konfirmasi-form">
                <input type="hidden" name="transaksi_id" value="<?= $id_transaksi ?>">
                <button type="submit" class="btn btn-pay" style="width: auto;">
                    <i class="fas fa-check-double"></i> Pesanan Diterima
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div class="card">
            <div class="card-body">
                <h4><i class="fas fa-map-marker-alt"></i> Info Pengiriman</h4>
                <p><strong><?= htmlspecialchars($transaksi['shipping_nama_penerima'] ?? '-') ?></strong></p>
                <p style="color: var(--text-grey);"><?= htmlspecialchars($transaksi['shipping_telepon_penerima'] ?? '-') ?></p>
                <hr style="border:0; border-top:1px dashed #eee; margin: 15px 0;">
                <p style="line-height: 1.6;"><?= $display_alamat_value ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4><i class="fas fa-shopping-bag"></i> Rincian Belanja</h4>
                <div style="margin-bottom: 20px;">
                    <?php foreach ($items_pesanan as $item): ?>
                    <div class="product-item">
                        <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar_utama'] ?? 'default.jpg') ?>" class="product-image">
                        <div class="product-details">
                            <h5><?= htmlspecialchars($item['nama_barang'] ?? '-') ?></h5>
                            <p><?= $item['jumlah'] ?> x Rp<?= number_format($item['harga_satuan'],0,',','.') ?></p>
                        </div>
                        <div class="product-price">
                            Rp<?= number_format($item['jumlah'] * $item['harga_satuan'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="payment-summary">
                    <div class="row"><span>Subtotal</span><span>Rp<?= number_format($transaksi['total_harga_produk'] ?? 0, 0, ',', '.') ?></span></div>
                    <div class="row"><span>Ongkos Kirim</span><span>Rp<?= number_format($transaksi['biaya_pengiriman'] ?? 0, 0, ',', '.') ?></span></div>
                    <div class="total-row"><span>Total Bayar</span><span>Rp<?= number_format($transaksi['total_final'] ?? 0, 0, ',', '.') ?></span></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. LOGIKA AUTO-CHECK STATUS
    // Cek setiap 3 detik biar lebih responsif
    <?php if ($bisa_bayar): ?>
    const checkInterval = setInterval(() => {
        fetch('/actions/cek_status_transaksi.php?id=<?= $id_transaksi ?>')
            .then(response => response.json())
            .then(data => {
                if (data.status_bayar === 'paid' || data.status_bayar === 'success' || data.status_global === 'diproses') {
                    clearInterval(checkInterval);
                    window.location.reload();
                } else if (data.status_bayar === 'failed' || data.status_bayar === 'expired') {
                    window.location.reload();
                }
            })
            .catch(err => console.log("Waiting for payment..."));
    }, 3000);
    <?php endif; ?>

    // 2. TIMER COUNTDOWN (Anti Crash)
    <?php if ($bisa_bayar && $sisa_waktu_detik > 0): ?>
    let timeLeft = <?= $sisa_waktu_detik ?>;
    const timerEl = document.getElementById('countdown-timer');
    
    if (timerEl) { // Cek dulu elemennya ada gak
        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerEl.innerHTML = "Waktu Habis";
                setTimeout(() => window.location.reload(), 2000);
                return;
            }
            
            timeLeft--;
            const h = Math.floor(timeLeft / 3600);
            const m = Math.floor((timeLeft % 3600) / 60);
            const s = timeLeft % 60;
            
            timerEl.textContent = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        }, 1000);
    }
    <?php endif; ?>

    // 3. MIDTRANS PAY BUTTON (Anti Crash)
    <?php if ($bisa_bayar && $snapToken): ?>
    const payButton = document.getElementById('bayarSekarang');
    if (payButton && window.snap) { // Cek dulu window.snap ada gak
        payButton.addEventListener('click', function () {
            window.snap.pay('<?= $snapToken ?>', {
                onSuccess: function(result){ 
                    setTimeout(() => window.location.reload(), 1000);
                },
                onPending: function(result){ window.location.reload(); },
                onError: function(result){ alert("Pembayaran gagal!"); }
            });
        });
    } else if(payButton && !window.snap) {
        console.error("Midtrans Snap JS belum terload sempurna. Cek koneksi internet atau Client Key.");
    }
    <?php endif; ?>

});
</script>

</body>
</html>