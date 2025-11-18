<?php
session_start();
include '../config/koneksi.php';

// Pastikan user sudah login untuk bisa melapor
if (!isset($_SESSION['user']['id'])) {
    // Redirect ke halaman login dengan pesan
    $_SESSION['pesan_error'] = "Anda harus login untuk melaporkan produk.";
    header("Location: ../auth/signin.php");
    exit;
}

// Ambil data produk dari URL
$barang_id = isset($_GET['barang_id']) ? (int)$_GET['barang_id'] : 0;
$nama_barang = isset($_GET['nama_barang']) ? htmlspecialchars(urldecode($_GET['nama_barang'])) : 'Tidak Diketahui';

if ($barang_id === 0) {
    die("ID produk tidak valid.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporkan Produk - <?= $nama_barang ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Laporkan Produk</h4>
                    </div>
                    <div class="card-body">
                        <p>Anda akan melaporkan produk:</p>
                        <h5 class="mb-4"><strong><?= $nama_barang ?></strong></h5>

                        <?php 
                        // Tampilkan pesan jika ada
                        if (isset($_SESSION['pesan_sukses'])) {
                            echo '<div class="alert alert-success">'.$_SESSION['pesan_sukses'].'</div>';
                            unset($_SESSION['pesan_sukses']);
                        }
                        if (isset($_SESSION['pesan_error_form'])) {
                            echo '<div class="alert alert-danger">'.$_SESSION['pesan_error_form'].'</div>';
                            unset($_SESSION['pesan_error_form']);
                        }
                        ?>

                        <form action="../proses/proses_laporan.php" method="POST">
                            <input type="hidden" name="barang_id" value="<?= $barang_id ?>">
                            <input type="hidden" name="user_id" value="<?= $_SESSION['user']['id'] ?>">
                            
                            <div class="mb-3">
                                <label for="alasan" class="form-label"><strong>Alasan Laporan <span class="text-danger">*</span></strong></label>
                                <select class="form-select" id="alasan" name="alasan" required>
                                    <option value="">-- Pilih Alasan --</option>
                                    <option value="Produk tidak sesuai deskripsi">Produk tidak sesuai deskripsi</option>
                                    <option value="Produk palsu/tiruan">Produk palsu/tiruan</option>
                                    <option value="Harga tidak wajar">Harga tidak wajar</option>
                                    <option value="Konten tidak pantas">Konten tidak pantas (gambar/deskripsi)</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label"><strong>Deskripsi Tambahan <span class="text-danger">*</span></strong></label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" placeholder="Jelaskan lebih detail mengenai laporan Anda..." required></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">Kirim Laporan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>