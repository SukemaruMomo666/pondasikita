<?php
include '../../../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($koneksi) || $koneksi->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
    exit;
}

// Ambil semua data dari POST
$voucherId = $_POST['voucherId'] ?? null;
$kode_voucher = $_POST['kode_voucher'];
$deskripsi = $_POST['deskripsi'];
$tipe_diskon = $_POST['tipe_diskon'];
$nilai_diskon = $_POST['nilai_diskon'];
$maks_diskon = !empty($_POST['maks_diskon']) ? $_POST['maks_diskon'] : NULL;
$min_pembelian = $_POST['min_pembelian'];
$kuota = $_POST['kuota'];
$tanggal_berakhir = date('Y-m-d H:i:s', strtotime($_POST['tanggal_berakhir'] . ' 23:59:59'));

// Cek apakah ini proses UPDATE atau INSERT
if (!empty($voucherId)) {
    // ----- INI LOGIKA UNTUK UPDATE -----
    $sql = "UPDATE vouchers SET 
                deskripsi = ?, 
                tipe_diskon = ?, 
                nilai_diskon = ?, 
                maks_diskon = ?, 
                min_pembelian = ?, 
                kuota = ?, 
                tanggal_berakhir = ? 
            WHERE id = ?";
            
    $stmt = $koneksi->prepare($sql);
    // Tipe data: s s d d i s i (deskripsi, tipe, nilai, maks, min, kuota, tgl, id)
    $stmt->bind_param('ssddisi', $deskripsi, $tipe_diskon, $nilai_diskon, $maks_diskon, $min_pembelian, $kuota, $tanggal_berakhir, $voucherId);
    $pesanSukses = 'Voucher berhasil diperbarui!';

} else {
    // ----- INI LOGIKA UNTUK INSERT (YANG SUDAH ADA SEBELUMNYA) -----
    $tanggal_mulai = date('Y-m-d H:i:s');
    $sql = "INSERT INTO vouchers (kode_voucher, deskripsi, tipe_diskon, nilai_diskon, maks_diskon, min_pembelian, kuota, tanggal_mulai, tanggal_berakhir) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('sssddiiss', $kode_voucher, $deskripsi, $tipe_diskon, $nilai_diskon, $maks_diskon, $min_pembelian, $kuota, $tanggal_mulai, $tanggal_berakhir);
    $pesanSukses = 'Voucher berhasil disimpan!';
}

// Eksekusi query
if ($stmt->execute()) {
    $id = !empty($voucherId) ? $voucherId : $koneksi->insert_id;
    
    // Ambil data terbaru untuk dikirim kembali ke JavaScript
    $result = $koneksi->query("SELECT * FROM vouchers WHERE id = $id");
    $newData = $result->fetch_assoc();
    
    echo json_encode(['status' => 'success', 'message' => $pesanSukses, 'new_data' => $newData]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
}

$stmt->close();
$koneksi->close();
?>