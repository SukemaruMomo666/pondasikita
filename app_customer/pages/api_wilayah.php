<?php
require '../../config/koneksi.php';

header('Content-Type: application/json');

$data = $_GET['data'] ?? '';
$id = $_GET['id'] ?? null;

$query = "";
$stmt = null;
$tabel = "";
$kolom_relasi = "";

switch ($data) {
    case 'provinsi':
        $tabel = "provinces";
        $query = "SELECT * FROM $tabel ORDER BY name ASC";
        $stmt = $koneksi->prepare($query);
        break;
    case 'kabupaten': // Frontend tetap memanggil 'kabupaten'
        $tabel = "regencies"; // tapi di backend kita query ke tabel 'regencies'
        $kolom_relasi = "province_id";
        $query = "SELECT * FROM $tabel WHERE $kolom_relasi = ? ORDER BY name ASC";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("s", $id);
        break;
    case 'kecamatan':
        $tabel = "districts";
        $kolom_relasi = "regency_id";
        $query = "SELECT * FROM $tabel WHERE $kolom_relasi = ? ORDER BY name ASC";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("s", $id);
        break;
    case 'desa':
        $tabel = "villages";
        $kolom_relasi = "district_id";
        $query = "SELECT * FROM $tabel WHERE $kolom_relasi = ? ORDER BY name ASC";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("s", $id);
        break;
    default:
        echo json_encode(['error' => 'Aksi tidak valid']);
        exit;
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $wilayah = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($wilayah);
    $stmt->close();
} else {
    echo json_encode(['error' => 'Query gagal disiapkan']);
}

$koneksi->close();
?>