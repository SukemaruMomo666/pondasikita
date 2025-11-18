<?php
// Pastikan error reporting diaktifkan untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Aktifkan ini untuk debugging, matikan di production!

// Selalu mulai dengan session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sesuaikan path ke file koneksi dan autoload Midtrans
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Fungsi untuk mengirim feedback dan redirect
function redirect_with_feedback($tipe, $pesan, $url) {
    $_SESSION['feedback'] = ['tipe' => $tipe, 'pesan' => $pesan];
    header("Location: " . $url);
    exit(); // PENTING: Pastikan exit() selalu dipanggil setelah header()
}

// 1. KONFIGURASI MIDTRANS
\Midtrans\Config::$serverKey = 'SB-Mid-server-KfGZdmNmRhhouinEJzESiAjl';
\Midtrans\Config::$isProduction = false; // Set ke true jika di production
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// 2. VALIDASI & PENGAMBILAN DATA AWAL DARI POST
// Pastikan method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_feedback('gagal', 'Metode request tidak valid.', '/index.php');
}

$id_user = $_SESSION['user']['id'] ?? null;
if (!$id_user) {
    redirect_with_feedback('gagal', 'Sesi tidak valid, silakan login kembali.', '/auth/login_customer.php');
}

// Hanya customer yang bisa membeli
if (($_SESSION['user']['level'] ?? '') !== 'customer') {
    redirect_with_feedback('gagal', 'Hanya pelanggan yang dapat melakukan aksi ini.', $_SERVER['HTTP_REFERER'] ?? '/index.php');
}

// --- PENGAMBILAN DATA ALAMAT DARI FORM (HIDDEN INPUTS DARI CHECKOUT.PHP) ---
// Data ini dikirim dari hidden inputs di checkout.php
$shipping_label_alamat = trim($_POST['shipping_label_alamat'] ?? '');
$shipping_nama_penerima = trim($_POST['shipping_nama_penerima'] ?? '');
$shipping_telepon_penerima = trim($_POST['shipping_telepon_penerima'] ?? '');
$shipping_alamat_lengkap = trim($_POST['shipping_alamat_lengkap'] ?? '');
$shipping_kecamatan = trim($_POST['shipping_kecamatan'] ?? '');
$shipping_kota_kabupaten = trim($_POST['shipping_kota_kabupaten'] ?? '');
$shipping_provinsi = trim($_POST['shipping_provinsi'] ?? '');
$shipping_kode_pos = trim($_POST['shipping_kode_pos'] ?? '');

// Validasi alamat pengiriman yang lebih robust (sesuai dengan yang diharapkan Midtrans dan DB)
// Jika salah satu komponen alamat penting kosong, redirect ke halaman edit profil
if (empty($shipping_alamat_lengkap) || empty($shipping_provinsi) || empty($shipping_kota_kabupaten) || empty($shipping_kecamatan)) {
    redirect_with_feedback('gagal', 'Alamat pengiriman tidak lengkap. Mohon lengkapi alamat Anda.', '/app_customer/pages/profil/crud_profil/edit_profil.php');
}

// Ambil dari form POST
$catatan = trim($_POST['catatan'] ?? '');
$tipe_pengambilan_form = strtoupper(trim($_POST['tipe_pengambilan'] ?? 'pengiriman')); // Pastikan huruf besar dan bersih dari spasi

// Mapping tipe_pengambilan_form ke enum database `sumber_transaksi`
$sumber_transaksi_db = 'ONLINE'; // Default untuk transaksi dari website
if ($tipe_pengambilan_form === 'AMBIL_DI_TOKO') {
    $sumber_transaksi_db = 'OFFLINE'; // Jika diambil di toko, bisa dianggap offline atau kategorikan sendiri
    // Jika diambil di toko, alamat pengiriman bisa diisi dengan alamat toko
    // Pastikan alamat ini konsisten jika diimplementasikan secara statis atau dinamis dari DB
    $shipping_alamat_lengkap = "Toko Bangunan Agung Jaya, Jl. Raya Pagaden No. 123, Pagaden, Subang, Jawa Barat";
    $shipping_kecamatan = "Pagaden"; // Contoh
    $shipping_kota_kabupaten = "Subang"; // Contoh
    $shipping_provinsi = "JAWA BARAT"; // Contoh
    $shipping_kode_pos = ""; // Kosongkan atau isi jika ada
}

$metode_pembayaran_db = 'Midtrans'; // Semua pembayaran online via gateway kita sebut 'Midtrans'

// Retrieve shipping costs per store (from 'shipping' array in POST)
$shipping_costs_per_store = $_POST['shipping'] ?? [];
$total_shipping_cost_all_stores = 0;
foreach ($shipping_costs_per_store as $toko_id => $cost) {
    $total_shipping_cost_all_stores += (float)$cost;
}

// Determine if it's a direct purchase or from cart
$is_direct_purchase = isset($_POST['direct_purchase']) && $_POST['direct_purchase'] == '1';

// Initialize variables for items and totals
$items_to_process = []; // For internal processing
$item_details_for_midtrans = []; // For Midtrans payload
$total_produk_subtotal_from_post = floatval($_POST['total_produk_subtotal'] ?? 0); // Ambil dari hidden input di checkout.php

$koneksi->begin_transaction();

try {
    // TAHAP 1: KUMPULKAN SEMUA ITEM DAN HITUNG TOTAL
    // Verifikasi item yang dikirim dari form (untuk keamanan dan konsistensi)
    if ($is_direct_purchase) {
        $product_id = intval($_POST['product_id'] ?? 0);
        $jumlah_diminta = intval($_POST['jumlah'] ?? 1);
        if ($product_id < 1 || $jumlah_diminta < 1) {
            throw new Exception("Data pembelian langsung tidak valid.");
        }

        $q = $koneksi->prepare("SELECT id, nama_barang, harga, stok, stok_di_pesan, toko_id FROM tb_barang WHERE id=?");
        if (!$q) {
            throw new Exception("Failed to prepare statement for direct purchase: " . $koneksi->error);
        }
        $q->bind_param("i", $product_id);
        $q->execute();
        $res = $q->get_result();
        $barang = $res->fetch_assoc();
        $q->close();

        if (!$barang) {
            throw new Exception("Produk tidak ditemukan.");
        }
        if (($barang['stok'] - ($barang['stok_di_pesan'] ?? 0)) < $jumlah_diminta) {
            throw new Exception("Stok produk '{$barang['nama_barang']}' tidak mencukupi. Sisa stok: " . ($barang['stok'] - ($barang['stok_di_pesan'] ?? 0)));
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
            'price' => (int)floatval($barang['harga']), // Midtrans expects integer price
            'quantity' => $jumlah_diminta,
            'name' => $barang['nama_barang']
        ];
        // Total produk subtotal dihitung ulang di backend untuk validasi
        $total_produk_subtotal_calculated = floatval($barang['harga']) * $jumlah_diminta;

    } else {
        $selected_keranjang_ids = $_POST['selected_items'] ?? [];
        if (empty($selected_keranjang_ids)) {
            throw new Exception("Tidak ada produk yang dipilih dari keranjang.");
        }

        $sanitized_selected_ids = array_map('intval', array_filter($selected_keranjang_ids, 'is_numeric'));
        if (empty($sanitized_selected_ids)) {
            throw new Exception("Format item keranjang tidak valid.");
        }

        $placeholders = implode(',', array_fill(0, count($sanitized_selected_ids), '?'));
        $types = 'i' . str_repeat('i', count($sanitized_selected_ids));

        $stmt = $koneksi->prepare("SELECT k.id as keranjang_id, b.id as barang_id, k.jumlah, b.nama_barang, b.harga, b.stok, b.stok_di_pesan, b.toko_id FROM tb_keranjang k JOIN tb_barang b ON k.barang_id = b.id WHERE k.user_id=? AND k.id IN ($placeholders)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for cart items: " . $koneksi->error);
        }

        $bind_params_for_cart_fetch = [];
        $bind_params_for_cart_fetch[] = &$id_user;
        foreach ($sanitized_selected_ids as $key => $value) {
            $bind_params_for_cart_fetch[] = &$sanitized_selected_ids[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $bind_params_for_cart_fetch));

        $stmt->execute();
        $res = $stmt->get_result();
        $total_produk_subtotal_calculated = 0; // Reset untuk perhitungan ulang

        while ($r = $res->fetch_assoc()) {
            if (($r['stok'] - ($r['stok_di_pesan'] ?? 0)) < $r['jumlah']) {
                throw new Exception("Stok produk '{$r['nama_barang']}' tidak mencukupi. Sisa stok: " . ($r['stok'] - ($r['stok_di_pesan'] ?? 0)));
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
                'name' => $r['nama_barang']
            ];
            $total_produk_subtotal_calculated += floatval($r['harga']) * intval($r['jumlah']);
        }
        $stmt->close();
    }

    if (empty($items_to_process)) {
        throw new Exception("Tidak ada item yang dapat diproses.");
    }

    // Verifikasi subtotal yang dikirim dari frontend dengan yang dihitung di backend
    if (abs($total_produk_subtotal_from_post - $total_produk_subtotal_calculated) > 0.01) { // Toleransi kecil untuk float
        // Ini adalah potensi fraud atau error perhitungan frontend
        throw new Exception("Perhitungan subtotal tidak cocok. Mohon ulangi proses checkout.");
    }

    $diskon_voucher = 0;
    $kode_voucher_terpakai = trim($_POST['kode_voucher_terpakai'] ?? '');
    $id_voucher_terpakai = null;

    if (!empty($kode_voucher_terpakai)) { // Hanya proses jika kode voucher ada
        $stmt_voucher = $koneksi->prepare("SELECT id, kode_voucher, tipe_diskon, nilai_diskon, maks_diskon, min_pembelian, kuota, kuota_terpakai FROM vouchers WHERE kode_voucher = ? AND status = 'AKTIF' AND tanggal_berakhir >= NOW()");
        if (!$stmt_voucher) {
            throw new Exception("Failed to prepare statement for voucher: " . $koneksi->error);
        }
        $stmt_voucher->bind_param("s", $kode_voucher_terpakai);
        $stmt_voucher->execute();
        $result_voucher = $stmt_voucher->get_result();

        if ($result_voucher->num_rows > 0) {
            $voucher = $result_voucher->fetch_assoc();

            if ($voucher['kuota_terpakai'] >= $voucher['kuota']) {
                   throw new Exception("Voucher '{$kode_voucher_terpakai}' telah habis kuota.");
            }
            if ($total_produk_subtotal_calculated < $voucher['min_pembelian']) {
                throw new Exception("Minimal pembelian untuk voucher '{$kode_voucher_terpakai}' adalah Rp" . number_format($voucher['min_pembelian'], 0, ',', '.'));
            }

            if ($voucher['tipe_diskon'] == 'RUPIAH') {
                $diskon_voucher = floatval($voucher['nilai_diskon']);
            } elseif ($voucher['tipe_diskon'] == 'PERSEN') {
                $diskon_voucher = (floatval($voucher['nilai_diskon']) / 100) * $total_produk_subtotal_calculated;
                if ($voucher['maks_diskon'] > 0 && $diskon_voucher > $voucher['maks_diskon']) {
                    $diskon_voucher = floatval($voucher['maks_diskon']);
                }
            }
            $id_voucher_terpakai = $voucher['id'];

            // Tambahkan diskon sebagai item negatif ke Midtrans
            $item_details_for_midtrans[] = [
                'id' => 'VOUCHER-' . $voucher['kode_voucher'],
                'price' => -((int)$diskon_voucher), // Pastikan negatif dan integer
                'quantity' => 1,
                'name' => 'Diskon (' . $voucher['kode_voucher'] . ')'
            ];
        } else {
            // Jika voucher tidak valid, reset kode voucher terpakai
            $kode_voucher_terpakai = null;
        }
        $stmt_voucher->close();
    }

    $total_final = ($total_produk_subtotal_calculated - $diskon_voucher);
    if ($tipe_pengambilan_form !== 'ambil_di_toko') {
        $total_final += $total_shipping_cost_all_stores;
        if ($total_shipping_cost_all_stores > 0) {
            // Tambahkan biaya pengiriman sebagai item ke Midtrans
            $item_details_for_midtrans[] = [
                'id' => 'SHIPPING-COST',
                'price' => (int)$total_shipping_cost_all_stores,
                'quantity' => 1,
                'name' => 'Biaya Pengiriman'
            ];
        }
    }

    if ($total_final < 0) $total_final = 0; // Pastikan total tidak negatif

    // --- TAHAN STOK (UPDATE stok_di_pesan) ---
    $stmt_update_stok = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = stok_di_pesan + ? WHERE id = ?");
    if (!$stmt_update_stok) {
        throw new Exception("Failed to prepare statement for stock update: " . $koneksi->error);
    }
    foreach ($items_to_process as $item) {
        $stmt_update_stok->bind_param("ii", $item['quantity'], $item['id']);
        $stmt_update_stok->execute();
    }
    $stmt_update_stok->close();

    // --- SIMPAN TRANSAKSI UTAMA KE tb_transaksi ---
    $kode_invoice = 'TRX-' . date('YmdHis') . '-' . mt_rand(1000, 9999); // Format kode invoice lebih unik
    $status_pembayaran_db = 'pending';
    $status_pesanan_global_db = 'menunggu_pembayaran';
    $payment_deadline = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 jam dari sekarang

    $stmt_transaksi = $koneksi->prepare(
        "INSERT INTO tb_transaksi
        (kode_invoice, sumber_transaksi, user_id,
         total_harga_produk, total_diskon, total_final,
         metode_pembayaran, status_pembayaran, status_pesanan_global, payment_deadline,
         shipping_label_alamat, shipping_nama_penerima, shipping_telepon_penerima,
         shipping_alamat_lengkap, shipping_kecamatan, shipping_kota_kabupaten, shipping_provinsi, shipping_kode_pos,
         catatan, voucher_digunakan, biaya_pengiriman, tanggal_transaksi)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    if (!$stmt_transaksi) {
        throw new Exception("Failed to prepare statement for transaction insert: " . $koneksi->error);
    }

    // Bind parameters untuk tb_transaksi (sesuai dengan skema database Anda)
    // Urutan:
    // 1. kode_invoice (s)
    // 2. sumber_transaksi (s)
    // 3. user_id (i)
    // 4. total_harga_produk (d)
    // 5. total_diskon (d)
    // 6. total_final (d)
    // 7. metode_pembayaran (s)
    // 8. status_pembayaran (s)
    // 9. status_pesanan_global (s)
    // 10. payment_deadline (s) -> DATETIME
    // 11. shipping_label_alamat (s)
    // 12. shipping_nama_penerima (s)
    // 13. shipping_telepon_penerima (s)
    // 14. shipping_alamat_lengkap (s)
    // 15. shipping_kecamatan (s)
    // 16. shipping_kota_kabupaten (s)
    // 17. shipping_provinsi (s)
    // 18. shipping_kode_pos (s)
    // 19. catatan (s)
    // 20. voucher_digunakan (s)
    // 21. biaya_pengiriman (d)

    $stmt_transaksi->bind_param(
        "ssiddsssssssssssssssd", // Harusnya ada 21 karakter di sini
        $kode_invoice,
        $sumber_transaksi_db,
        $id_user,
        $total_produk_subtotal_calculated, // total_harga_produk
        $diskon_voucher, // total_diskon
        $total_final, // total_final
        $metode_pembayaran_db,
        $status_pembayaran_db,
        $status_pesanan_global_db,
        $payment_deadline,
        $shipping_label_alamat,
        $shipping_nama_penerima,
        $shipping_telepon_penerima,
        $shipping_alamat_lengkap,
        $shipping_kecamatan,
        $shipping_kota_kabupaten,
        $shipping_provinsi,
        $shipping_kode_pos,
        $catatan,
        $kode_voucher_terpakai,
        $total_shipping_cost_all_stores // biaya_pengiriman
    );

    $stmt_transaksi->execute();
    $id_transaksi = $stmt_transaksi->insert_id;
    if ($id_transaksi === 0) {
        throw new Exception("Gagal membuat data transaksi utama. Error: " . $stmt_transaksi->error);
    }
    $stmt_transaksi->close();

    // --- SIMPAN DETAIL TRANSAKSI KE tb_detail_transaksi ---
    $stmt_detail = $koneksi->prepare("INSERT INTO tb_detail_transaksi(transaksi_id, toko_id, barang_id, nama_barang_saat_transaksi, harga_saat_transaksi, jumlah, subtotal, metode_pengiriman, biaya_pengiriman_item, status_pesanan_item) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_detail) {
        throw new Exception("Failed to prepare statement for detail transaction: " . $koneksi->error);
    }
    foreach ($items_to_process as $item) {
        $item_subtotal = $item['price'] * $item['quantity'];
        // Biaya pengiriman per item detail (jika ada, dari POST shipping[toko_id])
        $item_shipping_cost_for_detail = (float)($shipping_costs_per_store[$item['toko_id']] ?? 0.00);

        // Pastikan nilai yang dimasukkan ke ENUM sudah bersih dan sesuai
// Pastikan nilai yang dimasukkan ke ENUM sudah bersih dan sesuai
if ($tipe_pengambilan_form === 'AMBIL_DI_TOKO') {
    $metode_pengiriman_for_detail = 'DIAMBIL';
} else {
    $metode_pengiriman_for_detail = 'DIKIRIM';
}
// Tambahkan casting eksplisit ke string dan pastikan tidak ada spasi tambahan
$metode_pengiriman_for_detail = (string)trim($metode_pengiriman_for_detail);
        $status_pesanan_item = 'diproses'; // Default status untuk item detail
// DEBUGGING: Cek nilai metode_pengiriman_for_detail sebelum bind
error_log("Metode Pengiriman untuk item: " . $item['nama_barang'] . " adalah: " . $metode_pengiriman_for_detail);
// END DEBUGGING
$stmt_detail->bind_param("iiisidiids",
       
            $id_transaksi,
            $item['toko_id'],
            $item['id'],
            $item['nama_barang'],
            $item['price'],
            $item['quantity'],
            $item_subtotal,
            $metode_pengiriman_for_detail,
            $item_shipping_cost_for_detail,
            $status_pesanan_item
        );
        $stmt_detail->execute();
    }
    $stmt_detail->close();

    // --- HAPUS ITEM DARI KERANJANG (JIKA BUKAN PEMBELIAN LANGSUNG) ---
    if (!$is_direct_purchase && !empty($sanitized_selected_ids)) {
        $delete_placeholders = implode(',', array_fill(0, count($sanitized_selected_ids), '?'));
        $stmt_delete_cart = $koneksi->prepare("DELETE FROM tb_keranjang WHERE user_id = ? AND id IN ($delete_placeholders)");
        if (!$stmt_delete_cart) {
            throw new Exception("Failed to prepare statement for cart deletion: " . $koneksi->error);
        }

        $delete_types = 'i' . str_repeat('i', count($sanitized_selected_ids));
        $delete_params_array = [];
        $delete_params_array[] = &$id_user;
        foreach ($sanitized_selected_ids as $key => $value) {
            $delete_params_array[] = &$sanitized_selected_ids[$key];
        }
        call_user_func_array([$stmt_delete_cart, 'bind_param'], array_merge([$delete_types], $delete_params_array));

        $stmt_delete_cart->execute();
        $stmt_delete_cart->close();
    }

    // --- UPDATE KUOTA VOUCHER (JIKA ADA) ---
    if ($id_voucher_terpakai) {
        $stmt_update_kuota = $koneksi->prepare("UPDATE vouchers SET kuota_terpakai = kuota_terpakai + 1 WHERE id = ?");
        if (!$stmt_update_kuota) {
            throw new Exception("Failed to prepare statement for voucher quota update: " . $koneksi->error);
        }
        $stmt_update_kuota->bind_param("i", $id_voucher_terpakai);
        $stmt_update_kuota->execute();
        // Pastikan close() dipanggil HANYA jika statement berhasil dibuat dan dieksekusi
        $stmt_update_kuota->close();
    }

    // --- BUAT DAN SIMPAN SNAP TOKEN MIDTRANS ---
    $customer_name_for_midtrans = $shipping_nama_penerima;
    $customer_email_for_midtrans = $_SESSION['user']['email'] ?? 'email@example.com';
    $customer_phone_for_midtrans = $shipping_telepon_penerima;

    $name_parts = explode(' ', $customer_name_for_midtrans);
    $first_name_midtrans = $name_parts[0];
    $last_name_midtrans = count($name_parts) > 1 ? end($name_parts) : '';

    $address_details_for_midtrans = [
        'first_name' => $first_name_midtrans,
        'last_name' => $last_name_midtrans,
        'address' => $shipping_alamat_lengkap,
        'city' => $shipping_kota_kabupaten,
        'postal_code' => $shipping_kode_pos,
        'phone' => $shipping_telepon_penerima,
        'country_code' => 'IDN'
    ];

    $params = [
        'transaction_details' => [
            'order_id' => $kode_invoice,
            'gross_amount' => (int)$total_final
        ],
        'item_details' => $item_details_for_midtrans,
        'customer_details' => [
            'first_name' => $first_name_midtrans,
            'last_name' => $last_name_midtrans,
            'email' => $customer_email_for_midtrans,
            'phone' => $customer_phone_for_midtrans,
            'billing_address' => $address_details_for_midtrans,
            'shipping_address' => $address_details_for_midtrans
        ],
        'callbacks' => [
            'finish' => 'http://localhost/pondasikita/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi,
            'error' => 'http://localhost/pondasikita/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi . '&status=error',
            'pending' => 'http://localhost/pondasikita/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi . '&status=pending'
        ]
    ];

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($params);
    } catch (\Exception $e) {
        throw new Exception("Gagal mendapatkan Snap Token dari Midtrans: " . $e->getMessage());
    }

    if (empty($snapToken)) {
        throw new Exception("Snap Token kosong dari Midtrans.");
    }

    $stmt_update_token = $koneksi->prepare("UPDATE tb_transaksi SET snap_token = ? WHERE id = ?");
    if (!$stmt_update_token) {
        throw new Exception("Failed to prepare statement for token update: " . $koneksi->error);
    }
    $stmt_update_token->bind_param("si", $snapToken, $id_transaksi);
    $stmt_update_token->execute();
    $stmt_update_token->close();

    $koneksi->commit();

    $redirect_url = '/app_customer/pages/detail_pesanan.php?id=' . $id_transaksi;
    if ($tipe_pengambilan_form === 'ambil_di_toko') {
        $redirect_url .= '&pickup_success=1';
    }
    redirect_with_feedback('sukses', 'Pesanan berhasil dibuat! Silakan lanjutkan pembayaran.', $redirect_url);

} catch (Exception $e) {
    $koneksi->rollback();
    // Log error lengkap untuk debugging
    error_log("Checkout Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\nStack Trace:\n" . $e->getTraceAsString());

    // Tampilkan error langsung di browser untuk debugging jika display_errors aktif
    echo "<pre>Fatal Error in Checkout Process:</pre>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "Line: " . htmlspecialchars($e->getLine()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit(); // Hentikan eksekusi di sini jika ingin melihat error langsung

    // redirect_with_feedback('gagal', 'Terjadi kesalahan saat memproses pesanan Anda: ' . $e->getMessage(), $_SERVER['HTTP_REFERER'] ?? '/app_customer/pages/keranjang.php');
}