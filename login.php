<?php
session_start();
require_once 'db.php'; // Koneksi ke database

class Login {
    private $conn;

    // Konstruktor untuk menginisialisasi koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }

    // Fungsi untuk memverifikasi login
    public function verifyLogin($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM tbl_m_user WHERE email_tmu = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_tmu'])) {
            $_SESSION['id_tmu'] = $user['id_tmu'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_tmu'] = $user['nama_tmu'];

            return true;
        }

        return false;
    }
}

// Inisialisasi objek Database dan Login
$db = new Database();
$conn = $db->getConnection();
$login = new Login($conn);

// Variabel untuk menyimpan pesan error
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Verifikasi login
    if ($login->verifyLogin($email, $password)) {
        // Redirect ke dashboard admin jika login berhasil
        header('Location: dashboard-admin.php');
        exit();
    } else {
        // Simpan pesan error jika login gagal
        $error_message = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    /* Gaya untuk tombol toggle navbar */
    .navbar-toggler {
        border: 2px solid navy; /* Menambahkan border */
        border-radius: 5px; /* Membuat sudut membulat */
        transition: background-color 0.3s ease, transform 0.2s ease; /* Animasi efek hover */
    }

    /* Hover dan focus state untuk tombol toggle */
    .navbar-toggler:hover,
    .navbar-toggler:focus {
        background-color: #add8e6; /* Warna latar belakang saat disentuh */
        transform: scale(1.1); /* Sedikit memperbesar tombol */
        border-color: #000080; /* Ubah warna border saat disentuh */
    }
    </style>

</head>
<body style="background-color: #b0e0e6; font-family: 'Lucida Sans';">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top" style="background-color: #add8e6; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
    <div class="container-fluid">
        <a class="navbar-brand me-auto" style="color: navy; font-weight: bold;">
            <img src="img/logo.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px;">
            Himatif Care
        </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel" style="background-color: #add8e6;">
            <div class="offcanvas-header" style="background-color: #add8e6; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
                <img src="img/logo.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px;">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel" style="color: navy;">Himatif Care</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="btn" href="dashboard-utama.php" style="color:navy;">Dashboard</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Konten Halaman -->
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card" style="width: 100%; max-width: 400px; background-color :#add8e6; box-shadow: 0 0 25px rgba(0, 0, 0, 0.700); overflow: hidden;">
        <div class="card-body">
            <h2 class="card-title text-center mb-4"><b>Login Admin</b></h2>

            <!-- Tampilkan pesan error jika ada -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger text-center">
                    <?= $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Form login -->
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
