<?php
// ===== PENAMBAHAN KODE SESSION DIMULAI DI SINI =====

// 1. Memulai Sesi
session_start();

// 2. Cek apakah pengguna sudah login dan apakah perannya adalah 'admin'
// Jika tidak ada session 'login' atau session 'role' bukan 'admin', maka alihkan
if (!isset($_SESSION['user']) || $_SESSION['user']['level'] != 'admin') {
    // Jika tidak, alihkan ke halaman login dengan pesan error
    header("Location: ../../../login.php?pesan=akses_ditolak"); // Ganti dengan URL login Anda
    exit; // Wajib, untuk menghentikan eksekusi kode halaman ini
}

include '../../../config/koneksi.php';

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manajemen Produk</title>
    <link rel="stylesheet" href="../../../assets/css/styles.css">
    <link rel="stylesheet" href="../../../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../../assets/css/vendor.bundle.base.css">
    <link rel="shortcut icon" href="../../../assets/template/spica/template/images/favicon.png" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container-scroller">
    
    <?php include('partials/sidebar.php') ?>
    
    <div class="page-body-wrapper">
        <div class="main-panel">
            <?php include('partials/navbar.php') ?>
            
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Manajemen Produk</h4>
                        <p class="card-description">Kelola inventaris produk Anda</p>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="mdi mdi-plus"></i> Tambah Produk
                        </button>
                        <div class="table-responsive">
                            <table id="productsTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Gambar</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th>Tanggal Dibuat</th> <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> 
        </div> 
    </div> 
</div> 

<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="productForm" action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label for="nama_barang" class="form-label">Nama Barang *</label><input type="text" class="form-control" id="nama_barang" name="nama_barang" required></div>
                            <div class="mb-3"><label for="kategori_id" class="form-label">Kategori *</label><select class="form-control" id="kategori_id" name="kategori_id" required></select></div>
                            <div class="mb-3"><label for="harga" class="form-label">Harga *</label><input type="number" class="form-control" id="harga" name="harga" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label for="stok" class="form-label">Stok *</label><input type="number" class="form-control" id="stok" name="stok" required></div>
                            <div class="mb-3"><label for="berat" class="form-label">Berat (kg) *</label><input type="number" step="0.01" class="form-control" id="berat" name="berat" required></div>
                            <div class="mb-3"><label for="is_active" class="form-label">Status *</label><select class="form-control" id="is_active" name="is_active" required><option value="1" selected>Aktif</option><option value="0">Tidak Aktif</option></select></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="gambar_barang" class="form-label">Gambar Barang (Pilih 1 s/d 5 gambar)</label>
                        <input type="file" class="form-control" id="gambar_barang" name="gambar_barang[]" accept="image/*" multiple>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preview Gambar</label>
                        <div id="imagePreviewContainer" class="mt-2 d-flex flex-wrap border p-2" style="min-height: 140px;"></div>
                    </div>

                    <div class="mb-3"><label for="deskripsi" class="form-label">Deskripsi</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveProductBtn">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewProductModalLabel">Detail Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body" id="viewProductBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../../assets/template/spica/template/vendors/js/vendor.bundle.base.js"></script>
<script src="../../../assets/template/spica/template/js/off-canvas.js"></script>
<script src="../../../assets/template/spica/template/js/hoverable-collapse.js"></script>
<script src="../../../assets/template/spica/template/js/template.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
<script>
$(document).ready(function() {
    var table = $('#productsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'get_products_list.php',
            type: 'GET',
            dataSrc: '' 
        },
        // PENYESUAIAN 3: DEFAULT SORTING DIUBAH KE KOLOM TANGGAL (INDEKS 7)
        order: [[7, 'desc']], 
        columns: [
            { data: 'kode_barang' },
            { 
                data: 'gambar_utama',
                orderable: false,
                render: function(data) {
                    const imgPath = data ? `../../../assets/uploads/${data}` : 'https://via.placeholder.com/50';
                    return `<img src="${imgPath}" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">`;
                }
            },
            { data: 'nama_barang' },
            { data: 'nama_kategori' },
            { 
                data: 'harga',
                render: function(data) {
                    return 'Rp ' + parseInt(data).toLocaleString('id-ID');
                }
            },
            { data: 'stok_tersedia' },
            {
                data: 'is_active',
                render: function(data) {
                    return data == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Tidak Aktif</span>';
                }
            },
            // PENYESUAIAN 3: MENAMBAHKAN RENDER UNTUK KOLOM TANGGAL
            {
                data: 'created_at',
                render: function(data) {
                    if (!data) return '-';
                    let date = new Date(data);
                    let day = String(date.getDate()).padStart(2, '0');
                    let month = String(date.getMonth() + 1).padStart(2, '0');
                    let year = date.getFullYear();
                    return `${day}-${month}-${year}`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-info btn-sm view-btn" data-id="${row.id}" title="Lihat"><i class="mdi mdi-eye"></i></button>
                        <a href="edit_product.php?id=${row.id}" class="btn btn-warning btn-sm" title="Edit"><i class="mdi mdi-pencil"></i></a>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${row.id}" data-name="${row.nama_barang}" title="Hapus"><i class="mdi mdi-delete"></i></button>
                    `;
                }
            }
        ]
    });

    // Sisa JavaScript (tidak ada perubahan, tetap sama seperti sebelumnya)
    $('#addProductModal').on('show.bs.modal', function () {
        if ($('#kategori_id option').length <= 1) { 
            $.get('get_kategori.php', function (data) {
                var categories = (typeof data === 'string') ? JSON.parse(data) : data;
                if (categories.error) {
                    Swal.fire('Error!', categories.error, 'error');
                    return;
                }
                var options = '<option value="">Pilih kategori</option>';
                categories.forEach(function (category) {
                    options += `<option value="${category.id}">${category.nama_kategori}</option>`;
                });
                $('#kategori_id').html(options);
            }).fail(function() {
                Swal.fire('Error!', 'Gagal memuat kategori.', 'error');
            });
        }
    });

    $('#gambar_barang').on('change', function () {
        const previewContainer = $('#imagePreviewContainer');
        previewContainer.html(''); 
        if (this.files.length > 5) {
            Swal.fire('Batas Terlampaui', 'Anda hanya dapat mengunggah maksimal 5 gambar.', 'error');
            $(this).val('');
            return;
        }
        if (this.files.length > 0) {
            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrapper = $(`
                        <div class="thumbnail-wrapper" style="margin: 5px; text-align: center;">
                            <img src="${e.target.result}" class="thumbnail-img" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                            <div class="mt-1">
                                <input type="radio" class="form-check-input" name="is_utama" value="${index}" ${index === 0 ? 'checked' : ''}>
                                <label class="form-check-label small">Utama</label>
                            </div>
                        </div>`);
                    previewContainer.append(wrapper);
                };
                reader.readAsDataURL(file);
            });
        }
    });

    $('#saveProductBtn').on('click', function (e) {
        e.preventDefault();
        if (!$('#productForm')[0].checkValidity()) {
            $('#productForm')[0].reportValidity();
            return;
        }
        if ($('#gambar_barang')[0].files.length < 1) {
            Swal.fire('Gambar Wajib', 'Anda harus mengunggah minimal 1 gambar.', 'error');
            return;
        }
        var formData = new FormData($('#productForm')[0]);
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        $.ajax({
            url: $('#productForm').attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                Swal.close();
                if (response.status === 'success') {
                    Swal.fire('Berhasil!', response.message, 'success');
                    $('#addProductModal').modal('hide');
                    table.ajax.reload();
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                }
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire('Error!', 'Terjadi kesalahan server: ' + xhr.responseText, 'error');
            }
        });
    });

    $('#addProductModal').on('hidden.bs.modal', function () {
        $('#productForm')[0].reset();
        $('#imagePreviewContainer').html('');
        $('#kategori_id').html('<option value="">Pilih kategori</option>');
    });
    
    $('#productsTable tbody').on('click', '.view-btn', function () {
        var id = $(this).data('id');
        $.get(`get_product_detail.php?id=${id}`, function (response) {
            if(response.status === 'success'){
                let product = response.data;
                let imagesHtml = '<p>Tidak ada gambar.</p>';
                if(product.images && product.images.length > 0){
                    imagesHtml = '<div class="d-flex flex-wrap">';
                    product.images.forEach(img => {
                        imagesHtml += `<div class="thumbnail-wrapper" style="margin: 5px;"><img src="../../../assets/uploads/${img.nama_file}" class="thumbnail-img" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;"></div>`;
                    });
                    imagesHtml += '</div>';
                }
                let hargaFormatted = product.harga ? 'Rp ' + parseInt(product.harga).toLocaleString('id-ID') : 'Harga tidak diatur';
                let stokFormatted = product.stok !== null ? `${product.stok} unit` : 'Stok tidak diatur';
                let contentHtml = `
                    <div class="row">
                        <div class="col-md-8">
                            <h3>${product.nama_barang}</h3>
                            <p class="text-muted">Kode: ${product.kode_barang}</p>
                            <span class="badge bg-primary">${product.nama_kategori}</span>
                            <span class="badge ${product.is_active == 1 ? 'bg-success' : 'bg-danger'}">${product.is_active == 1 ? 'Aktif' : 'Tidak Aktif'}</span>
                            <hr>
                            <h4>${hargaFormatted}</h4>
                            <p><strong>Stok:</strong> ${stokFormatted} | <strong>Berat:</strong> ${product.berat} kg</p>
                            <h5>Deskripsi</h5>
                            <p>${product.deskripsi || '-'}</p>
                        </div>
                        <div class="col-md-4">
                            <h5>Gambar Produk</h5>
                            ${imagesHtml}
                        </div>
                    </div>
                `;
                $('#viewProductBody').html(contentHtml);
                $('#viewProductModal').modal('show');
            } else {
                Swal.fire('Gagal', response.message, 'error');
            }
        }, 'json');
    });
    
    $('#productsTable tbody').on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        Swal.fire({
            title: `Hapus ${name}?`,
            text: "Aksi ini tidak dapat dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('hapus_product.php', { id: id }, function (response) {
                    if (response.status === 'success') {
                        Swal.fire('Terhapus!', response.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>
</body>
</html>