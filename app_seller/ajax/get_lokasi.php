<?php
// Pastikan tidak ada output lain selain JSON
header('Content-Type: application/json');

require_once '../config/koneksi.php'; // Sesuaikan path

$response = [];

if (isset($_POST['province_id'])) {
    $province_id = (int)$_POST['province_id'];
    $stmt = $koneksi->prepare("SELECT id, name FROM cities WHERE province_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    $stmt->close();
} elseif (isset($_POST['city_id'])) {
    $city_id = (int)$_POST['city_id'];
    $stmt = $koneksi->prepare("SELECT id, name FROM districts WHERE city_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $city_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    $stmt->close();
}

echo json_encode($response);