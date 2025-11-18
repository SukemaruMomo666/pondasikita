<?php
// Aktifkan pelaporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi ke database
require_once __DIR__ . '/../../../config/koneksi.php';

// --- BAGIAN PROSES FORM (SAAT METHOD ADALAH POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $koneksi->begin_transaction();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); // Ambil ID di awal untuk redirect jika error

    try {
        // PERUBAHAN: Ambil flag untuk clear stok yang dipesan
        $clear_stok_flag = $_POST['clear_stok_dipesan'] ?? '0';

        // PERUBAHAN: Jika flag = 1, jalankan query untuk meng-nol-kan stok_di_pesan
        if ($clear_stok_flag === '1') {
            $stmt_clear_booked = $koneksi->prepare("UPDATE tb_barang SET stok_di_pesan = 0 WHERE id = ?");
            $stmt_clear_booked->bind_param("i", $id);
            $stmt_clear_booked->execute();
        }

        // 1. Ambil semua data dari form
        $nama_barang = trim($_POST['nama_barang']);
        $kategori_id = $_POST['kategori_id'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $berat = $_POST['berat'];
        $deskripsi = $_POST['deskripsi'];
        $is_active = $_POST['is_active'];

        // Data terkait gambar
        $gambar_dihapus_ids = $_POST['hapus_gambar'] ?? [];
        $gambar_utama_id = $_POST['is_utama'] ?? null;
        $gambar_baru = $_FILES['gambar_baru'] ?? [];

        // 2. Update data teks di tabel `tb_barang`
        $stmt_update = $koneksi->prepare(
            "UPDATE tb_barang SET nama_barang=?, kategori_id=?, harga=?, stok=?, berat=?, deskripsi=?, is_active=? WHERE id=?"
        );
        $stmt_update->bind_param("sidiisii", $nama_barang, $kategori_id, $harga, $stok, $berat, $deskripsi, $is_active, $id);
        $stmt_update->execute();

        // 3. Hapus gambar (Logika ini tetap sama)
        if (!empty($gambar_dihapus_ids)) {
            $in_clause = implode(',', array_fill(0, count($gambar_dihapus_ids), '?'));
            $types = str_repeat('i', count($gambar_dihapus_ids));
            
            $stmt_get_files = $koneksi->prepare("SELECT nama_file FROM tb_gambar_barang WHERE id IN ($in_clause)");
            $stmt_get_files->bind_param($types, ...$gambar_dihapus_ids);
            $stmt_get_files->execute();
            $result_files = $stmt_get_files->get_result();
            
            while($row = $result_files->fetch_assoc()){
                $filepath = __DIR__ . '/../../../assets/uploads/' . $row['nama_file'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }

            $stmt_delete = $koneksi->prepare("DELETE FROM tb_gambar_barang WHERE id IN ($in_clause)");
            $stmt_delete->bind_param($types, ...$gambar_dihapus_ids);
            $stmt_delete->execute();
        }
        
        // 4. Validasi jumlah gambar (Logika ini tetap sama)
        $stmt_count = $koneksi->prepare("SELECT COUNT(*) as count FROM tb_gambar_barang WHERE barang_id = ?");
        $stmt_count->bind_param("i", $id);
        $stmt_count->execute();
        $current_image_count = $stmt_count->get_result()->fetch_assoc()['count'];
        $new_image_count = isset($gambar_baru['name']) ? count(array_filter($gambar_baru['name'])) : 0;
        if (($current_image_count + $new_image_count) > 5) throw new Exception("Jumlah total gambar tidak boleh lebih dari 5.");
        if (($current_image_count + $new_image_count) == 0) throw new Exception("Produk harus memiliki minimal 1 gambar.");

        // 5. Upload gambar baru (Logika ini tetap sama)
        if ($new_image_count > 0) {
            $upload_dir = __DIR__ . '/../../../assets/uploads/';
            foreach ($gambar_baru['name'] as $index => $nama_file) {
                if ($gambar_baru['error'][$index] !== UPLOAD_ERR_OK) continue;
                $file_tmp = $gambar_baru['tmp_name'][$index];
                $ext = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
                $new_filename = 'BRG-' . $id . '-' . uniqid() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;
                if (!move_uploaded_file($file_tmp, $target_path)) throw new Exception("Gagal mengunggah file baru.");
                $stmt_insert = $koneksi->prepare("INSERT INTO tb_gambar_barang (barang_id, nama_file, is_utama) VALUES (?, ?, 0)");
                $stmt_insert->bind_param("is", $id, $new_filename);
                $stmt_insert->execute();
            }
        }

        // 6. Set gambar utama (Logika ini tetap sama)
        if ($gambar_utama_id !== null) {
            $stmt_reset_utama = $koneksi->prepare("UPDATE tb_gambar_barang SET is_utama = 0 WHERE barang_id = ?");
            $stmt_reset_utama->bind_param("i", $id);
            $stmt_reset_utama->execute();
            $stmt_set_utama = $koneksi->prepare("UPDATE tb_gambar_barang SET is_utama = 1 WHERE id = ? AND barang_id = ?");
            $stmt_set_utama->bind_param("ii", $gambar_utama_id, $id);
            $stmt_set_utama->execute();
        }

        $koneksi->commit();
        header('Location: kelola_data_barang.php?status=success&message=Produk berhasil diupdate');
        exit;

    } catch (Exception $e) {
        $koneksi->rollback();
        header('Location: edit_product.php?id=' . $id . '&status=error&message=' . urlencode($e->getMessage()));
        exit;
    }
}

// --- BAGIAN FETCH DATA (Logika ini tetap sama) ---
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) die("ID produk tidak valid.");

$stmt_product = $koneksi->prepare("SELECT * FROM tb_barang WHERE id = ?");
$stmt_product->bind_param("i", $product_id);
$stmt_product->execute();
$product = $stmt_product->get_result()->fetch_assoc();
if (!$product) die("Produk tidak ditemukan.");

$stmt_images = $koneksi->prepare("SELECT * FROM tb_gambar_barang WHERE barang_id = ? ORDER BY is_utama DESC, id ASC");
$stmt_images->bind_param("i", $product_id);
$stmt_images->execute();
$images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);
$product['images'] = $images;

$categories_result = mysqli_query($koneksi, "SELECT * FROM tb_kategori");
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk - <?php echo htmlspecialchars($product['nama_barang']); ?></title>
    <link rel="stylesheet" href="../../../assets/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../../assets/css/styles.css">
    <style>
        .form-container { max-width: 900px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .image-manager-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .thumbnail-wrapper { border: 1px solid #ddd; padding: 0.5rem; border-radius: 4px; text-align: center; }
        .thumbnail-img { width: 100%; height: 120px; object-fit: cover; margin-bottom: 0.5rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Produk</h2>
        
        <?php if (isset($_GET['status'])): ?>
            <div class="alert <?php echo $_GET['status'] == 'success' ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_product.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="clear_stok_dipesan" id="clearStokDipesanFlag" value="0">
            
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Nama Barang</label><input type="text" class="form-control" name="nama_barang" value="<?php echo htmlspecialchars($product['nama_barang']); ?>" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Kategori</label><select class="form-control" name="kategori_id" required><?php foreach ($categories as $cat) { echo "<option value='{$cat['id']}'" . ($cat['id'] == $product['kategori_id'] ? ' selected' : '') . ">{$cat['nama_kategori']}</option>"; } ?></select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Harga</label><input type="number" class="form-control" name="harga" value="<?php echo $product['harga']; ?>" required></div></div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Stok Total</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="stok" id="stokInput" value="<?php echo $product['stok']; ?>" required readonly>
                            <button type="button" class="btn btn-warning" id="updateStokBtn">Update Stok</button>
                        </div>
                        <small class="form-text text-muted" id="stokDiPesanInfo">Stok yang sedang dipesan: <?php echo $product['stok_di_pesan']; ?>. Klik 'Update Stok' untuk mereset.</small>
                    </div>
                </div>

                <div class="col-md-6"><div class="form-group"><label>Berat (kg)</label><input type="number" step="0.01" class="form-control" name="berat" value="<?php echo $product['berat']; ?>" required></div></div>
                <div class="col-md-12"><div class="form-group"><label>Deskripsi</label><textarea class="form-control" name="deskripsi" rows="4"><?php echo htmlspecialchars($product['deskripsi']); ?></textarea></div></div>
                <div class="col-md-12"><div class="form-group"><label>Status</label><select class="form-control" name="is_active" required><option value="1"<?php echo $product['is_active']==1 ? ' selected':'';?>>Aktif</option><option value="0"<?php echo $product['is_active']==0 ? ' selected':'';?>>Tidak Aktif</option></select></div></div>
            </div>

            <hr>
            <h4>Kelola Gambar</h4>
            <div class="form-group">
                <label>Gambar Saat Ini</label>
                <div class="image-manager-grid">
                    <?php foreach ($product['images'] as $img): ?>
                        <div class="thumbnail-wrapper">
                            <img src="../../../assets/uploads/<?php echo htmlspecialchars($img['nama_file']); ?>" class="thumbnail-img">
                            <div class="form-check"><input class="form-check-input" type="radio" name="is_utama" value="<?php echo $img['id']; ?>" <?php echo $img['is_utama'] ? 'checked' : ''; ?>><label class="form-check-label">Jadikan Utama</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="hapus_gambar[]" value="<?php echo $img['id']; ?>"><label class="form-check-label text-danger">Hapus</label></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="gambar_baru">Tambah Gambar Baru (Maksimal 5 total gambar)</label>
                <input type="file" class="form-control" id="gambar_baru" name="gambar_baru[]" multiple accept="image/*">
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="kelola_data_barang.php" class="btn btn-light">Batal</a>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function(){
        $('#updateStokBtn').on('click', function(){
            Swal.fire({
                title: 'Konfirmasi Update Stok',
                text: "Anda akan mereset 'stok yang dipesan' menjadi 0 dan bisa mengubah stok total. Lanjutkan?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // 1. Aktifkan input stok
                    $('#stokInput').prop('readonly', false);
                    
                    // 2. Set flag bahwa kita ingin clear stok_di_pesan
                    $('#clearStokDipesanFlag').val('1');
                    
                    // 3. Update tampilan info & disable tombol
                    $('#stokDiPesanInfo').text('Stok yang dipesan telah direset. Silakan masukkan stok total yang baru.');
                    $('#stokDiPesanInfo').removeClass('text-muted').addClass('text-success');
                    $(this).prop('disabled', true).text('Stok Siap Diupdate');
                    
                    Swal.fire(
                        'Siap!',
                        'Input stok total sekarang aktif.',
                        'success'
                    )
                }
            })
        });
    });
    </script>
</body>
</html>