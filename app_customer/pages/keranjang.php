<?php
require_once '../../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PERUBAHAN LOGIKA: DATA DIKELOMPOKKAN PER TOKO
$keranjang_per_toko = [];
// PERBAIKAN DI SINI: Akses ID user dari $_SESSION['user']['id']
// UBAH BARIS INI:
// if (isset($_SESSION['user_id'])) {
//     $user_id = $_SESSION['user_id'];
// MENJADI INI:
if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id']; // <-- INI PERBAIKANNYA

    // ... (sisa kode query dan proses data di bawahnya tetap sama)
    $query_str = "SELECT 
                      k.id AS id_keranjang, 
                      b.id AS barang_id,
                      b.nama_barang, 
                      b.harga, 
                      b.gambar_utama AS gambar, 
                      k.jumlah,
                      t.id AS toko_id,
                      t.nama_toko,
                      t.slug AS slug_toko
                    FROM tb_keranjang k 
                    JOIN tb_barang b ON k.barang_id = b.id 
                    JOIN tb_toko t ON b.toko_id = t.id
                    WHERE k.user_id = ?";
                    
    $stmt = $koneksi->prepare($query_str);
    // Tambahkan pengecekan jika prepare statement gagal
    if (!$stmt) {
        error_log("Prepare statement gagal di keranjang.php: " . $koneksi->error);
        // Mungkin tampilkan pesan error ke user jika diperlukan
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Proses dan kelompokkan data
        while ($item = $result->fetch_assoc()) {
            $toko_id = $item['toko_id'];
            if (!isset($keranjang_per_toko[$toko_id])) {
                $keranjang_per_toko[$toko_id] = [
                    'nama_toko' => $item['nama_toko'],
                    'slug_toko' => $item['slug_toko'],
                    'items' => []
                ];
            }
            $keranjang_per_toko[$toko_id]['items'][] = $item;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Pondasikita</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/navbar_style.css">
    <link rel="stylesheet" href="/assets/css/keranjang_page_style.css">
</head>
<body>

<?php 
    // ✅ PERBAIKAN: Menggunakan path absolut dari file ini untuk include
    // Pastikan partials/navbar.php ada di direktori yang benar dari keranjang.php
    // Jika keranjang.php ada di app_customer/pages/, maka partials/navbar.php ada di partials/
    // Jadi, pathnya adalah '../partials/navbar.php'
    include __DIR__ . '../partials/navbar.php'; 
?>

<div class="cart-page-container">
    <h2><i class="fas fa-shopping-cart"></i> Keranjang Belanja Anda</h2>

    <form id="keranjang-form" method="post" action="checkout.php">
        <?php if (!empty($keranjang_per_toko)): ?>
            <div class="cart-header">
                <label class="select-all-container">
                    <input type="checkbox" id="select-all" /> Pilih Semua
                </label>
            </div>

            <?php foreach ($keranjang_per_toko as $toko_id => $data_toko): ?>
                <div class="store-cart-group">
                    <div class="store-header">
                        <input type="checkbox" class="store-checkbox" data-toko-id="<?= $toko_id ?>">
                        <i class="fas fa-store-alt"></i>
                        <a href="toko.php?slug=<?= $data_toko['slug_toko'] ?>"><?= htmlspecialchars($data_toko['nama_toko']) ?></a>
                    </div>

                    <?php foreach ($data_toko['items'] as $item): 
                        $subtotal = $item['harga'] * $item['jumlah'];
                    ?>
                        <div class="cart-item">
                            <input type="checkbox" name="selected_items[]" value="<?= $item['id_keranjang'] ?>" class="item-checkbox" data-price="<?= $item['harga'] ?>" data-quantity="<?= $item['jumlah'] ?>" data-toko-id="<?= $toko_id ?>">
                            <img src="/assets/uploads/products/<?= htmlspecialchars($item['gambar'] ?? 'default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['nama_barang']) ?>"
                                 onerror="this.onerror=null; this.src='/assets/uploads/products/default.jpg';">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                <p class="item-price">Rp<?= number_format($item['harga'], 0, ',', '.') ?></p>
                                <div class="quantity-control">
                                    <button type="button" class="btn-qty-update" data-action="decrease" data-id="<?= $item['id_keranjang'] ?>">-</button>
                                    <span class="qty-display"><?= $item['jumlah'] ?></span>
                                    <button type="button" class="btn-qty-update" data-action="increase" data-id="<?= $item['id_keranjang'] ?>">+</button>
                                </div>
                            </div>
                            <div class="item-subtotal">
                                <span>Subtotal</span>
                                <p>Rp<?= number_format($subtotal, 0, ',', '.') ?></p>
                            </div>
                            <button type="button" class="btn-remove btn-qty-update" data-action="remove" data-id="<?= $item['id_keranjang'] ?>">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="cart-summary">
                <div class="total-price">
                    Total (<span id="total-items-count">0</span> barang): <strong>Rp<span id="total-price">0</span></strong>
                </div>
                <button type="submit" class="btn-checkout" id="checkout-button" disabled>Checkout</button>
            </div>
        <?php else: ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-cart fa-4x"></i>
                <p>Keranjang Anda masih kosong.</p>
                <a href="/index.php" class="btn-primary">Mulai Belanja</a>
            </div>
        <?php endif; ?>
    </form>
</div>
<script src="/assets/js/navbar.js"></script>
<script>
// ... (Kode JavaScript Anda yang tidak berubah) ...
document.addEventListener("DOMContentLoaded", () => {
    function updateCartAction(action, itemId) {
        if (action === 'remove' && !confirm('Anda yakin ingin menghapus item ini?')) return;

        const formData = new FormData();
        formData.append('action', action);
        formData.append('item_id', itemId);

        // ✅ PERBAIKAN: Menggunakan path absolut dari root website
        fetch('/actions/update_keranjang.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Muat ulang halaman untuk melihat perubahan
                    location.reload(); 
                } else {
                    // Tampilkan pesan error dari server
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('AJAX Error:', error));
    }

    document.querySelectorAll('.btn-qty-update').forEach(button => {
        button.addEventListener('click', function() {
            updateCartAction(this.dataset.action, this.dataset.id);
        });
    });

    // Logika perhitungan total dan checkbox (tidak diubah)
    const selectAllCheckbox = document.getElementById("select-all");
    const itemCheckboxes = document.querySelectorAll(".item-checkbox");
    const storeCheckboxes = document.querySelectorAll(".store-checkbox");
    const checkoutButton = document.getElementById('checkout-button');
    
    function updateTotalDisplay() {
        let currentTotal = 0;
        let itemsCount = 0;
        itemCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const price = parseFloat(checkbox.dataset.price);
                const qty = parseInt(checkbox.dataset.quantity);
                currentTotal += price * qty;
                itemsCount++;
            }
        });
        document.getElementById('total-price').textContent = currentTotal.toLocaleString('id-ID');
        document.getElementById('total-items-count').textContent = itemsCount;
        checkoutButton.disabled = currentTotal === 0;
    }
    
    selectAllCheckbox?.addEventListener('change', () => {
        itemCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        storeCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        updateTotalDisplay();
    });

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            if(!cb.checked && selectAllCheckbox) selectAllCheckbox.checked = false;
            updateTotalDisplay();
        });
    });

    storeCheckboxes.forEach(storeCb => {
        storeCb.addEventListener('change', function() {
            const tokoId = this.dataset.tokoId;
            document.querySelectorAll(`.item-checkbox[data-toko-id="${tokoId}"]`).forEach(itemCb => {
                itemCb.checked = this.checked;
            });
            updateTotalDisplay();
        });
    });

    updateTotalDisplay();
});
</script>
</body>
</html>