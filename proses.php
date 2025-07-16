<?php
include "db.php";
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai


// Tangkap data dari form
$nama_donatur = $_POST['nama'];
$email_donatur = $_POST['email'];
$jumlah_ttd = $_POST['nominal'];
$id_tk = $_POST['id_tk']; // ID kegiatan
$tgl_ttd = date('Y-m-d H:i:s');
$metode_ttd = 'transfer';
$status_ttd = 'pending';

try {
    $conn->beginTransaction(); // Mulai transaksi

    // Masukkan data donasi ke tabel tbl_t_donasi
    $query_donasi = "INSERT INTO tbl_t_donasi (id_tk, nama_donatur, email_donatur, tgl_ttd, jumlah_ttd, metode_ttd, status_ttd) 
                     VALUES (:id_tk, :nama_donatur, :email_donatur, :tgl_ttd, :jumlah_ttd, :metode_ttd, :status_ttd)";
    $stmt_donasi = $conn->prepare($query_donasi);
    $stmt_donasi->execute([
        ':id_tk' => $id_tk,
        ':nama_donatur' => $nama_donatur,
        ':email_donatur' => $email_donatur,
        ':tgl_ttd' => $tgl_ttd,
        ':jumlah_ttd' => $jumlah_ttd,
        ':metode_ttd' => $metode_ttd,
        ':status_ttd' => $status_ttd
    ]);

    // Ambil id_ttd dari hasil insert
    $id_ttd = $conn->lastInsertId();

    // Update jumlah terkumpul pada tabel kegiatan
    // $query_update_kegiatan = "UPDATE tbl_kegiatan 
                              //SET jumlah_terkumpul_tk = jumlah_terkumpul_tk + :jumlah_ttd 
                              //WHERE id_tk = :id_tk";
    //$stmt_update_kegiatan = $conn->prepare($query_update_kegiatan);
    //$stmt_update_kegiatan->execute([
        //':jumlah_ttd' => $jumlah_ttd,
        //':id_tk' => $id_tk
    //]);

    // Periksa apakah jumlah terkumpul telah mencapai atau melebihi target
    $query_check_kegiatan = "SELECT jumlah_terkumpul_tk, target_tk FROM tbl_kegiatan WHERE id_tk = :id_tk";
    $stmt_check_kegiatan = $conn->prepare($query_check_kegiatan);
    $stmt_check_kegiatan->execute([':id_tk' => $id_tk]);
    $kegiatan = $stmt_check_kegiatan->fetch(PDO::FETCH_ASSOC);

    if ($kegiatan && $kegiatan['jumlah_terkumpul_tk'] >= $kegiatan['target_tk']) {
        // Jika dana terkumpul sudah mencapai target, ubah status menjadi "close"
        $query_close_kegiatan = "UPDATE tbl_kegiatan SET status_tk = 'close' WHERE id_tk = :id_tk";
        $stmt_close_kegiatan = $conn->prepare($query_close_kegiatan);
        $stmt_close_kegiatan->execute([':id_tk' => $id_tk]);
    }

    $conn->commit(); // Selesaikan transaksi

    // Redirection ke halaman checkout
    header("Location: ./midtrans-php-master/examples/snap/checkout-process.php?id_ttd=$id_ttd&nominal=$jumlah_ttd");
    exit;
} catch (PDOException $e) {
    $conn->rollBack(); // Batalkan transaksi jika terjadi kesalahan
    echo "Error: " . htmlspecialchars($e->getMessage());
}
