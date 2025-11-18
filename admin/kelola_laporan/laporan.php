<?php
include '../../config/koneksi.php';
session_start();

setlocale(LC_TIME, 'id_ID.utf8', 'id_ID');

// ==============================================================================
// 1. PENGATURAN FILTER DAN PERIODE LAPORAN
// ==============================================================================
$jenis_laporan = isset($_GET['jenis_laporan']) ? $_GET['jenis_laporan'] : 'harian';
$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$sql_where_date = "";
$info_periode = "";
$chart_group_format = 'Y-m-d';
$agregasi_penjualan = [];

switch ($jenis_laporan) {
    case 'mingguan':
        $info_periode = "4 Minggu Terakhir";
        $chart_group_format = 'Y-W';
        // Inisialisasi 4 minggu terakhir dengan nilai 0
        for ($i = 3; $i >= 0; $i--) {
            $key = date('Y-W', strtotime("-$i week"));
            $agregasi_penjualan[$key] = 0;
        }
        $tanggal_awal = date('Y-m-d', strtotime('-4 week'));
        $sql_where_date = " AND tb_transaksi.tanggal_transaksi >= '$tanggal_awal'";
        break;
    
    case 'bulanan':
        $info_periode = "12 Bulan Terakhir";
        $chart_group_format = 'Y-m';
        // Inisialisasi 12 bulan terakhir dengan nilai 0
        for ($i = 11; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i month"));
            $agregasi_penjualan[$key] = 0;
        }
        $tanggal_awal = date('Y-m-01', strtotime('-11 months'));
        $sql_where_date = " AND tb_transaksi.tanggal_transaksi >= '$tanggal_awal'";
        break;

    case 'tahunan':
        $info_periode = "Tahun " . $tahun;
        $chart_group_format = 'Y-m';
        // Inisialisasi 12 bulan dalam setahun dengan nilai 0
        for ($m = 1; $m <= 12; $m++) {
            $key = $tahun . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $agregasi_penjualan[$key] = 0;
        }
        $sql_where_date = " AND YEAR(tb_transaksi.tanggal_transaksi) = '$tahun'";
        break;

    case 'kustom':
        // Untuk kustom, kita tidak menginisialisasi karena rentangnya dinamis
        if ($tanggal_mulai && $tanggal_selesai) {
            $sql_where_date = " AND DATE(tb_transaksi.tanggal_transaksi) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'";
            $info_periode = "Periode " . date('d M Y', strtotime($tanggal_mulai)) . " - " . date('d M Y', strtotime($tanggal_selesai));
        } else {
             $info_periode = "Semua Waktu (Filter Kustom Tidak Diisi)";
        }
        break;

    case 'harian':
    default:
        $info_periode = "7 Hari Terakhir";
        $chart_group_format = 'Y-m-d';
        // Inisialisasi 7 hari terakhir dengan nilai 0
        for ($i = 6; $i >= 0; $i--) {
            $key = date('Y-m-d', strtotime("-$i days"));
            $agregasi_penjualan[$key] = 0;
        }
        $tanggal_awal = date('Y-m-d', strtotime('-6 days'));
        $sql_where_date = " AND tb_transaksi.tanggal_transaksi >= '$tanggal_awal'";
        break;
}

// ==============================================================================
// 2. QUERY UTAMA YANG DINAMIS
// ==============================================================================
$kategori_query = mysqli_query($koneksi, "SELECT * FROM tb_kategori");
// --- PERBAIKAN QUERY UTAMA ---
$query = "
    SELECT 
        tb_transaksi.id AS transaksi_id, 
        tb_transaksi.tanggal_transaksi, 
        tb_transaksi.kode_invoice,
        tb_transaksi.total_harga, -- Mengganti total_harga menjadi total_harga
        tb_barang.nama_barang, 
        tb_detail_transaksi.jumlah,
        tb_detail_transaksi.harga_saat_transaksi, -- Kita butuh harga satuan
        tb_kategori.nama_kategori
    FROM tb_transaksi
    JOIN tb_detail_transaksi ON tb_transaksi.id = tb_detail_transaksi.transaksi_id
    JOIN tb_barang ON tb_detail_transaksi.barang_id = tb_barang.id
    JOIN tb_kategori ON tb_barang.kategori_id = tb_kategori.id
    WHERE (tb_transaksi.status_pembayaran = 'Paid' OR tb_transaksi.sumber_transaksi = 'offline')" . $sql_where_date;

if ($kategori_id !== '') {
    $query .= " AND tb_barang.kategori_id = '$kategori_id'";
}
$query .= " ORDER BY tb_transaksi.tanggal_transaksi ASC";
$result = mysqli_query($koneksi, $query);

// ==============================================================================
// 3. LOGIKA PENGOLAHAN DATA YANG AKURAT DAN DINAMIS
// ==============================================================================
$rows = [];
$total_penjualan = 0;
$processed_orders = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
        $order_id = $row['transaksi_id'];

        if (!isset($processed_orders[$order_id])) {
            $total_penjualan += $row['total_harga'];
            
            $key = date($chart_group_format, strtotime($row['tanggal_transaksi']));
            
            // Pastikan kunci ada di array (terutama untuk kasus 'kustom')
            if (!isset($agregasi_penjualan[$key])) {
                $agregasi_penjualan[$key] = 0;
            }
            $agregasi_penjualan[$key] += $row['total_harga'];
            
            $processed_orders[$order_id] = true;
        }
    }
}

// ==============================================================================
// 4. PERSIAPAN DATA CHART DENGAN LABEL YANG SESUAI
// ==============================================================================
$chart_labels = [];
$chart_data = [];
ksort($agregasi_penjualan);

foreach ($agregasi_penjualan as $key => $total) {
    $label = '';
    if ($jenis_laporan == 'mingguan') {
        $tahun = substr($key, 0, 4);
        $minggu = substr($key, 5, 2);
        $dto = new DateTime();
        $dto->setISODate($tahun, $minggu);
        $tgl_mulai_minggu = $dto->format('d M');
        $dto->modify('+6 days');
        $tgl_selesai_minggu = $dto->format('d M Y');
        $label = "$tgl_mulai_minggu - $tgl_selesai_minggu";
    } else if ($jenis_laporan == 'bulanan' || $jenis_laporan == 'tahunan') {
        $label = strftime('%B %Y', strtotime($key . '-01'));
    } else { // Harian atau Kustom
        $label = strftime('%d %B %Y', strtotime($key));
    }
    $chart_labels[] = $label;
    $chart_data[] = $total;
}
$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan Dinamis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print { .no-print { display: none; } #printableArea { display: block; } body * { visibility: hidden; } #printableArea, #printableArea * { visibility: visible; } #printableArea { position: absolute; left: 0; top: 0; width: 100%; } .table { font-size: 12px; } .table-dark th { background-color: #343a40 !important; color: #fff !important; -webkit-print-color-adjust: exact; } .table-secondary { background-color: #e2e3e5 !important; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>

<div class="container-scroller">

    <?php include('partials/sidebar.php'); ?>
    
    <div class="page-body-wrapper">
        <div class="main-panel">
            
            <?php include('partials/navbar.php'); ?>

            <div class="content-wrapper">

                <div class="no-print">
                    <h2 class="mb-4">Laporan Penjualan Dinamis</h2>
                    <form method="GET" class="row g-3 mb-4 align-items-end">
                        <div class="col-md-3">
                            <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
                            <select name="jenis_laporan" id="jenis_laporan" class="form-select">
                                <option value="harian" <?= ($jenis_laporan ?? '') == 'harian' ? 'selected' : '' ?>>Harian (7 Hari Terakhir)</option>
                                <option value="mingguan" <?= ($jenis_laporan ?? '') == 'mingguan' ? 'selected' : '' ?>>Mingguan (4 Minggu Terakhir)</option>
                                <option value="bulanan" <?= ($jenis_laporan ?? '') == 'bulanan' ? 'selected' : '' ?>>Bulanan (12 Bulan Terakhir)</option>
                                <option value="tahunan" <?= ($jenis_laporan ?? '') == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                                <option value="kustom" <?= ($jenis_laporan ?? '') == 'kustom' ? 'selected' : '' ?>>Kustom</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="kategori_id" class="form-label">Kategori</label>
                            <select name="kategori_id" class="form-select">
                                <option value="">Semua Kategori</option>
                                <?php 
                                if (isset($kategori_query) && $kategori_query && mysqli_num_rows($kategori_query) > 0) { 
                                    mysqli_data_seek($kategori_query, 0); 
                                    while($row_cat = mysqli_fetch_assoc($kategori_query)) { 
                                ?>
                                    <option value="<?= $row_cat['id'] ?>" <?= (isset($kategori_id) && $kategori_id == $row_cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row_cat['nama_kategori']) ?></option>
                                <?php 
                                    }
                                } 
                                ?>
                            </select>
                        </div>
                        <div id="filter_tahunan" class="col-md-2" style="display: none;">
                            <label for="tahun" class="form-label">Pilih Tahun</label>
                            <select name="tahun" class="form-select">
                                <?php for ($i = date('Y'); $i >= date('Y') - 10; $i--): ?>
                                    <option value="<?= $i ?>" <?= (isset($tahun) && $tahun == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div id="filter_kustom" class="row g-3" style="display: none; flex: 1;">
                            <div class="col-md-auto">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai ?? '') ?>">
                            </div>
                            <div class="col-md-auto">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control" value="<?= htmlspecialchars($tanggal_selesai ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                            <button type="button" onclick="window.print()" class="btn btn-success">Cetak</button>
                        </div>
                    </form>
                    <div class="mb-5">
                        <h4>Grafik Penjualan: <?= $info_periode ?? 'Pilih Periode' ?></h4>
                        <canvas id="penjualanChart" height="100"></canvas>
                    </div>
                </div>

                <div id="printableArea">
                    <h3 class="text-center mb-4 d-none d-print-block">Laporan Penjualan</h3>
                    <p class="d-none d-print-block"><strong>Periode Laporan:</strong> <?= $info_periode ?? '' ?></p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr><th>Tanggal</th><th>Kode Pesanan</th><th>Nama Barang</th><th>Kategori</th><th>Jumlah</th><th>Total Harga Pesanan</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (isset($rows) && count($rows) > 0) { 
                                    krsort($rows); 
                                    foreach ($rows as $row) { 
                                ?>
                                    <tr>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal_transaksi'])) ?></td>
                                        <td><?= htmlspecialchars($row['kode_invoice']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                        <td><?= $row['jumlah'] ?></td>
                                        <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php 
                                    }
                                } else { 
                                    echo '<tr><td colspan="6" class="text-center">Tidak ada data penjualan pada periode ini.</td></tr>'; 
                                } 
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary fw-bold">
                                    <td colspan="5" class="text-end">Total Penjualan Keseluruhan:</td>
                                    <td>Rp <?= number_format($total_penjualan ?? 0, 0, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
         </div>
    </div>
</div> 
    <script>
        document.getElementById('jenis_laporan').addEventListener('change', function () {
            const filterTahunan = document.getElementById('filter_tahunan');
            const filterKustom = document.getElementById('filter_kustom');
            filterTahunan.style.display = 'none';
            filterKustom.style.display = 'none';
            if (this.value === 'tahunan') { filterTahunan.style.display = 'block'; }
            else if (this.value === 'kustom') { filterKustom.style.display = 'flex'; }
        });
        document.getElementById('jenis_laporan').dispatchEvent(new Event('change'));
        if (<?= count($chart_data) > 0 ?>) {
            const ctx = document.getElementById('penjualanChart').getContext('2d');
            const penjualanChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: <?= $chart_labels_json ?>, datasets: [{ label: 'Total Penjualan (Rp)', data: <?= $chart_data_json ?>, backgroundColor: 'rgba(94, 25, 20, 0.7)', borderColor: 'rgba(94, 25, 20, 1)', borderWidth: 1 }] },
                options: { scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(context) { return 'Rp ' + context.parsed.y.toLocaleString('id-ID'); } } } } }
            });
        }
    </script>
</body>
</html>