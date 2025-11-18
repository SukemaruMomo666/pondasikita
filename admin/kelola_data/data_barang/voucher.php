<?php
// Koneksi ke database dan data notifikasi (diambil dari template Anda)
include '../../../config/koneksi.php';

// --- Ambil data untuk notifikasi (dengan perbaikan) ---

// 1. Notifikasi Stok Hampir Habis (stok < 10)
$queryStok = "SELECT nama_barang, stok FROM tb_barang WHERE stok < 10 ORDER BY stok ASC LIMIT 5";
$resultStok = $koneksi->query($queryStok);
$countStok = $koneksi->query("SELECT COUNT(*) as jumlah FROM tb_barang WHERE stok < 10")->fetch_assoc()['jumlah'];

// 2. Notifikasi Pesanan Baru (status = 'pending')
$queryPesanan = "SELECT kode_invoice, tanggal_transaksi FROM tb_transaksi WHERE status_pesanan = 'pending' ORDER BY tanggal_transaksi DESC LIMIT 5";
$resultPesanan = $koneksi->query($queryPesanan);
$countPesanan = $koneksi->query("SELECT COUNT(*) as jumlah FROM tb_transaksi WHERE status_pesanan = 'pending'")->fetch_assoc()['jumlah'];

// 3. Notifikasi Pesan Customer
$queryPesan = "SELECT * FROM tb_pesan ORDER BY created_at DESC LIMIT 5";
$resultPesan = $koneksi->query($queryPesan);
$countPesan = $koneksi->query("SELECT COUNT(*) as jumlah FROM tb_pesan")->fetch_assoc()['jumlah'];

// Hitung total notifikasi
$totalNotifikasi = $countStok + $countPesanan + $countPesan;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manajemen Voucher</title>
    
    <link rel="stylesheet" href="../../../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../../assets/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../../assets/css/styles.css">
    <link rel="shortcut icon" href="../../../assets/template/spica/template/images/favicon.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">

<style>
  
    /* Style untuk Modal Voucher */
    .modal {
        display: none; position: fixed; z-index: 1050; /* z-index lebih tinggi dari navbar */
        left: 0; top: 0; width: 100%; height: 100%; overflow: auto; 
        background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s;
    }
    .modal-content {
        background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888;
        width: 80%; max-width: 600px; border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideIn 0.3s;
    }
    .modal-header {
        padding: 1rem 1.5rem; border-bottom: 1px solid #e5e5e5;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h3 { margin: 0; color: #2c3e50; font-size: 1.25rem; }
    .close-btn {
        color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
        border: none; background: transparent;
    }
    .close-btn:hover { color: #000; }
    .modal-body { padding: 1.5rem; }
    .modal-footer {
        padding: 1rem 1.5rem; border-top: 1px solid #e5e5e5; text-align: right;
    }
    .hidden { display: none; }
    
    /* Penyesuaian form di dalam modal */
    .form-group label { font-weight: 600; }
    .form-group input, .form-group select {
        width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
    }
    .form-group input[type="checkbox"] { width: auto; }
    
    /* Status di tabel */
    .status { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; }
    .status-aktif { background-color: #28a745; }
    .status-habis { background-color: #ffc107; color: #212529; }
    .status-kedaluwarsa { background-color: #dc3545; }
    
    /* Tombol aksi di tabel */
    .actionsi-buttons {
        display: flex; /* <-- Fixed: Removed the semicolon here and added it after 'flex' */
        gap: 15px;
        margin-top: 0px;
    } /* <-- This is the closing brace for .actionsi-buttons, it should be here */
</style>
</head>
<body>
   <div class="container-scroller d-flex">
      
    <?php include('partials/sidebar.php') ?>
      
    <div class="container-fluid page-body-wrapper">
        
        <div class="main-panel">

            <?php include('partials/navbar.php'); ?>

            <div class="content-wrapper">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Manajemen Voucher</h4>
                        <p class="card-description">Buat, edit, dan kelola voucher untuk pelanggan Anda.</p>
                        
                        <div class="toolbar">
                            <button id="addVoucherBtn" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Voucher
                            </button>
                            <div class="search-bar">
                                <input type="text" id="searchInput" class="form-control" placeholder="Cari kode atau deskripsi...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped voucher-table">
                                <thead>
                                    <tr>
                                        <th>Kode Voucher</th>
                                        <th>Deskripsi</th>
                                        <th>Tipe</th>
                                        <th>Nilai</th>
                                        <th>Kuota</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="voucherTableBody">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div id="voucherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Voucher Baru</h3>
                <button type="button" class="close-btn">&times;</button>
            </div>
            <form id="voucherForm">
                <div class="modal-body">
                    <input type="hidden" id="voucherId" name="voucherId">
                    <div class="form-group">
                        <label for="deskripsi">Nama / Deskripsi Voucher</label>
                        <input type="text" class="form-control" id="deskripsi" name="deskripsi" placeholder="Contoh: Diskon Pelanggan Baru" required>
                    </div>
                    <div class="form-group">
                        <label for="tipePromo">Tipe Promo</label>
                        <select id="tipePromo" name="tipe_diskon" class="form-control" required>
                            <option value="RUPIAH">Potongan Rupiah</option>
                            <option value="PERSEN">Diskon Persentase</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nilaiDiskon">Nilai Diskon</label>
                        <input type="number" class="form-control" id="nilaiDiskon" name="nilai_diskon" placeholder="Isi nominal atau persen" required>
                    </div>
                    <div id="grupMaksDiskon" class="form-group hidden">
                        <label for="maksDiskon">Maksimal Potongan (Rp)</label>
                        <input type="number" class="form-control" id="maksDiskon" name="maks_diskon" placeholder="Contoh: 35000">
                    </div>
                    <div class="form-group">
                        <label for="minPembelian">Minimal Pembelian (Rp)</label>
                        <input type="number" class="form-control" id="minPembelian" name="min_pembelian" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="kuota">Kuota Penggunaan</label>
                        <input type="number" class="form-control" id="kuota" name="kuota" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_berakhir">Tanggal Berakhir</label>
                        <input type="date" class="form-control" id="tanggal_berakhir" name="tanggal_berakhir" required>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="isCustomCode" class="form-check-input">
                        <label for="isCustomCode" class="form-check-label">Buat Kode Voucher Custom</label>
                    </div>
                    <div id="grupKodeCustom" class="form-group hidden">
                        <label for="kodeCustom">Kode Voucher</label>
                        <input type="text" class="form-control" id="kodeCustom" name="kode_voucher_custom" placeholder="Contoh: BELANJAHEMAT">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-btn">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../../assets/template/spica/template/vendors/js/vendor.bundle.base.js"></script>
    <script src="../../../assets/template/spica/template/js/off-canvas.js"></script>
    <script src="../../../assets/template/spica/template/js/hoverable-collapse.js"></script>
    <script src="../../../assets/template/spica/template/js/template.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. REFERENSI ELEMEN
    const voucherTableBody = document.getElementById('voucherTableBody');
    const searchInput = document.getElementById('searchInput');
    const modal = document.getElementById('voucherModal');
    const addVoucherBtn = document.getElementById('addVoucherBtn');
    const voucherForm = document.getElementById('voucherForm');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtns = document.querySelectorAll('.close-btn');
    const tipePromo = document.getElementById('tipePromo');
    const grupMaksDiskon = document.getElementById('grupMaksDiskon');
    const isCustomCode = document.getElementById('isCustomCode');
    const grupKodeCustom = document.getElementById('grupKodeCustom');
    const voucherIdInput = document.getElementById('voucherId');
    const kodeCustomInput = document.getElementById('kodeCustom');
    const maksDiskonInput = document.getElementById('maksDiskon');
    const nilaiDiskonInput = document.getElementById('nilaiDiskon');
    const deskripsiInput = document.getElementById('deskripsi');
    const minPembelianInput = document.getElementById('minPembelian');
    const kuotaInput = document.getElementById('kuota');
    const tanggalBerakhirInput = document.getElementById('tanggal_berakhir');

    // 2. KUMPULAN FUNGSI
    function loadVouchers() {
        // PERBAIKAN: Nama file yang benar adalah get_vouchers.php (dengan 's')
        fetch('get_vouchers.php') 
            .then(response => response.json())
            .then(data => {
                voucherTableBody.innerHTML = '';
                if (data && data.length > 0) {
                    data.forEach(voucher => voucherTableBody.insertAdjacentHTML('beforeend', createTableRowHTML(voucher)));
                }
            })
            .catch(error => console.error('Gagal memuat data:', error));
    }
    
    // =======================================================
    // PERBAIKAN UTAMA ADA DI FUNGSI INI
    // =======================================================
    function createTableRowHTML(voucher) {
        let nilaiDisplay = voucher.tipe_diskon === 'RUPIAH' 
            ? `Rp ${parseInt(voucher.nilai_diskon).toLocaleString('id-ID')}` 
            : `${voucher.nilai_diskon}%`;
            
        if (voucher.tipe_diskon === 'PERSEN' && voucher.maks_diskon > 0) {
            nilaiDisplay += ` (Maks. Rp ${parseInt(voucher.maks_diskon).toLocaleString('id-ID')})`;
        }

        const kuotaDisplay = `${voucher.kuota_terpakai || 0}/${voucher.kuota}`;
        
        const today = new Date(); 
        today.setHours(0, 0, 0, 0);
        const endDate = new Date(voucher.tanggal_berakhir);
        
        let statusClass = 'status-aktif';
        let statusText = 'AKTIF';
        if (parseInt(voucher.kuota_terpakai) >= parseInt(voucher.kuota)) {
            statusClass = 'status-habis'; 
            statusText = 'HABIS';
        } else if (endDate < today) {
            statusClass = 'status-kedaluwarsa'; 
            statusText = 'KEDALUWARSA';
        }
        
        // Kode HTML yang benar menggunakan backtick ` dan sintaks ${...}
        return `
            <tr data-id="${voucher.id}">
                <td><strong>${voucher.kode_voucher}</strong></td>
                <td>${voucher.deskripsi}</td>
                <td>${voucher.tipe_diskon}</td>
                <td>${nilaiDisplay}</td>
                <td>${kuotaDisplay}</td>
                <td><span class="status ${statusClass}">${statusText}</span></td>
                <td class="actionsi-buttons">
                    <button class="btn btn-warning btn-sm btn-edit" title="Edit"><i class="mdi mdi-pencil"></i></button>
                    <button class="btn btn-danger btn-sm btn-delete" title="Hapus"><i class="mdi mdi-delete"></i></button>
                </td>
            </tr>`;
    }

    function updateTableRow(voucherData) {
        const row = voucherTableBody.querySelector(`tr[data-id="${voucherData.id}"]`);
        if (row) row.outerHTML = createTableRowHTML(voucherData);
    }

    function populateFormForEdit(voucher) {
        modalTitle.textContent = 'Edit Voucher';
        voucherIdInput.value = voucher.id;
        deskripsiInput.value = voucher.deskripsi;
        tipePromo.value = voucher.tipe_diskon;
        nilaiDiskonInput.value = parseFloat(voucher.nilai_diskon);
        minPembelianInput.value = parseFloat(voucher.min_pembelian);
        kuotaInput.value = voucher.kuota;
        tanggalBerakhirInput.value = voucher.tanggal_berakhir.split(' ')[0];
        grupMaksDiskon.classList.toggle('hidden', voucher.tipe_diskon !== 'PERSEN');
        if (voucher.tipe_diskon === 'PERSEN') {
            maksDiskonInput.value = voucher.maks_diskon ? parseFloat(voucher.maks_diskon) : '';
        }
        grupKodeCustom.classList.remove('hidden');
        kodeCustomInput.value = voucher.kode_voucher;
        kodeCustomInput.readOnly = true;
        isCustomCode.checked = true;
        isCustomCode.disabled = true;
        modal.style.display = 'block';
    }

    function openAddModal() {
        voucherForm.reset();
        modalTitle.textContent = 'Tambah Voucher Baru';
        voucherIdInput.value = '';
        grupMaksDiskon.classList.add('hidden');
        grupKodeCustom.classList.add('hidden');
        isCustomCode.checked = false;
        kodeCustomInput.readOnly = false;
        isCustomCode.disabled = false;
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }
    
    const generateVoucherCode = () => 'VCR' + Math.random().toString(36).substr(2, 9).toUpperCase();

    // 3. EVENT LISTENERS
    loadVouchers();
    addVoucherBtn.addEventListener('click', openAddModal);
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });

    voucherForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = voucherIdInput.value;
        if (!id) {
            const kode = isCustomCode.checked ? kodeCustomInput.value.trim().toUpperCase() : generateVoucherCode();
            if (isCustomCode.checked && kode === '') {
                return Swal.fire('Gagal', 'Kode voucher custom tidak boleh kosong.', 'error');
            }
            formData.append('kode_voucher', kode);
        } else {
            formData.append('kode_voucher', kodeCustomInput.value);
        }
        formData.delete('kode_voucher_custom');

        fetch('simpan_voucher.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Berhasil!', data.message, 'success');
                closeModal();
                if (id) {
                    updateTableRow(data.new_data);
                } else {
                    voucherTableBody.insertAdjacentHTML('afterbegin', createTableRowHTML(data.new_data));
                }
            } else {
                Swal.fire('Gagal!', data.message, 'error');
            }
        }).catch(err => console.error('Fetch Error:', err));
    });

    voucherTableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const row = btn.closest('tr');
        const id = row.dataset.id;
        
        if (btn.classList.contains('btn-edit')) {
            fetch(`get_voucher_detail.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') populateFormForEdit(data.data);
                    else Swal.fire('Gagal', data.message, 'error');
                });
        } else if (btn.classList.contains('btn-delete')) {
            const deskripsi = row.cells[1].textContent;
            Swal.fire({
                title: `Hapus voucher "${deskripsi}"?`, text: "Aksi ini tidak dapat dibatalkan!",
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('hapus_voucher.php', {
                        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id=' + id
                    }).then(res => res.json()).then(data => {
                        if (data.status === 'success') {
                            row.remove();
                            Swal.fire('Terhapus!', data.message, 'success');
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    });
                }
            });
        }
    });

    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        voucherTableBody.querySelectorAll('tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    tipePromo.addEventListener('change', function() {
        grupMaksDiskon.classList.toggle('hidden', this.value !== 'PERSEN');
        if (this.value !== 'PERSEN') maksDiskonInput.value = '';
    });

    isCustomCode.addEventListener('change', function() {
        grupKodeCustom.classList.toggle('hidden', !this.checked);
        if (!this.checked) kodeCustomInput.value = '';
    });
});
</script>
    </script>
</body>
</html>