<?php
session_start();
// Sesuaikan path ke file koneksi Anda
require_once __DIR__ . '/../../../config/koneksi.php';

// Keamanan: Pastikan user sudah login sebagai customer
// PERBAIKAN DI SINI: Akses data login dari $_SESSION['user']
if (!isset($_SESSION['user']['logged_in']) || !$_SESSION['user']['logged_in'] || $_SESSION['user']['level'] !== 'customer') {
    header("Location: /auth/login_customer.php");
    exit;
}

// Ambil user_id dari array $_SESSION['user']
$user_id = $_SESSION['user']['id']; // <-- PERBAIKAN UTAMA

// 1. Ambil data user dari tb_user
$stmt_user = $koneksi->prepare("SELECT * FROM tb_user WHERE id = ?");
// Tambahkan pengecekan jika prepare statement gagal
if (!$stmt_user) {
    die("Kesalahan database saat menyiapkan query user: " . $koneksi->error);
}
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

if (!$user) {
    // Jika user tidak ditemukan di DB padahal session ada, mungkin data korup atau dihapus.
    // Hancurkan sesi dan redirect ke login.
    session_unset();
    session_destroy();
    header("Location: /auth/login_customer.php?error=akun_tidak_ditemukan");
    exit;
}

// 2. Ambil data ALAMAT UTAMA dari tb_user_alamat (yang berisi *_id)
$stmt_alamat = $koneksi->prepare("SELECT * FROM tb_user_alamat WHERE user_id = ? AND is_utama = 1");
// Tambahkan pengecekan jika prepare statement gagal
if (!$stmt_alamat) {
    die("Kesalahan database saat menyiapkan query alamat: " . $koneksi->error);
}
$stmt_alamat->bind_param("i", $user_id);
$stmt_alamat->execute();
$result_alamat = $stmt_alamat->get_result();
$alamat_utama = $result_alamat->fetch_assoc(); // Ini akan menjadi array jika ada alamat, atau null jika tidak ada
$stmt_alamat->close();

// 3. Ambil daftar semua provinsi untuk dropdown pertama
$provinces = $koneksi->query("SELECT id, name FROM provinces ORDER BY name ASC");
// Pastikan query provinsi berhasil
if (!$provinces) {
    die("Kesalahan database saat mengambil provinsi: " . $koneksi->error);
}

// Format alamat lengkap untuk ditampilkan di halaman profil (jika ada)
$alamat_lengkap_formatted = "-"; // Nilai default jika tidak ada alamat
// PERBAIKAN: Pastikan variabel alamat_utama terisi sebelum diakses
if ($alamat_utama) { // Jika $alamat_utama ditemukan
    // Kueri untuk nama-nama lokasi (kota, kecamatan, provinsi)
    $provinsi_nama = '';
    $kota_nama = '';
    $kecamatan_nama = '';

    if (!empty($alamat_utama['province_id'])) {
        $stmt_p = $koneksi->prepare("SELECT name FROM provinces WHERE id = ?");
        if ($stmt_p) { $stmt_p->bind_param("i", $alamat_utama['province_id']); $stmt_p->execute(); $provinsi_nama = $stmt_p->get_result()->fetch_assoc()['name'] ?? ''; $stmt_p->close(); }
    }
    if (!empty($alamat_utama['city_id'])) {
        $stmt_c = $koneksi->prepare("SELECT name FROM cities WHERE id = ?");
        if ($stmt_c) { $stmt_c->bind_param("i", $alamat_utama['city_id']); $stmt_c->execute(); $kota_nama = $stmt_c->get_result()->fetch_assoc()['name'] ?? ''; $stmt_c->close(); }
    }
    if (!empty($alamat_utama['district_id'])) {
        $stmt_d = $koneksi->prepare("SELECT name FROM districts WHERE id = ?");
        if ($stmt_d) { $stmt_d->bind_param("i", $alamat_utama['district_id']); $stmt_d->execute(); $kecamatan_nama = $stmt_d->get_result()->fetch_assoc()['name'] ?? ''; $stmt_d->close(); }
    }

    $alamat_lengkap_formatted = 
        htmlspecialchars($alamat_utama['alamat_lengkap']) . '<br>' .
        'Kec. ' . htmlspecialchars($kecamatan_nama) . ', ' .
        htmlspecialchars($kota_nama) . ',<br>' .
        htmlspecialchars($provinsi_nama) .
        (!empty($alamat_utama['kode_pos']) ? ', ' . htmlspecialchars($alamat_utama['kode_pos']) : '');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil - Pondasikita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/navbar_style.css">
    <link rel="stylesheet" href="/assets/css/profil_style.css">
    
    <style>
        .crop-modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .crop-modal.active { display: flex; }
        .crop-modal-content { background-color: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; text-align: center; }
        .crop-modal-content img { max-width: 100%; }
        #crop-image-container { max-height: 60vh; margin-bottom: 15px; }
        .crop-modal-actions { margin-top: 15px; }
        .form-section-header { margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }

        /* ✅ Style tambahan agar Select2 sesuai tema */
        .select2-container .select2-selection--single {
            height: 42px; /* Samakan tinggi dengan input lain */
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        .select2-dropdown {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>

<?php include '../../partials/navbar.php'; // Sesuaikan path ini ?>

<div class="profile-container">
    <div class="profile-header">
        <h2><i class="fa-solid fa-pen-to-square"></i> Edit Profil</h2>
        <p>Perbarui informasi personal dan alamat utama Anda.</p>
    </div>

    <div class="profile-card edit-mode">
        <form action="../../../actions/update_profil.php" method="POST" enctype="multipart/form-data" class="profile-form">
            
            <div class="form-section-header"><h4><i class="fa-solid fa-user-circle"></i> Informasi Akun</h4></div>
            <div class="form-group-split">
                <div class="form-group profile-picture-edit">
                    <label>Foto Profil</label>
                    <img src="/assets/uploads/avatars/<?= htmlspecialchars($user['profile_picture_url'] ?? 'default-avatar.png') ?>" 
                         alt="Foto Profil Saat Ini" 
                         class="profile-picture-preview" id="imagePreview" onerror="this.onerror=null;this.src='/assets/uploads/avatars/default-avatar.png';">
                    <input type="file" name="profile_picture_input" id="profile_picture_input" class="form-control-file" accept="image/jpeg, image/png">
                    <input type="hidden" name="profile_picture_base64" id="profile_picture_base64">
                    <small>Pilih file JPG atau PNG, maks 2MB.</small>
                </div>
                <div class="form-group-column">
                    <div class="form-group"><label for="username">Username</label><input type="text" id="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly disabled><small>Username tidak dapat diubah.</small></div>
                    <div class="form-group"><label for="email">Email</label><input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly disabled><small>Email tidak dapat diubah.</small></div>
                </div>
            </div>
            <div class="form-group"><label for="nama">Nama Lengkap</label><input type="text" id="nama" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required></div>
            <div class="form-group"><label for="no_telepon">Nomor Telepon</label><input type="tel" id="no_telepon" name="no_telepon" class="form-control" value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>" placeholder="Contoh: 081234567890"></div>
            <div class="form-group"><label>Jenis Kelamin</label><div class="radio-group"><label><input type="radio" name="jenis_kelamin" value="Laki-laki" <?= ($user['jenis_kelamin'] ?? '') == 'Laki-laki' ? 'checked' : '' ?>> Laki-laki</label><label><input type="radio" name="jenis_kelamin" value="Perempuan" <?= ($user['jenis_kelamin'] ?? '') == 'Perempuan' ? 'checked' : '' ?>> Perempuan</label></div></div>
            <div class="form-group"><label for="tanggal_lahir">Tanggal Lahir</label><input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>"></div>

            <div class="form-section-header"><h4><i class="fa-solid fa-map-location-dot"></i> Alamat Utama</h4><small>Alamat ini akan digunakan sebagai default saat checkout.</small></div>
            <input type="hidden" name="alamat_id" value="<?= htmlspecialchars($alamat_utama['id'] ?? '') ?>">
            <div class="form-group"><label for="label_alamat">Label Alamat</label><input type="text" id="label_alamat" name="label_alamat" class="form-control" value="<?= htmlspecialchars($alamat_utama['label_alamat'] ?? 'Rumah') ?>" placeholder="Contoh: Rumah, Kantor, Apartemen" required></div>
            <div class="form-group"><label for="nama_penerima">Nama Penerima</label><input type="text" id="nama_penerima" name="nama_penerima" class="form-control" value="<?= htmlspecialchars($alamat_utama['nama_penerima'] ?? $user['nama']) ?>" required></div>
            <div class="form-group"><label for="telepon_penerima">Telepon Penerima</label><input type="tel" id="telepon_penerima" name="telepon_penerima" class="form-control" value="<?= htmlspecialchars($alamat_utama['telepon_penerima'] ?? $user['no_telepon']) ?>" required placeholder="Contoh: 081234567890"></div>
            <div class="form-group"><label for="alamat_lengkap">Alamat Lengkap</label><textarea id="alamat_lengkap" name="alamat_lengkap" class="form-control" rows="3" placeholder="Nama Jalan, Nomor Rumah, RT/RW..." required><?= htmlspecialchars($alamat_utama['alamat_lengkap'] ?? '') ?></textarea></div>
            
            <div class="form-group">
                <label for="provinsi">Provinsi</label>
                <select id="provinsi" name="province_id" class="form-control" required>
                    <option value="">-- Pilih Provinsi --</option>
                    <?php
                    // Pastikan $provinces adalah objek mysqli_result yang valid dan dapat diulang
                    if ($provinces && $provinces->num_rows > 0) {
                        mysqli_data_seek($provinces, 0); 
                        while($row = $provinces->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= (isset($alamat_utama['province_id']) && $alamat_utama['province_id'] == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile;
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="kota_kabupaten">Kota / Kabupaten</label>
                <select id="kota_kabupaten" name="city_id" class="form-control" required disabled>
                    <option value="">-- Pilih Provinsi Terlebih Dahulu --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="kecamatan">Kecamatan</label>
                <select id="kecamatan" name="district_id" class="form-control" required disabled>
                    <option value="">-- Pilih Kota/Kabupaten Terlebih Dahulu --</option>
                </select>
            </div>
            
            <div class="form-group"><label for="kode_pos">Kode Pos</label><input type="text" id="kode_pos" name="kode_pos" class="form-control" value="<?= htmlspecialchars($alamat_utama['kode_pos'] ?? '') ?>" placeholder="Kode Pos (Opsional)"></div>

            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan Perubahan</button><a href="/pages/profil.php" class="btn btn-secondary">Batal</a></div>
        </form>
    </div>
</div>

<div id="cropModal" class="crop-modal">
    <div class="crop-modal-content">
        <h3>Pangkas Foto Profil</h3>
        <div id="crop-image-container">
            <img id="imageToCrop" src="">
        </div>
        <div class="crop-modal-actions">
            <button type="button" id="cropButton" class="btn btn-primary">Pangkas & Gunakan</button>
            <button type="button" id="cancelCropButton" class="btn btn-secondary">Batal</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="/assets/js/navbar.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
// Script Cropper (Tidak Diubah)
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile_picture_input');
    const imagePreview = document.getElementById('imagePreview');
    const base64Input = document.getElementById('profile_picture_base64');
    
    const modal = document.getElementById('cropModal');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropButton = document.getElementById('cropButton');
    const cancelCropButton = document.getElementById('cancelCropButton');
    
    let cropper;

    fileInput.addEventListener('change', function(event) {
        const files = event.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imageToCrop.src = e.target.result;
                modal.classList.add('active');
                
                if (cropper) { cropper.destroy(); }

                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1, viewMode: 1, background: false,
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    cropButton.addEventListener('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({ width: 250, height: 250 });
            imagePreview.src = canvas.toDataURL('image/jpeg');
            base64Input.value = canvas.toDataURL('image/jpeg');
            modal.classList.remove('active');
            cropper.destroy();
            fileInput.value = '';
        }
    });

    cancelCropButton.addEventListener('click', function() {
        modal.classList.remove('active');
        if (cropper) { cropper.destroy(); }
        fileInput.value = '';
    });
});
</script>

<script>
// Script Dropdown Dinamis dengan Select2
$(document).ready(function() {
    const provinsiSelect = $('#provinsi');
    const kotaSelect = $('#kota_kabupaten');
    const kecamatanSelect = $('#kecamatan');

    // Inisialisasi Select2 pada semua dropdown
    provinsiSelect.select2({ placeholder: '-- Pilih Provinsi --', width: '100%' });
    kotaSelect.select2({ placeholder: '-- Pilih Kota/Kabupaten --', width: '100%' });
    kecamatanSelect.select2({ placeholder: '-- Pilih Kecamatan --', width: '100%' });

    // Perhatikan: Existing IDs akan diambil dari PHP saat halaman dimuat
    const existingIds = {
        province: "<?= $alamat_utama['province_id'] ?? '' ?>",
        city: "<?= $alamat_utama['city_id'] ?? '' ?>",
        district: "<?= $alamat_utama['district_id'] ?? '' ?>"
    };

    function fetchAndPopulate(type, parentId, targetSelect, selectedId = null) {
        // Ganti path ini jika lokasi file get_locations.php berbeda
        // Ini diasumsikan get_locations.php ada di direktori yang sama dengan edit_profil.php
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
                
                if (selectedId) {
                    targetSelect.val(selectedId).trigger('change');
                }
                
                targetSelect.prop('disabled', false);
            })
            .catch(error => {
                console.error(`Error fetching ${type}:`, error);
                targetSelect.html(`<option value="">Gagal memuat data</option>`).trigger('change');
            });
    }

    provinsiSelect.on('change', function() {
        const provinceId = $(this).val();
        kotaSelect.val(null).trigger('change');
        kecamatanSelect.val(null).trigger('change').prop('disabled', true).html('<option value="">-- Pilih Kota/Kabupaten Terlebih Dahulu --</option>');
        
        if (provinceId) {
            fetchAndPopulate('kota', provinceId, kotaSelect);
        } else {
            kotaSelect.prop('disabled', true).html('<option value="">-- Pilih Provinsi --</option>');
        }
    });

    kotaSelect.on('change', function() {
        const cityId = $(this).val();
        kecamatanSelect.val(null).trigger('change');

        if (cityId) {
            fetchAndPopulate('kecamatan', cityId, kecamatanSelect);
        } else {
            kecamatanSelect.prop('disabled', true).html('<option value="">-- Pilih Kota/Kabupaten Terlebih Dahulu --</option>');
        }
    });

    function loadExistingAddress() {
        if (existingIds.province) {
            provinsiSelect.val(existingIds.province).trigger('change');
            
            const checkKotaLoaded = setInterval(function() {
                // Pastikan option selain "Memuat..." sudah ada
                if (kotaSelect.find('option').length > 1 && !kotaSelect.prop('disabled')) {
                    clearInterval(checkKotaLoaded);
                    if (existingIds.city) {
                        kotaSelect.val(existingIds.city).trigger('change');
                        
                        const checkKecamatanLoaded = setInterval(function() {
                            // Pastikan option selain "Memuat..." sudah ada
                            if (kecamatanSelect.find('option').length > 1 && !kecamatanSelect.prop('disabled')) {
                                clearInterval(checkKecamatanLoaded);
                                if(existingIds.district) {
                                    kecamatanSelect.val(existingIds.district).trigger('change');
                                }
                            }
                        }, 100);
                    }
                }
            }, 100);
        }
    }
    
    // Panggil setelah inisialisasi awal
    // Diberi sedikit delay agar Select2 siap
    setTimeout(loadExistingAddress, 100);
});
</script>

</body>
</html>
