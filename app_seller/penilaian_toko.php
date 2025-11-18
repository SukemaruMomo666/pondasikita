<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'];

// Ambil data ringkasan rating
$sql_summary = "SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM tb_toko_review WHERE toko_id = ?";
$stmt_summary = $koneksi->prepare($sql_summary);
$stmt_summary->bind_param("i", $toko_id);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

// PLACEHOLDER: Data performa lain
$chat_response_rate = "95%";
$chat_response_time = "≈ 1 jam";
$cancellation_rate = "0.5%";
$late_shipment_rate = "1.2%";

// ==========================================================
// ==         PERBAIKAN FINAL QUERY SQL DI SINI            ==
// ==========================================================
$sql_reviews = "
    SELECT 
        r.id, r.rating, r.ulasan, r.balasan_penjual, r.created_at,
        u.nama as nama_user, 
        -- Menggunakan ANY_VALUE() untuk mengatasi error ONLY_FULL_GROUP_BY
        ANY_VALUE(b.nama_barang) as nama_barang, 
        ANY_VALUE(b.gambar_utama) AS gambar_barang
    FROM 
        tb_toko_review r 
    JOIN 
        tb_user u ON r.user_id = u.id 
    LEFT JOIN 
        tb_detail_transaksi dt ON r.transaksi_id = dt.transaksi_id AND r.toko_id = dt.toko_id
    LEFT JOIN 
        tb_barang b ON dt.barang_id = b.id
    WHERE 
        r.toko_id = ?
    GROUP BY
        r.id
    ORDER BY 
        r.created_at DESC
";
$stmt_reviews = $koneksi->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $toko_id);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penilaian Toko - Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css">
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Penilaian Toko</h1>
                </div>

                <div class="card performance-summary-card mb-4">
                    <div class="card-body">
                        <div class="main-rating">
                            <h4 class="card-title">Penilaian Toko</h4>
                            <h2><?= number_format($summary['avg_rating'] ?? 0, 1) ?><small>/5.0</small></h2>
                            <div class="stars">
                                <?php for($i=0; $i < 5; $i++) { echo '<i class="mdi mdi-star'.($i < round($summary['avg_rating'] ?? 0) ? '' : '-outline').'"></i>'; } ?>
                            </div>
                            <small class="text-secondary">Total <?= $summary['total_reviews'] ?? 0 ?> penilaian</small>
                        </div>
                        <div class="other-metrics">
                            <div class="metric-item">
                                <span>Tingkat Respons Chat</span><p><?= $chat_response_rate ?></p>
                                <span>Waktu Respons Chat</span><p><?= $chat_response_time ?></p>
                            </div>
                            <div class="metric-item">
                                <span>Tingkat Pembatalan</span><p><?= $cancellation_rate ?></p>
                                <span>Keterlambatan Pengiriman</span><p><?= $late_shipment_rate ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <ul class="nav filter-tabs">
                            <li class="nav-item"><a class="nav-link active" href="#">Semua</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">5 Bintang</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">4 Bintang</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">3 Bintang</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">2 Bintang</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">1 Bintang</a></li>
                        </ul>
                        <div id="review-list-container">
                             <?php if ($result_reviews->num_rows > 0): ?>
                                <?php while($review = $result_reviews->fetch_assoc()): ?>
                                    <div class="review-item">
                                        <div class="review-avatar"><i class="mdi mdi-account-circle"></i></div>
                                        <div class="review-content">
                                            <div class="review-header">
                                                <span class="user-name"><?= htmlspecialchars($review['nama_user']) ?></span>
                                                <span class="review-date"><?= date('d M Y, H:i', strtotime($review['created_at'])) ?></span>
                                            </div>
                                            <div class="review-stars">
                                                <?php for($i=0; $i < 5; $i++) { echo '<i class="mdi mdi-star'.($i < $review['rating'] ? '' : '-outline').'"></i>'; } ?>
                                            </div>
                                            <p class="review-comment"><?= nl2br(htmlspecialchars($review['ulasan'])) ?></p>
                                            
                                            <?php if(!empty($review['nama_barang'])): ?>
                                                <div class="reviewed-product">
                                                    Produk yang diulas (salah satu dari transaksi): <?= htmlspecialchars($review['nama_barang']) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($review['balasan_penjual'])): ?>
                                                <div class="seller-reply">
                                                    <p class="reply-header">Balasan Penjual</p>
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($review['balasan_penjual'])) ?></p>
                                                </div>
                                            <?php else: ?>
                                                <form action="../actions/proses_balas_ulasan.php" method="POST" class="reply-form">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <textarea name="balasan" class="form-control mb-2" rows="2" placeholder="Tulis balasan untuk pelanggan..."></textarea>
                                                    <button type="submit" class="btn btn-sm btn-primary">Balas</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center p-5 empty-state">
                                    <i class="mdi mdi-star-off" style="font-size: 3rem; color: #E5E7EB;"></i>
                                    <p class="mt-2">Belum ada penilaian untuk toko Anda.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('.filter-tabs .nav-link').on('click', function(e) {
    e.preventDefault();
    $('.filter-tabs .nav-link').removeClass('active');
    $(this).addClass('active');
});
</script>
</body>
</html>