<?php
// Menggunakan namespace dari PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Memuat autoloader dari Composer. Sesuaikan path jika direktori vendor Anda berbeda.
require '../../../vendor/autoload.php';

// Atur header output sebagai JSON
header('Content-Type: application/json');

// Fungsi untuk membuat template email HTML yang menarik
function buatTemplateEmailHTML($subjek, $isiPesan) {
    // Deteksi dan format voucher
    $isiPesan = preg_replace_callback(
        '/\[VOUCHER:(.*?)\]/',
        function ($matches) {
            return '
                <div style="background-color: #e9ecef; padding: 20px; text-align: center; margin: 20px 0;">
                    <p style="font-size: 16px; margin: 0; color: #495057;">Kode Voucher Spesial Untuk Anda:</p>
                    <div style="font-size: 28px; font-weight: bold; color: #007bff; letter-spacing: 4px; border: 2px dashed #007bff; padding: 15px; margin-top: 10px; display: inline-block;">
                        ' . htmlspecialchars($matches[1]) . '
                    </div>
                </div>';
        },
        htmlspecialchars($isiPesan)
    );

    // Konversi baris baru menjadi tag <br>
    $isiPesanFormatted = nl2br($isiPesan);
    
    // Template Email HTML
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            /* CSS bisa ditaruh di sini */
        </style>
    </head>
    <body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); overflow: hidden;">
                        <tr>
                            <td align="center" style="background-color: #007bff; padding: 20px; color: #ffffff;">
                                <h1 style="margin: 0; font-size: 24px;">Pesan Dari Toko Anda</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 30px 20px; color: #333333; line-height: 1.6;">
                                <h2 style="color: #007bff;">' . htmlspecialchars($subjek) . '</h2>
                                <p>' . $isiPesanFormatted . '</p>
                                <p>Terima kasih atas perhatian Anda.</p>
                                <p>Hormat kami,<br>Tim Toko Anda</p>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" style="background-color: #f8f9fa; padding: 15px; font-size: 12px; color: #6c757d;">
                                &copy; ' . date("Y") . ' Toko Anda. Semua Hak Cipta Dilindungi.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}


// Validasi input dari form
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email_penerima']) || empty($_POST['subjek']) || empty($_POST['isi_pesan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit;
}

$emailPenerima = $_POST['email_penerima'];
$subjek = $_POST['subjek'];
$isiPesan = $_POST['isi_pesan'];


// Inisialisasi PHPMailer
$mail = new PHPMailer(true);

try {
    // Pengaturan Server SMTP Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'prabualamxi@gmail.com';  // GANTI DENGAN EMAIL ANDA
    $mail->Password   = 'msvn hkni daqy pohl';   // GANTI DENGAN APP PASSWORD GMAIL ANDA
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Pengaturan Pengirim dan Penerima
    $mail->setFrom('emailanda@gmail.com', 'Admin Toko Anda'); // GANTI DENGAN NAMA DAN EMAIL ANDA
    $mail->addAddress($emailPenerima);

    // Buat konten email menggunakan template
    $mail->isHTML(true);
    $mail->Subject = $subjek;
    $mail->Body    = buatTemplateEmailHTML($subjek, $isiPesan);
    $mail->AltBody = $isiPesan; // Versi teks biasa

    $mail->send();
    echo json_encode(['status' => 'success', 'message' => 'Email berhasil dikirim!']);
} catch (Exception $e) {
    // Kirim response error dalam format JSON
    echo json_encode(['status' => 'error', 'message' => "Pesan tidak dapat dikirim. Mailer Error: {$mail->ErrorInfo}"]);
}