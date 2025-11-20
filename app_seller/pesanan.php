<?php
session_start(); // HANYA Panggil SEKALI di awal file
require_once '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Keamanan & ambil data toko
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); 
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt_toko = $koneksi->prepare("SELECT id FROM tb_toko WHERE user_id = ?");
$stmt_toko->bind_param("i", $user_id);
$stmt_toko->execute();
$toko_id = $stmt_toko->get_result()->fetch_assoc()['id'] ?? null;
$stmt_toko->close();

if (!$toko_id) {
    die("Data toko tidak ditemukan untuk pengguna ini. Silakan hubungi admin.");
}

// Query untuk mengambil semua item pesanan untuk toko ini
// PERUBAHAN: Mengurutkan berdasarkan status agar yang 'siap_kirim' muncul di atas
$sql = "SELECT d.id, d.jumlah, d.subtotal, d.status_pesanan_item, 
               t.kode_invoice, t.tanggal_transaksi,
               b.nama_barang, b.gambar_utama, -- Pastikan ini sesuai dengan nama kolom di DB Anda
               u.nama as nama_pelanggan
        FROM tb_detail_transaksi d
        JOIN tb_transaksi t ON d.transaksi_id = t.id
        JOIN tb_barang b ON d.barang_id = b.id
        JOIN tb_user u ON t.user_id = u.id
        WHERE d.toko_id = ?
        ORDER BY FIELD(d.status_pesanan_item, 'siap_kirim', 'diproses', 'dikirim', 'sampai_tujuan', 'dibatalkan', 'ditolak') DESC, t.tanggal_transaksi DESC";

$pesanan_query = $koneksi->prepare($sql);
$pesanan_query->bind_param("i", $toko_id);
$pesanan_query->execute();
$result_pesanan = $pesanan_query->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pesanan - Pondasikita Seller Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/seller_style.css"> 
        <link rel="stylesheet" href="/assets/css/sidebar.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css"> 
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="page-body-wrapper">
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="sidebar-toggle-btn d-lg-none"><i class="mdi mdi-menu"></i></button>
            </div>
            <div class="navbar-right">
                <a href="#" class="navbar-icon"><i class="mdi mdi-bell-outline"></i></a>
                <a href="#" class="navbar-icon"><i class="mdi mdi-help-circle-outline"></i></a>
                <div class="navbar-profile">
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Seller') ?></span>
                    <i class="mdi mdi-chevron-down profile-arrow"></i>
                </div>
            </div>
        </nav>

        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title"><i class="mdi mdi-receipt"></i> Manajemen Pesanan</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="order-filter-tabs mb-4">
                            <a href="#" class="filter-tab active" data-status="">Semua</a>
                            <a href="#" class="filter-tab" data-status="belum_dibayar">Belum Dibayar</a>
                            <a href="#" class="filter-tab" data-status="perlu_dikirim">Perlu Dikirim</a>
                            <a href="#" class="filter-tab" data-status="dikirim">Dikirim</a>
                            <a href="#" class="filter-tab" data-status="selesai">Selesai</a>
                            <a href="#" class="filter-tab" data-status="dibatalkan">Dibatalkan</a>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                            <div class="search-bar d-flex flex-grow-1 me-3 mb-2 mb-md-0">
                                <input type="text" id="orderSearchInput" class="form-control" placeholder="Cari berdasarkan nama produk/invoice">
                                <button class="btn btn-secondary ms-2"><i class="mdi mdi-magnify"></i></button>
                            </div>
                            <button type="submit" id="btn-mass-shipping" class="btn btn-primary" disabled form="mass-shipping-form">
                                <i class="mdi mdi-truck-fast"></i> Proses Pengiriman Massal (<span id="selected-count">0</span>)
                            </button>
                        </div>

                        <form action="../actions/proses_pengiriman_massal.php" method="POST" id="mass-shipping-form">
                            <div class="table-responsive">
                                <table id="pesananTable" class="table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all-orders"></th>
                                            <th style="min-width: 250px;">Produk Dipesan</th>
                                            <th>Total Pesanan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result_pesanan->num_rows > 0): ?>
                                            <?php 
                                            // Group by invoice for a Shopee-like view
                                            $grouped_orders = [];
                                            while($pesanan = $result_pesanan->fetch_assoc()) {
                                                // Adjusting column name from 'gambar_utama' to 'gambar_barang' if needed
                                                $pesanan['gambar_barang'] = $pesanan['gambar_barang'] ?? 'default.jpg'; // Fallback if column name is different or null
                                                $grouped_orders[$pesanan['kode_invoice']][] = $pesanan;
                                            }
                                            ?>
                                            <?php foreach ($grouped_orders as $invoice => $items): ?>
                                            <tr class="order-group-header" data-invoice="<?= htmlspecialchars($invoice) ?>">
                                                <td colspan="5">
                                                    <div class="order-header-info">
                                                        <span class="invoice-number">Invoice: <?= htmlspecialchars($invoice) ?></span>
                                                        <span class="order-date"><?= date('d M Y, H:i', strtotime($items[0]['tanggal_transaksi'])) ?></span>
                                                        <span class="customer-name">Pelanggan: <?= htmlspecialchars($items[0]['nama_pelanggan']) ?></span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php $total_invoice_amount = 0; ?>
                                            <?php foreach ($items as $index => $item): ?>
                                            <tr class="order-item-row" data-invoice="<?= htmlspecialchars($invoice) ?>" data-status="<?= htmlspecialchars($item['status_pesanan_item']) ?>">
                                                <td>
                                                    <?php if($item['status_pesanan_item'] == 'siap_kirim'): ?>
                                                        <input type="checkbox" name="pesanan_ids[]" value="<?= $item['id'] ?>" class="order-checkbox">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="product-info-cell">
                                                        <img src="../assets/img/products/<?= htmlspecialchars($item['gambar_barang']) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>" class="product-thumb" onerror="this.onerror=null;this.src='https://via.placeholder.com/50';">
                                                        <div class="product-details">
                                                            <span class="product-name"><?= htmlspecialchars($item['nama_barang']) ?></span>
                                                            <span class="product-qty">x<?= $item['jumlah'] ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php if ($index === 0): // Hanya tampilkan ini sekali per invoice ?>
                                                <td rowspan="<?= count($items) ?>" class="total-invoice-cell">
                                                    Rp <?= number_format(array_sum(array_column($items, 'subtotal')), 0, ',', '.') ?>
                                                </td>
                                                <td rowspan="<?= count($items) ?>" class="status-cell">
                                                    <?php
                                                        $status_text = ucfirst(str_replace('_', ' ', $item['status_pesanan_item']));
                                                        $badge_class = '';
                                                        switch($item['status_pesanan_item']) {
                                                            case 'diproses': $badge_class = 'badge-primary'; break;
                                                            case 'siap_kirim': $badge_class = 'badge-warning'; break;
                                                            case 'dikirim': $badge_class = 'badge-gradient-info'; break;
                                                            case 'sampai_tujuan': $badge_class = 'badge-success'; break;
                                                            case 'dibatalkan':
                                                            case 'ditolak': $badge_class = 'badge-danger'; break;
                                                            default: $badge_class = 'badge-secondary';
                                                        }
                                                    ?>
                                                    <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                                </td>
                                                <td rowspan="<?= count($items) ?>" class="action-cell">
                                                    <form action="../actions/proses_pesanan.php" method="POST" class="d-flex flex-column align-items-center">
                                                        <input type="hidden" name="detail_transaksi_id" value="<?= $item['id'] ?>">
                                                        <select name="status_baru" class="form-control form-control-sm mb-2">
                                                            <option value="diproses" <?= $item['status_pesanan_item'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                                            <option value="siap_kirim" <?= $item['status_pesanan_item'] == 'siap_kirim' ? 'selected' : '' ?>>Siap Kirim</option>
                                                            <option value="dikirim" <?= $item['status_pesanan_item'] == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                                                            <option value="sampai_tujuan" <?= $item['status_pesanan_item'] == 'sampai_tujuan' ? 'selected' : '' ?>>Sampai Tujuan</option>
                                                            <option value="dibatalkan" <?= $item['status_pesanan_item'] == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Ubah</button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2">Detail</button>
                                                    </form>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Belum ada pesanan masuk untuk toko Anda.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // --- Logika untuk Sidebar Dropdown ---
    $('[data-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $(target).toggleClass('show');
        $(this).attr('aria-expanded', $(target).hasClass('show'));
        $('.collapse.show').not(target).removeClass('show').prev('[data-toggle="collapse"]').attr('aria-expanded', 'false');
    });

    // --- Logika untuk Toggle Sidebar di Mobile ---
    $('.sidebar-toggle-btn').on('click', function() {
        $('.sidebar').toggleClass('active');
        $('.page-body-wrapper').toggleClass('sidebar-active');
    });

    // --- DataTables (Nonaktif untuk Grouping Kompleks, Gunakan Fitur Manual JS) ---
    // $('#pesananTable').DataTable({
    //     "order": [[2, "desc"]], 
    //     "pagingType": "full_numbers",
    //     "searching": true,
    //     "info": true
    // });

    // --- LOGIKA UNTUK PENGIRIMAN MASSAL ---
    const selectAll = document.getElementById('select-all-orders');
    const checkboxes = document.querySelectorAll('.order-checkbox');
    const massShippingBtn = document.getElementById('btn-mass-shipping');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateButtonState() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        selectedCountSpan.textContent = checkedCount;
        massShippingBtn.disabled = checkedCount === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                // Periksa apakah elemen badge memang ada sebelum mencoba mengakses textContent
                const badgeElement = cb.closest('.order-item-row').querySelector('.badge');
                if (badgeElement && badgeElement.textContent.trim().toLowerCase().includes('siap kirim')) {
                     cb.checked = this.checked;
                }
            });
            updateButtonState();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked) {
                selectAll.checked = false;
            } else {
                const allReadyForShipment = Array.from(checkboxes).every(cb => {
                    const badgeElement = cb.closest('.order-item-row').querySelector('.badge');
                    return !badgeElement || !badgeElement.textContent.trim().toLowerCase().includes('siap kirim') || cb.checked;
                });
                selectAll.checked = allReadyForShipment;
            }
            updateButtonState();
        });
    });
    
    $('#mass-shipping-form').on('submit', function(e) {
        const selectedCount = document.querySelectorAll('.order-checkbox:checked').length;
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Pilih setidaknya satu pesanan untuk diproses.');
            return;
        }
        if (!confirm(`Anda yakin ingin memproses ${selectedCount} pesanan terpilih menjadi "Dikirim"?`)) {
            e.preventDefault();
        }
    });

    updateButtonState(); // Panggil saat halaman dimuat

    // --- Logika Search Bar (Basic) ---
    $('#orderSearchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#pesananTable tbody tr.order-group-header, #pesananTable tbody tr.order-item-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // --- Logika Filter Tabs (Basic - hanya menampilkan/menyembunyikan baris) ---
    $('.filter-tab').on('click', function(e) {
        e.preventDefault();
        $('.filter-tab').removeClass('active'); // Hapus 'active' dari semua tab
        $(this).addClass('active'); // Tambahkan 'active' ke tab yang diklik

        var filterStatus = $(this).data('status'); // Ambil status dari data-status

        $('#pesananTable tbody tr.order-group-header, #pesananTable tbody tr.order-item-row').each(function() {
            var $row = $(this);
            // Untuk baris header grup, kita perlu memeriksa apakah ada item di dalamnya yang cocok
            if ($row.hasClass('order-group-header')) {
                var invoice = $row.data('invoice');
                var hasVisibleItem = false;
                $('#pesananTable tbody tr.order-item-row[data-invoice="' + invoice + '"]').each(function() {
                    var itemStatus = $(this).data('status');
                    if (filterStatus === '' || itemStatus === filterStatus) {
                        hasVisibleItem = true;
                        return false; // Break loop if a visible item is found
                    }
                });
                if (filterStatus === '' || hasVisibleItem) {
                    $row.show();
                } else {
                    $row.hide();
                }
            } else { // Ini adalah baris item pesanan
                var itemStatus = $row.data('status');
                if (filterStatus === '' || itemStatus === filterStatus) {
                    $row.show();
                } else {
                    $row.hide();
                }
            }
        });
        
        // Setelah filtering, mungkin ada header group yang tidak memiliki item terlihat.
        // Logika ini menyembunyikan header group jika semua item di bawahnya disembunyikan.
        // Ini bisa dioptimalkan, tapi untuk saat ini, ini adalah pendekatan dasar.
        $('#pesananTable tbody tr.order-group-header').each(function() {
            var $headerRow = $(this);
            var invoice = $headerRow.data('invoice');
            var allItemsHidden = true;
            $('#pesananTable tbody tr.order-item-row[data-invoice="' + invoice + '"]').each(function() {
                if ($(this).is(':visible')) {
                    allItemsHidden = false;
                    return false;
                }
            });
            if (allItemsHidden && filterStatus !== '') { // Hanya sembunyikan jika ada filter aktif
                $headerRow.hide();
            } else if (filterStatus === '') { // Pastikan header tampil jika filter "Semua"
                $headerRow.show();
            }
        });

    });
});
</script>
</body>
</html>