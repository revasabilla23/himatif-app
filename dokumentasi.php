<?php
session_start();
require('db.php');

if (!isset($_SESSION['id_tmu']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Proses tambah dokumentasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deks_tmp = $_POST['deks_tmp'];
    $id_tk = $_POST['id_tk'];  // Menyertakan id_tk dari form
    $upload_dir = 'uploads/dokumentasi/';
    $upload_file = $upload_dir . basename($_FILES['foto_tmp']['name']);
    $image_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));

    if (in_array($image_type, ['jpg', 'jpeg', 'png'])) {
        if (move_uploaded_file($_FILES['foto_tmp']['tmp_name'], $upload_file)) {
            // Menyimpan dokumentasi ke database
            $stmt = $conn->prepare("INSERT INTO tbl_h_dokumentasi (id_tk, foto_thd, desk_thd) VALUES (:id_tk, :foto_tmp, :deks_tmp)");
            $stmt->bindParam(':id_tk', $id_tk);  // Menyimpan ID Kegiatan
            $stmt->bindParam(':foto_tmp', $_FILES['foto_tmp']['name']);
            $stmt->bindParam(':deks_tmp', $deks_tmp);

            if ($stmt->execute()) {
                $success = "Dokumentasi berhasil diunggah.";
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

// Menampilkan daftar dokumentasi
$stmt = $conn->query("SELECT * FROM tbl_h_dokumentasi");
$dokumentasis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses hapus dokumentasi
if (isset($_GET['delete_id'])) {
    $id_thd = $_GET['delete_id'];

    // Ambil data dokumentasi untuk menghapus file
    $stmt = $conn->prepare("SELECT foto_thd FROM tbl_h_dokumentasi WHERE id_thd = :id_thd");
    $stmt->bindParam(':id_thd', $id_thd);
    $stmt->execute();
    $dokumentasi = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dokumentasi) {
        $file_path = "uploads/dokumentasi/" . $dokumentasi['foto_thd'];
        if (file_exists($file_path)) {
            unlink($file_path); // Hapus file dari server
        }

        // Hapus data dari database
        $stmt = $conn->prepare("DELETE FROM tbl_h_dokumentasi WHERE id_thd = :id_thd");
        $stmt->bindParam(':id_thd', $id_thd);
        $stmt->execute();
    }

    header("Location: dokumentasi.php");
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
    <title>Dokumentasi</title>
</head>
<body>

    <!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bx-donate-heart'></i>
			<span class="text">Admin Menu</span>
		</a>
		<ul class="side-menu top">
			<li >
				<a href="dashboard-admin.php">
					<i class='bx bxs-home' ></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href="kategori.php">
					<i class='bx bxs-category-alt' ></i>
					<span class="text">Kategori</span>
				</a>
			</li>
			<li>
				<a href="pamflet.php">
					<i class='bx bx-images' ></i>
					<span class="text">Phamflet</span>
				</a>
			</li>
			<li>
				<a href="Kegiatan.php">
					<i class='bx bxs-briefcase-alt-2' ></i>
					<span class="text">Kegiatan</span>
				</a>
			</li>
			<li>
				<a href="transaksi.php">
					<i class='bx bxs-credit-card' ></i>
					<span class="text">Transaksi</span>
				</a>
			</li>
			<li  class="active">
				<a href="dokumentasi.php">
					<i class='bx bxs-image' ></i>
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
                    <h1>Dokumentasi</h1>
                    <ul class="breadcrumb">
                        <li><a href="dashboard-admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right' ></i></li>
                        <li><a class="active" href="dokumentasi.php">Dokumentasi</a></li>
                    </ul>
                </div>
                <a id="openModalButton" class="btn-download">
                    <i class='bx bx-add-to-queue'></i>
                    <span class="text">Tambah Dokumentasi</span>
                </a>
            </div>

            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Daftar Dokumentasi</h3>
                    </div>
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
                            <?php if (count($dokumentasis) > 0): ?>
                                <?php foreach ($dokumentasis as $index => $dokumentasi): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <img src="uploads/dokumentasi/<?= htmlspecialchars($dokumentasi['foto_thd']) ?>" alt="Dokumentasi" style="width: 100px; height: auto; border-radius: 5px;">
                                        </td>
                                        <td><?= htmlspecialchars($dokumentasi['desk_thd']) ?></td>
                                        <td>
                                            <span class="status completed">
                                                <a href="dokumentasi.php?delete_id=<?= $dokumentasi['id_thd'] ?>"
                                                onclick="return confirm('Yakin ingin menghapus dokumentasi ini?')"
                                                style="color: inherit; text-decoration: none;">Hapus</a>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data dokumentasi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>
    <!-- CONTENT -->
	 <!-- Modal untuk Tambah Dokumentasi -->
	 <div id="modal" class="modal">
    <div class="modal-box">
        <h3>Upload Dokumentasi Baru</h3>
        <form action="dokumentasi.php" method="POST" enctype="multipart/form-data">
            <!-- Pesan sukses atau error -->
            <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <!-- Input untuk memilih kegiatan -->
            <div class="mb-3">
                <label for="id_tk" class="form-label">Pilih Kegiatan</label>
                <select class="form-select" id="id_tk" name="id_tk" required>
                    <option value="" disabled selected>Pilih Kegiatan</option>
                    <?php
                    // Menampilkan daftar kegiatan
                    $stmt_kegiatan = $conn->query("SELECT id_tk, judul_tk FROM tbl_kegiatan");
                    while ($row = $stmt_kegiatan->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$row['id_tk']}'>{$row['judul_tk']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Input untuk Foto Dokumentasi -->
            <div class="mb-3">
                <label for="foto_tmp" class="form-label">Foto Dokumentasi</label>
                <input type="file" class="form-control" id="foto_tmp" name="foto_tmp" required>
            </div>

            <!-- Input untuk Deskripsi Dokumentasi -->
            <div class="mb-3">
                <label for="deks_tmp" class="form-label">Deskripsi Dokumentasi</label>
                <textarea class="form-control" id="deks_tmp" name="deks_tmp" rows="3" required></textarea>
            </div>

            <!-- Tombol untuk submit dan batal -->
            <div class="modal-actions">
                <button type="submit" name="add_dokumentasi" class="btn btn-primary">Upload Dokumentasi</button>
                <button type="button" id="closeModalButton" class="btn btn-secondary">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Referensi elemen modal dan tombol
    const modal = document.getElementById('modal');
    const openModalButton = document.getElementById('openModalButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const formModal = modal.querySelector('form');

    // Buka modal
    openModalButton.addEventListener('click', function (event) {
        event.preventDefault(); // Mencegah tindakan default tombol
        modal.style.display = 'flex'; // Tampilkan modal
        sidebar.classList.add('hide'); // Sembunyikan sidebar saat modal dibuka
    });

    // Tutup modal
    closeModalButton.addEventListener('click', function () {
        modal.style.display = 'none'; // Sembunyikan modal
    });

    // Tutup modal ketika klik di luar box
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none'; // Sembunyikan modal
        }
    });

    // Toggle sidebar (tetap berfungsi seperti biasa)
    toggleSidebar.addEventListener('click', () => {
        sidebar.classList.toggle('hide');
    });

    // Pastikan sidebar hilang ketika form di dalam modal diklik
    formModal.addEventListener('click', function() {
        sidebar.classList.add('hide');
    });
</script>

<style>
.modal {
    font-family: 'Poppins', sans-serif;
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
    background: #F9F9F9; /* Warna dasar putih */
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
.modal-box textarea,
.modal-box select {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    width: 100%;
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #eee;
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
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.modal-actions .btn-primary {
    background-color: #3C91E6;
    color: #F9F9F9;
    font-weight: bold;
}

.modal-actions .btn-primary:hover {
    background-color: #CFE8FF;
}

.modal-actions .btn-secondary {
    background-color: #DB504A;
    color: #F9F9F9;
}

.modal-actions .btn-secondary:hover {
    background-color: #AAAAAA;
}

/* Tampilkan modal */
.modal.show {
    display: flex;
}



</style>

</body>
</html>
