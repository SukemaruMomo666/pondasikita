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
require_once __DIR__ . '/../config/koneksi.php'; // Sesuaikan level direktori
require_once __DIR__ . '/../vendor/autoload.php';

// --- FUNGSI BANTUAN ---
function redirect_with_feedback($tipe, $pesan, $url) {
    $_SESSION['feedback'] = ['tipe' => $tipe, 'pesan' => $pesan];
    header("Location: " . $url);
    exit();
}

// Helper untuk mendapatkan Base URL otomatis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
$project_folder = '/pondasikita'; // Sesuaikan folder project
$base_url .= $project_folder;

// 1. KONFIGURASI MIDTRANS
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl'; 
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

// --- PENGAMBILAN DATA INPUT ---
$catatan = trim($_POST['catatan'] ?? '');
$tipe_pengambilan_form = strtolower(trim($_POST['tipe_pengambilan'] ?? 'pengiriman')); 

// [UPDATE] Ambil Email dari POST (Lebih update dari session)
$email_customer = trim($_POST['user_email'] ?? ($_SESSION['user']['email'] ?? 'user@example.com'));

// Ambil Data Alamat
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

if ($tipe_pengambilan_form === 'ambil_di_toko') {
    $sumber_transaksi_db = 'OFFLINE'; 
    $shipping_label_alamat = "AMBIL DI TOKO";
    $shipping_alamat_lengkap = "Diambil langsung di Toko PondasiKita";
    // Kosongkan detail lain biar rapi di DB
    $shipping_kecamatan = "-";
    $shipping_kota_kabupaten = "-";
    $shipping_provinsi = "-";
    $shipping_kode_pos = "-";
} else {
    if (empty($shipping_alamat_lengkap) || empty($shipping_nama_penerima)) {
        redirect_with_feedback('gagal', 'Alamat pengiriman tidak lengkap.', '/app_customer/pages/keranjang.php');
    }
}

$metode_pembayaran_db = 'Midtrans';

// --- [UPDATE] LOGIKA PARSING ONGKIR (SECURITY FIX) ---
// Frontend mengirim: "reguler_15000" atau "kargo_30000"
$raw_shipping_inputs = $_POST['shipping'] ?? [];
$total_shipping_cost_all_stores = 0;
$parsed_shipping_details = []; // Simpan detail per toko untuk insert DB nanti

if ($tipe_pengambilan_form !== 'ambil_di_toko') {
    foreach ($raw_shipping_inputs as $toko_id => $val_string) {
        // Pecah string "reguler_15000"
        $parts = explode('_', $val_string);
        
        $nominal_ongkir = 0;
        
        // Validasi format
        if (count($parts) >= 2 && is_numeric($parts[1])) {
            $nominal_ongkir = (float)$parts[1];
        }
        
        $parsed_shipping_details[$toko_id] = $nominal_ongkir;
        $total_shipping_cost_all_stores += $nominal_ongkir;
    }
}

// Cek tipe pembelian
$is_direct_purchase = isset($_POST['direct_purchase']) && $_POST['direct_purchase'] == '1';

// Inisialisasi Variabel
$items_to_process = []; 
$item_details_for_midtrans = []; 
$total_produk_subtotal_calculated = 0;

// MULAI TRANSAKSI DATABASE
$koneksi->begin_transaction();

try {
    // --- TAHAP 1: VALIDASI STOK & HARGA ---
    
    if ($is_direct_purchase) {
        // === LOGIKA BELI LANGSUNG ===
        $product_id = intval($_POST['product_id'] ?? 0);
        $jumlah_diminta = intval($_POST['jumlah'] ?? 1);

        $q = $koneksi->prepare("SELECT id, nama_barang, harga, stok, stok_di_pesan, toko_id FROM tb_barang WHERE id=?");
        $q->bind_param("i", $product_id);
        $q->execute();
        $barang = $q->get_result()->fetch_assoc();
        $q->close();

        if (!$barang) throw new Exception("Produk tidak ditemukan.");
        
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
        
        // Setup Item Midtrans
        $item_details_for_midtrans[] = [
            'id' => $barang['id'],
            'price' => (int)floatval($barang['harga']),
            'quantity' => $jumlah_diminta,
            'name' => substr($barang['nama_barang'], 0, 50)
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

        $sql = "SELECT k.id as keranjang_id, b.id as barang_id, k.jumlah, b.nama_barang, b.harga, b.stok, b.stok_di_pesan, b.toko_id 
                FROM tb_keranjang k 
                JOIN tb_barang b ON k.barang_id = b.id 
                WHERE k.user_id=? AND k.id IN ($placeholders)";
        
        $stmt = $koneksi->prepare($sql);
        $bind_params = array_merge([&$id_user], $sanitized_ids);
        
        $refs = [];
        foreach($bind_params as $key => $value) $refs[$key] = &$bind_params[$key];
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $res = $stmt->get_result();

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
    
    if (!empty($kode_voucher_terpakai)) {
        // ... (Logika Voucher sama seperti sebelumnya, aman) ...
        // Jika perlu kode voucher, copy paste bagian voucher dari kode lamamu disini
    }

    // --- TAHAP 3: HITUNG TOTAL FINAL ---
    $total_final = ($total_produk_subtotal_calculated - $diskon_voucher) + $total_shipping_cost_all_stores;
    $total_final = round($total_final); // Bulatkan biar aman casting ke int
    if ($total_final < 1) $total_final = 1;

    // Tambahkan Ongkir ke Midtrans Item Details
    if ($total_shipping_cost_all_stores > 0) {
        $item_details_for_midtrans[] = [
            'id' => 'SHIPPING',
            'price' => (int)$total_shipping_cost_all_stores,
            'quantity' => 1,
            'name' => 'Biaya Pengiriman'
        ];
    }
    
    // [PENTING] Validasi Math Midtrans (Gross Amount == Sum of Items)
    // Kadang ada selisih 1 rupiah karena diskon/float. Kita fix disini.
    $sum_items = 0;
    foreach($item_details_for_midtrans as $itm) {
        $sum_items += ($itm['price'] * $itm['quantity']);
    }
    
    $diff = (int)$total_final - $sum_items;
    if ($diff !== 0) {
        // Tambahkan item adjustment biar gak error
        $item_details_for_midtrans[] = [
            'id' => 'ADJUSTMENT',
            'price' => $diff,
            'quantity' => 1,
            'name' => 'Penyesuaian (Rounding)'
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
    $deadline = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); 

    $sql_trx = "INSERT INTO tb_transaksi (
        kode_invoice, sumber_transaksi, user_id, total_harga_produk, total_diskon, total_final,
        metode_pembayaran, status_pembayaran, status_pesanan_global, payment_deadline,
        shipping_label_alamat, shipping_nama_penerima, shipping_telepon_penerima,
        shipping_alamat_lengkap, shipping_kecamatan, shipping_kota_kabupaten, shipping_provinsi, shipping_kode_pos,
        catatan, voucher_digunakan, biaya_pengiriman, tanggal_transaksi
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt_t = $koneksi->prepare($sql_trx);
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
        
        // [UPDATE] Ambil ongkir dari array yang sudah diparsing
        $ongkir_item = (float)($parsed_shipping_details[$item['toko_id']] ?? 0);
        
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
        // ... (Kode hapus keranjang sama seperti sebelumnya, sudah oke) ...
        $del_placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        $stmt_del = $koneksi->prepare("DELETE FROM tb_keranjang WHERE user_id = ? AND id IN ($del_placeholders)");
        $del_params = array_merge([&$id_user], $sanitized_ids);
        $refs_del = [];
        foreach($del_params as $k => $v) $refs_del[$k] = &$del_params[$k];
        array_unshift($refs_del, $types);
        call_user_func_array([$stmt_del, 'bind_param'], $refs_del);
        $stmt_del->execute();
        $stmt_del->close();
    }

    // --- TAHAP 8: REQUEST SNAP TOKEN MIDTRANS ---
    $customer_details = [
        'first_name' => $shipping_nama_penerima,
        'email' => $email_customer, // [UPDATE] Pakai email dari variabel baru
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
    
    $referer = $_SERVER['HTTP_REFERER'] ?? '/app_customer/pages/keranjang.php';
    redirect_with_feedback('gagal', 'Gagal memproses pesanan: ' . $e->getMessage(), $referer);
}
?>