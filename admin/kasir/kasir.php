<?php
// Anda bisa tambahkan include untuk header/sidebar jika struktur template Anda modular
// include '../../../config/koneksi.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Point of Sale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .product-card { cursor: pointer; border-radius: 8px; transition: all 0.2s ease-in-out; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 150px; object-fit: cover; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .product-price { font-size: 1.1rem; font-weight: bold; color:rgb(234, 5, 5); }
        .transaction-panel { position: sticky; top: 20px; max-height: 95vh; overflow-y: auto; }
        #cart-items { max-height: 35vh; overflow-y: auto; }
        .badge-stock { font-size: 0.75rem; }
        .form-control-sm.quantity-input { width: 60px; text-align: center; }
    </style>
</head>
<body>

<div class="container-fluid p-3">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title">Daftar Produk</h4>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="flex-grow-1">
                            <input type="text" id="search-input" class="form-control" placeholder="Cari nama atau kode produk...">
                        </div>
                        <div id="category-filters" class="btn-group" role="group">
                            </div>
                    </div>
                    <div id="product-grid" class="row g-3" style="max-height: 75vh; overflow-y: auto;">
                        <div id="loading-spinner" class="text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm transaction-panel">
                <div class="card-body d-flex flex-column">
                    <h4 class="card-title">Transaksi</h4>
                    <hr>
                    <div class="mb-3">
                        <label for="customer-name" class="form-label">Nama Pelanggan (Opsional)</label>
                        <input type="text" id="customer-name" class="form-control">
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
                            <div class="col-md-6">
                                <label for="payment-method" class="form-label">Metode Bayar</label>
                                <select id="payment-method" class="form-select">
                                    <option value="Tunai" selected>Tunai</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="amount-paid" class="form-label">Jumlah Bayar</label>
                                <input type="number" id="amount-paid" class="form-control" placeholder="0">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label small text-muted">Uang Cepat:</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-success quick-cash-btn" data-amount="exact">Uang Pas</button>
                                <button type="button" class="btn btn-outline-primary quick-cash-btn" data-amount="50000">50rb</button>
                                <button type="button" class="btn btn-outline-primary quick-cash-btn" data-amount="100000">100rb</button>
                            </div>
                        </div>
                        <div class="mt-2 fs-5">
                            <span>Kembalian:</span>
                        </div>
                         <div class="mt-2 fs-5">
                            <span>Kembalian:</span>
                            <span id="change-due" class="fw-bold">Rp 0</span>
                        </div>
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
$(document).ready(function() {
    // State aplikasi
    let allProducts = [];
    let cart = [];

    // Fungsi untuk memformat angka menjadi Rupiah
    const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    
    // Fungsi untuk merender produk ke grid
    function renderProducts(productsToRender) {
        const grid = $('#product-grid');
        grid.empty();
        if (productsToRender.length === 0) {
            grid.html('<p class="text-center text-muted">Produk tidak ditemukan.</p>');
            return;
        }
        productsToRender.forEach(p => {
            const stockBadge = p.stok_tersedia > 0 
                ? `<span class="badge bg-success badge-stock">${p.stok_tersedia} Tersedia</span>`
                : `<span class="badge bg-danger badge-stock">Stok Habis</span>`;
            
            const disabledClass = p.stok_tersedia <= 0 ? 'disabled' : '';
            const cardHtml = `
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card product-card h-100 ${disabledClass}" data-id="${p.id}">
                        <img src="../../assets/uploads/${p.gambar_utama || 'placeholder.png'}" alt="${p.nama_barang}">
                        <div class="card-body d-flex flex-column p-2">
                            <h6 class="card-title small flex-grow-1">${p.nama_barang}</h6>
                            <p class="card-text product-price">${formatRupiah(p.harga)}</p>
                            ${stockBadge}
                        </div>
                    </div>
                </div>`;
            grid.append(cardHtml);
        });
    }

    // Fungsi untuk merender kategori
    function renderCategories(categories) {
        const filters = $('#category-filters');
        filters.html('<button class="btn btn-secondary category-filter active" data-id="all">Semua</button>');
        categories.forEach(cat => {
            filters.append(`<button class="btn btn-outline-secondary category-filter" data-id="${cat.id}">${cat.nama_kategori}</button>`);
        });
    }
    
    // Fungsi untuk update tampilan keranjang
    function updateCartDisplay() {
        const cartItems = $('#cart-items');
        cartItems.empty();
        if (cart.length === 0) {
            cartItems.html('<li id="empty-cart-message" class="list-group-item text-center text-muted">Keranjang masih kosong</li>');
        } else {
            cart.forEach((item, index) => {
                const itemHtml = `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${item.name}</div>
                            <small>${formatRupiah(item.price)}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-outline-secondary btn-sm btn-dec" data-index="${index}"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" class="form-control-sm quantity-input" value="${item.quantity}" min="1" data-index="${index}">
                            <button class="btn btn-outline-secondary btn-sm btn-inc" data-index="${index}"><i class="fa-solid fa-plus"></i></button>
                            <button class="btn btn-danger btn-sm ms-2 btn-remove" data-index="${index}"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </li>`;
                cartItems.append(itemHtml);
            });
        }
        calculateTotal();
    }
    
    // Fungsi untuk kalkulasi total
    function calculateTotal() {
        let total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        $('#total-price').text(formatRupiah(total));
        calculateChange();
    }

    // Fungsi untuk kalkulasi kembalian
    function calculateChange() {
        let total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let amountPaid = parseFloat($('#amount-paid').val()) || 0;
        let change = amountPaid >= total ? amountPaid - total : 0;
        $('#change-due').text(formatRupiah(change));
    }
    
    // Fungsi untuk menambah item ke keranjang
    function addToCart(productId) {
        const product = allProducts.find(p => p.id == productId);
        if (!product || product.stok_tersedia <= 0) return;

        const cartItem = cart.find(item => item.id == productId);
        if (cartItem) {
            if(cartItem.quantity < product.stok_tersedia) {
                cartItem.quantity++;
            } else {
                 Swal.fire({ icon: 'warning', title: 'Stok Tidak Cukup', timer: 1500, showConfirmButton: false });
            }
        } else {
            cart.push({ id: product.id, name: product.nama_barang, price: product.harga, quantity: 1, maxStock: product.stok_tersedia });
        }
        updateCartDisplay();
    }
    
    // Memuat data produk saat halaman siap
    function loadProducts() {
        $('#loading-spinner').show();
        $.get('api/get_products_list.php')
        // GANTI PATH JIKA PERLU
            .done(function(data) {
                allProducts = data;
                renderProducts(allProducts);
            })
            .fail(function() {
                Swal.fire('Error', 'Gagal memuat data produk.', 'error');
            })
            .always(function() {
                 $('#loading-spinner').hide();
            });
    }

    // Memuat data kategori
    function loadCategories() {
        $.get('api/get_kategori_list.php')
            .done(function(data) {
                renderCategories(data);
            });
    }
    
    // Event Handlers
    // ==================================
// === TAMBAHKAN BLOK BARU INI ===
// ==================================
// Event handler untuk tombol uang cepat
$('.transaction-panel').on('click', '.quick-cash-btn', function() {
    const amount = $(this).data('amount');
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    let finalAmount = 0;
    if (amount === 'exact') {
        finalAmount = total;
    } else {
        finalAmount = parseInt(amount);
    }
    
    // Set nilai input jumlah bayar
    $('#amount-paid').val(finalAmount);
    
    // PENTING: Trigger event 'keyup' untuk otomatis menghitung kembalian
    $('#amount-paid').trigger('keyup');
});
// ==================================
// === AKHIR BLOK BARU ===
// ==================================
    $('#product-grid').on('click', '.product-card:not(.disabled)', function() {
        addToCart($(this).data('id'));
    });

    $('#cart-items').on('click', '.btn-inc', function() {
        const index = $(this).data('index');
        if(cart[index].quantity < cart[index].maxStock) {
            cart[index].quantity++;
            updateCartDisplay();
        }
    });

    $('#cart-items').on('click', '.btn-dec', function() {
        const index = $(this).data('index');
        cart[index].quantity--;
        if (cart[index].quantity === 0) {
            cart.splice(index, 1);
        }
        updateCartDisplay();
    });

    $('#cart-items').on('change', '.quantity-input', function() {
        const index = $(this).data('index');
        let newQty = parseInt($(this).val());
        if(isNaN(newQty) || newQty < 1) newQty = 1;
        if(newQty > cart[index].maxStock) {
            newQty = cart[index].maxStock;
            Swal.fire({ icon: 'warning', title: 'Stok Tidak Cukup', timer: 1500, showConfirmButton: false });
        }
        cart[index].quantity = newQty;
        updateCartDisplay();
    });

    $('#cart-items').on('click', '.btn-remove', function() {
        cart.splice($(this).data('index'), 1);
        updateCartDisplay();
    });

    $('#search-input').on('keyup', function() {
        const term = $(this).val().toLowerCase();
        const filteredProducts = allProducts.filter(p => p.nama_barang.toLowerCase().includes(term) || p.kode_barang.toLowerCase().includes(term));
        renderProducts(filteredProducts);
        $('.category-filter').removeClass('active');
        $('.category-filter[data-id="all"]').addClass('active');
    });

// --- GANTI DENGAN KODE DEBUG INI ---
$('#category-filters').on('click', '.category-filter', function() {
    // Men-debug Tombol Aktif
    $('.category-filter').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('active btn-secondary');
    
    const categoryId = $(this).data('id');
    $('#search-input').val('');

    // Menampilkan data ke console untuk dilacak
    console.log('--- DEBUG INFO ---');
    console.log('ID Kategori yang diklik:', categoryId, '(Tipe datanya:', typeof categoryId, ')');
    if (allProducts.length > 0) {
        console.log('ID Kategori dari produk pertama:', allProducts[0].kategori_id, '(Tipe datanya:', typeof allProducts[0].kategori_id, ')');
    }
    console.log('------------------');

    if (categoryId === 'all') {
        renderProducts(allProducts);
    } else {
        // PERBAIKAN LOGIKA FILTER: Mengubah keduanya menjadi angka sebelum membandingkan
        const filteredProducts = allProducts.filter(p => parseInt(p.kategori_id) === parseInt(categoryId));
        renderProducts(filteredProducts);
    }
});

    $('#amount-paid').on('keyup', calculateChange);

    $('#cancel-btn').on('click', function() {
        Swal.fire({
            title: 'Batalkan Transaksi?',
            text: "Keranjang akan dikosongkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonText: 'Tidak',
            confirmButtonText: 'Ya, batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                resetTransaction();
            }
        });
    });

    function resetTransaction() {
        cart = [];
        updateCartDisplay();
        $('#customer-name').val('');
        $('#amount-paid').val('');
        calculateChange();
    }

    $('#process-payment-btn').on('click', function() {
        if (cart.length === 0) {
            Swal.fire('Keranjang Kosong', 'Silakan tambahkan produk terlebih dahulu.', 'warning');
            return;
        }

        const transactionData = {
            customerName: $('#customer-name').val(),
            cart: cart,
            total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
            paymentMethod: $('#payment-method').val(),
            amountPaid: parseFloat($('#amount-paid').val()) || 0,
            change: parseFloat($('#total-price').text().replace(/[^0-9]/g, '')) - (parseFloat($('#amount-paid').val()) || 0)
        };
        transactionData.change = transactionData.amountPaid - transactionData.total;
        
        if (transactionData.paymentMethod === 'Tunai' && transactionData.amountPaid < transactionData.total) {
             Swal.fire('Pembayaran Kurang', 'Jumlah uang yang dibayarkan kurang dari total tagihan.', 'error');
            return;
        }

        Swal.fire({
            title: 'Proses Transaksi?',
            text: `Total: ${formatRupiah(transactionData.total)}`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: 'api/proses_transaksi.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(transactionData),
                }).catch(error => {
                    Swal.showValidationMessage(`Request Gagal: ${error.statusText}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                 if(result.value.status === 'success'){
                     Swal.fire({
                         title: 'Berhasil!',
                         text: `Transaksi dengan kode ${result.value.data.invoiceCode} berhasil disimpan.`,
                         icon: 'success'
                     });
                     resetTransaction();
                     loadProducts(); // Reload produk untuk update stok
                 } else {
                     Swal.fire('Gagal!', result.value.message || 'Terjadi kesalahan di server.', 'error');
                 }
            }
        });
    });

    // Inisialisasi
    loadProducts();
    loadCategories();
});
</script>
</body>
</html>