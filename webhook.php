<?php
// Konfigurasi database
include_once 'db.php';
// Load library phpdotenv
require 'vendor/autoload.php';

// Inisialisasi Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$email_gmail = $_ENV['GMAIL_USER'];
$app_password = $_ENV['GMAIL_PASS'];

// Inisialisasi objek Database dan Login
$db = new Database();
$conn = $db->getConnection();

// Dapatkan data payload dari Midtrans
$input = file_get_contents('php://input');
if ($input === false || empty($input)) {
    file_put_contents('webhook_log.txt', "Payload kosong diterima.\n", FILE_APPEND);
    http_response_code(400); // Bad Request
    die("Payload kosong.");
}

$json = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents('webhook_log.txt', "Payload tidak valid: " . json_last_error_msg() . "\n", FILE_APPEND);
    http_response_code(400); // Bad Request
    die("Payload tidak valid.");
}

// Log untuk debugging
file_put_contents('webhook_log.txt', "Payload diterima: " . json_encode($json, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

$order_id = $json['order_id'] ?? '';
$transaction_status = $json['transaction_status'] ?? '';
$fraud_status = $json['fraud_status'] ?? '';

// Validasi data
if (empty($order_id) || empty($transaction_status)) {
    file_put_contents('webhook_log.txt', "Payload tidak lengkap.\n", FILE_APPEND);
    http_response_code(400); // Bad Request
    die("Payload tidak lengkap.");
}

// Tentukan status transaksi
$status = 'gagal';
if ($transaction_status === 'capture' && $fraud_status === 'accept') {
    $status = 'berhasil';
} elseif ($transaction_status === 'settlement') {
    $status = 'berhasil';
} elseif ($transaction_status === 'pending') {
    $status = 'pending';
}

// Perbarui status transaksi di database
try {
    $stmt_update = $conn->prepare("UPDATE tbl_t_donasi SET status_ttd = :status WHERE id_ttd = :order_id");
    $stmt_update->bindParam(':status', $status);
    $stmt_update->bindParam(':order_id', $order_id);
    if ($stmt_update->execute()) {
        file_put_contents('webhook_log.txt', "Status transaksi diperbarui menjadi: $status untuk ID: $order_id\n", FILE_APPEND);

        // Jika transaksi berhasil, perbarui jumlah terkumpul dan kirim email
        if ($status === 'berhasil') {
            $stmt_donasi = $conn->prepare("SELECT email_donatur, jumlah_ttd FROM tbl_t_donasi WHERE id_ttd = :order_id");
            $stmt_donasi->bindParam(':order_id', $order_id);
            $stmt_donasi->execute();
            $donasi = $stmt_donasi->fetch(PDO::FETCH_ASSOC);

            if ($donasi) {
                // Perbarui jumlah terkumpul
                $stmt_update_kegiatan = $conn->prepare("UPDATE tbl_kegiatan 
                    SET jumlah_terkumpul_tk = jumlah_terkumpul_tk + :jumlah 
                    WHERE id_tk = :id_tk");
                $stmt_update_kegiatan->bindParam(':jumlah', $donasi['jumlah_ttd']);
                $stmt_update_kegiatan->bindParam(':id_tk', $donasi['id_tk']);
                $stmt_update_kegiatan->execute();

                // Kirim email ke donatur
                $email = $donasi['email_donatur'];
                $amount = $donasi['jumlah_ttd'];
                sendEmailToDonor($email, $amount, $order_id);
            }
        }
    } else {
        file_put_contents('webhook_log.txt', "Gagal memperbarui status untuk ID: $order_id\n", FILE_APPEND);
    }
} catch (PDOException $e) {
    file_put_contents('webhook_log.txt', "Query error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500); // Internal Server Error
    die("Database error.");
}

// Fungsi untuk mengirim email ke donatur
function sendEmailToDonor($email, $amount, $order_id)
{
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Konfigurasi Gmail SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['GMAIL_USER']; // Dapatkan dari .env
        $mail->Password = $_ENV['GMAIL_PASS']; // Dapatkan dari .env
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Informasi email
        $mail->setFrom($_ENV['GMAIL_USER'], 'Himatif Care');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Terima Kasih atas Donasi Anda!';
        $mail->Body = "
            <h1>Donasi Anda Telah Berhasil!</h1>
            <p>Halo,</p>
            <p>Terima kasih telah berdonasi sebesar <strong>Rp" . number_format($amount, 0, ',', '.') . "</strong> untuk kegiatan kami.</p>
            <p>Kode Transaksi Anda: <strong>$order_id</strong></p>
            <p>Semoga donasi Anda membawa manfaat besar bagi yang membutuhkan.</p>
            <p>Salam hangat,<br>Himatif Care</p>
        ";

        $mail->send();
        file_put_contents('webhook_log.txt', "Email berhasil dikirim ke: $email\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents('webhook_log.txt', "Gagal mengirim email ke $email: " . $mail->ErrorInfo . "\n", FILE_APPEND);
    }
}

// Berikan respons sukses ke Midtrans
http_response_code(200);
echo "OK";