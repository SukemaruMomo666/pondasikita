<?php
// Logika untuk memeriksa login seller...

// Logika untuk menyimpan layout custom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['layout_data'])) {
    $layout_json = $_POST['layout_data']; // Ini akan berisi data JSON dari JavaScript
    $seller_id = 1; // Ganti dengan ID seller dari session

    // Validasi bahwa $layout_json adalah JSON yang valid
    // json_decode($layout_json);
    // if (json_last_error() === JSON_ERROR_NONE) {
        // Simpan ke database
        // $stmt = $pdo->prepare("UPDATE sellers SET shop_layout = ?, shop_template = NULL WHERE id = ?");
        // $stmt->execute([$layout_json, $seller_id]);
        $message = "Layout toko berhasil disimpan!";
    // } else {
    //     $error_message = "Terjadi kesalahan saat menyimpan layout.";
    // }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Editor Dekorasi Toko</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .editor-container { display: flex; height: 100vh; }
        .components-panel { width: 250px; border-right: 1px solid #ccc; padding: 10px; background: #f9f9f9; }
        .components-panel h3 { margin-top: 0; }
        .component { padding: 10px; border: 1px dashed #aaa; margin-bottom: 10px; background: #fff; cursor: grab; text-align: center; }
        
        .canvas-panel { flex-grow: 1; padding: 20px; background: #e9e9e9; }
        .canvas-wrapper { max-width: 400px; margin: auto; background: #fff; min-height: 700px; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
        #shop-canvas { min-height: 650px; border: 2px dashed #ccc; padding: 10px; }
        #shop-canvas .component-instance { padding: 20px; border: 1px solid #ddd; margin-bottom: 10px; background: #f0f8ff; position: relative; }
        #shop-canvas .component-instance .delete-btn { position: absolute; top: 5px; right: 5px; cursor: pointer; color: red; font-weight: bold; }

        .top-bar { padding: 10px; background: #333; color: white; text-align: right; }
        .btn-save { background: #d9534f; color: white; padding: 10px 20px; border: none; font-size: 1em; cursor: pointer; border-radius: 5px; }
        .drag-over { background-color: #dff0d8; border-color: #3c763d; }
    </style>
</head>
<body>

<div class="top-bar">
    <form id="save-layout-form" method="POST" style="display: inline;">
        <input type="hidden" name="layout_data" id="layout_data_input">
        <button type="button" class="btn-save" onclick="saveLayout()">Simpan Tampilan</button>
    </form>
</div>

<div class="editor-container">
    <div class="components-panel">
        <h3>Komponen</h3>
        <div class="component" draggable="true" data-type="banner">Banner Toko</div>
        <div class="component" draggable="true" data-type="produk_unggulan">Produk Unggulan</div>
        <div class="component" draggable="true" data-type="video">Video</div>
        <div class="component" draggable="true" data-type="kategori">Daftar Kategori</div>
    </div>

    <div class="canvas-panel">
        <div class="canvas-wrapper">
             <div id="shop-canvas">
                <div class="component-instance" data-type="banner">Banner Toko <span class="delete-btn" onclick="this.parentElement.remove()">X</span></div>
            </div>
        </div>
    </div>
</div>

<script>
    const components = document.querySelectorAll('.component');
    const canvas = document.getElementById('shop-canvas');

    // Menangani event 'dragstart' pada komponen di panel kiri
    components.forEach(component => {
        component.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', e.target.dataset.type);
            e.target.style.opacity = '0.5';
        });
        component.addEventListener('dragend', (e) => {
            e.target.style.opacity = '1';
        });
    });

    // Mencegah default browser agar drop bisa terjadi
    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
        canvas.classList.add('drag-over');
    });

    canvas.addEventListener('dragleave', () => {
        canvas.classList.remove('drag-over');
    });
    
    // Menangani event 'drop' pada kanvas
    canvas.addEventListener('drop', (e) => {
        e.preventDefault();
        canvas.classList.remove('drag-over');
        const componentType = e.dataTransfer.getData('text/plain');
        
        // Buat elemen baru di kanvas
        const newInstance = document.createElement('div');
        newInstance.className = 'component-instance';
        newInstance.setAttribute('data-type', componentType);
        newInstance.textContent = componentType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()); // Format teks
        
        // Tambahkan tombol hapus
        const deleteBtn = document.createElement('span');
        deleteBtn.className = 'delete-btn';
        deleteBtn.textContent = 'X';
        deleteBtn.onclick = () => newInstance.remove();
        
        newInstance.appendChild(deleteBtn);
        canvas.appendChild(newInstance);
    });

    // Fungsi untuk menyimpan layout
    function saveLayout() {
        const layout = [];
        const instances = canvas.querySelectorAll('.component-instance');
        
        instances.forEach(instance => {
            // Di dunia nyata, Anda mungkin ingin menyimpan lebih banyak detail
            // seperti ID produk spesifik, URL gambar banner, dll.
            layout.push({
                type: instance.dataset.type
                // contoh: config: { productId: 123 }
            });
        });
        
        // Masukkan data layout (dalam format JSON) ke input form
        const layoutDataInput = document.getElementById('layout_data_input');
        layoutDataInput.value = JSON.stringify(layout);

        // Kirim form
        if (confirm('Apakah Anda yakin ingin menyimpan layout ini?')) {
            document.getElementById('save-layout-form').submit();
        }
    }
</script>

</body>
</html>