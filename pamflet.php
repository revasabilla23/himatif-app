<?php
session_start();
require_once 'db.php';
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai


// Cek login admin
if (!isset($_SESSION['id_tmu']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Kelas untuk manajemen pamflet
class Pamflet {
    private $conn;

    // Konstruktor untuk inisialisasi koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }

    // Fungsi untuk menambah pamflet
    public function addPamflet($deks_tmp, $foto_tmp) {
        $stmt = $this->conn->prepare("INSERT INTO tbl_m_pamflet (foto_tmp, deks_tmp) VALUES (:foto_tmp, :deks_tmp)");
        $stmt->bindParam(':foto_tmp', $foto_tmp);
        $stmt->bindParam(':deks_tmp', $deks_tmp);
        return $stmt->execute();
    }

    // Fungsi untuk mengambil semua pamflet
    public function getAllPamflets() {
        $stmt = $this->conn->query("SELECT * FROM tbl_m_pamflet");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fungsi untuk menghapus pamflet
    public function deletePamflet($id_tmp) {
        // Ambil data pamflet untuk menghapus file
        $stmt = $this->conn->prepare("SELECT foto_tmp FROM tbl_m_pamflet WHERE id_tmp = :id_tmp");
        $stmt->bindParam(':id_tmp', $id_tmp);
        $stmt->execute();
        $pamflet = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pamflet) {
            $file_path = "uploads/pamflet/" . $pamflet['foto_tmp'];
            if (file_exists($file_path)) {
                unlink($file_path); // Hapus file dari server
            }

            // Hapus data dari database
            $stmt = $this->conn->prepare("DELETE FROM tbl_m_pamflet WHERE id_tmp = :id_tmp");
            $stmt->bindParam(':id_tmp', $id_tmp);
            return $stmt->execute();
        }
        return false;
    }
}

// Inisialisasi koneksi database dan kelas Pamflet
$db = new Database();
$conn = $db->getConnection();
$pamfletManager = new Pamflet($conn);

// Variabel pesan sukses atau error
$success = $error = "";

// Proses tambah pamflet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deks_tmp = $_POST['deks_tmp'];
    $upload_dir = 'uploads/pamflet/';
    $upload_file = $upload_dir . basename($_FILES['foto_tmp']['name']);
    $image_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));

    if (in_array($image_type, ['jpg', 'jpeg', 'png'])) {
        if (move_uploaded_file($_FILES['foto_tmp']['tmp_name'], $upload_file)) {
            if ($pamfletManager->addPamflet($deks_tmp, $_FILES['foto_tmp']['name'])) {
                $success = "Pamflet berhasil diunggah.";
            } else {
                $error = "Gagal menyimpan ke database.";
            }
        } else {
            $error = "Gagal mengunggah file.";
        }
    } else {
        $error = "Format file tidak didukung. (Hanya jpg, jpeg, png)";
    }
}

// Ambil daftar pamflet
$pamflets = $pamfletManager->getAllPamflets();

// Proses hapus pamflet
if (isset($_GET['delete_id'])) {
    if ($pamfletManager->deletePamflet($_GET['delete_id'])) {
        $success = "Pamflet berhasil dihapus.";
    } else {
        $error = "Gagal menghapus pamflet.";
    }

    header("Location: pamflet.php");
    exit();
}
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
            <li><a href="dashboard-admin.php"><i class='bx bxs-home'></i><span class="text">Dashboard</span></a></li>
            <li><a href="kategori.php"><i class='bx bxs-category-alt'></i><span class="text">Kategori</span></a></li>
            <li class="active"><a href="pamflet.php"><i class='bx bx-images'></i><span class="text">Pamflet</span></a></li>
            <li><a href="Kegiatan.php"><i class='bx bxs-briefcase-alt-2'></i><span class="text">Kegiatan</span></a></li>
            <li><a href="transaksi.php"><i class='bx bxs-credit-card'></i><span class="text">Transaksi</span></a></li>
            <li><a href="dokumentasi.php"><i class='bx bxs-image'></i><span class="text">Dokumentasi</span></a></li>
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
                    <h1>Pamflet</h1>
                    <ul class="breadcrumb">
                        <li><a href="dashboard-admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="pamflet.php">Pamflet</a></li>
                    </ul>
                </div>
                <a id="openModalButton" class="btn-download">
                    <i class='bx bx-add-to-queue'></i>
                    <span class="text">Tambah Pamflet</span>
                </a>
            </div>
            <div class="table-data">
                <div class="order">
                    <div class="head"><h3>Daftar Pamflet</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pamflets) > 0): ?>
                                <?php foreach ($pamflets as $index => $pamflet): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <img src="uploads/pamflet/<?= htmlspecialchars($pamflet['foto_tmp']) ?>" alt="Pamflet" style="width: 100px; height: auto; border-radius: 5px;">
                                        </td>
                                        <td><?= htmlspecialchars($pamflet['deks_tmp']) ?></td>
                                        <td>
                                            <span class="status completed">
                                                <a href="pamflet.php?delete_id=<?= $pamflet['id_tmp'] ?>" 
                                                   onclick="return confirm('Yakin ingin menghapus pamflet ini?')" 
                                                   style="color: inherit; text-decoration: none;">Hapus</a>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data pamflet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

    <div id="modal" class="modal">
        <div class="modal-box">
            <h3>Upload Pamflet Baru</h3>
            <form action="pamflet.php" method="POST" enctype="multipart/form-data">
                <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <div class="mb-3">
                    <label for="foto_tmp" class="form-label">Foto Pamflet</label>
                    <input type="file" class="form-control" id="foto_tmp" name="foto_tmp" required>
                </div>

                <div class="mb-3">
                    <label for="deks_tmp" class="form-label">Deskripsi Pamflet</label>
                    <textarea class="form-control" id="deks_tmp" name="deks_tmp" rows="3" required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="submit" name="add_pamflet" class="btn btn-primary">Upload Pamflet</button>
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
    width: 600px; /* Lebar diperbesar */
    max-width: 90%; /* Agar tidak melebihi layar */
    max-height: 80%; /* Batasi tinggi */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* Shadow lembut */
    overflow-y: auto; /* Scroll jika konten panjang */
    text-align: center; /* Pusatkan teks */
}

/* Form Inputs */
.modal-box input,
.modal-box textarea {
    font-family: var(--poppins);
    font-weight: 700;
    width: 100%;
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid var(--grey);
    border-radius: 5px;
    outline: none;
}

/* Modal Actions */
.modal-actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 15px;
}

/* Buttons */
.modal-actions .btn {
    font-family: var(--poppins);
    font-weight: 600;
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.modal-actions .btn-primary {
    background-color: var(--blue);
    color: var(--light);
    font-weight: bold;
}

.modal-actions .btn-primary:hover {
    background-color: var(--light-blue);
}

.modal-actions .btn-secondary {
    background-color: var(--red);
    color: var(--light);
}

.modal-actions .btn-secondary:hover {
    background-color: var(--dark-grey);
}

/* Tampilkan modal */
.modal.show {
    display: flex;
} 
    </style>
</body>
</html>

