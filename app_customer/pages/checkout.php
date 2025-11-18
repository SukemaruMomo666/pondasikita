<?php
// Pastikan error reporting diaktifkan untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Gunakan __DIR__ untuk path absolut yang lebih robust
require_once __DIR__ . '/../../config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Cek apakah pengguna sudah login
if (!isset($_SESSION['user']['id'])) {
    header("Location: /auth/login_customer.php"); // Pastikan ini path yang benar ke login
    exit;
}

$id_user = $_SESSION['user']['id'];

// --- PENTING: Ambil data alamat LENGKAP dari tb_user_alamat ---
// Validasi Alamat Pengguna (Menggunakan tb_user_alamat)
$stmt_alamat = $koneksi->prepare("
    SELECT ua.id, ua.alamat_lengkap, ua.kode_pos, ua.province_id, ua.city_id, ua.district_id,
           ua.label_alamat, ua.nama_penerima, ua.telepon_penerima,
           p.name AS province_name, c.name AS city_name, d.name AS district_name
    FROM tb_user_alamat ua
    LEFT JOIN provinces p ON ua.province_id = p.id
    LEFT JOIN cities c ON ua.city_id = c.id
    LEFT JOIN districts d ON ua.district_id = d.id
    WHERE ua.user_id = ? AND ua.is_utama = 1
");
if (!$stmt_alamat) {
    $_SESSION['error_message'] = "Database error: Gagal menyiapkan query alamat utama. " . $koneksi->error;
    header("Location: /index.php"); // Atau ke halaman yang lebih relevan
    exit;
}
$stmt_alamat->bind_param("i", $id_user);
$stmt_alamat->execute();
$result_alamat = $stmt_alamat->get_result();
$alamat_user = $result_alamat->fetch_assoc();
$stmt_alamat->close();

// === PERBAIKAN UTAMA: Validasi alamat tanpa redirect paksa ke edit_profil ===
// Pesan error jika alamat tidak lengkap
$alamat_incomplete_message = "Alamat utama Anda belum lengkap. Mohon lengkapi Nama Penerima, Telepon Penerima, Provinsi, Kota, Kecamatan, dan Alamat Lengkap untuk melanjutkan.";
$is_alamat_incomplete = false;

if (!$alamat_user || empty($alamat_user['nama_penerima']) || empty($alamat_user['telepon_penerima']) || empty($alamat_user['province_id']) || empty($alamat_user['city_id']) || empty($alamat_user['district_id']) || empty($alamat_user['alamat_lengkap'])) {
    $is_alamat_incomplete = true;
    // Jika ada session error_message dari proses lain, kita biarkan saja.
    // Jika tidak ada, atau jika pesan dari validasi alamat ini, kita set.
    if (!isset($_SESSION['error_message']) || $_SESSION['error_message'] !== $alamat_incomplete_message) {
        $_SESSION['error_message'] = $alamat_incomplete_message;
    }
} else {
    // Alamat sudah lengkap, hapus pesan error jika itu dari validasi alamat ini.
    if (isset($_SESSION['error_message']) && $_SESSION['error_message'] === $alamat_incomplete_message) {
        unset($_SESSION['error_message']);
    }
}
// =========================================================================

// Format alamat untuk tampilan
$alamat_display = '';
if ($alamat_user) {
    $alamat_display =
        htmlspecialchars($alamat_user['label_alamat'] ?? 'Alamat Utama') . ' (' . htmlspecialchars($alamat_user['nama_penerima'] ?? '') . ' - ' . htmlspecialchars($alamat_user['telepon_penerima'] ?? '') . ')<br>' .
        htmlspecialchars($alamat_user['alamat_lengkap']) . '<br>' .
        'Kec. ' . htmlspecialchars($alamat_user['district_name'] ?? '') . ', ' .
        htmlspecialchars($alamat_user['city_name'] ?? '') . ',<br>' .
        htmlspecialchars($alamat_user['province_name'] ?? '') .
        (!empty($alamat_user['kode_pos']) ? ', ' . htmlspecialchars($alamat_user['kode_pos']) : '');
} else {
    $alamat_display = 'Alamat utama belum diatur. Silakan lengkapi profil Anda.';
}


// 2. Inisialisasi variabel untuk item per toko dan total
$items_per_toko = []; // Ini akan menyimpan item yang dikelompokkan per toko
$total_produk = 0;
$selected_ids_from_cart = []; // Ini akan menyimpan ID dari tb_keranjang jika dari keranjang
$is_direct_purchase = isset($_GET['product_id']);

// 3. Logika untuk mengambil item yang akan di-checkout dan mengisi $items_per_toko
if ($is_direct_purchase) {
    // === LOGIKA UNTUK PEMBELIAN LANGSUNG ===
    $product_id = intval($_GET['product_id']);
    $jumlah = isset($_GET['jumlah']) ? intval($_GET['jumlah']) : 1;

    $sql = "SELECT b.id AS barang_id, b.nama_barang, b.harga, b.gambar_utama,
                   t.id AS toko_id, t.nama_toko, c.name AS kota_toko, b.stok, b.stok_di_pesan
            FROM tb_barang b
            JOIN tb_toko t ON b.toko_id = t.id
            LEFT JOIN cities c ON t.city_id = c.id
            WHERE b.id = ?";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: Gagal menyiapkan query produk langsung. " . $koneksi->error;
        header("Location: /index.php");
        exit;
    }
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        // Cek stok untuk pembelian langsung
        if (($row['stok'] - ($row['stok_di_pesan'] ?? 0)) < $jumlah) {
            $_SESSION['error_message'] = "Stok produk '" . htmlspecialchars($row['nama_barang']) . "' tidak mencukupi. Sisa stok: " . ($row['stok'] - ($row['stok_di_pesan'] ?? 0));
            header("Location: /app_customer/pages/produk_detail.php?id=" . $product_id); // Redirect kembali ke detail produk
            exit;
        }
        $row['jumlah'] = $jumlah; // Tambahkan jumlah ke baris produk
        $toko_id = $row['toko_id'];
        $items_per_toko[$toko_id] = [
            'nama_toko' => $row['nama_toko'],
            'kota_toko' => $row['kota_toko'] ?? 'Lokasi tidak diketahui',
            'items' => [$row] // Masukkan produk ke dalam array items
        ];
        $total_produk += $row['harga'] * $row['jumlah'];
    } else {
        $_SESSION['error_message'] = "Produk tidak ditemukan atau tidak valid.";
        header("Location: /index.php"); // Redirect jika produk tidak ditemukan
        exit;
    }
} else {
    // === LOGIKA UNTUK PEMBELIAN DARI KERANJANG ===
    // Ambil ID keranjang dari POST. Ini adalah ID dari tabel tb_keranjang
    $selected_ids_from_cart = $_POST['selected_items'] ?? [];

    if (empty($selected_ids_from_cart)) {
        $_SESSION['error_message'] = "Tidak ada produk yang dipilih untuk checkout.";
        header("Location: /app_customer/pages/keranjang.php");
        exit;
    }

    // Sanitasi ID keranjang untuk mencegah SQL Injection
    $sanitized_cart_ids = array_map('intval', array_filter($selected_ids_from_cart, 'is_numeric'));

    if (empty($sanitized_cart_ids)) {
        $_SESSION['error_message'] = "Format item keranjang tidak valid.";
        header("Location: /app_customer/pages/keranjang.php");
        exit;
    }

    // Buat placeholder untuk query IN clause
    $placeholders = implode(',', array_fill(0, count($sanitized_cart_ids), '?'));
    $types = 'i' . str_repeat('i', count($sanitized_cart_ids)); // 'i' untuk user_id + 'i' untuk setiap cart ID

    // Query untuk mengambil detail item dari keranjang
    $sql = "SELECT k.id AS keranjang_id, b.id AS barang_id, b.nama_barang, b.harga, b.gambar_utama, k.jumlah,
                   t.id AS toko_id, t.nama_toko, c.name AS kota_toko, b.stok, b.stok_di_pesan
            FROM tb_keranjang k
            JOIN tb_barang b ON k.barang_id = b.id
            JOIN tb_toko t ON b.toko_id = t.id
            LEFT JOIN cities c ON t.city_id = c.id
            WHERE k.user_id = ? AND k.id IN ($placeholders)";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: Gagal menyiapkan query keranjang. " . $koneksi->error;
        header("Location: /app_customer/pages/keranjang.php");
        exit;
    }

    // Bangun array parameter untuk bind_param
    $params_cart = [];
    $params_cart[] = &$id_user;
    foreach ($sanitized_cart_ids as $key => $value) {
        $params_cart[] = &$sanitized_cart_ids[$key]; // Penting: gunakan &$array[$key] untuk referensi
    }
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params_cart));

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (($row['stok'] - ($row['stok_di_pesan'] ?? 0)) < $row['jumlah']) {
            $_SESSION['error_message'] = "Stok produk '" . htmlspecialchars($row['nama_barang']) . "' di keranjang tidak mencukupi. Sisa stok: " . ($row['stok'] - ($row['stok_di_pesan'] ?? 0)) . ". Mohon sesuaikan jumlah di keranjang.";
            header("Location: /app_customer/pages/keranjang.php");
            exit;
        }
        $toko_id = $row['toko_id'];
        if (!isset($items_per_toko[$toko_id])) {
            $items_per_toko[$toko_id] = [
                'nama_toko' => $row['nama_toko'],
                'kota_toko' => $row['kota_toko'] ?? 'Lokasi tidak diketahui',
                'items' => []
            ];
        }
        $items_per_toko[$toko_id]['items'][] = $row;
        $total_produk += $row['harga'] * $row['jumlah'];
    }
    $stmt->close();
}

// Jika setelah semua proses, tidak ada item yang valid, redirect ke keranjang
if (empty($items_per_toko)) { // Menggunakan $items_per_toko karena ini yang akan digunakan di tampilan
    $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Tidak ada item yang valid untuk di-checkout. Silakan pilih produk.";
    header("Location: /app_customer/pages/keranjang.php");
    exit;
}

// Total produk yang akan digunakan di HTML dan JS
$total = $total_produk;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/theme.css"> <link rel="stylesheet" href="/assets/css/navbar_style.css"> <link rel="stylesheet" href="/assets/css/checkout_page_style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php
    // Pastikan path ke partials/navbar.php benar
    include __DIR__ . '/../partials/navbar.php';
?>

<div class="checkout-container">
    <header class="checkout-header">
        <h1><i class="fas fa-file-invoice-dollar"></i> Rincian Pesanan</h1>
    </header>

    <main class="checkout-content">
        <div class="checkout-details">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger" role="alert" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h3>
                    <a href="crud_profil/edit_profil.php" class="btn-edit-alamat">Ubah Alamat</a>
                </div>
                <div class="card-body">
                    <p id="display-alamat"><?= $alamat_display ?></p>
                </div>
            </div>

            <form id="checkout-form" action="/actions/proses_checkout.php" method="post">
                <input type="hidden" name="total_produk_subtotal" value="<?= $total ?>">

                <input type="hidden" name="shipping_label_alamat" value="<?= htmlspecialchars($alamat_user['label_alamat'] ?? '') ?>">
                <input type="hidden" name="shipping_nama_penerima" value="<?= htmlspecialchars($alamat_user['nama_penerima'] ?? '') ?>">
                <input type="hidden" name="shipping_telepon_penerima" value="<?= htmlspecialchars($alamat_user['telepon_penerima'] ?? '') ?>">
                <input type="hidden" name="shipping_alamat_lengkap" value="<?= htmlspecialchars($alamat_user['alamat_lengkap'] ?? '') ?>">
                <input type="hidden" name="shipping_kecamatan" value="<?= htmlspecialchars($alamat_user['district_name'] ?? '') ?>">
                <input type="hidden" name="shipping_kota_kabupaten" value="<?= htmlspecialchars($alamat_user['city_name'] ?? '') ?>">
                <input type="hidden" name="shipping_provinsi" value="<?= htmlspecialchars($alamat_user['province_name'] ?? '') ?>">
                <input type="hidden" name="shipping_kode_pos" value="<?= htmlspecialchars($alamat_user['kode_pos'] ?? '') ?>">


                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-box-open"></i> Produk Dipesan</h3>
                    </div>
                    <div class="card-body">
                        <div class="product-list">
                            <?php
                            // Loop melalui items_per_toko untuk menampilkan produk
                            foreach ($items_per_toko as $toko_id => $data_toko):
                                foreach ($data_toko['items'] as $item):
                                    $subtotal_item = $item['harga'] * $item['jumlah'];
                            ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar_utama'] ?? 'default.jpg') ?>"
                                            alt="<?= htmlspecialchars($item['nama_barang']) ?>"
                                            onerror="this.onerror=null; this.src='/assets/uploads/products/default.jpg';">
                                </div>
                                <div class="product-info">
                                    <p class="product-name"><?= htmlspecialchars($item['nama_barang']) ?></p>
                                    <p class="product-quantity">Jumlah: <?= $item['jumlah'] ?></p>
                                </div>
                                <div class="product-price">
                                    <p>Rp<?= number_format($subtotal_item, 0, ',', '.') ?></p>
                                </div>
                            </div>
                            <?php endforeach; endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-money-check-alt"></i> Opsi Pembayaran & Pengiriman</h3></div>
                    <div class="card-body">
                        <p>Pembayaran akan diproses melalui Midtrans setelah Anda membuat pesanan.</p>

                        <?php if ($is_direct_purchase): ?>
                            <input type="hidden" name="direct_purchase" value="1">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($items_per_toko[array_key_first($items_per_toko)]['items'][0]['barang_id']) ?>">
                            <input type="hidden" name="jumlah" value="<?= htmlspecialchars($items_per_toko[array_key_first($items_per_toko)]['items'][0]['jumlah']) ?>">
                        <?php else: // Dari keranjang ?>
                            <?php foreach ($sanitized_cart_ids as $keranjang_id): // Gunakan $sanitized_cart_ids ?>
                                <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($keranjang_id) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="tipe_pengambilan">Tipe Pengambilan</label>
                            <select name="tipe_pengambilan" id="tipe_pengambilan" required>
                                <option value="pengiriman">Dikirim ke Alamat</option>
                                <option value="ambil_di_toko">Ambil di Toko</option>
                            </select>
                        </div>

                        <?php foreach ($items_per_toko as $toko_id => $data): ?>
                            <div class="shipping-option" id="shipping-option-<?= $toko_id ?>">
                                <label for="shipping-<?= $toko_id ?>">Opsi Pengiriman untuk <?= htmlspecialchars($data['nama_toko']) ?></label>
                                <select name="shipping[<?= $toko_id ?>]" id="shipping-<?= $toko_id ?>" class="shipping-select" data-toko-id="<?= $toko_id ?>">
                                    <option value="15000" selected>Reguler - Rp15.000</option>
                                    <option value="30000">Kargo - Rp30.000</option>
                                </select>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-group">
                            <label for="catatan">Catatan (Opsional)</label>
                            <textarea name="catatan" id="catatan" rows="2" placeholder="Tinggalkan pesan untuk penjual..."></textarea>
                        </div>

                        <div class="voucher-section">
                            <input type="text" id="voucher-input" placeholder="Masukkan Kode Voucher">
                            <button type="button" id="apply-voucher-btn">Terapkan</button>
                        </div>
                        <input type="hidden" name="kode_voucher_terpakai" id="kode_voucher_terpakai" value="">

                    </div>
                </div>
            </form>
        </div>

        <aside class="checkout-summary">
            <div class="card summary-card">
                <div class="card-header"><h3>Ringkasan Belanja</h3></div>
                <div class="card-body" data-subtotal="<?= $total ?>">
                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span id="subtotal-display">Rp<?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Pengiriman</span>
                        <span id="shipping-total-display">Rp0</span>
                    </div>
                    <div id="discount-row" class="summary-row hidden">
                        <span>Diskon Voucher</span>
                        <span id="discount-amount">- Rp0</span>
                    </div>
                    <hr class="summary-divider">
                    <div class="summary-total">
                        <span>Total Pembayaran</span>
                        <span id="grand-total-display">Rp<?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" form="checkout-form" class="btn-submit" <?= $is_alamat_incomplete ? 'disabled' : '' ?>>
                        Buat Pesanan & Bayar
                    </button>
                </div>
            </div>
        </aside>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subtotalProduk = <?= $total ?>;
    const shippingSelects = document.querySelectorAll('.shipping-select');
    const tipePengambilanSelect = document.getElementById('tipe_pengambilan');
    const checkoutForm = document.getElementById('checkout-form');
    const voucherInput = document.getElementById('voucher-input');
    const applyVoucherBtn = document.getElementById('apply-voucher-btn');
    const kodeVoucherTerpakaiHidden = document.getElementById('kode_voucher_terpakai');
    const btnSubmit = document.querySelector('.btn-submit');

    let diskonVoucher = 0; // Global variable to store current discount

    // Fungsi untuk memperbarui status tombol submit berdasarkan kelengkapan alamat
    function updateSubmitButtonStatus() {
        const isAlamatIncomplete = <?= json_encode($is_alamat_incomplete) ?>;
        if (isAlamatIncomplete) {
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Lengkapi Alamat untuk Melanjutkan';
        } else {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Buat Pesanan & Bayar';
        }
    }
    updateSubmitButtonStatus(); // Panggil saat DOMContentLoaded


    function calculateTotal() {
        let totalShipping = 0;
        const selectedTipePengambilan = tipePengambilanSelect.value;

        // Hanya tambahkan biaya pengiriman jika tipe_pengambilan adalah 'pengiriman'
        if (selectedTipePengambilan === 'pengiriman') {
            shippingSelects.forEach(select => {
                totalShipping += parseFloat(select.value);
            });
            document.getElementById('shipping-total-display').innerText = 'Rp' + totalShipping.toLocaleString('id-ID');
        } else {
            // Jika 'ambil_di_toko', biaya pengiriman 0
            document.getElementById('shipping-total-display').innerText = 'Rp0';
        }

        const grandTotal = subtotalProduk + totalShipping - diskonVoucher;

        document.getElementById('grand-total-display').innerText = 'Rp' + grandTotal.toLocaleString('id-ID');

        // Update discount display
        const discountRow = document.getElementById('discount-row');
        const discountAmountSpan = document.getElementById('discount-amount');
        if (diskonVoucher > 0) {
            discountAmountSpan.innerText = '- Rp' + diskonVoucher.toLocaleString('id-ID');
            discountRow.classList.remove('hidden');
        } else {
            discountRow.classList.add('hidden');
        }
    }

    // Event listener untuk perubahan opsi pengiriman per toko
    shippingSelects.forEach(select => {
        select.addEventListener('change', calculateTotal);
    });

    // Event listener untuk perubahan tipe pengambilan (Dikirim vs Ambil di Toko)
    tipePengambilanSelect.addEventListener('change', function() {
        const selectedTipe = this.value;
        const shippingOptionsDivs = document.querySelectorAll('.shipping-option');

        // Sembunyikan/tampilkan opsi pengiriman per toko
        shippingOptionsDivs.forEach(div => {
            if (selectedTipe === 'ambil_di_toko') {
                div.style.display = 'none'; // Sembunyikan opsi pengiriman
            } else {
                div.style.display = 'block'; // Tampilkan opsi pengiriman
            }
        });
        calculateTotal(); // Recalculate total
    });

    // Initial calculation when page loads
    calculateTotal();

    // Event Listener for Voucher Application
    applyVoucherBtn.addEventListener('click', function() {
        const kodeVoucher = voucherInput.value.trim();

        if (kodeVoucher === '') {
            alert('Silakan masukkan kode voucher.');
            return;
        }

        const formData = new FormData();
        formData.append('kode_voucher', kodeVoucher);
        formData.append('subtotal', subtotalProduk); // Use subtotal produk for voucher check

        fetch('/actions/cek_voucher.php', { // Path absolut lebih aman jika root web server sudah diatur
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                diskonVoucher = data.diskon;
                kodeVoucherTerpakaiHidden.value = kodeVoucher; // Simpan kode di hidden input
                calculateTotal(); // Update total setelah diskon
            } else {
                alert(data.message);
                diskonVoucher = 0; // Reset diskon jika voucher tidak valid
                kodeVoucherTerpakaiHidden.value = ''; // Hapus kode dari hidden input
                calculateTotal(); // Update total setelah reset
            }
        })
        .catch(error => {
            console.error('Error applying voucher:', error);
            alert('Terjadi kesalahan saat menerapkan voucher. Silakan coba lagi.');
            diskonVoucher = 0;
            kodeVoucherTerpakaiHidden.value = '';
            calculateTotal();
        });
    });
});
</script>
</body>
</html>