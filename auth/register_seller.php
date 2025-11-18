<?php
// Sesuaikan path ke file koneksi Anda.
// Path ini mungkin perlu disesuaikan tergantung struktur folder Anda.
require_once '../config/koneksi.php';

// Ambil daftar semua provinsi untuk dropdown pertama
$provinces = $koneksi->query("SELECT id, name FROM provinces ORDER BY name ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buka Toko - Pondasikita</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/auth_style.css">
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .select2-container .select2-selection--single {
            height: 42px; /* Samakan tinggi dengan input lain */
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
            color: #333;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            top: 1px;
        }
        .select2-dropdown {
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group label {
            margin-bottom: 5px; /* Memberi sedikit jarak antara label dan select box */
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-sidebar">
            <div>
                <h1>Jadilah Partner Kami.</h1>
                <p>Jangkau lebih banyak pelanggan dan kelola bisnis Anda dengan mudah bersama Pondasikita.</p>
            </div>
              <span>© Pondasikita 2025</span>
        </div>
        <div class="auth-main">
            <div class="form-wrapper">
                <h2>Formulir Pendaftaran Toko</h2>
                <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
                
                <form id="registerSellerForm" method="POST" enctype="multipart/form-data">
                    <div id="message" class="message-box" style="display:none;"></div>
                    
                    <h3>1. Informasi Pemilik</h3>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_pemilik">Nama Lengkap Pemilik (Sesuai KTP)</label>
                        <input type="text" id="nama_pemilik" name="nama_pemilik" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Kata Sandi</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <h3 style="margin-top:2rem;">2. Informasi Toko</h3>
                    <div class="form-group">
                        <label for="nama_toko">Nama Toko</label>
                        <input type="text" id="nama_toko" name="nama_toko" required>
                    </div>
                    <div class="form-group">
                        <label for="telepon_toko">No. Telepon Toko/WhatsApp</label>
                        <input type="tel" id="telepon_toko" name="telepon_toko" required>
                    </div>
                    <div class="form-group">
                        <label for="alamat_toko">Alamat Lengkap Toko</label>
                        <textarea id="alamat_toko" name="alamat_toko" rows="3" placeholder="Contoh: Jl. Merdeka No. 12, RT 01/RW 05, Nama Gedung/Kompleks" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="provinsi">Provinsi</label>
                        <select id="provinsi" name="province_id" required>
                            <option value="">-- Pilih Provinsi --</option>
                            <?php while($row = $provinces->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                     <div class="form-group">
                        <label for="kota_kabupaten">Kota / Kabupaten</label>
                        <select id="kota_kabupaten" name="city_id" required disabled>
                            <option value="">-- Pilih Provinsi Terlebih Dahulu --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="kecamatan">Kecamatan</label>
                        <select id="kecamatan" name="district_id" required disabled>
                            <option value="">-- Pilih Kota/Kabupaten Terlebih Dahulu --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="logo_toko">Logo Toko (Opsional)</label>
                        <input type="file" id="logo_toko" name="logo_toko" accept="image/*">
                    </div>

                    <button type="submit" class="btn-submit">Daftarkan Toko</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Inisialisasi Select2
        $('#provinsi').select2({ placeholder: '-- Pilih Provinsi --', width: '100%' });
        $('#kota_kabupaten').select2({ placeholder: '-- Pilih Kota/Kabupaten --', width: '100%' });
        $('#kecamatan').select2({ placeholder: '-- Pilih Kecamatan --', width: '100%' });

        // Fungsi untuk mengambil data dari API
        function fetchAndPopulate(type, parentId, targetSelect) {
            // Sesuaikan path ke API Anda jika perlu. Path '/api/...' diasumsikan dari root website.
            const endpoint = `api/get_locations.php?type=${type}&parent_id=${parentId}`;
            
            targetSelect.prop('disabled', true).html('<option value="">Memuat...</option>').trigger('change');

            fetch(endpoint)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    targetSelect.empty().append(`<option value="">-- Pilih ${type.charAt(0).toUpperCase() + type.slice(1)} --</option>`);
                    
                    data.forEach(item => {
                        const option = new Option(item.name, item.id, false, false);
                        targetSelect.append(option);
                    });
                    
                    targetSelect.prop('disabled', false).trigger('change');
                })
                .catch(error => {
                    console.error(`Error fetching ${type}:`, error);
                    targetSelect.html(`<option value="">Gagal memuat data</option>`).trigger('change');
                });
        }

        // Event listener untuk dropdown Provinsi
        $('#provinsi').on('change', function() {
            const provinceId = $(this).val();
            // Reset dan disable dropdown anak
            $('#kota_kabupaten').val(null).trigger('change');
            $('#kecamatan').val(null).trigger('change').prop('disabled', true).html('<option value="">-- Pilih Kota/Kabupaten --</option>');
            
            if (provinceId) {
                fetchAndPopulate('kota', provinceId, $('#kota_kabupaten'));
            } else {
                $('#kota_kabupaten').prop('disabled', true).html('<option value="">-- Pilih Provinsi --</option>');
            }
        });

        // Event listener untuk dropdown Kota/Kabupaten
        $('#kota_kabupaten').on('change', function() {
            const cityId = $(this).val();
            $('#kecamatan').val(null).trigger('change');

            if (cityId) {
                fetchAndPopulate('kecamatan', cityId, $('#kecamatan'));
            } else {
                $('#kecamatan').prop('disabled', true).html('<option value="">-- Pilih Kota/Kabupaten --</option>');
            }
        });
    });
    </script>
    
    <script>
        document.getElementById('registerSellerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');
            const submitButton = form.querySelector('.btn-submit');

            submitButton.disabled = true;
            submitButton.textContent = 'Memproses...';

            // Pastikan path ke proses_register_seller.php sudah benar
            fetch('../actions/proses_register_seller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.textContent = data.message;
                messageDiv.className = 'message-box ' + (data.status === 'success' ? 'success' : 'error');
                messageDiv.style.display = 'block';

                if (data.status === 'success') {
                    form.reset();
                    // Reset dropdown select2 ke placeholder awal
                    $('#provinsi').val(null).trigger('change');
                    $('#kota_kabupaten').val(null).trigger('change');
                    $('#kecamatan').val(null).trigger('change');
                    
                    setTimeout(() => {
                        window.location.href = 'login.php?status=reg_seller_success';
                    }, 3000);
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Daftarkan Toko';
                }
            })
            .catch(error => {
                console.error('Submit Error:', error);
                messageDiv.textContent = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
                messageDiv.className = 'message-box error';
                messageDiv.style.display = 'block';
                submitButton.disabled = false;
                submitButton.textContent = 'Daftarkan Toko';
            });
        });
    </script>

</body>
</html>