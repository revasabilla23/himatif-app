<?php
// Mengimpor koneksi database
require_once 'db.php';
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai


// Validasi ID Donasi
$id_ttd = $_GET['id_ttd'] ?? null;

if (!$id_ttd) {
    die("Donasi ID tidak ditemukan. Debug: " . print_r($_GET, true));
}

// Query untuk mendapatkan data donasi dan kegiatan terkait
$query = "SELECT 
            tbl_t_donasi.*, 
            tbl_kegiatan.judul_tk 
          FROM 
            tbl_t_donasi
          JOIN 
            tbl_kegiatan 
          ON 
            tbl_t_donasi.id_tk = tbl_kegiatan.id_tk
          WHERE 
            tbl_t_donasi.id_ttd = :id_ttd";

try {
    // Menyiapkan statement menggunakan koneksi dari db.php
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_ttd', $id_ttd, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validasi jika data tidak ditemukan
    if (!$data) {
        die("Data donasi tidak ditemukan untuk ID: $id_ttd");
    }
} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

// Data donasi
$nama_donatur = $data['nama_donatur'];
$email_donatur = $data['email_donatur'];
$nominal = $data['jumlah_ttd'];
$judul_kegiatan = $data['judul_tk'];
$tanggal_donasi = $data['tgl_ttd'];

// Mengimpor pustaka dompdf
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

// Inisialisasi dompdf
$dompdf = new Dompdf();

// Konten HTML yang akan diubah menjadi PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mutasi Donasi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: auto; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td, th { padding: 8px; border: 1px solid #ddd; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="color: navy;">Mutasi Donasi Himatif Care</h2>
        <p style="text-align: justify;">Terima kasih atas dukungan dan kepercayaan Anda. Donasi dalam kegiatan <b>' . htmlspecialchars($judul_kegiatan) . '</b> yang Anda berikan sangat berarti dalam mendukung kelancaran kegiatan ini.
         Semoga kontribusi Anda membawa manfaat besar bagi banyak orang dan menjadi berkah untuk kita semua. </p>
        <table>
            <tr>
                <th>Nama Donatur</th>
                <td>' . htmlspecialchars($nama_donatur) . '</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>' . htmlspecialchars($email_donatur) . '</td>
            </tr>
            <tr>
                <th>Jumlah Donasi</th>
                <td>Rp ' . number_format($nominal, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <th>Tanggal Donasi</th>
                <td>' . htmlspecialchars($tanggal_donasi) . '</td>
            </tr>
            <tr>
                <th>Judul Kegiatan</th>
                <td>' . htmlspecialchars($judul_kegiatan) . '</td>
            </tr>
        </table>
    </div>
</body>
</html>';

// Mengatur HTML ke dompdf
$dompdf->loadHtml($html);

// Mengatur ukuran dan orientasi halaman
$dompdf->setPaper('A4', 'portrait');

// Merender HTML menjadi PDF
$dompdf->render();

// Mengunduh PDF
$dompdf->stream("mutasi_donasi_$id_ttd.pdf", ["Attachment" => 1]);
?>
