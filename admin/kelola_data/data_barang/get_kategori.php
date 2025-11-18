<?php
header('Content-Type: application/json');
require_once '../../../config/koneksi.php'; // Pastikan path ini benar

// Periksa apakah koneksi berhasil
if (!$koneksi) {
    // Tangani kesalahan koneksi database
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

// 1. Ambil semua kategori yang sudah ada
$query_select = "SELECT id, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC";
$result_select = mysqli_query($koneksi, $query_select);

$categories = [];
if ($result_select) { // Pastikan query berhasil
    while ($row = mysqli_fetch_assoc($result_select)) {
        $categories[] = $row;
    }
} else {
    // Tangani kesalahan query select
    echo json_encode(['error' => 'Failed to query categories: ' . mysqli_error($koneksi)]);
    exit();
}

// 2. Kalau belum ada kategori, insert default
if (empty($categories)) {
    $defaultCategories = [
        "Semen & Perekat",
        "Cat & Perlengkapan",
        "Besi & Baja",
        "Kayu & Plywood",
        "Keramik & Granit",
        "Pipa & Fitting",
        "Alat-alat Konstruksi",
        "Listrik & Penerangan",
        "Sanitasi",
        "Bahan Bangunan Lainnya"
    ];

    // Gunakan transaksi untuk memastikan semua insert berhasil atau tidak sama sekali
    mysqli_begin_transaction($koneksi);
    $all_inserts_successful = true;

    // Persiapkan statement untuk insert
    // Kita hanya menginsert nama_kategori karena kolom lain punya nilai default atau bisa NULL
    $stmt_insert = $koneksi->prepare("INSERT INTO tb_kategori (nama_kategori) VALUES (?)");

    if ($stmt_insert) {
        foreach ($defaultCategories as $category) {
            $stmt_insert->bind_param("s", $category);
            if (!$stmt_insert->execute()) {
                $all_inserts_successful = false;
                break; // Hentikan jika ada insert yang gagal
            }
        }
        $stmt_insert->close();
    } else {
        $all_inserts_successful = false;
        error_log("Failed to prepare insert statement: " . mysqli_error($koneksi)); // Log error
    }


    if ($all_inserts_successful) {
        mysqli_commit($koneksi); // Komit transaksi jika semua berhasil
        // Ambil lagi datanya setelah insert
        $result_select = mysqli_query($koneksi, $query_select);
        $categories = [];
        if ($result_select) {
            while ($row = mysqli_fetch_assoc($result_select)) {
                $categories[] = $row;
            }
        }
    } else {
        mysqli_rollback($koneksi); // Rollback jika ada yang gagal
        // Tangani kasus di mana insert default gagal
        error_log("Failed to insert default categories. Rolling back transaction.");
        // Mungkin Anda ingin mengembalikan array kosong atau pesan error di sini
        echo json_encode(['error' => 'Failed to initialize default categories.']);
        exit();
    }
}

// Mengembalikan data kategori dalam format JSON
echo json_encode($categories);

// Tutup koneksi database
mysqli_close($koneksi);
?>
