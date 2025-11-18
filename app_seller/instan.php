<?php
// Logika untuk memeriksa login seller
// session_start();
// if (!isset($_SESSION['seller_id'])) { ... }

// Logika untuk memproses saat seller memilih template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_template'])) {
    $template_id = $_POST['template_id'];
    $seller_id = 1; // Ganti dengan ID seller yang sedang login dari session

    // Di sini Anda akan menyimpan pilihan ke database
    // Contoh:
    // $pdo = new PDO(...);
    // $stmt = $pdo->prepare("UPDATE sellers SET shop_template = ? WHERE id = ?");
    // $stmt->execute([$template_id, $seller_id]);

    $message = "Template '" . htmlspecialchars($template_id) . "' berhasil diterapkan!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pilih Template Instan</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; }
        h1 { text-align: center; }
        .template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .template-card { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .template-card img { width: 100%; display: block; background-color: #eee; }
        .template-card .info { padding: 15px; }
        .template-card h3 { margin: 0 0 10px 0; }
        .btn-apply { width: 100%; background: #5cb85c; color: white; border: none; padding: 10px; font-size: 1em; border-radius: 5px; cursor: pointer; }
        .message { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: center;}
    </style>
</head>
<body>

<div class="container">
    <h1>Pilih Template Instan</h1>

    <?php if (isset($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="template-grid">
        <div class="template-card">
            <img src="https://via.placeholder.com/300x400.png?text=Template+Produk+Terlaris" alt="Template Produk Terlaris">
            <div class="info">
                <h3>Produk Terlaris</h3>
                <form method="POST">
                    <input type="hidden" name="template_id" value="template_best_seller">
                    <button type="submit" name="apply_template" class="btn-apply">Terapkan</button>
                </form>
            </div>
        </div>

        <div class="template-card">
            <img src="https://via.placeholder.com/300x400.png?text=Template+Minimalis" alt="Template Minimalis">
            <div class="info">
                <h3>Minimalis Modern</h3>
                 <form method="POST">
                    <input type="hidden" name="template_id" value="template_minimalist">
                    <button type="submit" name="apply_template" class="btn-apply">Terapkan</button>
                </form>
            </div>
        </div>

        <div class="template-card">
            <img src="https://via.placeholder.com/300x400.png?text=Template+Flash+Sale" alt="Template Flash Sale">
            <div class="info">
                <h3>Fokus Flash Sale</h3>
                 <form method="POST">
                    <input type="hidden" name="template_id" value="template_flash_sale">
                    <button type="submit" name="apply_template" class="btn-apply">Terapkan</button>
                </form>
            </div>
        </div>
        
         </div>
</div>

</body>
</html>