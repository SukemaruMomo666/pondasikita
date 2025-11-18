<?php
session_start();

// Proteksi halaman admin
// Pastikan role admin sudah benar di session Anda, mungkin 'role' atau 'level'
if (!isset($_SESSION['user']['id']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    header("Location: ../signin.php"); // Sesuaikan path ke file signin Anda
    exit;
}

include '../config/koneksi.php'; // Sesuaikan path ke file koneksi Anda

if (!isset($_GET['kode_invoice'])) {
    header("Location: daftar_pesanan_admin.php"); // Sesuaikan path ke daftar pesanan admin
    exit;
}

// ... (setelah include koneksi.php)

if (!isset($_GET['kode_invoice'])) {
    header("Location: daftar_pesanan_admin.php");
    exit;
}

$kode_invoice = $_GET['kode_invoice']; // Tidak perlu escape di sini

// Gunakan Prepared Statements untuk semua query
try {
    // 1. Ambil data transaksi utama dan user
    $stmtPesanan = $koneksi->prepare(
        "SELECT t.*, u.username AS nama_pemesan, u.email AS email_pemesan, u.no_telepon AS telepon_pemesan
         FROM tb_transaksi t
         LEFT JOIN tb_user u ON t.user_id = u.id
         WHERE t.kode_invoice = ?"
    );
    $stmtPesanan->bind_param("s", $kode_invoice);
    $stmtPesanan->execute();
    $resultPesanan = $stmtPesanan->get_result();

    if ($resultPesanan->num_rows === 0) {
        $pesanError = "Pesanan dengan kode #".htmlspecialchars($kode_invoice)." tidak ditemukan.";
    } else {
        $pesanan = $resultPesanan->fetch_assoc();
        $id_pesanan = $pesanan['id'];

        // 2. Ambil detail transaksi
        $stmtDetail = $koneksi->prepare("SELECT * FROM tb_detail_transaksi WHERE transaksi_id = ?");
        $stmtDetail->bind_param("i", $id_pesanan);
        $stmtDetail->execute();
        $resultDetail = $stmtDetail->get_result();
        
        // 3. Ambil data pembayaran
        $stmtPembayaran = $koneksi->prepare("SELECT * FROM tb_pembayaran WHERE pesanan_id = ? ORDER BY tanggal_konfirmasi DESC LIMIT 1");
        $stmtPembayaran->bind_param("i", $id_pesanan);
        $stmtPembayaran->execute();
        $resultPembayaran = $stmtPembayaran->get_result();
        
        $dataPembayaran = ($resultPembayaran->num_rows > 0) ? $resultPembayaran->fetch_assoc() : null;
        
        // Tutup semua statement
        $stmtPesanan->close();
        $stmtDetail->close();
        $stmtPembayaran->close();
    }
} catch (Exception $e) {
    // Catat error atau tampilkan pesan error yang aman
    $pesanError = "Terjadi kesalahan pada database. Silakan coba lagi nanti.";
    // error_log($e->getMessage()); // Opsional: catat error ke file log server
}


// Fungsi badge status
function getStatusBadge($status) {
    $statusLower = strtolower(trim($status ?? ''));
    $badgeClass = 'bg-secondary';
    $displayText = ucwords($status);

    switch ($statusLower) {
        case 'menunggu pembayaran':
            $badgeClass = 'bg-warning text-dark';
            break;
        case 'diproses':
        case 'siap diambil':
            $badgeClass = 'bg-info';
            break;
        case 'dikirim':
            $badgeClass = 'bg-primary';
            break;
        case 'selesai':
            $badgeClass = 'bg-success';
            break;
        case 'dibatalkan':
        case 'gagal': // Added 'gagal'
        case 'unpaid': // For payment status
            $badgeClass = 'bg-danger';
            break;
        case 'paid': // For payment status
            $badgeClass = 'bg-success';
            break;
    }

    return "<span class='badge {$badgeClass}'>" . htmlspecialchars($displayText) . "</span>";
}

// ENUM yang valid dari database
$opsiStatusPesanan = [
    'Menunggu Pembayaran',
    'Diproses',
    'Siap Diambil',
    'Dikirim',
    'Selesai',
    'Dibatalkan',
];

$opsiStatusPembayaran = [
    'Unpaid',
    'Paid',
    'Gagal' // Added 'Gagal' here
];
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo htmlspecialchars($kode_invoice); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .card-title { margin-bottom: 0.5rem; }
        .info-label { font-weight: 600; }
        .address-block { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .action-buttons .btn { margin-bottom: 5px; }
        .img-thumbnail-bukti { max-width: 200px; max-height: 200px; cursor: pointer; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="kelola_data/data_barang/kelola_data_barang.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAdmin" aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="daftar_pesanan_admin.php">Daftar Pesanan</a></li>
                    </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <?php if (isset($pesanError)): ?>
            <div class="alert alert-danger"><?php echo $pesanError; ?></div>
            <a href="daftar_pesanan_admin.php" class="btn btn-primary">Kembali ke Daftar Pesanan</a>
        <?php elseif (isset($pesanan)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Detail Pesanan #<?php echo htmlspecialchars($pesanan['kode_invoice']); ?></h3>
                <a href="daftar_pesanan_admin.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
            <hr>

            <?php if (isset($_SESSION['pesan'])): ?>
                <div class="alert alert-<?php echo $_SESSION['pesan']['jenis']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['pesan']['isi']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['pesan']); // Hapus pesan setelah ditampilkan ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="bi bi-file-text-fill"></i> Informasi Pesanan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <span class="info-label">Tanggal Pesan:</span> <?php echo date('d M Y, H:i', strtotime($pesanan['tanggal_transaksi'])); ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <span class="info-label">Metode Pembayaran:</span> <?php echo htmlspecialchars($pesanan['metode_pembayaran']); ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <span class="info-label">Status Pesanan:</span> <?php echo getStatusBadge($pesanan['status_pesanan']); ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <span class="info-label">Status Pembayaran:</span> <?php echo getStatusBadge($pesanan['status_pembayaran']); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3 address-block">
                                <h6 class="info-label">Alamat Pengiriman:</h6>
                                <address class="mb-0 ms-2">
                                    <?php 
                                    // Tampilkan alamat toko jika metode COD, atau alamat pengiriman jika bukan
                                    if ($pesanan['metode_pembayaran'] == 'cod') {
                                        echo "Toko Bangunan Agung Jaya<br>Jl. Raya Pagaden No. 123, Pagaden, Subang, Jawa Barat"; // Contoh alamat toko
                                    } else {
                                        echo nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); 
                                    }
                                    ?>
                                </address>
                            </div>

                            <?php if (!empty($pesanan['catatan'])): ?>
                            <div class="mb-3">
                                <h6 class="info-label">Catatan dari Pelanggan:</h6>
                                <p class="ms-2 fst-italic">"<?php echo htmlspecialchars($pesanan['catatan']); ?>"</p>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0"><i class="bi bi-person-fill"></i> Informasi Pelanggan</h5>
                        </div>
                        <div class="card-body">
                               <p><span class="info-label">Nama:</span> <?php echo htmlspecialchars($pesanan['nama_pemesan']); ?></p>
                               <p><span class="info-label">Email:</span> <a href="mailto:<?php echo htmlspecialchars($pesanan['email_pemesan']); ?>"><?php echo htmlspecialchars($pesanan['email_pemesan']); ?></a></p>
                               <p><span class="info-label">Telepon:</span> <?php echo htmlspecialchars($pesanan['telepon_pemesan'] ?? '-'); ?></p>
                               </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                               <h5 class="card-title mb-0"><i class="bi bi-cart-fill"></i> Rincian Produk</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Harga</th>
                                            <th class="text-end">Subtotal</th>
                                            </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $subtotalProdukGlobal = 0;
                                        if ($resultDetail && mysqli_num_rows($resultDetail) > 0) {
                                            while ($detail = mysqli_fetch_assoc($resultDetail)) {
                                                $harga_satuan = (float)$detail['harga_saat_transaksi'];
                                                $jumlah = (int)$detail['jumlah'];
                                                // Asumsi 'diskon' di tb_detail_pesanan adalah diskon per item.
                                                // Jika diskon hanya global (voucher), kolom ini mungkin selalu 0 atau tidak ada.
                                                // Menggunakan 'total_diskon' dari tb_pesanan untuk voucher global.
                                                $subtotal_item = $harga_satuan * $jumlah; // Diskon item tidak dihitung di sini
                                                $subtotalProdukGlobal += $subtotal_item;
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($detail['nama_barang_saat_transaksi']); ?></td>
                                                    <td class="text-center"><?php echo $jumlah; ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($subtotal_item, 0, ',', '.'); ?></td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted'>Tidak ada item produk.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                               <dl class="row mb-0">
                                       <dt class="col-7">Subtotal Produk:</dt>
                                       <dd class="col-5 text-end">Rp <?php echo number_format($subtotalProdukGlobal, 0, ',', '.'); ?></dd>

                                       <dt class="col-7">Biaya Pengiriman:</dt>
                                       <dd class="col-5 text-end">Rp <?php echo number_format($pesanan['biaya_pengiriman'], 0, ',', '.'); ?></dd>
                                       
                                       <?php if ($pesanan['total_diskon'] > 0): // Gunakan total_diskon dari tb_pesanan ?>
                                       <dt class="col-7 text-danger">Diskon Voucher (<?php echo htmlspecialchars($pesanan['voucher_digunakan']); ?>):</dt>
                                       <dd class="col-5 text-end text-danger">- Rp <?php echo number_format($pesanan['total_diskon'], 0, ',', '.'); ?></dd>
                                       <?php endif; ?>
                                       
                                       <dt class="col-7 border-top pt-2 fs-5">Grand Total:</dt>
                                       <dd class="col-5 text-end border-top pt-2 fs-5 fw-bold">
                                               Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>
                                       </dd>
                               </dl>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4" id="kelola-pesanan">
                        <div class="card-header bg-warning">
                            <h5 class="card-title mb-0"><i class="bi bi-pencil-square"></i> Kelola Pesanan</h5>
                        </div>
                        <div class="card-body action-buttons">
                            <form action="proses_update_pesanan.php" method="POST" class="mb-3">
                                <input type="hidden" name="id_transaksi" value="<?php echo $id_pesanan; ?>">
                                <input type="hidden" name="kode_invoice" value="<?php echo htmlspecialchars($kode_invoice); ?>">
                                
                                <div class="mb-2">
                                    <label for="status_pesanan" class="form-label">Ubah Status Pesanan:</label>
                                    <select name="status_pesanan" id="status_pesanan" class="form-select form-select-sm">
                                        <?php foreach ($opsiStatusPesanan as $status_opt) : ?>
                                            <option value="<?php echo $status_opt; ?>" <?php echo ($pesanan['status_pesanan'] == $status_opt) ? 'selected' : ''; ?>>
                                                <?php echo $status_opt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="status_pembayaran" class="form-label">Ubah Status Pembayaran:</label>
                                    <select name="status_pembayaran" id="status_pembayaran" class="form-select form-select-sm">
                                        <?php foreach ($opsiStatusPembayaran as $status_p_opt) : ?>
                                            <option value="<?php echo $status_p_opt; ?>" <?php echo ($pesanan['status_pembayaran'] == $status_p_opt) ? 'selected' : ''; ?>>
                                                <?php echo $status_p_opt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100"><i class="bi bi-check-circle"></i> Simpan Perubahan</button>
                            </form>
                            <hr>

                            <?php if ($pesanan['metode_pembayaran'] !== 'cod' && ($pesanan['status_pesanan'] == 'Diproses' || $pesanan['status_pesanan'] == 'Menunggu Pembayaran')) : ?>
                                <form action="proses_update_pesanan.php" method="POST" class="mb-2">
                                    <input type="hidden" name="id_transaksi" value="<?php echo $id_pesanan; ?>">
                                    <input type="hidden" name="kode_invoice" value="<?php echo htmlspecialchars($kode_invoice); ?>">
                                    <input type="hidden" name="action" value="tandai_dikirim">
                                    <button type="submit" class="btn btn-info btn-sm w-100" onclick="return confirm('Yakin ingin menandai pesanan ini sudah dikirim?');">
                                        <i class="bi bi-truck"></i> Tandai Pesanan Dikirim
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($pesanan['metode_pembayaran'] == 'cod' && $pesanan['status_pesanan'] == 'Siap Diambil'): ?>
                                <form action="proses_update_pesanan.php" method="POST" class="mb-2">
                                    <input type="hidden" name="id_transaksi" value="<?php echo $id_pesanan; ?>">
                                    <input type="hidden" name="kode_invoice" value="<?php echo htmlspecialchars($kode_invoice); ?>">
                                    <input type="hidden" name="action" value="konfirmasi_diambil">
                                    <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Yakin ingin mengkonfirmasi pesanan ini sudah diambil oleh pelanggan? Status akan berubah menjadi Selesai.');">
                                        <i class="bi bi-box-seam"></i> Konfirmasi Pesanan Diambil (COD)
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($dataPembayaran && $pesanan['status_pembayaran'] == 'Unpaid'): ?>
                                <button type="button" class="btn btn-success btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalBuktiPembayaran">
                                    <i class="bi bi-patch-check-fill"></i> Lihat & Verifikasi Bukti Bayar
                                </button>
                            <?php elseif ($dataPembayaran && $pesanan['status_pembayaran'] == 'Paid'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalBuktiPembayaran">
                                    <i class="bi bi-eye-fill"></i> Lihat Bukti Bayar (Terverifikasi)
                                </button>
                            <?php elseif ($dataPembayaran && $pesanan['status_pembayaran'] == 'Gagal'): // Display 'Gagal' button
                                // You might want to provide an option to re-verify or manually set to Unpaid/Paid
                                ?>
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalBuktiPembayaran">
                                    <i class="bi bi-x-circle-fill"></i> Bukti Bayar (Gagal)
                                </button>
                            <?php else: ?>
                                <span class="d-block text-muted text-center">Tidak ada bukti pembayaran yang perlu diverifikasi untuk metode ini.</span>
                            <?php endif; ?>

                            <a href="cetak_invoice_admin.php?kode_invoice=<?php echo $kode_invoice; ?>" target="_blank" class="btn btn-info btn-sm w-100"><i class="bi bi-printer-fill"></i> Cetak Invoice</a>
                        </div>
                    </div>

                </div>
            </div>

            <?php if ($dataPembayaran && $pesanan['metode_pembayaran'] == 'transfer'): ?>
            <div class="modal fade" id="modalBuktiPembayaran" tabindex="-1" aria-labelledby="modalBuktiPembayaranLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalBuktiPembayaranLabel">Bukti Pembayaran - Pesanan #<?php echo htmlspecialchars($kode_invoice); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><span class="info-label">Tanggal Konfirmasi:</span> <?php echo date('d M Y, H:i', strtotime($dataPembayaran['tanggal_konfirmasi'])); ?></p>
                                    <p><span class="info-label">Bank Pengirim:</span> <?php echo htmlspecialchars($dataPembayaran['bank_pengirim']); ?></p>
                                    <p><span class="info-label">Nomor Rekening:</span> <?php echo htmlspecialchars($dataPembayaran['nomor_rekening_pengirim']); ?></p>
                                    <p><span class="info-label">Atas Nama:</span> <?php echo htmlspecialchars($dataPembayaran['nama_rekening_pengirim']); ?></p>
                                    <p><span class="info-label">Jumlah Transfer:</span> Rp <?php echo number_format($dataPembayaran['jumlah_transfer'], 0, ',', '.'); ?></p>
                                    <?php if(!empty($dataPembayaran['catatan_pembayaran'])): ?>
                                    <p><span class="info-label">Catatan:</span> <?php echo htmlspecialchars($dataPembayaran['catatan_pembayaran']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-center">
                                    <h6>Bukti Transfer:</h6>
                                    <?php if (!empty($dataPembayaran['bukti_transfer']) && file_exists('../uploads/bukti_pembayaran/' . $dataPembayaran['bukti_transfer'])): ?>
                                        <a href="../uploads/bukti_pembayaran/<?php echo htmlspecialchars($dataPembayaran['bukti_transfer']); ?>" target="_blank">
                                            <img src="../uploads/bukti_pembayaran/<?php echo htmlspecialchars($dataPembayaran['bukti_transfer']); ?>" alt="Bukti Transfer" class="img-fluid img-thumbnail img-thumbnail-bukti">
                                        </a>
                                        <small class="d-block">Klik gambar untuk memperbesar</small>
                                    <?php else: ?>
                                        <p class="text-muted">Bukti transfer tidak tersedia atau tidak ditemukan.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <?php if ($pesanan['status_pembayaran'] == 'Unpaid'): ?>
                            <form action="proses_update_pesanan.php" method="POST" style="display: inline-block;">
                                <input type="hidden" name="id_transaksi" value="<?php echo $id_pesanan; ?>">
                                <input type="hidden" name="kode_invoice" value="<?php echo htmlspecialchars($kode_invoice); ?>">
                                <input type="hidden" name="verifikasi_pembayaran" value="terima">
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Terima Pembayaran</button>
                            </form>
                            <form action="proses_update_pesanan.php" method="POST" style="display: inline-block;">
                                <input type="hidden" name="id_transaksi" value="<?php echo $id_pesanan; ?>">
                                <input type="hidden" name="kode_invoice" value="<?php echo htmlspecialchars($kode_invoice); ?>">
                                <input type="hidden" name="verifikasi_pembayaran" value="tolak">
                                <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg"></i> Tolak Pembayaran</button>
                            </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>


        <?php endif; // End if (isset($pesanan)) ?>
    </div>

    <footer class="py-4 bg-light mt-auto"> <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">Hak Cipta © Toko Anda <?php echo date("Y"); ?></div>
            </div>
        </div>
    </footer>
</body>
</html>