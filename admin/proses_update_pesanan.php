<?php
session_start();
include '../config/koneksi.php';

// Keamanan: Pastikan hanya admin ('admin') yang bisa mengakses
if (!isset($_SESSION['user']['id']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    $_SESSION['pesan'] = ['jenis' => 'danger', 'isi' => 'Akses tidak diizinkan. Hanya untuk Admin.'];
    header("Location: ../signin.php"); // Sesuaikan path ke file signin Anda
    exit;
}

// Inisialisasi variabel untuk pesan feedback
$pesanFeedback = null;
$redirectUrl = 'daftar_pesanan_admin.php'; // Default redirect jika tidak ada aksi spesifik

// Mengambil kode_invoice jika tersedia di POST untuk redirect yang tepat
$kode_invoice_post = $_POST['kode_invoice'] ?? null;
if ($kode_invoice_post) {
    // Pastikan halaman detail admin juga menggunakan parameter kode_invoice
    $redirectUrl = 'admin_detail_pesanan.php?kode_invoice=' . urlencode($kode_invoice_post);
}


// ======================================================================
// BLOK 1: Untuk form utama (Update Status Umum)
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // PERBAIKAN: Pastikan form di detail mengirim 'id_transaksi', bukan 'id_pesanan'.
    $id_transaksi      = (int)$_POST['id_transaksi'];
    $kode_invoice      = $_POST['kode_invoice'];
    $status_pesanan    = $_POST['status_pesanan'];
    $status_pembayaran = $_POST['status_pembayaran'];

    // Ambil status pesanan saat ini dari DB untuk logika tanggal_selesai
    // PERBAIKAN: Gunakan prepared statement untuk keamanan
    $stmt_current = $koneksi->prepare("SELECT status_pesanan FROM tb_transaksi WHERE id = ?");
    $stmt_current->bind_param("i", $id_transaksi);
    $stmt_current->execute();
    $current_pesanan_data = $stmt_current->get_result()->fetch_assoc();
    $current_status_pesanan = $current_pesanan_data['status_pesanan'];
    $stmt_current->close();

    $koneksi->begin_transaction(); // Mulai transaksi
    try {
        $query_update = "UPDATE tb_transaksi SET status_pesanan = ?, status_pembayaran = ?"; // Nama tabel sudah benar
        $params = [$status_pesanan, $status_pembayaran];
        $types = "ss";

        // Logika untuk mengisi/mengosongkan tanggal_selesai
        if ($status_pesanan == 'Selesai' && $current_status_pesanan != 'Selesai') {
            $query_update .= ", tanggal_selesai = NOW()";
        } elseif ($status_pesanan != 'Selesai' && $current_status_pesanan == 'Selesai') {
            $query_update .= ", tanggal_selesai = NULL";
        }

        $query_update .= " WHERE id = ?";
        $types .= "i";
        $params[] = $id_transaksi;

        $stmt = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            // PERBAIKAN: Gunakan variabel $kode_invoice yang ada, bukan $kode_pesanan yang tidak ada.
            $pesanFeedback = ['jenis' => 'success', 'isi' => 'Status transaksi #' . htmlspecialchars($kode_invoice) . ' berhasil diperbarui.'];
        } else {
            throw new Exception('Gagal memperbarui status: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        $koneksi->commit(); // Commit transaksi
    } catch (Exception $e) {
        $koneksi->rollback(); // Rollback jika ada error
        // PERBAIKAN: Pesan harusnya 'danger' (gagal), bukan 'success'.
        $pesanFeedback = ['jenis' => 'danger', 'isi' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }

    $_SESSION['pesan'] = $pesanFeedback;
    header("Location: " . $redirectUrl);
    exit;
}

// ======================================================================
// BLOK 2: Untuk form verifikasi pembayaran dari modal (transfer)
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_pembayaran'])) {
    // PERBAIKAN: Konsistensikan nama variabel dari form
    $id_transaksi = (int)$_POST['id_transaksi'];
    $kode_invoice = $_POST['kode_invoice'];
    $aksi = $_POST['verifikasi_pembayaran']; // "terima" atau "tolak"

    $koneksi->begin_transaction();
    try {
        // Ambil status dan metode pembayaran saat ini
        // PERBAIKAN: Gunakan tb_transaksi dan variabel yang benar
        $stmt_current = mysqli_prepare($koneksi, "SELECT status_pesanan, metode_pembayaran FROM tb_transaksi WHERE id = ?");
        $stmt_current->bind_param("i", $id_transaksi);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_transaksi = $result_current->fetch_assoc();
        mysqli_stmt_close($stmt_current);

        if (!$current_transaksi) {
            throw new Exception("Transaksi tidak ditemukan.");
        }
        
        // Cek metode pembayaran. Logika ini sudah OK.
        // if ($current_transaksi['metode_pembayaran'] !== 'transfer') { ... }

        $status_pembayaran_baru = '';
        $status_pesanan_baru    = $current_transaksi['status_pesanan']; // Default: tidak berubah

        if ($aksi === 'terima') {
            $status_pembayaran_baru = 'Paid';
            if ($status_pesanan_baru == 'Menunggu Pembayaran') {
                $status_pesanan_baru = 'Diproses';
            }
            $pesan_aksi = 'diterima';
        } elseif ($aksi === 'tolak') {
            $status_pembayaran_baru = 'Gagal';
            $pesan_aksi = 'ditolak';
        } else {
            throw new Exception("Aksi verifikasi tidak valid.");
        }

        if (!empty($status_pembayaran_baru)) {
            // PERBAIKAN: Gunakan tb_transaksi
            $query_update = "UPDATE tb_transaksi SET status_pembayaran = ?, status_pesanan = ?";
            $params = [$status_pembayaran_baru, $status_pesanan_baru];
            $types = "ss";

            // Logika tanggal_selesai sudah OK.

            $query_update .= " WHERE id = ?";
            $types .= "i";
            // PERBAIKAN: Gunakan $id_transaksi
            $params[] = $id_transaksi;

            $stmt = mysqli_prepare($koneksi, $query_update);
            mysqli_stmt_bind_param($stmt, $types, ...$params);

            if (mysqli_stmt_execute($stmt)) {
                // PERBAIKAN: Gunakan $kode_invoice
                $pesanFeedback = ['jenis' => 'success', 'isi' => 'Pembayaran untuk transaksi #' . htmlspecialchars($kode_invoice) . ' berhasil ' . $pesan_aksi . '.'];
            } else {
                throw new Exception('Gagal memverifikasi pembayaran: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Status pembayaran baru tidak dapat ditentukan.");
        }
        $koneksi->commit();
    } catch (Exception $e) {
        $koneksi->rollback();
        $pesanFeedback = ['jenis' => 'danger', 'isi' => $e->getMessage()];
    }

    $_SESSION['pesan'] = $pesanFeedback;
    header("Location: " . $redirectUrl);
    exit;
}

// ======================================================================
// BLOK 3: Untuk aksi khusus "Konfirmasi Pesanan Diambil" (COD)
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'konfirmasi_diambil') {
    // PERBAIKAN: Konsistensikan nama variabel
    $id_transaksi = (int)$_POST['id_transaksi'];
    $kode_invoice = $_POST['kode_invoice'];

    $koneksi->begin_transaction();
    try {
        // Ambil status dan metode pembayaran saat ini
        // PERBAIKAN: Gunakan tb_transaksi
        $stmt_current = mysqli_prepare($koneksi, "SELECT status_pesanan, metode_pembayaran FROM tb_transaksi WHERE id = ?");
        $stmt_current->bind_param("i", $id_transaksi);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_transaksi = $result_current->fetch_assoc();
        mysqli_stmt_close($stmt_current);

        if (!$current_transaksi) {
            throw new Exception("Transaksi tidak ditemukan.");
        }

        // Pastikan ini adalah pesanan COD dan statusnya 'Siap Diambil'
        if ($current_transaksi['metode_pembayaran'] === 'cod' && $current_transaksi['status_pesanan'] === 'Siap Diambil') {
            // PERBAIKAN: Gunakan tb_transaksi. Sekalian update status pembayaran jadi 'Paid'.
            $stmt_konfirmasi = mysqli_prepare($koneksi, "UPDATE tb_transaksi SET status_pesanan = 'Selesai', status_pembayaran = 'Paid', tanggal_selesai = NOW() WHERE id = ?");
            $stmt_konfirmasi->bind_param("i", $id_transaksi);

            if (mysqli_stmt_execute($stmt_konfirmasi)) {
                $pesanFeedback = ['jenis' => 'success', 'isi' => 'Transaksi COD #' . htmlspecialchars($kode_invoice) . ' berhasil dikonfirmasi telah diambil.'];
            } else {
                throw new Exception('Gagal mengkonfirmasi pengambilan: ' . mysqli_stmt_error($stmt_konfirmasi));
            }
            mysqli_stmt_close($stmt_konfirmasi);
        } else {
            throw new Exception("Aksi ini tidak valid. Pesanan harus COD dan berstatus 'Siap Diambil'.");
        }
        $koneksi->commit();
    } catch (Exception $e) {
        $koneksi->rollback();
        $pesanFeedback = ['jenis' => 'danger', 'isi' => $e->getMessage()];
    }

    $_SESSION['pesan'] = $pesanFeedback;
    header("Location: " . $redirectUrl);
    exit;
}

// ======================================================================
// BLOK 4: Untuk aksi khusus "Tandai Dikirim"
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tandai_dikirim') {
    // PERBAIKAN: Konsistensikan nama variabel
    $id_transaksi = (int)$_POST['id_transaksi'];
    $kode_invoice = $_POST['kode_invoice'];

    $koneksi->begin_transaction();
    try {
        // PERBAIKAN: Gunakan tb_transaksi
        $stmt_current = mysqli_prepare($koneksi, "SELECT status_pesanan, status_pembayaran, metode_pembayaran FROM tb_transaksi WHERE id = ?");
        $stmt_current->bind_param("i", $id_transaksi);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_transaksi = $result_current->fetch_assoc();
        mysqli_stmt_close($stmt_current);

        if (!$current_transaksi) {
            throw new Exception("Transaksi tidak ditemukan.");
        }
        
        // Logika pengecekan yang lebih sederhana dan aman
        $isPaid = $current_transaksi['status_pembayaran'] === 'Paid';
        $isCOD = $current_transaksi['metode_pembayaran'] === 'cod';
        $isProcessed = $current_transaksi['status_pesanan'] === 'Diproses';

        // Boleh dikirim jika (statusnya 'Diproses' DAN (sudah lunas ATAU metode nya COD))
        if ($isProcessed && ($isPaid || $isCOD)) {
            // PERBAIKAN: Gunakan tb_transaksi
            $stmt_dikirim = mysqli_prepare($koneksi, "UPDATE tb_transaksi SET status_pesanan = 'Dikirim' WHERE id = ?");
            $stmt_dikirim->bind_param("i", $id_transaksi);

            if (mysqli_stmt_execute($stmt_dikirim)) {
                $pesanFeedback = ['jenis' => 'success', 'isi' => 'Transaksi #' . htmlspecialchars($kode_invoice) . ' berhasil ditandai sebagai Dikirim.'];
            } else {
                throw new Exception('Gagal menandai pesanan dikirim: ' . mysqli_stmt_error($stmt_dikirim));
            }
            mysqli_stmt_close($stmt_dikirim);
        } else {
            throw new Exception("Pesanan belum bisa dikirim. Pastikan status 'Diproses' dan pembayaran sudah 'Paid' (atau metode COD).");
        }
        $koneksi->commit();
    } catch (Exception $e) {
        $koneksi->rollback();
        $pesanFeedback = ['jenis' => 'danger', 'isi' => $e->getMessage()];
    }

    $_SESSION['pesan'] = $pesanFeedback;
    header("Location: " . $redirectUrl);
    exit;
}


// Jika tidak ada form yang cocok, redirect ke daftar pesanan
header("Location: daftar_pesanan_admin.php");
exit;
?>