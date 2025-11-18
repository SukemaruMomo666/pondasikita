<?php
require_once '../../../config/koneksi.php';

// Get all categories with product counts
// Pastikan query mengambil kolom 'icon_class' juga
$query = "SELECT k.*, COUNT(b.id) as jumlah_produk 
          FROM tb_kategori k 
          LEFT JOIN tb_barang b ON b.kategori_id = k.id 
          GROUP BY k.id 
          ORDER BY k.nama_kategori";
$result = mysqli_query($koneksi, $query);

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Toko Bangunan</title>
       <link rel="stylesheet" href="../../../assets/css/styles.css">
    <link rel="stylesheet" href="../../../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../../assets/css/vendor.bundle.base.css">
 
    <link rel="shortcut icon" href="../../../assets/template/spica/template/images/favicon.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #771c15;
            --primary-hover-color: #5e1914;
            --text-on-primary: #ffffff;
        }
        body { background-color: #f8f9fa; }
        .sidebar { background-color: var(--primary-color); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { 
            color: var(--text-on-primary); 
            background-color: var(--primary-hover-color);
        }
        .card { transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .category-icon { 
            font-size: 5rem; /* Ukuran icon lebih besar */
            color: var(--primary-color); 
            line-height: 1; /* Pastikan center */
            display: inline-block; /* Untuk margin auto */
            margin-bottom: 1rem; /* Spasi bawah icon */
        }
        .btn-primary-custom { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary-custom:hover { background-color: var(--primary-hover-color); }
        .modal-header-custom { background-color: var(--primary-color); color: white; }
    </style>
</head>
<body>
  <div class="container-scroller">
    
    <?php include('partials/sidebar.php'); ?>
    
    <div class="page-body-wrapper">
        <div class="main-panel">
            
            <?php include('partials/navbar.php'); ?>

            <div class="content-wrapper">

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Kelola Kategori</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-1"></i> Tambah Kategori
                    </button>
                </div>

                <div class="row" id="categoryContainer">
                    <?php if (empty($categories)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Belum ada kategori yang ditambahkan</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4 category-card" data-id="<?= $category['id'] ?>">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <div class="mb-3">
                                        <?php 
                                        $icon_class = !empty($category['icon_class']) ? htmlspecialchars($category['icon_class']) : 'fas fa-box-open'; 
                                        ?>
                                        <i class="<?= $icon_class ?> fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="card-title"><?= htmlspecialchars($category['nama_kategori']) ?></h5>
                                    <p class="text-muted mb-3"><?= $category['jumlah_produk'] ?> produk</p>
                                    <div class="mt-auto">
                                        <button class="btn btn-sm btn-outline-primary btn-edit" 
                                                data-id="<?= $category['id'] ?>"
                                                data-nama="<?= htmlspecialchars($category['nama_kategori']) ?>"
                                                data-deskripsi="<?= htmlspecialchars($category['deskripsi'] ?? '') ?>"
                                                data-icon-class="<?= htmlspecialchars($category['icon_class'] ?? '') ?>" ><i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                data-id="<?= $category['id'] ?>"
                                                <?= $category['jumlah_produk'] > 0 ? 'disabled title="Tidak bisa menghapus kategori yang memiliki produk"' : '' ?>>
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addForm" action="aksi_kategori.php?act=insert" method="POST">
                    <div class="modal-header modal-header-custom text-white">
                        <h5 class="modal-title" id="addCategoryModalLabel">Tambah Kategori Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="icon_class" class="form-label">Kelas Icon Font Awesome</label>
                            <input type="text" class="form-control" id="icon_class" name="icon_class" placeholder="Contoh: fas fa-box-open">
                            <small class="form-text text-muted">Cari icon di <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">Font Awesome Free</a>. Contoh: `fas fa-tools`</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary-custom text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" action="aksi_kategori.php?act=update" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header modal-header-custom text-white">
                        <h5 class="modal-title" id="editCategoryModalLabel">Edit Kategori</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama_kategori" name="nama_kategori" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_icon_class" class="form-label">Kelas Icon Font Awesome</label>
                            <input type="text" class="form-control" id="edit_icon_class" name="icon_class" placeholder="Contoh: fas fa-box-open">
                            <small class="form-text text-muted">Cari icon di <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">Font Awesome Free</a>. Contoh: `fas fa-tools`</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary-custom text-white">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus kategori <strong id="deleteCategoryName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Data yang dihapus tidak dapat dikembalikan!</p>
                    <input type="hidden" id="delete_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Edit button click handler
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            const nama = $(this).data('nama');
            const deskripsi = $(this).data('deskripsi');
            const icon_class = $(this).data('icon-class'); // Ambil data icon class
            
            $('#edit_id').val(id);
            $('#edit_nama_kategori').val(nama);
            $('#edit_deskripsi').val(deskripsi);
            $('#edit_icon_class').val(icon_class); // Set nilai icon class ke input field
            
            $('#editCategoryModal').modal('show');
        });

        // Delete button click handler (tidak berubah)
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const nama = $(this).closest('.category-card').find('.card-title').text();
            
            $('#delete_id').val(id);
            $('#deleteCategoryName').text(nama);
            $('#deleteModal').modal('show');
        });

        // Confirm delete (tidak berubah)
        $('#confirmDelete').click(function() {
            const id = $('#delete_id').val();
            const btn = $(this);
            
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghapus...');
            
            $.post('aksi_kategori.php?act=delete', { id: id }, function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: result.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('.category-card[data-id="' + id + '"]').remove();
                            if ($('#categoryContainer').children().length === 0) {
                                $('#categoryContainer').html('<div class="col-12"><div class="alert alert-info">Belum ada kategori yang ditambahkan</div></div>');
                            }
                        });
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                    console.error("Raw response:", response);
                    Swal.fire('Error', 'Terjadi kesalahan saat memproses data (respons tidak valid).', 'error');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                Swal.fire('Error', 'Gagal menghubungi server: ' + textStatus, 'error');
                console.error("AJAX error:", textStatus, errorThrown, jqXHR.responseText);
            }).always(function() {
                btn.prop('disabled', false).html('Hapus');
                $('#deleteModal').modal('hide');
            });
        });

        // Form submission handlers (update agar mengirim icon_class)
        $('#addForm, #editForm').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const originalText = btn.html();
            
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(), // Gunakan serialize() karena tidak ada file upload
                // processData: false,      // Tidak perlu lagi
                // contentType: false,      // Tidak perlu lagi
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: result.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); 
                            });
                        } else {
                            Swal.fire('Gagal', result.message, 'error');
                        }
                    } catch (e) {
                        console.error("Error parsing JSON response:", e);
                        console.error("Raw response:", response);
                        Swal.fire('Error', 'Terjadi kesalahan saat memproses data (respons tidak valid).', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire('Error', 'Gagal menghubungi server: ' + textStatus, 'error');
                    console.error("AJAX error:", textStatus, errorThrown, jqXHR.responseText);
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                    $('#addCategoryModal').modal('hide');
                    $('#editCategoryModal').modal('hide');
                    form[0].reset();
                }
            });
        });
    });
    </script>
</body>
</html>