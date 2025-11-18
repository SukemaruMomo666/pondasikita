<?php
// TAMBAHKAN 3 BARIS INI
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../../../config/koneksi.php'; // Sesuaikan path jika perlu
// ... sisa kode Anda ...
$koneksi = new mysqli($host, $user, $pass, $db);
// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
}

// ========================================================================
// BAGIAN 1: QUERY DATA CUSTOMER (SUDAH DISESUAIKAN DENGAN DATABASE ANDA)
// ========================================================================
$sql = "SELECT 
            u.id, u.username, u.nama, u.email, u.no_telepon, u.alamat, u.level, u.is_verified, u.is_banned, u.last_login, u.created_at,
            COUNT(p.id) AS total_pesanan,
            COALESCE(SUM(p.total_harga), 0) AS total_belanja 
        FROM 
            tb_user u
        LEFT JOIN 
            tb_transaksi p ON u.id = p.user_id
        WHERE 
            u.level = 'customer'
        GROUP BY 
            u.id
        ORDER BY 
            total_belanja DESC";

$result = $koneksi->query($sql);

$customers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}
$koneksi->close();

// ========================================================================
// BAGIAN 2: FUNGSI HELPER (SUDAH DIMODIFIKASI)
// ========================================================================

function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// --- FUNGSI BARU DITAMBAHKAN DI SINI ---
function getVerificationStatusBadge($is_verified) {
    if ($is_verified == 1) {
        return '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Terverifikasi</span>';
    } else {
        return '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill"></i> Belum Diverifikasi</span>';
    }
}

function getBannedStatusBadge($is_banned) {
    return $is_banned == 1 ? '<span class="badge bg-danger">Banned</span>' : '<span class="badge bg-success">Aktif</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Data Customer</title>
     <link rel="stylesheet" href="../../../assets/css/styles.css">
    <link rel="stylesheet" href="../../../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../../assets/css/vendor.bundle.base.css">
    <link rel="shortcut icon" href="../../../assets/template/spica/template/images/favicon.png" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
</head>
<body>
<div class="container-scroller">
    <?php include('partials/sidebar.php'); ?> 
    
    <div class="page-body-wrapper">
        
        <div class="main-panel">
            
            <?php include('partials/navbar.php'); ?>

            <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">Manajemen Data Customer</h1>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Pelanggan</th>
                                            <th>Kontak</th>
                                            <th>Status Akun</th>
                                            <th>Status Verifikasi</th> <th>Riwayat Pembelian</th>
                                            <th>Tgl Registrasi</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($customers)): ?>
                                            <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?= $customer['id'] ?></td>
                                                <td>
                                                    <strong><?= !empty($customer['nama']) ? htmlspecialchars($customer['nama']) : '-' ?></strong><br>
                                                    <small class="text-muted">@<?= !empty($customer['username']) ? htmlspecialchars($customer['username']) : '-' ?></small>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <i class="bi bi-envelope-fill"></i>
                                                        <?= !empty($customer['email']) ? htmlspecialchars($customer['email']) : '-' ?>
                                                        <br>
                                                        <i class="bi bi-telephone-fill"></i>
                                                        <?= !empty($customer['no_telepon']) ? htmlspecialchars($customer['no_telepon']) : '-' ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= getBannedStatusBadge($customer['is_banned']) ?>
                                                </td>
                                                
                                                <td>
                                                    <?= getVerificationStatusBadge($customer['is_verified']) ?>
                                                </td>

                                                <td>
                                                    <a class="lihat-riwayat fs-5" title="Tampilkan/Sembunyikan Riwayat">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                    <div class="riwayat-detail">
                                                        <small>
                                                            <strong>Total Pesanan:</strong> <?= $customer['total_pesanan'] ?> kali<br>
                                                            <strong>Total Belanja:</strong> <?= format_rupiah($customer['total_belanja']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td><?= date('d M Y', strtotime($customer['created_at'])) ?></td>
                                                <td class="text-center">
                                                    <a href="ban_customer.php?id=<?= $customer['id'] ?>&status=<?= $customer['is_banned'] ? 0 : 1 ?>" 
                                                    class="btn <?= $customer['is_banned'] ? 'btn-success' : 'btn-danger' ?> btn-sm" 
                                                    title="<?= $customer['is_banned'] ? 'Unban Customer' : 'Ban Customer' ?>"
                                                    onclick="return confirm('Anda yakin ingin <?= $customer['is_banned'] ? 'mengaktifkan' : 'membanned' ?> akun <?= htmlspecialchars($customer['nama']) ?>?')">
                                                        <i class="bi bi-x-circle-fill"></i>
                                                    </a>

                                                    <button type="button" class="btn btn-primary btn-sm tombol-kirim-email" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#emailModal"
                                                            data-nama="<?= htmlspecialchars($customer['nama']) ?>"
                                                            data-email="<?= htmlspecialchars($customer['email']) ?>"
                                                            title="Kirim Email ke <?= htmlspecialchars($customer['nama']) ?>">
                                                        <i class="bi bi-envelope-fill"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Tidak ada data customer yang ditemukan.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Kirim Pesan ke <span id="namaPenerima"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formKirimEmail">
                    <div class="mb-3">
                        <label for="emailTujuan" class="form-label">Kirim Ke:</label>
                        <input type="email" class="form-control" id="emailTujuan" name="email_penerima" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="subjekEmail" class="form-label">Subjek:</label>
                        <input type="text" class="form-control" id="subjekEmail" name="subjek" required>
                    </div>
                    <div class="mb-3">
                        <label for="isiPesan" class="form-label">Isi Pesan:</label>
                        <textarea class="form-control" id="isiPesan" name="isi_pesan" rows="8" required></textarea>
                        <div class="form-text">
                           Tips: Untuk membuat voucher menonjol, gunakan format `[VOUCHER:KODEANDA]` di dalam pesan Anda.
                        </div>
                    </div>
                    <div id="statusKirim" class="mt-3"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" form="formKirimEmail" class="btn btn-primary" id="tombolKirim">
                    <span id="tombolKirimText">Kirim Email</span>
                    <div class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ... (kode untuk riwayat pembelian yang sudah ada bisa tetap di sini) ...
        const tombolLihat = document.querySelectorAll('.lihat-riwayat');
        tombolLihat.forEach(function(tombol) { /* ... */ });

        // JAVASCRIPT BARU UNTUK MODAL EMAIL
        const emailModal = document.getElementById('emailModal');
        const formKirimEmail = document.getElementById('formKirimEmail');
        const statusKirimDiv = document.getElementById('statusKirim');
        const tombolKirim = document.getElementById('tombolKirim');
        const tombolKirimText = document.getElementById('tombolKirimText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        // 1. Saat Modal akan ditampilkan
        emailModal.addEventListener('show.bs.modal', function(event) {
            // Tombol yang diklik untuk memicu modal
            const button = event.relatedTarget;
            
            // Ambil data dari atribut data-*
            const nama = button.getAttribute('data-nama');
            const email = button.getAttribute('data-email');

            // Isi form di dalam modal dengan data customer
            document.getElementById('namaPenerima').textContent = nama;
            document.getElementById('emailTujuan').value = email;

            // Bersihkan form dan status dari pengiriman sebelumnya
            formKirimEmail.reset(); // Hapus isi subjek & pesan
            document.getElementById('emailTujuan').value = email; // Isi lagi emailnya setelah reset
            statusKirimDiv.innerHTML = '';
            tombolKirim.disabled = false;
            tombolKirimText.textContent = 'Kirim Email';
            loadingSpinner.classList.add('d-none');
        });

        // 2. Saat form di dalam modal disubmit
        formKirimEmail.addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah form submit dan refresh halaman

            // Tampilkan loading
            tombolKirim.disabled = true;
            tombolKirimText.textContent = 'Mengirim...';
            loadingSpinner.classList.remove('d-none');
            statusKirimDiv.innerHTML = '';

            const formData = new FormData(formKirimEmail);

            // Kirim data ke backend menggunakan Fetch API (AJAX)
            fetch('kirim_email_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    statusKirimDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    formKirimEmail.reset(); // Kosongkan form setelah berhasil
                } else {
                    statusKirimDiv.innerHTML = `<div class="alert alert-danger">Gagal: ${data.message}</div>`;
                }
            })
            .catch(error => {
                statusKirimDiv.innerHTML = `<div class="alert alert-danger">Terjadi error. Coba lagi. ${error}</div>`;
            })
            .finally(() => {
                // Sembunyikan loading
                tombolKirim.disabled = false;
                tombolKirimText.textContent = 'Kirim Email';
                loadingSpinner.classList.add('d-none');
            });
        });
    });
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tombolLihat = document.querySelectorAll('.lihat-riwayat');
            tombolLihat.forEach(function(tombol) {
                tombol.addEventListener('click', function(event) {
                    event.preventDefault();
                    const detailDiv = tombol.nextElementSibling;
                    if (detailDiv.style.display === 'none' || detailDiv.style.display === '') {
                        detailDiv.style.display = 'block';
                    } else {
                        detailDiv.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>