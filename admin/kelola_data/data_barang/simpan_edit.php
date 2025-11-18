<?php
include '../../../koneksi.php';

$id = $_POST['id'];
$nama_barang = $_POST['nama_barang'];
$kategori_id = $_POST['kategori_id'];
$harga = $_POST['harga'];
$stok = $_POST['stok'];
$deskripsi = $_POST['deskripsi'];

$gambar_barang = null;
$upload_dir = realpath(__DIR__ . '/../../../assets/uploads') . DIRECTORY_SEPARATOR;

// Hapus gambar jika diminta
if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
    $sql_old = "SELECT gambar_barang FROM tb_barang WHERE id = ?";
    $stmt_old = $koneksi->prepare($sql_old);
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $result = $stmt_old->get_result();

    if ($result->num_rows > 0) {
        $old_file = $result->fetch_assoc()['gambar_barang'];
        if ($old_file && file_exists($upload_dir . $old_file)) {
            unlink($upload_dir . $old_file);
        }
    }
    $gambar_barang = null;
}

// Proses upload gambar baru jika ada
if (isset($_FILES['gambar_barang']) && $_FILES['gambar_barang']['error'] == 0) {
    $nama_file = time() . '_' . basename($_FILES['gambar_barang']['name']);
    $target_file = $upload_dir . $nama_file;

    if (move_uploaded_file($_FILES['gambar_barang']['tmp_name'], $target_file)) {
        $gambar_barang = $nama_file;

        // Hapus gambar lama dari server jika ada
        $sql_old = "SELECT gambar_barang FROM tb_barang WHERE id = ?";
        $stmt_old = $koneksi->prepare($sql_old);
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $result = $stmt_old->get_result();

        if ($result->num_rows > 0) {
            $old_file = $result->fetch_assoc()['gambar_barang'];
            if ($old_file && file_exists($upload_dir . $old_file)) {
                unlink($upload_dir . $old_file);
            }
        }
    }
}

// Buat query update
if ($gambar_barang !== null) {
    $query = "UPDATE tb_barang SET nama_barang=?, kategori_id=?, harga=?, stok=?, deskripsi=?, gambar_barang=? WHERE id=?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("siiissi", $nama_barang, $kategori_id, $harga, $stok, $deskripsi, $gambar_barang, $id);
} else {
    $query = "UPDATE tb_barang SET nama_barang=?, kategori_id=?, harga=?, stok=?, deskripsi=? WHERE id=?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("siiisi", $nama_barang, $kategori_id, $harga, $stok, $deskripsi, $id);
}

if ($stmt->execute()) {
    header("Location: ../barang.php?pesan=berhasil_edit");
    exit();
} else {
    echo "Gagal menyimpan perubahan: " . $stmt->error;
}
?>
