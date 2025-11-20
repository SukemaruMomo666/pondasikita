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
    header("Location: /auth/login_customer.php");
    exit;
}

$id_user = $_SESSION['user']['id'];

// --- AMBIL DATA ALAMAT PROFIL ---
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
$stmt_alamat->bind_param("i", $id_user);
$stmt_alamat->execute();
$result_alamat = $stmt_alamat->get_result();
$alamat_user = $result_alamat->fetch_assoc();
$stmt_alamat->close();

// Validasi kelengkapan alamat profil (hanya warning, tidak memblokir jika pakai manual)
$is_alamat_incomplete = false;
if (!$alamat_user || empty($alamat_user['nama_penerima']) || empty($alamat_user['alamat_lengkap'])) {
    $is_alamat_incomplete = true;
}

// --- PERSIAPAN DATA JSON UNTUK JS ---
// Kita simpan data alamat profil ke array agar bisa diakses JS
$saved_address_data = [
    'label' => $alamat_user['label_alamat'] ?? 'Alamat Utama',
    'nama' => $alamat_user['nama_penerima'] ?? '',
    'telepon' => $alamat_user['telepon_penerima'] ?? '',
    'alamat' => $alamat_user['alamat_lengkap'] ?? '',
    'kecamatan' => $alamat_user['district_name'] ?? '',
    'kota' => $alamat_user['city_name'] ?? '',
    'provinsi' => $alamat_user['province_name'] ?? '',
    'kodepos' => $alamat_user['kode_pos'] ?? ''
];

// Format tampilan alamat profil
$alamat_display_html = '';
if ($alamat_user) {
    $alamat_display_html =
        '<strong>'.htmlspecialchars($alamat_user['label_alamat'] ?? 'Alamat Utama') . '</strong> (' . htmlspecialchars($alamat_user['nama_penerima'] ?? '') . ' - ' . htmlspecialchars($alamat_user['telepon_penerima'] ?? '') . ')<br>' .
        htmlspecialchars($alamat_user['alamat_lengkap']) . '<br>' .
        'Kec. ' . htmlspecialchars($alamat_user['district_name'] ?? '') . ', ' .
        htmlspecialchars($alamat_user['city_name'] ?? '') . ', ' .
        htmlspecialchars($alamat_user['province_name'] ?? '') . ' ' .
        htmlspecialchars($alamat_user['kode_pos'] ?? '');
} else {
    $alamat_display_html = '<span class="text-danger">Alamat profil belum lengkap.</span>';
}

// --- LOGIKA KERANJANG / PRODUK (SAMA SEPERTI SEBELUMNYA) ---
$items_per_toko = [];
$total_produk = 0;
$is_direct_purchase = isset($_GET['product_id']);

if ($is_direct_purchase) {
    // ... (Logika Beli Langsung - Tidak Diubah) ...
    $product_id = intval($_GET['product_id']);
    $jumlah = isset($_GET['jumlah']) ? intval($_GET['jumlah']) : 1;
    $sql = "SELECT b.id AS barang_id, b.nama_barang, b.harga, b.gambar_utama,
                   t.id AS toko_id, t.nama_toko, c.name AS kota_toko, b.stok, b.stok_di_pesan
            FROM tb_barang b JOIN tb_toko t ON b.toko_id = t.id LEFT JOIN cities c ON t.city_id = c.id WHERE b.id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $row['jumlah'] = $jumlah;
        $items_per_toko[$row['toko_id']] = [
            'nama_toko' => $row['nama_toko'], 'kota_toko' => $row['kota_toko'], 'items' => [$row]
        ];
        $total_produk += $row['harga'] * $jumlah;
    }
} else {
    // ... (Logika Keranjang - Tidak Diubah) ...
    $selected_ids = $_POST['selected_items'] ?? [];
    if (empty($selected_ids)) { header("Location: /app_customer/pages/keranjang.php"); exit; }
    
    $sanitized_ids = array_map('intval', $selected_ids);
    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $types = 'i' . str_repeat('i', count($sanitized_ids));
    $sql = "SELECT k.id AS keranjang_id, b.id AS barang_id, b.nama_barang, b.harga, b.gambar_utama, k.jumlah,
                   t.id AS toko_id, t.nama_toko, c.name AS kota_toko, b.stok, b.stok_di_pesan
            FROM tb_keranjang k JOIN tb_barang b ON k.barang_id = b.id JOIN tb_toko t ON b.toko_id = t.id LEFT JOIN cities c ON t.city_id = c.id
            WHERE k.user_id = ? AND k.id IN ($placeholders)";
    $stmt = $koneksi->prepare($sql);
    $params = array_merge([$types, $id_user], $sanitized_ids);
    // Trik bind_param dynamic dengan referensi
    $tmp = []; foreach($params as $key => $value) $tmp[$key] = &$params[$key];
    call_user_func_array([$stmt, 'bind_param'], $tmp);
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($items_per_toko[$row['toko_id']])) {
            $items_per_toko[$row['toko_id']] = ['nama_toko' => $row['nama_toko'], 'kota_toko' => $row['kota_toko'], 'items' => []];
        }
        $items_per_toko[$row['toko_id']]['items'][] = $row;
        $total_produk += $row['harga'] * $row['jumlah'];
    }
    $stmt->close();
}

$total = $total_produk;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/navbar_style.css">
    <link rel="stylesheet" href="/assets/css/checkout_page_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS Tambahan untuk Toggle Alamat */
        .address-selection { margin-bottom: 15px; }
        .address-option { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; }
        .manual-address-form { display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ddd; }
        .manual-address-form.active { display: block; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-col { flex: 1; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
        .saved-address-box { background: #f9f9f9; padding: 10px; border-radius: 5px; border: 1px solid #eee; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/navbar.php'; ?>

<div class="checkout-container">
    <header class="checkout-header">
        <h1><i class="fas fa-file-invoice-dollar"></i> Rincian Pesanan</h1>
    </header>

    <main class="checkout-content">
        <div class="checkout-details">
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h3>
                </div>
                <div class="card-body">
                    <div class="address-selection">
                        <label class="address-option">
                            <input type="radio" name="address_type" value="saved" checked>
                            <strong>Gunakan Alamat Profil</strong>
                        </label>
                        
                        <div id="saved-address-container" class="saved-address-box">
                            <?= $alamat_display_html ?>
                            <div style="margin-top:5px;">
                                <a href="crud_profil/edit_profil.php" style="font-size:12px; color:#007bff;">Ubah di Profil</a>
                            </div>
                        </div>

                        <label class="address-option" style="margin-top: 15px;">
                            <input type="radio" name="address_type" value="manual">
                            <strong>Input Alamat Baru (Manual)</strong>
                        </label>
                    </div>

                    <div id="manual-address-form" class="manual-address-form">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Nama Penerima</label>
                                <input type="text" class="form-control manual-input" id="manual_nama" placeholder="Nama Lengkap">
                            </div>
                            <div class="form-col">
                                <label>No. Telepon</label>
                                <input type="text" class="form-control manual-input" id="manual_telepon" placeholder="08xxxx">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alamat Lengkap (Jalan, RT/RW)</label>
                            <textarea class="form-control manual-input" id="manual_alamat" rows="2"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <label>Provinsi</label>
                                <input type="text" class="form-control manual-input" id="manual_provinsi" placeholder="Contoh: Jawa Barat">
                            </div>
                            <div class="form-col">
                                <label>Kota/Kabupaten</label>
                                <input type="text" class="form-control manual-input" id="manual_kota" placeholder="Contoh: Bandung">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <label>Kecamatan</label>
                                <input type="text" class="form-control manual-input" id="manual_kecamatan" placeholder="Contoh: Cicendo">
                            </div>
                            <div class="form-col">
                                <label>Kode Pos</label>
                                <input type="text" class="form-control manual-input" id="manual_kodepos" placeholder="40xxx">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="checkout-form" action="/actions/proses_checkout.php" method="post">
                <input type="hidden" name="total_produk_subtotal" value="<?= $total ?>">

                <input type="hidden" name="shipping_label_alamat" id="final_label" value="<?= htmlspecialchars($alamat_user['label_alamat'] ?? 'Alamat Utama') ?>">
                <input type="hidden" name="shipping_nama_penerima" id="final_nama" value="<?= htmlspecialchars($alamat_user['nama_penerima'] ?? '') ?>">
                <input type="hidden" name="shipping_telepon_penerima" id="final_telepon" value="<?= htmlspecialchars($alamat_user['telepon_penerima'] ?? '') ?>">
                <input type="hidden" name="shipping_alamat_lengkap" id="final_alamat" value="<?= htmlspecialchars($alamat_user['alamat_lengkap'] ?? '') ?>">
                <input type="hidden" name="shipping_kecamatan" id="final_kecamatan" value="<?= htmlspecialchars($alamat_user['district_name'] ?? '') ?>">
                <input type="hidden" name="shipping_kota_kabupaten" id="final_kota" value="<?= htmlspecialchars($alamat_user['city_name'] ?? '') ?>">
                <input type="hidden" name="shipping_provinsi" id="final_provinsi" value="<?= htmlspecialchars($alamat_user['province_name'] ?? '') ?>">
                <input type="hidden" name="shipping_kode_pos" id="final_kodepos" value="<?= htmlspecialchars($alamat_user['kode_pos'] ?? '') ?>">

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-box-open"></i> Produk Dipesan</h3>
                    </div>
                    <div class="card-body">
                        <div class="product-list">
                            <?php foreach ($items_per_toko as $toko_id => $data_toko): ?>
                                <?php foreach ($data_toko['items'] as $item): $subtotal_item = $item['harga'] * $item['jumlah']; ?>
                                <div class="product-item">
                                    <div class="product-image">
                                        <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar_utama'] ?? 'default.jpg') ?>" width="60">
                                    </div>
                                    <div class="product-info">
                                        <p class="product-name"><?= htmlspecialchars($item['nama_barang']) ?></p>
                                        <p class="product-quantity">Jumlah: <?= $item['jumlah'] ?></p>
                                    </div>
                                    <div class="product-price">
                                        <p>Rp<?= number_format($subtotal_item, 0, ',', '.') ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-money-check-alt"></i> Opsi Pembayaran & Pengiriman</h3></div>
                    <div class="card-body">
                        <?php if ($is_direct_purchase): ?>
                            <input type="hidden" name="direct_purchase" value="1">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($items_per_toko[array_key_first($items_per_toko)]['items'][0]['barang_id']) ?>">
                            <input type="hidden" name="jumlah" value="<?= htmlspecialchars($items_per_toko[array_key_first($items_per_toko)]['items'][0]['jumlah']) ?>">
                        <?php else: ?>
                            <?php foreach ($sanitized_ids as $kid): ?><input type="hidden" name="selected_items[]" value="<?= $kid ?>"><?php endforeach; ?>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="tipe_pengambilan">Tipe Pengambilan</label>
                            <select name="tipe_pengambilan" id="tipe_pengambilan" class="form-control">
                                <option value="pengiriman">Dikirim ke Alamat</option>
                                <option value="ambil_di_toko">Ambil di Toko</option>
                            </select>
                        </div>

                        <?php foreach ($items_per_toko as $toko_id => $data): ?>
                            <div class="shipping-option" id="shipping-option-<?= $toko_id ?>" style="margin-top:10px;">
                                <label>Pengiriman: <?= htmlspecialchars($data['nama_toko']) ?></label>
                                <select name="shipping[<?= $toko_id ?>]" class="shipping-select form-control">
                                    <option value="15000">Reguler - Rp15.000</option>
                                    <option value="30000">Kargo - Rp30.000</option>
                                </select>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-group" style="margin-top:15px;">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <aside class="checkout-summary">
            <div class="card summary-card">
                <div class="card-header"><h3>Ringkasan</h3></div>
                <div class="card-body">
                    <div class="summary-row"><span>Subtotal</span> <span>Rp<?= number_format($total, 0, ',', '.') ?></span></div>
                    <div class="summary-row"><span>Pengiriman</span> <span id="shipping-total-display">Rp0</span></div>
                    <hr>
                    <div class="summary-total"><span>Total</span> <span id="grand-total-display">Rp<?= number_format($total, 0, ',', '.') ?></span></div>
                </div>
                <div class="card-footer">
                    <button type="submit" form="checkout-form" class="btn-submit" id="btn-buar-pesanan">Buat Pesanan</button>
                </div>
            </div>
        </aside>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. DATA ALAMAT PROFIL (DARI PHP)
    const savedAddress = <?= json_encode($saved_address_data) ?>;
    const isProfileIncomplete = <?= json_encode($is_alamat_incomplete) ?>;

    // 2. ELEMEN DOM
    const radioAddress = document.querySelectorAll('input[name="address_type"]');
    const manualFormDiv = document.getElementById('manual-address-form');
    const savedAddressDiv = document.getElementById('saved-address-container');
    const manualInputs = document.querySelectorAll('.manual-input');
    const btnSubmit = document.getElementById('btn-buar-pesanan');

    // Input Hidden Final (Yang dikirim ke server)
    const finalInputs = {
        label: document.getElementById('final_label'),
        nama: document.getElementById('final_nama'),
        telepon: document.getElementById('final_telepon'),
        alamat: document.getElementById('final_alamat'),
        kecamatan: document.getElementById('final_kecamatan'),
        kota: document.getElementById('final_kota'),
        provinsi: document.getElementById('final_provinsi'),
        kodepos: document.getElementById('final_kodepos')
    };

    // 3. LOGIKA SWITCH ALAMAT
    function handleAddressTypeChange() {
        const selectedType = document.querySelector('input[name="address_type"]:checked').value;

        if (selectedType === 'manual') {
            // Tampilkan form manual
            manualFormDiv.classList.add('active');
            savedAddressDiv.style.opacity = '0.5';
            
            // Set Label ke Manual
            finalInputs.label.value = "Alamat Baru";
            
            // Aktifkan tombol submit (validasi manual nanti)
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Buat Pesanan & Bayar';

            // Update hidden inputs dari form manual sekarang juga
            updateHiddenFromManual();
        } else {
            // Kembali ke Profil
            manualFormDiv.classList.remove('active');
            savedAddressDiv.style.opacity = '1';

            // Kembalikan data hidden ke data profil
            finalInputs.label.value = savedAddress.label;
            finalInputs.nama.value = savedAddress.nama;
            finalInputs.telepon.value = savedAddress.telepon;
            finalInputs.alamat.value = savedAddress.alamat;
            finalInputs.kecamatan.value = savedAddress.kecamatan;
            finalInputs.kota.value = savedAddress.kota;
            finalInputs.provinsi.value = savedAddress.provinsi;
            finalInputs.kodepos.value = savedAddress.kodepos;

            // Cek validasi profil
            if (isProfileIncomplete) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Lengkapi Alamat Profil';
            } else {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Buat Pesanan & Bayar';
            }
        }
    }

    // 4. SYNC INPUT MANUAL KE HIDDEN INPUT
    function updateHiddenFromManual() {
        // Hanya update jika mode manual aktif
        if (document.querySelector('input[name="address_type"]:checked').value !== 'manual') return;

        finalInputs.nama.value = document.getElementById('manual_nama').value;
        finalInputs.telepon.value = document.getElementById('manual_telepon').value;
        finalInputs.alamat.value = document.getElementById('manual_alamat').value;
        finalInputs.kecamatan.value = document.getElementById('manual_kecamatan').value;
        finalInputs.kota.value = document.getElementById('manual_kota').value;
        finalInputs.provinsi.value = document.getElementById('manual_provinsi').value;
        finalInputs.kodepos.value = document.getElementById('manual_kodepos').value;
    }

    // Pasang Event Listeners
    radioAddress.forEach(radio => {
        radio.addEventListener('change', handleAddressTypeChange);
    });

    manualInputs.forEach(input => {
        input.addEventListener('input', updateHiddenFromManual);
    });

    // Validasi Form Manual saat Submit
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const selectedType = document.querySelector('input[name="address_type"]:checked').value;
        if (selectedType === 'manual') {
            if (!document.getElementById('manual_nama').value || !document.getElementById('manual_alamat').value || !document.getElementById('manual_telepon').value) {
                e.preventDefault();
                alert("Mohon lengkapi data alamat baru (Nama, Telepon, Alamat).");
            }
        }
    });

    // Jalankan sekali saat load
    handleAddressTypeChange();


    // --- LOGIKA HITUNG TOTAL (BAWAAN) ---
    const subtotal = <?= $total ?>;
    const shippingSelects = document.querySelectorAll('.shipping-select');
    const tipePengambilan = document.getElementById('tipe_pengambilan');

    function calculateTotal() {
        let shippingCost = 0;
        if (tipePengambilan.value === 'pengiriman') {
            shippingSelects.forEach(sel => shippingCost += parseInt(sel.value));
            document.querySelectorAll('.shipping-option').forEach(el => el.style.display = 'block');
        } else {
            document.querySelectorAll('.shipping-option').forEach(el => el.style.display = 'none');
        }
        
        document.getElementById('shipping-total-display').innerText = 'Rp' + shippingCost.toLocaleString('id-ID');
        document.getElementById('grand-total-display').innerText = 'Rp' + (subtotal + shippingCost).toLocaleString('id-ID');
    }

    tipePengambilan.addEventListener('change', calculateTotal);
    shippingSelects.forEach(sel => sel.addEventListener('change', calculateTotal));
    calculateTotal();
});
</script>
</body>
</html>