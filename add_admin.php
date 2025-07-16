<?php
session_start();
require("db.php");

// Proses tambah admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $nama_tmu = $_POST['nama_tmu'];
    $email_tmu = $_POST['email_tmu'];
    $password_tmu = $_POST['password_tmu'];
    $password_hash = password_hash($password_tmu, PASSWORD_DEFAULT);

    // Periksa apakah email sudah ada di database
    $stmt = $conn->prepare("SELECT * FROM tbl_m_user WHERE email_tmu = :email_tmu");
    $stmt->bindParam(':email_tmu', $email_tmu);
    $stmt->execute();
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        $error = "Email sudah terdaftar. Gunakan email lain.";
    } else {
        // Menambahkan admin baru ke database
        $stmt = $conn->prepare("INSERT INTO tbl_m_user (nama_tmu, email_tmu, password_tmu, role) VALUES (:nama_tmu, :email_tmu, :password_tmu, 'admin')");
        $stmt->bindParam(':nama_tmu', $nama_tmu);
        $stmt->bindParam(':email_tmu', $email_tmu);
        $stmt->bindParam(':password_tmu', $password_hash);

        if ($stmt->execute()) {
            $success = "Admin baru berhasil ditambahkan!";
        } else {
            $error = "Terjadi kesalahan. Admin tidak dapat ditambahkan.";
        }
    }
}

// Proses hapus admin
if (isset($_GET['delete_id'])) {
    $admin_id = $_GET['delete_id'];

    // Pastikan admin tidak menghapus dirinya sendiri
    if ($admin_id != $_SESSION['id_tmu']) {
        $stmt = $conn->prepare("DELETE FROM tbl_m_user WHERE id_tmu = :id_tmu");
        $stmt->bindParam(':id_tmu', $admin_id);
        $stmt->execute();
        header('Location: add_admin.php');
        exit();
    } else {
        $error = "Anda tidak dapat menghapus akun Anda sendiri.";
    }
}

// Pagination
$page_admin = isset($_GET['page_admin']) ? (int)$_GET['page_admin'] : 1;
$perPage = 10; // Menentukan jumlah data yang ditampilkan per halaman
$offset_admin = ($page_admin - 1) * $perPage; // Menghitung offset berdasarkan halaman yang dipilih

// Pencarian admin berdasarkan nama atau email
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_m_user WHERE role = 'admin' AND (nama_tmu LIKE :search OR email_tmu LIKE :search)");
    $search_param = '%' . $search_query . '%';
    $stmt->bindParam(':search', $search_param);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_m_user WHERE role = 'admin'");
}
$stmt->execute();
$totalAdmins = $stmt->fetchColumn();

// Menghitung jumlah halaman
$totalPages_admin = ceil($totalAdmins / $perPage);

// Query untuk mengambil data admin
if ($search_query) {
    $stmt = $conn->prepare("SELECT id_tmu, nama_tmu, email_tmu FROM tbl_m_user WHERE role = 'admin' AND (nama_tmu LIKE :search OR email_tmu LIKE :search) LIMIT :offset, :limit");
    $stmt->bindParam(':search', $search_param);
} else {
    $stmt = $conn->prepare("SELECT id_tmu, nama_tmu, email_tmu FROM tbl_m_user WHERE role = 'admin' LIMIT :offset, :limit");
}
$stmt->bindParam(':offset', $offset_admin, PDO::PARAM_INT);
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Tambah Admin</title>
</head>
<body>

    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bx-donate-heart'></i>
            <span class="text">Admin Menu</span>
        </a>
        <ul class="side-menu top">
            <li><a href="dashboard-admin.php"><i class='bx bxs-home'></i><span class="text">Dashboard</span></a></li>
            <li><a href="kategori.php"><i class='bx bxs-category-alt'></i><span class="text">Kategori</span></a></li>
            <li><a href="pamflet.php"><i class='bx bx-images'></i><span class="text">Pamflet</span></a></li>
            <li><a href="kegiatan.php"><i class='bx bxs-briefcase-alt-2'></i><span class="text">Kegiatan</span></a></li>
            <li><a href="transaksi.php"><i class='bx bxs-credit-card'></i><span class="text">Transaksi</span></a></li>
            <li><a href="dokumentasi.php"><i class='bx bxs-image'></i><span class="text">Dokumentasi</span></a></li>
        </ul>
        <ul class="side-menu">
            <li><a href="logout.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
            <li class="active"><a href="add_admin.php"><i class='bx bxs-user-circle'></i><span class="text">Tambah Admin</span></a></li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <nav>
			<i class="bx bx-menu toggle-sidebar"></i>
			<span class="nav-link"></span>
		</nav>
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Kelola Admin</h1>
                    <ul class="breadcrumb">
                        <li><a href="dashboard-admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="add_admin.php">Kelola Admin</a></li>
                    </ul>
                </div>
                <!-- Tombol Tambah Admin -->
                <a id="openAdminFormButton" class="btn-download">
                    <i class='bx bx-add-to-queue'></i>
                    <span class="text">Tambah Admin</span>
                </a>
            </div>

            <!-- Modal Tambah Admin -->
            <div id="adminFormModal" class="modal">
                <div class="modal-box">
                    <h3>Tambah Admin Baru</h3>
                    <form method="POST" action="add_admin.php" class="mb-4">
                        <div class="mb-3">
                            <label for="nama_tmu" class="form-label">Nama Admin</label>
                            <input type="text" class="form-control" id="nama_tmu" name="nama_tmu" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_tmu" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_tmu" name="email_tmu" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_tmu" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password_tmu" name="password_tmu" required>
                        </div>
                        <button type="submit" name="add_admin" class="btn btn-primary">Tambahkan Admin</button>
                        <button type="button" id="closeAdminFormButton" class="btn btn-secondary" style="width: 100px; height: auto; border-radius: 5px;">Batal</button>
                    </form>
                </div>
            </div>

            <!-- Tabel Daftar Admin dengan Pencarian -->
            <div class="table-data">
                <div class="order">
                    <div class="head" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Daftar Admin</h3>
                        <form method="GET" action="add_admin.php" style="display: flex; gap: 10px;">
                            <input type="text" name="search" class="form-control" placeholder="Cari admin berdasarkan nama atau email..." value="<?= htmlspecialchars($search_query) ?>" style="padding: 5px; border: 1px solid #ddd; border-radius: 5px;" />
                            <button type="submit" class="btn btn-primary" style="padding: 5px 10px; border-radius: 5px;">Cari</button>
                            <a href="add_admin.php" class="btn btn-secondary" style="padding: 5px 10px; border-radius: 5px;">Reset</a>
                        </form>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                           $no = $offset_admin + 1;
                           if ($admins) {
                               foreach ($admins as $admin) {
                                   echo "<tr>
                                           <td>{$no}</td>
                                           <td>{$admin['nama_tmu']}</td>
                                           <td>{$admin['email_tmu']}</td>
                                           <td>
                                               <span class='status completed'>
                                                   <a href='?delete_id={$admin['id_tmu']}' 
                                                      onclick=\"return confirm('Apakah anda yakin ingin mengahpus Admin ini?')\" 
                                                      style='color: inherit; text-decoration: none;'>Hapus</a>
                                               </span>
                                           </td>
                                       </tr>";
                                   $no++;
                               }
                           } else {
                               echo "<tr><td colspan='4'>Tidak ada admin ditemukan.</td></tr>";
                           }
                           
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="pagination" style="text-align:center; margin-top: 20px;">
                        <a href="?page_admin=<?= max(1, $page_admin - 1) ?>&search=<?= urlencode($search_query) ?>" class="prev" style="border: 1.5px solid black; padding: 2px;">Previous</a>
                        <span><?= $page_admin ?> of <?= $totalPages_admin ?></span>
                        <a href="?page_admin=<?= min($totalPages_admin, $page_admin + 1) ?>&search=<?= urlencode($search_query) ?>" class="next" style="border: 1.5px solid black; padding: 2px;">Next</a>
                    </div>
                </div>
            </div>

        </main>
    </section>

<script>
    const openModalButton = document.getElementById("openAdminFormButton");
        const closeModalButton = document.getElementById("closeAdminFormButton");
        const modal = document.getElementById("adminFormModal");

        // Buka modal
        openModalButton.addEventListener("click", () => {
            modal.style.display = "flex";
        });

        // Tutup modal
        closeModalButton.addEventListener("click", () => {
            modal.style.display = "none";
        });

        // Tutup modal ketika klik di luar form
        window.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });

        const toggleSidebar = document.querySelector('.toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('hide');
        });
</script>
   

<!-- CSS -->
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
/* Menambahkan garis pada tabel */
.table {
    width: 100%;
    border-collapse: collapse; 
    margin-top: 20px;
}

/* Membuat garis pada setiap sel tabel */
.table th, .table td {
    border: 1px solid #ddd; 
    padding: 10px; 
    text-align: center; 
}

/* Menambahkan warna latar belakang pada header tabel */
.table th {
    background-color: var(--blue); 
    color: white; 
}

/* Memberikan efek hover pada baris tabel */
.table tbody tr:hover {
    background-color: var(--light-blue); 
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
    width: 500px;
    max-width: 100%;
    max-height: 80vh; /* Maksimum tinggi modal */
    overflow-y: auto; /* Tambahkan scroll jika konten melebihi tinggi */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* Shadow lembut */
    text-align: left; /* Pusatkan teks */
}

/* Form Inputs */
.modal-box input,
.modal-box textarea,
.modal-box select {
	font-family: var(--poppins);
	font-weight: 600;
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
}

/* Buttons */
.btn {
	font-family: var(--poppins);
	font-weight: 600;
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn-primary {
    background-color: var(--blue);
    color: var(--dark);
    font-weight: bold;
}

.btn-primary:hover {
    background-color: var(--dark-grey);
}

.btn-secondary {
    background-color: var(--orange);
    color: var(--dark);
}

.btn-secondary:hover {
    background-color: var(--light-orange);
}

</style>


</body>
</html>