<?php
session_start();
include('db.php'); // Sertakan koneksi database

// Cek apakah pengguna sudah login dan memiliki role 'admin'
if (!isset($_SESSION['id_tmu']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Proses form tambah kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul_tk = $_POST['judul_tk'] ?? '';
    $deks_tk = $_POST['deks_tk'] ?? '';
    $id_tmk = $_POST['id_tmk'] ?? '';
    $id_tmp = $_POST['id_tmp'] ?? '';
    $tgl_mulai_tk = $_POST['tgl_mulai_tk'] ?? '';
    $tgl_selesai_tk = $_POST['tgl_selesai_tk'] ?? '';
    $target_tk = $_POST['target_tk'] ?? '';

    // Validasi input
    if ($judul_tk && $deks_tk && $id_tmk && $id_tmp && $tgl_mulai_tk && $tgl_selesai_tk && $target_tk) {
        try {
            $stmt = $conn->prepare("INSERT INTO tbl_kegiatan (judul_tk, deks_tk, id_tmk, id_tmp, tgl_mulai_tk, tgl_selesai_tk, target_tk)
                                    VALUES (:judul_tk, :deks_tk, :id_tmk, :id_tmp, :tgl_mulai_tk, :tgl_selesai_tk, :target_tk)");
            $stmt->execute([
                ':judul_tk' => $judul_tk,
                ':deks_tk' => $deks_tk,
                ':id_tmk' => $id_tmk,
                ':id_tmp' => $id_tmp,
                ':tgl_mulai_tk' => $tgl_mulai_tk,
                ':tgl_selesai_tk' => $tgl_selesai_tk,
                ':target_tk' => $target_tk,
            ]);

            header('Location: kegiatan.php?success=1');
            exit();
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error_message = "Harap isi semua data!";
    }
}

// Mengubah status kegiatan (open/close)
if (isset($_GET['change_status']) && isset($_GET['id_tk']) && isset($_GET['status_tk'])) {
    $id_tk = $_GET['id_tk'];
    $status_tk = $_GET['status_tk'] == 'open' ? 'close' : 'open';

    try {
        $stmt = $conn->prepare("UPDATE tbl_kegiatan SET status_tk = :status_tk WHERE id_tk = :id_tk");
        $stmt->bindParam(':status_tk', $status_tk);
        $stmt->bindParam(':id_tk', $id_tk);
        if ($stmt->execute()) {
            header("Location: kegiatan.php");
            exit();
        } else {
            $error_message = "Gagal mengubah status kegiatan.";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

//meghapus kategori
if (isset($_GET['delete_activity']) && isset($_GET['id_tk'])) {
    $id_tk = $_GET['id_tk'];

    try {
        // Memulai transaksi
        $conn->beginTransaction();

        // Hapus data transaksi yang terkait
        $stmt1 = $conn->prepare("DELETE FROM tbl_t_donasi WHERE id_tk = :id_tk");
        $stmt1->bindParam(':id_tk', $id_tk);
        if (!$stmt1->execute()) {
            throw new Exception("Gagal menghapus data transaksi.");
        }

        // Hapus data kegiatan
        $stmt2 = $conn->prepare("DELETE FROM tbl_kegiatan WHERE id_tk = :id_tk");
        $stmt2->bindParam(':id_tk', $id_tk);
        if (!$stmt2->execute()) {
            throw new Exception("Gagal menghapus data kegiatan.");
        }

        // Commit transaksi
        $conn->commit();

        // Redirect ke halaman kegiatan
        header("Location: kegiatan.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        $conn->rollBack();
        echo "Terjadi kesalahan: " . $e->getMessage();
    }
}




// Mengambil semua kategori dan pamflet untuk dropdown
$stmt_kategori = $conn->query("SELECT * FROM tbl_m_kategori");
$stmt_pamflet = $conn->query("SELECT * FROM tbl_m_pamflet");

// Mengambil data kegiatan beserta kategori dan pamflet
$stmt_kegiatan = $conn->query("SELECT tk.*, k.nama_tmk, p.foto_tmp 
                                FROM tbl_kegiatan tk
                                LEFT JOIN tbl_m_kategori k ON tk.id_tmk = k.id_tmk
                                LEFT JOIN tbl_m_pamflet p ON tk.id_tmp = p.id_tmp");
$kegiatan = $stmt_kegiatan->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<!-- My CSS -->
	<link rel="stylesheet" href="style.css">

	<title>Himatif Care</title>
</head>
<body>


	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bx-donate-heart'></i>
			<span class="text">Admin Menu</span>
		</a>
		<ul class="side-menu top">
			<li>
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
			<li class="active">
				<a href="kegiatan.php">
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
			<li>
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
		<!-- MAIN -->
		<main>
			<div class="head-title">
				<div class="left">
					<h1>Kegiatan</h1>
					<ul class="breadcrumb">
						<li>
							<a href="dashboard-admin.php">Dashboard</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="kegiatan.php">Kegiatan</a>
						</li>
					</ul>
				</div>
				<a id="openModalButton" class="btn-download">
					<i class='bx bx-add-to-queue'></i>
					<span class="text">Tambah Kegiatan</span>
				</a>
			</div>
			<div class="table-data">
				<div class="order">
					<div class="head">
						<h3>Daftar Kegiatan</h3>
					</div>
					<table>
						<thead>
							<tr>
								<th>Judul</th>
								<th>Periode</th>
								<th>Kategori</th>
								<th>Target</th>
								<th>Terkumpul</th>
								<th>Status</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php if (!empty($kegiatan)): ?>
								<?php foreach ($kegiatan as $item): ?>
									<tr>
										<td>
											<img src="uploads/pamflet/<?= htmlspecialchars($item['foto_tmp']) ?>" alt="<?= htmlspecialchars($item['judul_tk']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
											<?= htmlspecialchars($item['judul_tk']) ?>
										</td>
										<td><?= htmlspecialchars($item['tgl_mulai_tk']) ?> <br> <?= htmlspecialchars($item['tgl_selesai_tk']) ?></td>
										<td><?= htmlspecialchars($item['nama_tmk']) ?></td>
										<td>Rp<?= number_format($item['target_tk'], 0, ',', '.') ?></td>
										<td>Rp<?= number_format($item['jumlah_terkumpul_tk'], 0, ',', '.') ?></td>
										<td>
											<span class="status <?= $item['status_tk'] == 'open' ? 'process' : 'completed' ?>">
												<?= htmlspecialchars($item['status_tk']) ?>
											</span>
										</td>
										<td>
											<div class="d-flex" style="gap: 10px;">
												<span class="status <?= $item['status_tk'] == 'open' ? 'completed' : 'process' ?>">
													<a href="?change_status&id_tk=<?= $item['id_tk'] ?>&status_tk=<?= $item['status_tk'] ?>" class="btn btn-warning" style="padding: 5px 10px; text-decoration: none; color: white;">
														<?= $item['status_tk'] == 'open' ? 'Tutup' : 'Buka' ?>
													</a>
												</span>
											</div>
											<br>
											<div class="d-flex" style="gap: 10px;">
    <span class="status pending">
        <a href="?delete_activity&id_tk=<?= $item['id_tk'] ?>" 
           class="btn btn-danger" 
           style="padding: 5px 10px; text-decoration: none; color: white;" 
           onclick="return confirm('Yakin ingin menghapus kegiatan ini? Semua transaksi terkait juga akan dihapus.')">
           Hapus
        </a>
    </span>
</div>

										</td>
									</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr>
									<td colspan="7" class="text-center">Tidak ada kegiatan yang tersedia.</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
	
<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-box">
        <h3>Tambah Kegiatan Baru</h3>
        <form method="POST" action="kegiatan.php" class="p-4 border rounded bg-light shadow-sm">
            <div class="mb-3">
                <label for="judul_tk" class="form-label">Judul Kegiatan</label>
                <input type="text" class="form-control" id="judul_tk" name="judul_tk" placeholder="Masukkan judul kegiatan" required>
            </div>
            <div class="mb-3">
                <label for="deks_tk" class="form-label">Deskripsi Kegiatan</label>
                <textarea class="form-control" id="deks_tk" name="deks_tk" rows="3" placeholder="Deskripsikan kegiatan" required></textarea>
            </div>
            <div class="mb-3">
                <label for="id_tmk" class="form-label">Kategori</label>
                <select class="form-select" id="id_tmk" name="id_tmk" required>
                    <?php while ($kategori = $stmt_kategori->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?= $kategori['id_tmk'] ?>"><?= htmlspecialchars($kategori['nama_tmk']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="id_tmp" class="form-label">Pamflet</label>
                <select class="form-select" id="id_tmp" name="id_tmp" required>
                    <?php while ($pamflet = $stmt_pamflet->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?= $pamflet['id_tmp'] ?>"><?= htmlspecialchars($pamflet['foto_tmp']) ?></option>
                    <?php endwhile;?>
                </select>
            </div>
            <div class="mb-3">
                <label for="tgl_mulai_tk" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" id="tgl_mulai_tk" name="tgl_mulai_tk" required>
            </div>
            <div class="mb-3">
                <label for="tgl_selesai_tk" class="form-label">Tanggal Selesai</label>
                <input type="date" class="form-control" id="tgl_selesai_tk" name="tgl_selesai_tk" required>
            </div>
            <div class="mb-3">
                <label for="target_tk" class="form-label">Target Dana</label>
                <input type="text" class="form-control" id="target_tk" name="target_tk" placeholder="Masukkan target dana" required>
            </div>
            <div class="modal-actions">
                <button type="submit" name="add_activity" class="btn btn-primary">Tambah Kegiatan</button>
                <button type="button" id="closeModalButton" class="btn btn-secondary">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    const targetTkInput = document.getElementById('target_tk');

    targetTkInput.addEventListener('input', function (e) {
        let value = this.value.replace(/[^,\d]/g, ''); // Hanya angka dan koma
        let formattedValue = formatRupiah(value, 'Rp. ');
        this.value = formattedValue;
    });

    function formatRupiah(value, prefix) {
        let numberString = value.replace(/[^,\d]/g, '').toString();
        let split = numberString.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix === undefined ? rupiah : (rupiah ? prefix + rupiah : '');
    }
    // Sebelum form dikirim, pastikan mengirimkan nilai target_tk sebagai angka mentah
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        const targetTkValue = targetTkInput.value.replace(/[^0-9]/g, ''); // Hapus format Rupiah
        targetTkInput.value = targetTkValue; // Ganti nilai input dengan angka mentah
    });
    // Referensi elemen modal dan tombol
    const modal = document.getElementById('modal');
    const openModalButton = document.getElementById('openModalButton');
    const closeModalButton = document.getElementById('closeModalButton');

    // Buka modal
    openModalButton.addEventListener('click', function (event) {
        event.preventDefault(); // Mencegah tindakan default tombol
        modal.style.display = 'flex'; // Tampilkan modal
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
    color: var(--dark);
    font-weight: bold;
}

.modal-actions .btn-primary:hover {
    background-color: var(--dark-grey);
}

.modal-actions .btn-secondary {
    background-color: var(--orange);
    color: var(--dark);
}

.modal-actions .btn-secondary:hover {
    background-color: var(--light-orange);
}
</style>



</body>
</html>