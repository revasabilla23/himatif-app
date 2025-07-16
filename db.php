<?php
class Database {
    private $host = '127.0.0.1';
    private $port = '3307'; // Port database
    private $dbname = 'himatif';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Konstruktor untuk inisialisasi koneksi
    public function __construct() {
        $this->connect();
        $this->checkConnection();
    }

    // Fungsi untuk menghubungkan ke database
    private function connect() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=$this->host;port=$this->port;dbname=$this->dbname";
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Log error ke file
                file_put_contents('db_error_log.txt', "Koneksi gagal: " . $e->getMessage() . "\n", FILE_APPEND);
                die("Koneksi gagal: " . $e->getMessage());
            }
        }
    }

    // Fungsi untuk mengecek koneksi
    private function checkConnection() {
        if ($this->conn) {
            // Log sukses ke file
            file_put_contents('db_log.txt', "Koneksi berhasil ke database $this->dbname\n", FILE_APPEND);
        } else {
            file_put_contents('db_error_log.txt', "Koneksi ke database gagal!\n", FILE_APPEND);
            die("Koneksi ke database gagal!");
        }
    }

    // Getter untuk mendapatkan koneksi
    public function getConnection() {
        return $this->conn;
    }
}

// Inisialisasi objek Database
$db = new Database();
$conn = $db->getConnection();
?>
