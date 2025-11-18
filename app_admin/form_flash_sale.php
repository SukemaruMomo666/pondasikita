<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'admin') {
    header("Location: login_mimin.php");
    exit;
}

// --- LOGIKA UNTUK MODE EDIT ATAU TAMBAH ---
$is_edit_mode = false;
$event_data = [
    'id' => '',
    'nama_event' => '',
    'banner_event' => '',
    'tanggal_mulai' => '',
    'tanggal_berakhir' => '',
    'is_active' => 1 // Default aktif saat buat baru
];

if (isset($_GET['id'])) {
    $is_edit_mode = true;
    $event_id = (int)$_GET['id'];
    
    $stmt = $koneksi->prepare("SELECT * FROM tb_flash_sale_events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $event_data = $result->fetch_assoc();
    } else {
        die("Event tidak ditemukan.");
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $is_edit_mode ? 'Edit' : 'Buat' ?> Event Flash Sale - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
</head>
<body>
<div class="container-scroller">
    <?php 
    $current_page_name = 'kelola_flash_sale.php';
    include 'partials/sidebar_admin.php'; 
    ?>
    <div class="page-body-wrapper">
        <main class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title"><?= $is_edit_mode ? 'Edit' : 'Buat' ?> Event Flash Sale</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="actions/proses_flash_sale.php" method="POST" enctype="multipart/form-data">
                            <!-- Input tersembunyi untuk ID saat mode edit -->
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="event_id" value="<?= $event_data['id'] ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="nama_event">Nama Event</label>
                                <input type="text" name="nama_event" id="nama_event" class="form-control" 
                                       value="<?= htmlspecialchars($event_data['nama_event']) ?>" required 
                                       placeholder="Contoh: Flash Sale Gajian 7.7">
                            </div>

                            <div class="form-group">
                                <label for="banner_event">Banner Event (Opsional)</label>
                                <input type="file" name="banner_event" id="banner_event" class="form-control" accept="image/jpeg, image/png">
                                <small class="text-secondary">Rekomendasi ukuran: 1200x400 pixel. Kosongkan jika tidak ingin mengubah banner yang ada.</small>
                                <?php if ($is_edit_mode && !empty($event_data['banner_event'])): ?>
                                    <div class="mt-2">
                                        <p>Banner saat ini:</p>
                                        <img src="../assets/uploads/flash_sale/<?= htmlspecialchars($event_data['banner_event']) ?>" alt="Banner" style="max-width: 300px; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal_mulai">Tanggal Mulai</label>
                                        <input type="datetime-local" name="tanggal_mulai" id="tanggal_mulai" class="form-control" 
                                               value="<?= !empty($event_data['tanggal_mulai']) ? date('Y-m-d\TH:i', strtotime($event_data['tanggal_mulai'])) : '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal_berakhir">Tanggal Berakhir</label>
                                        <input type="datetime-local" name="tanggal_berakhir" id="tanggal_berakhir" class="form-control" 
                                               value="<?= !empty($event_data['tanggal_berakhir']) ? date('Y-m-d\TH:i', strtotime($event_data['tanggal_berakhir'])) : '' ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check form-switch mb-4">
                               <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $event_data['is_active'] ? 'checked' : '' ?>>
                               <label class="form-check-label" for="is_active">Aktifkan Event</label>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Simpan Event</button>
                                <a href="kelola_flash_sale.php" class="btn btn-outline">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
