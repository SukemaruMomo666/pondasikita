<?php
session_start();
include '../config/koneksi.php'; // Sesuaikan path jika perlu

// 1. PERBAIKAN: Membaca parameter 'kode_invoice' dari URL
if (!isset($_GET['kode_invoice'])) {
    die("Error: Kode invoice tidak ditemukan.");
}
$kode_invoice = mysqli_real_escape_string($koneksi, $_GET['kode_invoice']);

// 2. PERBAIKAN: Query ke tabel utama tb_transaksi
$queryPesanan = "SELECT 
                    t.*, 
                    u.username as nama_pemesan,
                    u.email,
                    u.no_telepon
                FROM tb_transaksi t
                LEFT JOIN tb_user u ON t.user_id = u.id
                WHERE t.kode_invoice = '$kode_invoice'";
$resultPesanan = mysqli_query($koneksi, $queryPesanan);

if (!$resultPesanan || mysqli_num_rows($resultPesanan) == 0) {
    die("Error: Invoice dengan kode " . htmlspecialchars($kode_invoice) . " tidak ditemukan.");
}
$pesanan = mysqli_fetch_assoc($resultPesanan);

// 3. PERBAIKAN: Ambil Detail Item dari tb_detail_transaksi
$queryDetail = "SELECT *
                FROM tb_detail_transaksi
                WHERE transaksi_id = '{$pesanan['id']}'";
$resultDetail = mysqli_query($koneksi, $queryDetail);

$detail_items = [];
if ($resultDetail) {
    while ($row = mysqli_fetch_assoc($resultDetail)) {
        $detail_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($pesanan['kode_pesanan']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
            background-color: #fff;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        .invoice-header .title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
            font-weight: bold;
        }
        .invoice-details table {
            width: 100%;
            text-align: right;
        }
        .invoice-details table td {
            padding: 2px 0;
        }
        .bill-to {
            margin-bottom: 40px;
        }
        .items-table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        .items-table th {
            background: #eee;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            padding: 8px;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .items-table .total-row td {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .notes {
            margin-top: 30px;
            font-size: 0.9em;
            color: #777;
        }
        .print-actions {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .invoice-box {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <a href="daftar_pesanan_admin.php" class="btn btn-secondary">Kembali ke Daftar Pesanan</a>
        <button onclick="window.print()" class="btn btn-primary">Cetak Invoice</button>
    </div>

    <div class="invoice-box">
        <div class="invoice-header">
            <div>
                <h3 style="font-weight:bold;">Toko Bangunan Tiga Daya</h3>
                <p>
                    Padaasih Kec. Cibogo<br>
                    Kota Subang, 41285<br>
                    Email: tigadaya@tokobangunan.com
                </p>
         
            </div>
            <div class="invoice-details">
                <div class="title">INVOICE</div>
                <table>
                    <tr>
                        <td>Kode Invoice:</td>
                        <td><strong>#<?php echo htmlspecialchars($pesanan['kode_invoice']); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Tanggal Pesanan:</td>
                        <td><?php echo date('d M Y', strtotime($pesanan['tanggal_transaksi'])); ?></td>
                    </tr>
                    <tr>
                        <td>Status Pembayaran:</td>
                        <td><?php echo htmlspecialchars(ucwords($pesanan['status_pembayaran'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bill-to">
<strong>Ditagihkan kepada:</strong><br>
<?php echo htmlspecialchars($pesanan['nama_pemesan'] ?? $pesanan['nama_pelanggan']); ?><br>
            <?php echo nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); ?><br>
            <?php echo htmlspecialchars($pesanan['no_telepon']); ?><br>
            <?php echo htmlspecialchars($pesanan['email'] ?? ''); ?>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi Produk</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detail_items)): ?>
<?php foreach ($detail_items as $item): ?>
    <tr>
        <td><?php echo htmlspecialchars($item['nama_barang_saat_transaksi']); ?></td>
        <td class="text-center"><?php echo $item['jumlah']; ?></td>
        <td class="text-end">Rp <?php echo number_format($item['harga_saat_transaksi'], 0, ',', '.'); ?></td>
        <td class="text-end">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
    </tr>
<?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Detail item tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
                
                <tr class="total-row">
                    <td colspan="3" class="text-end">Subtotal Barang</td>
                    <td class="text-end">Rp <?php echo number_format($pesanan['total_produk'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Biaya Pengiriman</td>
                    <td class="text-end">Rp <?php echo number_format($pesanan['biaya_pengiriman'], 0, ',', '.'); ?></td>
                </tr>
                <tr class="total-row" style="background-color: #f2f2f2;">
                    <td colspan="3" class="text-end"><strong>GRAND TOTAL</strong></td>
                   <td class="text-end"><strong>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            <strong>Catatan:</strong><br>
            Metode Pembayaran: <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($pesanan['metode_pembayaran']))); ?><br>
            Terima kasih telah berbelanja di Toko Bangunan Kami. Mohon simpan invoice ini sebagai bukti pembelian yang sah.
        </div>

        <div class="text-center mt-5">
            <p>Hormat Kami,</p>
            <br><br><br>
            <p>(___________________)</p>
            <p><strong>Toko Bangunan Agung Jaya</strong></p>
        </div>
    </div>
</body>
</html>