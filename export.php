<?php
// Autoload Composer
require 'vendor/autoload.php'; // Pastikan autoload.php benar
require 'db.php'; // Koneksi database
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['export_data'])) {
    $id_tk = filter_input(INPUT_GET, 'id_tk', FILTER_SANITIZE_NUMBER_INT);
    $jenis_data = filter_input(INPUT_GET, 'jenis_data', FILTER_SANITIZE_STRING);
    $format = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING);

    if (!$id_tk || !$jenis_data || !$format) {
        exit("Parameter tidak lengkap!");
    }

    try {
        $data = fetchData($conn, $id_tk, $jenis_data);
        if (empty($data)) {
            exit("Data tidak ditemukan!");
        }

        if ($format === 'pdf') {
            exportPDF($data, $jenis_data, $id_tk);
        } elseif ($format === 'excel') {
            exportCSV($data, $jenis_data, $id_tk);
        } else {
            exit("Format tidak valid!");
        }
    } catch (Exception $e) {
        exit("Terjadi kesalahan: " . $e->getMessage());
    }
}


function fetchData($conn, $id_tk, $jenis_data) {
    if ($jenis_data === 'donasi') {
        $query = "SELECT td.nama_donatur, td.email_donatur, td.jumlah_ttd, td.metode_ttd, td.status_ttd , td.tgl_ttd
                  FROM tbl_t_donasi td 
                  WHERE td.id_tk = :id_tk AND td.status_ttd = 'berhasil'";
    } else {
        $query = "SELECT tp.deks_ttp, tp.jumlah_ttp , tp.tgl_ttp
                  FROM tbl_t_pengeluaran tp 
                  WHERE tp.id_tk = :id_tk";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute([':id_tk' => $id_tk]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



// Fungsi untuk ekspor PDF
function exportPDF($data, $jenis_data, $id_tk) {
    ob_start(); // Memulai output buffering

    // Memastikan FPDF sudah tersedia
    // require_once 'path/to/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage();

    // Tambahkan Logo dan HIMATIFCare
    $logoPath = 'img/logo.png'; // Ganti dengan path file logo Anda
    $logoWidth = 40; // Lebar logo
    $logoHeight = 25; // Tinggi logo

    // Posisi logo di sebelah kiri
    $pdf->Image($logoPath, 10, 10, $logoWidth, $logoHeight);

    // Header HIMATIF CARE
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'HIMATIF CARE', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, strtoupper('LAPORAN ' . strtoupper($jenis_data)), 0, 1, 'C');
    $pdf->Ln(10);

    // Informasi Kegiatan
    $pdf->SetFont('Arial', '', 12);
    global $conn;

    // Ambil data judul kegiatan dan total debit berdasarkan jumlah_ttd
    $stmt = $conn->prepare("
        SELECT 
            tk.judul_tk, 
            COALESCE(SUM(td.jumlah_ttd), 0) AS total_debit 
        FROM 
            tbl_kegiatan tk 
        LEFT JOIN 
            tbl_t_donasi td 
        ON 
            tk.id_tk = td.id_tk 
        WHERE 
            tk.id_tk = :id_tk
            AND td.status_ttd = 'berhasil'
    ");
    $stmt->execute([':id_tk' => $id_tk]);
    $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

    $judulKegiatan = $kegiatan['judul_tk'];
    $totalDebit = $kegiatan['total_debit'];
    $tanggalCetak = date('d-m-Y');

    $pdf->Cell(0, 8, 'Nama Kegiatan: ' . $judulKegiatan, 0, 1, 'L');
    $pdf->Cell(0, 8, 'Tanggal: ' . $tanggalCetak, 0, 1, 'L');
    $pdf->Ln(10);

    // Header Tabel
    $pdf->SetFont('Arial', 'B', 12);
    

    if ($jenis_data === 'donasi') { // Untuk Pemasukan
        $headers = ['No', 'Tanggal', 'Nama Donatur', 'Nominal'];
        $widths = [10, 40, 80, 50];
        $pdf->SetFillColor(173, 216, 230); // Warna biru muda
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 10, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Isi Tabel
        $pdf->SetFont('Arial', '', 12);
        foreach ($data as $index => $row) {
            $pdf->Cell($widths[0], 10, $index + 1, 1, 0, 'C'); // Nomor
            $pdf->Cell($widths[1], 10, date('d-m-Y', strtotime($row['tgl_ttd'])), 1, 0, 'C'); // Tanggal
            $pdf->Cell($widths[2], 10, $row['nama_donatur'], 1, 0, 'L'); // Nama Donatur
            $pdf->Cell($widths[3], 10, 'Rp ' . number_format($row['jumlah_ttd'], 0, ',', '.'), 1, 0, 'R'); // Nominal
            $pdf->Ln();
        }

        // Total Pemasukan
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(array_sum($widths) - $widths[3], 10, 'Total', 1, 0, 'C'); // Gabungan kolom No, Tanggal, Nama Donatur
        $pdf->Cell($widths[3], 10, 'Rp ' . number_format($totalDebit, 0, ',', '.'), 1, 0, 'R');
        $pdf->Ln();

    } else { // Untuk Pengeluaran
        $headers = ['No', 'Tanggal', 'Deskripsi', 'Jumlah'];
        $widths = [10, 40, 100, 40];
        // Background Warna Biru untuk Header
        $pdf->SetFillColor(173, 216, 230); // Warna biru muda
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 10, $header, 1, 0, 'C',true);
        }
        $pdf->Ln();

        // Isi Tabel
        $pdf->SetFont('Arial', '', 12);
        $totalKredit = 0;

        foreach ($data as $index => $row) {
            $pdf->Cell($widths[0], 10, $index + 1, 1, 0, 'C'); // Nomor
            $pdf->Cell($widths[1], 10, date('d-m-Y', strtotime($row['tgl_ttp'])), 1, 0, 'C'); // Tanggal
            $pdf->Cell($widths[2], 10, $row['deks_ttp'], 1, 0, 'L'); // Deskripsi
            $jumlah = isset($row['jumlah_ttp']) ? $row['jumlah_ttp'] : 0;
            $pdf->Cell($widths[3], 10, 'Rp ' . number_format($jumlah, 0, ',', '.'), 1, 0, 'L'); // Jumlah
            $totalKredit += $jumlah;
            $pdf->Ln();
        }

        // Total Debit, Kredit, dan Saldo
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(array_sum($widths) - $widths[3], 10, 'Debit', 1, 0, 'C');
        $pdf->Cell($widths[3], 10, 'Rp ' . number_format($totalDebit, 0, ',', '.'), 1, 0, 'L');
        $pdf->Ln();

        $pdf->Cell(array_sum($widths) - $widths[3], 10, 'Kredit', 1, 0, 'C');
        $pdf->Cell($widths[3], 10, 'Rp ' . number_format($totalKredit, 0, ',', '.'), 1, 0, 'L');
        $pdf->Ln();

        $balance = $totalDebit - $totalKredit;
        $pdf->Cell(array_sum($widths) - $widths[3], 10, 'Saldo Akhir', 1, 0, 'C');
        $pdf->Cell($widths[3], 10, 'Rp ' . number_format($balance, 0, ',', '.'), 1, 0, 'L');
        $pdf->Ln();

        
    }

    // Menyimpan file PDF
    $filename = $jenis_data === 'donasi'
        ? "laporan_donasi_{$id_tk}.pdf"
        : "laporan_pengeluaran_{$id_tk}.pdf";

    ob_end_clean();
    $pdf->Output('I', $filename);
    exit();
}





// Fungsi untuk ekspor Excel
function exportCSV($data, $jenis_data, $id_tk) {
    // Pastikan data memiliki setidaknya satu elemen untuk mendapatkan header
    if (empty($data) || !is_array($data)) {
        die("Data tidak valid atau kosong.");
    }

    // Ambil informasi kegiatan dari database
    global $conn;
    $stmt = $conn->prepare("SELECT judul_tk FROM tbl_kegiatan WHERE id_tk = :id_tk");
    $stmt->execute([':id_tk' => $id_tk]);
    $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tanggal saat mencetak
    $tanggalCetak = date('d-m-Y');

    // Hitung total jumlah
    $total = 0;
    if ($jenis_data === 'donasi') {
        $total = array_sum(array_column($data, 'jumlah_ttd')); // Total dari jumlah donasi
    } else {
        $total = array_sum(array_column($data, 'jumlah_ttp')); // Total dari jumlah pengeluaran
    }

    // Nama file berdasarkan jenis data
    $filename = $jenis_data === 'donasi' 
        ? "donasi_kegiatan_$id_tk.csv" 
        : "pengeluaran_kegiatan_$id_tk.csv";

    // Atur header untuk pengunduhan file
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    // Buka output stream untuk menulis CSV
    $output = fopen('php://output', 'w');

    // Tambahkan informasi kegiatan ke file CSV
    fputcsv($output, ['Judul Kegiatan:', $kegiatan['judul_tk']]);
    fputcsv($output, ['Tanggal Cetak:', $tanggalCetak]);
    fputcsv($output, ['Jenis Data:', ucfirst($jenis_data)]);
    fputcsv($output, ['Jumlah Total:', 'Rp ' . number_format($total, 0, ',', '.')]);
    fputcsv($output, []); // Baris kosong untuk pemisah

    // Header tabel
    if ($jenis_data === 'donasi') {
        $headers = ['No', 'Nama Donatur', 'Jumlah', 'Metode Pembayaran'];
    } else {
        $headers = ['No', 'Deskripsi', 'Jumlah'];
    }

    // Tulis header tabel dengan format yang konsisten
    fputcsv($output, $headers);

    // Tulis baris data
    foreach ($data as $index => $row) {
        if ($jenis_data === 'donasi') {
            fputcsv($output, [
                $index + 1,
                $row['nama_donatur'],
                'Rp ' . number_format($row['jumlah_ttd'], 0, ',', '.'),
                $row['metode_ttd']
            ]);
        } else {
            fputcsv($output, [
                $index + 1,
                $row['deks_ttp'],
                'Rp ' . number_format($row['jumlah_ttp'], 0, ',', '.')
            ]);
        }
    }

    // Tutup output stream
    fclose($output);
    exit();
}
// Fungsi Ekspor Excel
function exportExcel($data, $jenis_data, $id_tk)
{
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . ($jenis_data === 'donasi' ? "donasi_kegiatan_$id_tk" : "pengeluaran_kegiatan_$id_tk") . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Header
    echo implode("\t", array_keys($data[0])) . "\n";

    // Data
    foreach ($data as $row) {
        echo implode("\t", array_values($row)) . "\n";
    }

    exit();
}
?>
