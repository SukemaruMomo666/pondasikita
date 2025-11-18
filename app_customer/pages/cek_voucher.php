<?php
include '../config/koneksi.php';
header('Content-Type: application/json');

// Ambil data yang dikirim JavaScript
$kode_voucher = $_POST['kode_voucher'] ?? '';
$subtotal = floatval($_POST['subtotal'] ?? 0);

if (empty($kode_voucher)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode voucher tidak boleh kosong.']);
    exit;
}

// 1. Cari voucher di database
$stmt = $koneksi->prepare("SELECT * FROM vouchers WHERE kode_voucher = ?");
$stmt->bind_param("s", $kode_voucher);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Kode voucher tidak valid.']);
    exit;
}

$voucher = $result->fetch_assoc();

// 2. Lakukan validasi
if ($voucher['status'] !== 'AKTIF') {
    echo json_encode(['status' => 'error', 'message' => 'Voucher ini sudah tidak aktif.']);
    exit;
}

if (new DateTime() > new DateTime($voucher['tanggal_berakhir'])) {
    echo json_encode(['status' => 'error', 'message' => 'Voucher sudah kedaluwarsa.']);
    exit;
}

if ($voucher['kuota_terpakai'] >= $voucher['kuota']) {
    echo json_encode(['status' => 'error', 'message' => 'Kuota untuk voucher ini sudah habis.']);
    exit;
}

if ($subtotal < $voucher['min_pembelian']) {
    $min_pembelian_formatted = 'Rp' . number_format($voucher['min_pembelian'], 0, ',', '.');
    echo json_encode(['status' => 'error', 'message' => 'Minimal pembelian untuk voucher ini adalah ' . $min_pembelian_formatted]);
    exit;
}

// 3. Jika semua validasi lolos, hitung diskon
$diskon = 0;
if ($voucher['tipe_diskon'] == 'RUPIAH') {
    $diskon = floatval($voucher['nilai_diskon']);
} elseif ($voucher['tipe_diskon'] == 'PERSEN') {
    $diskon = (floatval($voucher['nilai_diskon']) / 100) * $subtotal;
    // Cek apakah ada batas maksimal diskon
    if ($voucher['maks_diskon'] > 0 && $diskon > $voucher['maks_diskon']) {
        $diskon = floatval($voucher['maks_diskon']);
    }
}

// 4. Kirim respon sukses
echo json_encode([
    'status' => 'success',
    'message' => 'Voucher berhasil digunakan!',
    'diskon' => $diskon,
    'kode_voucher' => $kode_voucher
]);

$stmt->close();
$koneksi->close();
?>