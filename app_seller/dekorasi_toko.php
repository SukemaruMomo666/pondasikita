<?php
// Di sini Anda akan memiliki logika untuk memeriksa apakah seller sudah login.
// session_start();
// if (!isset($_SESSION['seller_id'])) {
//     header('Location: /login.php');
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dekorasi Toko</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        p { color: #666; text-align: center; font-size: 1.1em; }
        .options-wrapper { display: flex; justify-content: space-around; margin-top: 40px; gap: 20px; }
        .option-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; width: 45%; text-align: center; transition: box-shadow 0.3s; }
        .option-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .option-card h2 { margin-top: 0; color: #d9534f; }
        .option-card p { font-size: 0.9em; }
        .btn { display: inline-block; background-color: #d9534f; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Dekorasi Toko Anda</h1>
        <p>Tingkatkan penjualan dengan membuat tampilan toko yang unik dan menarik bagi pengunjung. Mulai hanya dengan 2 langkah!</p>

        <div class="options-wrapper">
            <div class="option-card">
                <h2>Dekorasi Instan</h2>
                <p>Tidak punya banyak waktu? Gunakan template siap pakai yang sudah kami optimalkan untuk menarik pembeli.</p>
                <a href="instan.php" class="btn">Pilih Template Instan</a>
            </div>

            <div class="option-card">
                <h2>Dekorasi Custom</h2>
                <p>Punya ide kreatif? Atur sendiri tata letak tokomu dari awal menggunakan komponen yang tersedia.</p>
                <a href="custom.php" class="btn">Buat Dekorasi Sendiri</a>
            </div>
        </div>
    </div>

</body>
</html>