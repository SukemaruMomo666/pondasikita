<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/koneksi.php';

// Sesuaikan path ke file koneksi Anda
require_once __DIR__ . '/../../../config/koneksi.php';

// Ambil parameter dari URL dengan aman
$type = $_GET['type'] ?? '';
$parent_id = (int)($_GET['parent_id'] ?? 0);

$data = [];
$query = "";

if ($type === 'kota' && $parent_id > 0) {
    $query = "SELECT id, name FROM cities WHERE province_id = ? ORDER BY name ASC";
} elseif ($type === 'kecamatan' && $parent_id > 0) {
    $query = "SELECT id, name FROM districts WHERE city_id = ? ORDER BY name ASC";
}

if ($query) {
    $stmt = $koneksi->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
}

echo json_encode($data);
