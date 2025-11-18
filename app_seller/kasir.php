<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); // Ke login seller
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir (Point of Sale) - Pondasikita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Menggunakan CSS dari file kasir lama Anda, bisa juga dipisah ke file .css sendiri */
        body { background-color: #f0f2f5; }
        .product-card { cursor: pointer; border-radius: 8px; transition: all 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 120px; object-fit: cover; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .product-price { font-size: 1rem; font-weight: bold; color: #B91C1C; }
        .transaction-panel { position: sticky; top: 20px; max-height: 95vh; overflow-y: auto; }
        #cart-items { max-height: 35vh; overflow-y: auto; }
        .form-control-sm.quantity-input { width: 60px; text-align: center; }
    </style>
</head>
<body>

<div class="container-fluid p-3">
    <a href="dashboard.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title">Daftar Produk Toko Anda</h4>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="flex-grow-1">
                            <input type="text" id="search-input" class="form-control" placeholder="Cari nama atau kode produk...">
                        </div>
                        <div id="category-filters" class="btn-group" role="group"></div>
                    </div>
                    <div id="product-grid" class="row g-3" style="max-height: 75vh; overflow-y: auto;">
                        <div id="loading-spinner" class="text-center p-5"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm transaction-panel">
                <div class="card-body d-flex flex-column">
                    <h4 class="card-title">Transaksi Kasir</h4>
                    <hr>
                    <div class="mb-3">
                        <label for="customer-name" class="form-label">Nama Pelanggan (Opsional)</label>
                        <input type="text" id="customer-name" class="form-control" placeholder="Pelanggan Offline">
                    </div>
                    <h6>Keranjang</h6>
                    <ul id="cart-items" class="list-group list-group-flush mb-3">
                        <li id="empty-cart-message" class="list-group-item text-center text-muted">Keranjang masih kosong</li>
                    </ul>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between fs-5 fw-bold">
                            <span>TOTAL</span>
                            <span id="total-price">Rp 0</span>
                        </div>
                        <hr>
                        <div class="row g-2">
                            <div class="col-md-6"><label for="payment-method" class="form-label">Metode Bayar</label><select id="payment-method" class="form-select"><option value="Tunai" selected>Tunai</option><option value="QRIS">QRIS</option><option value="Lainnya">Lainnya</option></select></div>
                            <div class="col-md-6"><label for="amount-paid" class="form-label">Jumlah Bayar</label><input type="number" id="amount-paid" class="form-control" placeholder="0"></div>
                        </div>
                        <div class="mt-2"><label class="form-label small text-muted">Uang Cepat:</label><div class="btn-group w-100"><button type="button" class="btn btn-outline-success quick-cash-btn" data-amount="exact">Uang Pas</button><button type="button" class="btn btn-outline-primary quick-cash-btn" data-amount="50000">50rb</button><button type="button" class="btn btn-outline-primary quick-cash-btn" data-amount="100000">100rb</button></div></div>
                        <div class="mt-2 fs-5"><span>Kembalian:</span><span id="change-due" class="fw-bold ms-2">Rp 0</span></div>
                        <div class="d-grid gap-2 mt-3">
                            <button id="process-payment-btn" class="btn btn-primary btn-lg"><i class="fa-solid fa-cash-register"></i> Proses & Bayar</button>
                            <button id="cancel-btn" class="btn btn-outline-danger"><i class="fa-solid fa-times"></i> Batalkan Transaksi</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Seluruh kode JavaScript kompleks Anda dari file kasir lama bisa ditempel di sini.
// Pastikan path AJAX diubah untuk menunjuk ke /actions/kasir_...
$(document).ready(function() {
    let allProducts = []; let cart = [];
    const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    function renderProducts(products) { /* ... fungsi render produk Anda ... */ }
    function renderCategories(categories) { /* ... fungsi render kategori Anda ... */ }
    function updateCartDisplay() { /* ... fungsi update keranjang Anda ... */ }
    function calculateTotal() { /* ... fungsi kalkulasi total Anda ... */ }
    function calculateChange() { /* ... fungsi kalkulasi kembalian Anda ... */ }
    function addToCart(productId) { /* ... fungsi add to cart Anda ... */ }
    
    // PERUBAHAN: Path AJAX disesuaikan
    function loadProducts() {
        $.get('../actions/kasir_get_produk.php').done(data => {
            allProducts = data; renderProducts(allProducts);
        }).always(() => $('#loading-spinner').hide());
    }
    function loadCategories() {
        $.get('../actions/kasir_get_kategori.php').done(data => renderCategories(data));
    }
    
    // ... Sisa event handler Anda: klik produk, search, filter, tombol +/-/hapus, cancel, dll ...

    $('#process-payment-btn').on('click', function() {
        // Logika proses pembayaran Anda, pastikan path AJAX nya benar
        const transactionData = { /* ... data transaksi ... */ };
        $.ajax({
            url: '../actions/kasir_proses_transaksi.php', // PERUBAHAN: Path AJAX
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(transactionData),
            // ...
        });
    });

    loadProducts(); loadCategories();
});
</script>

</body>
</html>