<?php
// actions/proses_checkout.php

// Pastikan error reporting diaktifkan untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Selalu mulai dengan session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sesuaikan path ke file koneksi dan autoload Midtrans
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

// --- FUNGSI BANTUAN ---
function redirect_with_feedback($tipe, $pesan, $url) {
    $_SESSION['feedback'] = ['tipe' => $tipe, 'pesan' => $pesan];
    header("Location: " . $url);
    exit();
}

// Helper untuk mendapatkan Base URL otomatis (http/https + domain)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST']; // Contoh: http://localhost atau https://pondasikita.com
// Sesuaikan folder project jika ada (misal: /pondasikita)
$project_folder = '/pondasikita'; // KOSONGKAN string ini jika di hosting root domain, ISI jika di localhost/folder
$base_url .= $project_folder;


// 1. KONFIGURASI MIDTRANS
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl'; // Ganti dengan Server Key Production nanti
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// 2. VALIDASI REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_feedback('gagal', 'Metode request tidak valid.', '/index.php');
}

// 3. VALIDASI USER
$id_user = $_SESSION['user']['id'] ?? null;
if (!$id_user) {
    redirect_with_feedback('gagal', 'Sesi tidak valid, silakan login kembali.', '/auth/login_customer.php');
}

if (($_SESSION['user']['level'] ?? '') !== 'customer') {
    redirect_with_feedback('gagal', 'Hanya pelanggan yang dapat melakukan aksi ini.', '/index.php');
}

// --- PENGAMBILAN DATA INPUT ---
$catatan = trim($_POST['catatan'] ?? '');
// PERBAIKAN: Gunakan lowercase agar konsisten dengan HTML value="ambil_di_toko"
$tipe_pengambilan_form = strtolower(trim($_POST['tipe_pengambilan'] ?? 'pengiriman')); 

// Ambil Data Alamat dari Hidden Input
$shipping_label_alamat = trim($_POST['shipping_label_alamat'] ?? '');
$shipping_nama_penerima = trim($_POST['shipping_nama_penerima'] ?? '');
$shipping_telepon_penerima = trim($_POST['shipping_telepon_penerima'] ?? '');
$shipping_alamat_lengkap = trim($_POST['shipping_alamat_lengkap'] ?? '');
$shipping_kecamatan = trim($_POST['shipping_kecamatan'] ?? '');
$shipping_kota_kabupaten = trim($_POST['shipping_kota_kabupaten'] ?? '');
$shipping_provinsi = trim($_POST['shipping_provinsi'] ?? '');
$shipping_kode_pos = trim($_POST['shipping_kode_pos'] ?? '');

// --- LOGIKA ALAMAT & SUMBER TRANSAKSI ---
$sumber_transaksi_db = 'ONLINE'; 

// PERBAIKAN LOGIKA VALIDASI ALAMAT
if ($tipe_pengambilan_form === 'ambil_di_toko') {
    // Jika ambil di toko, kita TIMPA alamat pengiriman dengan alamat Toko Fisik
    $sumber_transaksi_db = 'OFFLINE'; 
    
    // Gunakan data user untuk nama/telepon, tapi alamat pakai alamat toko
    // (Opsional: Bisa dibuat hardcode alamat toko pusat)
    $shipping_label_alamat = "AMBIL DI TOKO";
    $shipping_alamat_lengkap = "Diambil langsung di Toko PondasiKita";
    $shipping_kecamatan = "-";
    $shipping_kota_kabupaten = "-";
    $shipping_provinsi = "-";
    $shipping_kode_pos = "-";

} else {
    // JIKA PENGIRIMAN REGULER/KARGO, WAJIB VALIDASI ALAMAT
    if (empty($shipping_alamat_lengkap) || empty($shipping_nama_penerima) || empty($shipping_telepon_penerima)) {
        redirect_with_feedback('gagal', 'Alamat pengiriman tidak lengkap. Mohon lengkapi data Anda.', '/app_customer/pages/keranjang.php');
    }
}

$metode_pembayaran_db = 'Midtrans';

// Hitung Biaya Ongkir
$shipping_costs_per_store = $_POST['shipping'] ?? [];
$total_shipping_cost_all_stores = 0;

// Jika ambil di toko, ongkir dipaksa 0
if ($tipe_pengambilan_form === 'ambil_di_toko') {
    $total_shipping_cost_all_stores = 0;
} else {
    foreach ($shipping_costs_per_store as $cost) {
        $total_shipping_cost_all_stores += (float)$cost;
    }
}

// Cek tipe pembelian
$is_direct_purchase = isset($_POST['direct_purchase']) && $_POST['direct_purchase'] == '1';

// Inisialisasi Variabel
$items_to_process = []; 
$item_details_for_midtrans = []; 
$total_produk_subtotal_from_post = floatval($_POST['total_produk_subtotal'] ?? 0); 

// MULAI TRANSAKSI DATABASE
$koneksi->begin_transaction();

try {
    // --- TAHAP 1: VALIDASI STOK & HARGA ---
    
    if ($is_direct_purchase) {
        // === LOGIKA BELI LANGSUNG ===
        $product_id = intval($_POST['product_id'] ?? 0);
        $jumlah_diminta = intval($_POST['jumlah'] ?? 1);

        if ($product_id < 1 || $jumlah_diminta < 1) throw new Exception("Data pembelian langsung tidak valid.");

        $q = $koneksi->prepare("SELECT id, nama_barang, harga, stok, stok_di_pesan, toko_id FROM tb_barang WHERE id=?");
        $q->bind_param("i", $product_id);
        $q->execute();
        $barang = $q->get_result()->fetch_assoc();
        $q->close();

        if (!$barang) throw new Exception("Produk tidak ditemukan.");
        
        // Validasi Stok
        $stok_tersedia = $barang['stok'] - ($barang['stok_di_pesan'] ?? 0);
        if ($stok_tersedia < $jumlah_diminta) {
            throw new Exception("Stok '{$barang['nama_barang']}' habis/kurang. Sisa: {$stok_tersedia}");
        }

        $items_to_process[] = [
            'id' => $barang['id'],
            'quantity' => $jumlah_diminta,
            'price' => floatval($barang['harga']),
            'toko_id' => $barang['toko_id'],
            'nama_barang' => $barang['nama_barang']
        ];

        $item_details_for_midtrans[] = [
            'id' => $barang['id'],
            'price' => (int)floatval($barang['harga']),
            'quantity' => $jumlah_diminta,
            'name' => substr($barang['nama_barang'], 0, 50) // Midtrans max name length limit safe
        ];
        
        $total_produk_subtotal_calculated = floatval($barang['harga']) * $jumlah_diminta;

    } else {
        // === LOGIKA DARI KERANJANG ===
        $selected_keranjang_ids = $_POST['selected_items'] ?? [];
        if (empty($selected_keranjang_ids)) throw new Exception("Tidak ada produk yang dipilih.");

        $sanitized_ids = array_map('intval', array_filter($selected_keranjang_ids, 'is_numeric'));
        if (empty($sanitized_ids)) throw new Exception("Format item keranjang invalid.");

        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        $types = 'i' . str_repeat('i', count($sanitized_ids));

        // Query join keranjang & barang
        $sql = "SELECT k.id as keranjang_id, b.id as barang_id, k.jumlah, b.nama_barang, b.harga, b.stok, b.stok_di_pesan, b.toko_id 
                FROM tb_keranjang k 
                JOIN tb_barang b ON k.barang_id = b.id 
                WHERE k.user_id=? AND k.id IN ($placeholders)";
        
        $stmt = $koneksi->prepare($sql);
        
        // Bind params dinamis
        $bind_params = array_merge([&$id_user], $sanitized_ids);
        // Trik PHP 8 untuk call_user_func_array dengan referensi
        $refs = [];
        foreach($bind_params as $key => $value) $refs[$key] = &$bind_params[$key];
        
        array_unshift($refs, $types); // Masukkan string type di awal
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $res = $stmt->get_result();
        $total_produk_subtotal_calculated = 0;

        while ($r = $res->fetch_assoc()) {
            $stok_tersedia = $r['stok'] - ($r['stok_di_pesan'] ?? 0);
            if ($stok_tersedia < $r['jumlah']) {
                throw new Exception("Stok '{$r['nama_barang']}' tidak mencukupi. Sisa: {$stok_tersedia}");
            }

            $items_to_process[] = [
                'id' => $r['barang_id'],
                'quantity' => intval($r['jumlah']),
                'price' => floatval($r['harga']),
                'toko_id' => $r['toko_id'],
                'nama_barang' => $r['nama_barang']
            ];
            
            $item_details_for_midtrans[] = [
                'id' => $r['barang_id'],
                'price' => (int)floatval($r['harga']),
                'quantity' => intval($r['jumlah']),
                'name' => substr($r['nama_barang'], 0, 50)
            ];
            
            $total_produk_subtotal_calculated += floatval($r['harga']) * intval($r['jumlah']);
        }
        $stmt->close();
    }

    if (empty($items_to_process)) throw new Exception("Gagal memproses item.");

    // --- TAHAP 2: HITUNG VOUCHER ---
    $diskon_voucher = 0;
    $kode_voucher_terpakai = trim($_POST['kode_voucher_terpakai'] ?? '');
    $id_voucher_terpakai = null;

    if (!empty($kode_voucher_terpakai)) {
        $stmt_v = $koneksi->prepare("SELECT id, kode_voucher, tipe_diskon, nilai_diskon, maks_diskon, min_pembelian, kuota, kuota_terpakai FROM vouchers WHERE kode_voucher = ? AND status = 'AKTIF' AND tanggal_berakhir >= NOW()");
        $stmt_v->bind_param("s", $kode_voucher_terpakai);
        $stmt_v->execute();
        $res_v = $stmt_v->get_result();

        if ($res_v->num_rows > 0) {
            $v = $res_v->fetch_assoc();
            if ($v['kuota_terpakai'] >= $v['kuota']) throw new Exception("Voucher habis.");
            if ($total_produk_subtotal_calculated < $v['min_pembelian']) throw new Exception("Minimal pembelian kurang.");

            if ($v['tipe_diskon'] == 'RUPIAH') {
                $diskon_voucher = floatval($v['nilai_diskon']);
            } else {
                $diskon_voucher = (floatval($v['nilai_diskon']) / 100) * $total_produk_subtotal_calculated;
                if ($v['maks_diskon'] > 0 && $diskon_voucher > $v['maks_diskon']) {
                    $diskon_voucher = floatval($v['maks_diskon']);
                }
            }
            $id_voucher_terpakai = $v['id'];

            // Tambahkan item negatif ke midtrans (Diskon)
            $item_details_for_midtrans[] = [
                'id' => 'VOUCHER',
                'price' => -((int)$diskon_voucher),
                'quantity' => 1,
                'name' => 'Diskon Voucher'
            ];
        } else {
            $kode_voucher_terpakai = null; // Voucher tidak valid
        }
        $stmt_v->close();
    }

    // --- TAHAP 3: HITUNG TOTAL FINAL ---
    $total_final = ($total_produk_subtotal_calculated - $diskon_voucher) + $total_shipping_cost_all_stores;
    if ($total_final < 1) $total_final = 1; // Midtrans minimal 1 rupiah

    // Tambahkan Ongkir ke Midtrans Item Details
    if ($total_shipping_cost_all_stores > 0) {
        $item_details_for_midtrans[] = [
            'id' => 'SHIPPING',
            'price' => (int)$total_shipping_cost_all_stores,
            'quantity' => 1,
            'name' => 'Biaya Pengiriman'
        ];
    }

    // --- TAHAP 4: UPDATE STOK "DI PESAN" ---
    $stmt_stok = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan + ? WHERE id = ?");
    foreach ($items_to_process as $item) {
        $stmt_stok->bind_param("ii", $item['quantity'], $item['id']);
        $stmt_stok->execute();
    }
    $stmt_stok->close();

    // --- TAHAP 5: INSERT TRANSAKSI HEADER ---
    $kode_invoice = 'TRX-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
    $status_pembayaran = 'pending';
    $status_pesanan = 'menunggu_pembayaran';
    $deadline = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 Jam

    $sql_trx = "INSERT INTO tb_transaksi (
        kode_invoice, sumber_transaksi, user_id, total_harga_produk, total_diskon, total_final,
        metode_pembayaran, status_pembayaran, status_pesanan_global, payment_deadline,
        shipping_label_alamat, shipping_nama_penerima, shipping_telepon_penerima,
        shipping_alamat_lengkap, shipping_kecamatan, shipping_kota_kabupaten, shipping_provinsi, shipping_kode_pos,
        catatan, voucher_digunakan, biaya_pengiriman, tanggal_transaksi
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt_t = $koneksi->prepare($sql_trx);
    // Total binding parameters: 21
    $stmt_t->bind_param("ssiddsssssssssssssssd", 
        $kode_invoice, $sumber_transaksi_db, $id_user, $total_produk_subtotal_calculated, $diskon_voucher, $total_final,
        $metode_pembayaran_db, $status_pembayaran, $status_pesanan, $deadline,
        $shipping_label_alamat, $shipping_nama_penerima, $shipping_telepon_penerima,
        $shipping_alamat_lengkap, $shipping_kecamatan, $shipping_kota_kabupaten, $shipping_provinsi, $shipping_kode_pos,
        $catatan, $kode_voucher_terpakai, $total_shipping_cost_all_stores
    );
    $stmt_t->execute();
    $id_transaksi = $stmt_t->insert_id;
    $stmt_t->close();

    // --- TAHAP 6: INSERT DETAIL TRANSAKSI ---
    $stmt_d = $koneksi->prepare("INSERT INTO tb_detail_transaksi (transaksi_id, toko_id, barang_id, nama_barang_saat_transaksi, harga_saat_transaksi, jumlah, subtotal, metode_pengiriman, biaya_pengiriman_item, status_pesanan_item) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($items_to_process as $item) {
        $subtotal_item = $item['price'] * $item['quantity'];
        $ongkir_item = (float)($shipping_costs_per_store[$item['toko_id']] ?? 0);
        
        // PERBAIKAN LOGIKA ENUM
        $metode_kirim_detail = ($tipe_pengambilan_form === 'ambil_di_toko') ? 'DIAMBIL' : 'DIKIRIM';
        $status_item_awal = 'diproses'; 

        $stmt_d->bind_param("iiisidiids", 
            $id_transaksi, $item['toko_id'], $item['id'], $item['nama_barang'],
            $item['price'], $item['quantity'], $subtotal_item,
            $metode_kirim_detail, $ongkir_item, $status_item_awal
        );
        $stmt_d->execute();
    }
    $stmt_d->close();

    // --- TAHAP 7: HAPUS KERANJANG ---
    if (!$is_direct_purchase && !empty($sanitized_ids)) {
        $del_placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        $stmt_del = $koneksi->prepare("DELETE FROM tb_keranjang WHERE user_id = ? AND id IN ($del_placeholders)");
        
        $del_params = array_merge([&$id_user], $sanitized_ids);
        $refs_del = [];
        foreach($del_params as $k => $v) $refs_del[$k] = &$del_params[$k];
        array_unshift($refs_del, $types); // Reuse types string from earlier
        
        call_user_func_array([$stmt_del, 'bind_param'], $refs_del);
        $stmt_del->execute();
        $stmt_del->close();
    }

    // --- TAHAP 8: REQUEST SNAP TOKEN MIDTRANS ---
    $customer_details = [
        'first_name' => $shipping_nama_penerima,
        'email' => $_SESSION['user']['email'] ?? 'user@example.com',
        'phone' => $shipping_telepon_penerima,
        'billing_address' => ['address' => $shipping_alamat_lengkap, 'city' => $shipping_kota_kabupaten, 'postal_code' => $shipping_kode_pos],
        'shipping_address' => ['address' => $shipping_alamat_lengkap, 'city' => $shipping_kota_kabupaten, 'postal_code' => $shipping_kode_pos]
    ];

    $params_midtrans = [
        'transaction_details' => [
            'order_id' => $kode_invoice,
            'gross_amount' => (int)$total_final,
        ],
        'item_details' => $item_details_for_midtrans,
        'customer_details' => $customer_details,
        'callbacks' => [
            'finish' => $base_url . '/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi,
            'error' => $base_url . '/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi . '&status=error',
            'pending' => $base_url . '/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi . '&status=pending'
        ]
    ];

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($params_midtrans);
    } catch (Exception $e) {
        throw new Exception("Midtrans Error: " . $e->getMessage());
    }

    // Update Snap Token ke Database
    $stmt_token = $koneksi->prepare("UPDATE tb_transaksi SET snap_token = ? WHERE id = ?");
    $stmt_token->bind_param("si", $snapToken, $id_transaksi);
    $stmt_token->execute();
    $stmt_token->close();

    // Commit Semua Transaksi
    $koneksi->commit();

    // --- REDIRECT SUKSES ---
    $url_redirect = '/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi;
    if ($tipe_pengambilan_form === 'ambil_di_toko') {
        $url_redirect .= '&pickup_success=1';
    }
    
    redirect_with_feedback('sukses', 'Pesanan berhasil dibuat!', $url_redirect);

} catch (Exception $e) {
    $koneksi->rollback();
    error_log("Checkout Gagal: " . $e->getMessage());
    
    // Kembalikan user ke halaman checkout atau keranjang dengan pesan error
    $referer = $_SERVER['HTTP_REFERER'] ?? '/app_customer/pages/keranjang.php';
    redirect_with_feedback('gagal', 'Gagal memproses pesanan: ' . $e->getMessage(), $referer);
}
?>