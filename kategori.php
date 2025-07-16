<?php
session_start();
require_once 'db.php';
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai

// Cek apakah pengguna adalah admin
if (!isset($_SESSION['id_tmu']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Kelas untuk manajemen kategori
class Kategori {
    private $conn;

    // Konstruktor untuk inisialisasi koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }

    // Fungsi untuk menambah kategori
    public function addKategori($nama_tmk, $desk_tmk) {
        $stmt = $this->conn->prepare("INSERT INTO tbl_m_kategori (nama_tmk, desk_tmk) VALUES (:nama_tmk, :desk_tmk)");
        $stmt->bindParam(':nama_tmk', $nama_tmk);
        $stmt->bindParam(':desk_tmk', $desk_tmk);
        return $stmt->execute();
    }

    // Fungsi untuk menghapus kategori
    public function deleteKategori($id_tmk) {
        $stmt = $this->conn->prepare("DELETE FROM tbl_m_kategori WHERE id_tmk = :id_tmk");
        $stmt->bindParam(':id_tmk', $id_tmk);
        return $stmt->execute();
    }

    // Fungsi untuk mengambil semua kategori
    public function getAllKategori() {
        $stmt = $this->conn->prepare("SELECT * FROM tbl_m_kategori ORDER BY id_tmk ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Inisialisasi koneksi database dan kelas Kategori
$db = new Database();
$conn = $db->getConnection();
$kategoriManager = new Kategori($conn);

// Variabel pesan sukses atau error
$success = $error = "";

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $nama_tmk = $_POST['nama_tmk'];
    $desk_tmk = $_POST['desk_tmk'];

    if ($kategoriManager->addKategori($nama_tmk, $desk_tmk)) {
        $success = "Kategori berhasil ditambahkan.";
        header("Location: kategori.php");
        exit();
    } else {
        $error = "Terjadi kesalahan saat menambahkan kategori.";
    }
}

// Proses Hapus Kategori
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    if ($kategoriManager->deleteKategori($delete_id)) {
        $success = "Kategori berhasil dihapus.";
        header("Location: kategori.php");
        exit();
    } else {
        $error = "Terjadi kesalahan saat menghapus kategori.";
    }
}

// Ambil data kategori
$categories = $kategoriManager->getAllKategori();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Himatif Care</title>
</head>
<body>
<section id="sidebar">
    <a href="#" class="brand">
        <i class='bx bx-donate-heart'></i>
        <span class="text">Admin Menu</span>
    </a>
    <ul class="side-menu top">
        <li>
            <a href="dashboard-admin.php">
                <i class='bx bxs-home'></i>
                <span class="text">Dashboard</span>
            </a>
        </li>
        <li class="active">
            <a href="kategori.php">
                <i class='bx bxs-category-alt'></i>
                <span class="text">Kategori</span>
            </a>
        </li>
        <li>
            <a href="pamflet.php">
                <i class='bx bx-images'></i>
                <span class="text">Pamflet</span>
            </a>
        </li>
        <li>
            <a href="kegiatan.php">
                <i class='bx bxs-briefcase-alt-2'></i>
                <span class="text">Kegiatan</span>
            </a>
        </li>
        <li>
            <a href="transaksi.php">
                <i class='bx bxs-credit-card'></i>
                <span class="text">Transaksi</span>
            </a>
        </li>
        <li>
            <a href="dokumentasi.php">
                <i class='bx bxs-image'></i>
                <span class="text">Dokumentasi</span>
            </a>
        </li>
    </ul>
    <ul class="side-menu">
        <li>
            <a href="logout.php" class="logout">
                <i class='bx bxs-log-out-circle'></i>
                <span class="text">Logout</span>
            </a>
            <a href="add_admin.php" class="Add Admin">
                <i class='bx bxs-user-circle'></i>
                <span class="text">Add Admin</span>
            </a>
        </li>
    </ul>
</section>

<section id="content">
    <nav>
		<i class="bx bx-menu toggle-sidebar"></i>
		<span class="nav-link"></span>
	</nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kategori</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard-admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Kategori</a></li>
                </ul>
            </div>
            <a id="openModalButton" class="btn-download">
                <i class='bx bx-add-to-queue'></i>
                <span class="text">Tambah Kategori</span>
            </a>
        </div>

        <!-- Pesan Sukses/Error -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-data">
            <div class="order">
                <div class="head">
                    <h3>Daftar Kategori</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($category['nama_tmk']) ?></td>
                                    <td><?= htmlspecialchars($category['desk_tmk']) ?></td>
                                    <td>
                                        <a href="kategori.php?delete_id=<?= $category['id_tmk'] ?>" 
                                           class="status completed"
                                           onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data kategori.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</section>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-box">
        <p>Tambah Kategori</p>
        <form method="POST" action="kategori.php">
            <input type="text" name="nama_tmk" placeholder="Nama Kategori" required>
            <input type="text" name="desk_tmk" placeholder="Deskripsi Kategori">
            <div class="modal-actions">
                <button type="submit" name="add_category" class="btn btn-primary">Tambah</button>
                <button type="button" id="closeModalButton" class="btn btn-secondary">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modal');
    const openModalButton = document.getElementById('openModalButton');
    const closeModalButton = document.getElementById('closeModalButton');

    openModalButton.addEventListener('click', function (event) {
        event.preventDefault();
        modal.style.display = 'flex';
    });

    closeModalButton.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    toggleSidebar.addEventListener('click', () => {
        sidebar.classList.toggle('hide');
    });
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@400;500;600;700&display=swap');
:root {
	--poppins: 'Poppins', sans-serif;
	--lato: 'Lato', sans-serif;

	--light: #F9F9F9;
	--blue: #3C91E6;
	--light-blue: #CFE8FF;
	--grey: #eee;
	--dark-grey: #AAAAAA;
	--dark: #342E37;
	--red: #DB504A;
	--yellow: #FFCE26;
	--light-yellow: #FFF2C6;
	--orange: #FD7238;
	--light-orange: #FFE0D3;
}
    /* Modal Overlay */
.modal {
	font-family: var(--poppins);
	font-weight: 700;
    display: none; /* Tersembunyi secara default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6); /* Transparansi */
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

/* Modal Box */
.modal-box {
    background: var(--light); /* Warna dasar putih */
    padding: 20px;
    border-radius: 10px;
    width: 300px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* Shadow lembut */
    text-align: center; /* Pusatkan teks */
}

/* Form Inputs */
.modal-box input {
	font-family: var(--poppins);
	font-weight: 700;
    width: 100%;
    margin-bottom: 15px;
    padding: 8px;
    border: 1px solid var(--grey);
    border-radius: 5px;
    outline: none;
}

/* Modal Actions */
.modal-actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

/* Buttons */
.modal-actions .btn {
	font-family: var(--poppins);
	font-weight: 600;
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.modal-actions .btn-primary {
    background-color: var(--blue);
    color: var(--dark);
    font-weight: bold;
}

.modal-actions .btn-primary:hover {
    background-color: var(--dark-grey);
}

.modal-actions .btn-secondary {
    background-color: var(--blue);
    color: var(--dark);
}

.modal-actions .btn-secondary:hover {
    background-color: var(--dark-grey);
}
</style>
</body>
</html>

