<div class="tab-pane fade" id="info-penjualan">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Variasi, Harga, & Stok</h5>
                <button type="button" class="btn btn-sm btn-success" id="btn-tambah-variasi">
                    <i class="mdi mdi-plus"></i> Tambah Variasi
                </button>
            </div>
            
            <p class="card-description">
                Tambahkan variasi jika produk Anda memiliki pilihan berbeda, seperti warna atau ukuran. 
                Jika produk tidak memiliki variasi, cukup isi satu baris saja.
            </p>

            <div id="container-variasi">
                
                <div class="row align-items-center variasi-row mb-2">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Nama Variasi</label>
                            <input type="text" name="variasi[nama][]" class="form-control" placeholder="Cth: Merah, XL">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="number" name="variasi[harga][]" class="form-control" placeholder="Rp" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number" name="variasi[stok][]" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kode SKU (Opsional)</label>
                            <input type="text" name="variasi[sku][]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-danger btn-hapus-variasi" disabled>
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                </div>

            </div> </div>
    </div>
</div>
<script>
    $(document).ready(function() {
    
    // ... kode DataTables dan lainnya ...

    // --- LOGIKA UNTUK TAMBAH/HAPUS VARIASI ---

    // Ketika Tombol "Tambah Variasi" di-klik
    $('#btn-tambah-variasi').on('click', function() {
        // Hitung jumlah baris yang ada untuk validasi (opsional)
        var rowCount = $('#container-variasi .variasi-row').length;
        if (rowCount >= 10) { // Batasi maksimal 10 variasi misalnya
            alert('Anda telah mencapai batas maksimal variasi.');
            return;
        }

        // Ini adalah "template" HTML untuk satu baris variasi baru
        var barisVariasiBaru = `
            <div class="row align-items-center variasi-row mb-2">
                <div class="col-md-3"><div class="form-group"><input type="text" name="variasi[nama][]" class="form-control" placeholder="Cth: Merah, XL"></div></div>
                <div class="col-md-3"><div class="form-group"><input type="number" name="variasi[harga][]" class="form-control" placeholder="Rp" required></div></div>
                <div class="col-md-2"><div class="form-group"><input type="number" name="variasi[stok][]" class="form-control" required></div></div>
                <div class="col-md-3"><div class="form-group"><input type="text" name="variasi[sku][]" class="form-control"></div></div>
                <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger btn-hapus-variasi"><i class="mdi mdi-delete"></i></button></div>
            </div>
        `;
        
        // Tambahkan baris baru ke dalam container
        $('#container-variasi').append(barisVariasiBaru);
    });

    // Ketika Tombol "Hapus" di salah satu baris di-klik
    // Kita gunakan 'delegated event' karena tombol hapus dibuat secara dinamis
    $('#container-variasi').on('click', '.btn-hapus-variasi', function() {
        // Hapus elemen .variasi-row terdekat dari tombol yang di-klik
        $(this).closest('.variasi-row').remove();
    });

});
</script>