<?php
// Mengimpor koneksi database
include "../../../db.php"; // Pastikan path ke db.php benar

// Inisialisasi objek Database dan Login
$db = new Database();
$conn = $db->getConnection();

// Validasi ID Donasi
$id_ttd = $_GET['id_ttd'] ?? null;

if (!$id_ttd) {
    die("Donasi ID tidak ditemukan.");
}

// Query untuk mendapatkan data donasi dan statusnya
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

  // Debugging untuk memastikan data ditemukan
  if (!$data) {
      die("Data donasi tidak ditemukan untuk ID: $id_ttd");
  }

  // Ambil status transaksi dari database
  $status = $data['status_ttd']; // Menyimpan status dari database

} catch (PDOException $e) {
  die("Error Database: " . $e->getMessage());
}

return $data;
?>
