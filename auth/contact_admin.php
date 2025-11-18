<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<style>
    body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f7f6;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    color: #333;
}

.container {
    background-color: #ffffff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    box-sizing: border-box;
}

h2 {
    text-align: center;
    color: #007bff;
    margin-bottom: 25px;
    font-size: 28px;
    font-weight: bold;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #555;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 16px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group textarea:focus {
    border-color: #5e1914;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.25);
    outline: none;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.btn-submit {
    width: 100%;
    padding: 12px;
    background-color: #5e1914;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.btn-submit:hover {
    background-color: #5e1914;
    transform: translateY(-2px);
}

.btn-submit:active {
    transform: translateY(0);
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
    font-weight: bold;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
    font-weight: bold;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .container {
        margin: 20px;
        padding: 25px 30px;
    }

    h2 {
        font-size: 24px;
    }

    .btn-submit {
        font-size: 16px;
        padding: 10px;
    }
}
</style>
<body>
    <div class="container">
        <h2>Hubungi Admin</h2>
        <?php
            if (isset($_SESSION['message'])) {
                echo "<p class='success-message'>" . htmlspecialchars($_SESSION['message']) . "</p>";
                unset($_SESSION['message']);
            }

            if (isset($_SESSION['error'])) {
                echo "<p class='error-message'>" . htmlspecialchars($_SESSION['error']) . "</p>";
                unset($_SESSION['error']);
            }
        ?>
        <form action="../proses/kirim_pesan.php" method="POST">
            <div class="form-group">
                <label for="name">Nama Anda</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Anda</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="message">Pesan</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn-submit">Kirim Pesan</button>
        </form>
    </div>
</body>
</html>
