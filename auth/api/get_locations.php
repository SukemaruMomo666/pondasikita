<?php
// /api/get_locations.php

// Mengatur header agar output selalu berupa JSON
header('Content-Type: application/json');

// Sesuaikan path ke file koneksi Anda
require_once __DIR__ . '/../../config/koneksi.php'; // Sesuaikan path ini

// Ambil parameter dari URL dengan aman
$type = $_GET['type'] ?? '';
$parent_id = (int)($_GET['parent_id'] ?? 0);

$data = [];
$query = "";

// Tentukan query berdasarkan tipe yang diminta
if ($type === 'kota' && $parent_id > 0) {
    // Nama tabel di database Anda mungkin `regencies` bukan `cities`
    $query = "SELECT id, name FROM cities WHERE province_id = ? ORDER BY name ASC";
} elseif ($type === 'kecamatan' && $parent_id > 0) {
    // Nama tabel di database Anda mungkin `districts`
    $query = "SELECT id, name FROM districts WHERE city_id = ? ORDER BY name ASC";
}

// Hanya jalankan query jika valid
if ($query !== "") {
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

// Selalu outputkan JSON yang valid, meskipun datanya kosong
echo json_encode($data);
?>