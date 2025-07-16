<?php
session_start();
require("db.php"); // Sertakan file koneksi database

if (!isset($_SESSION['id_tmu']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Mekanisme otomatis untuk menutup kegiatan berdasarkan tanggal
try {
    $stmt_update_status = $conn->prepare("UPDATE tbl_kegiatan 
                                          SET status_tk = 'close' 
                                          WHERE tgl_berakhir_tk < CURDATE() AND status_tk = 'open'");
    $stmt_update_status->execute();
} catch (Exception $e) {
    error_log("Gagal memperbarui status kegiatan otomatis: " . $e->getMessage());
}

// Tambah Donasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_donation'])) {
    $conn->beginTransaction(); // Mulai transaksi
    try {
        $stmt = $conn->prepare("INSERT INTO tbl_t_donasi (id_tk, nama_donatur, email_donatur, jumlah_ttd, metode_ttd, status_ttd) 
                                VALUES (:id_tk, :nama, :email, :jumlah, :metode, 'berhasil')");
        $stmt->execute([
            ':id_tk' => $_POST['id_tk'],
            ':nama' => $_POST['nama_donatur'],
            ':email' => $_POST['email_donatur'],
            ':jumlah' => $_POST['jumlah_ttd'],
            ':metode' => $_POST['metode_ttd']
        ]);

        $stmt_update = $conn->prepare("UPDATE tbl_kegiatan 
                                       SET jumlah_terkumpul_tk = jumlah_terkumpul_tk + :jumlah 
                                       WHERE id_tk = :id_tk");
        $stmt_update->execute([
            ':jumlah' => $_POST['jumlah_ttd'],
            ':id_tk' => $_POST['id_tk']
        ]);

        $stmt_check = $conn->prepare("SELECT jumlah_terkumpul_tk, target_tk FROM tbl_kegiatan WHERE id_tk = :id_tk");
        $stmt_check->execute([':id_tk' => $_POST['id_tk']]);
        $kegiatan = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($kegiatan['jumlah_terkumpul_tk'] >= $kegiatan['target_tk']) {
            $stmt_close = $conn->prepare("UPDATE tbl_kegiatan SET status_tk = 'close' WHERE id_tk = :id_tk");
            $stmt_close->execute([':id_tk' => $_POST['id_tk']]);
        }

        $conn->commit();
        $success = "Donasi berhasil ditambahkan dan kegiatan diperbarui.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Tambah Pengeluaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expenditure'])) {
    $conn->beginTransaction(); // Mulai transaksi
    try {
        // Tambah data pengeluaran
        $stmt = $conn->prepare("INSERT INTO tbl_t_pengeluaran (id_tk, jumlah_ttp, deks_ttp) 
                                VALUES (:id_tk, :jumlah, :deks)");
        $stmt->execute([
            ':id_tk' => $_POST['id_tk'],
            ':jumlah' => $_POST['jumlah_ttp'],
            ':deks' => $_POST['deks_ttp']
        ]);

        // Kurangi jumlah terkumpul pada kegiatan
        $stmt_update = $conn->prepare("UPDATE tbl_kegiatan 
                                       SET jumlah_terkumpul_tk = jumlah_terkumpul_tk - :jumlah 
                                       WHERE id_tk = :id_tk");
        $stmt_update->execute([
            ':jumlah' => $_POST['jumlah_ttp'],
            ':id_tk' => $_POST['id_tk']
        ]);

        $conn->commit(); // Commit transaksi
        $success = "Pengeluaran berhasil ditambahkan, dan jumlah terkumpul diperbarui.";
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback jika terjadi kesalahan
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Menampilkan data donasi dengan pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query_donasi = "SELECT td.*, tk.judul_tk 
                 FROM tbl_t_donasi td
                 LEFT JOIN tbl_kegiatan tk ON td.id_tk = tk.id_tk";

if (!empty($search)) {
    $query_donasi .= " WHERE td.nama_donatur LIKE :search OR tk.judul_tk LIKE :search";
    $stmt_donasi = $conn->prepare($query_donasi);
    $stmt_donasi->execute([':search' => "%$search%"]);
} else {
    $stmt_donasi = $conn->query($query_donasi);
}

// Menampilkan data pengeluaran
$query_pengeluaran = "SELECT tp.*, tk.judul_tk 
                      FROM tbl_t_pengeluaran tp
                      LEFT JOIN tbl_kegiatan tk ON tp.id_tk = tk.id_tk";
$stmt_pengeluaran = $conn->query($query_pengeluaran);

// Menghapus Donasi
if (isset($_GET['delete_donation'])) {
    $conn->beginTransaction(); // Mulai transaksi
    try {
        // Ambil informasi donasi yang akan dihapus
        $stmt_donasi = $conn->prepare("SELECT id_tk, jumlah_ttd FROM tbl_t_donasi WHERE id_ttd = :id_ttd");
        $stmt_donasi->execute([':id_ttd' => $_GET['id_ttd']]);
        $donasi = $stmt_donasi->fetch(PDO::FETCH_ASSOC);

        if ($donasi) {
            // Kurangi jumlah terkumpul di tbl_kegiatan
            $stmt_update = $conn->prepare("UPDATE tbl_kegiatan 
                                           SET jumlah_terkumpul_tk = jumlah_terkumpul_tk - :jumlah 
                                           WHERE id_tk = :id_tk");
            $stmt_update->execute([
                ':jumlah' => $donasi['jumlah_ttd'],
                ':id_tk' => $donasi['id_tk']
            ]);

            // Hapus donasi dari tbl_t_donasi
            $stmt_delete = $conn->prepare("DELETE FROM tbl_t_donasi WHERE id_ttd = :id_ttd");
            $stmt_delete->execute([':id_ttd' => $_GET['id_ttd']]);

            $conn->commit(); // Commit transaksi
            $success = "Donasi berhasil dihapus, dan jumlah terkumpul diperbarui.";
        } else {
            throw new Exception("Donasi tidak ditemukan.");
        }
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaksi jika terjadi kesalahan
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
    header("Location: transaksi.php");
    exit();
}

// Menghapus Pengeluaran
if (isset($_GET['delete_expenditure'])) {
    $conn->beginTransaction(); // Mulai transaksi
    try {
        // Ambil informasi pengeluaran yang akan dihapus
        $stmt_pengeluaran = $conn->prepare("SELECT id_tk, jumlah_ttp FROM tbl_t_pengeluaran WHERE id_ttp = :id_ttp");
        $stmt_pengeluaran->execute([':id_ttp' => $_GET['id_ttp']]);
        $pengeluaran = $stmt_pengeluaran->fetch(PDO::FETCH_ASSOC);

        if ($pengeluaran) {
            // Kembalikan jumlah terkumpul di tbl_kegiatan
            $stmt_update = $conn->prepare("UPDATE tbl_kegiatan 
                                           SET jumlah_terkumpul_tk = jumlah_terkumpul_tk + :jumlah 
                                           WHERE id_tk = :id_tk");
            $stmt_update->execute([
                ':jumlah' => $pengeluaran['jumlah_ttp'],
                ':id_tk' => $pengeluaran['id_tk']
            ]);

            // Hapus pengeluaran dari tbl_t_pengeluaran
            $stmt_delete = $conn->prepare("DELETE FROM tbl_t_pengeluaran WHERE id_ttp = :id_ttp");
            $stmt_delete->execute([':id_ttp' => $_GET['id_ttp']]);

            $conn->commit(); // Commit transaksi
            $success = "Pengeluaran berhasil dihapus, dan jumlah terkumpul diperbarui.";
        } else {
            throw new Exception("Pengeluaran tidak ditemukan.");
        }
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaksi jika terjadi kesalahan
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
    header("Location: transaksi.php");
    exit();
}
// Pencarian dan Pagination untuk Donasi
$search_donasi = isset($_GET['search_donasi']) ? trim($_GET['search_donasi']) : '';
$page_donasi = isset($_GET['page_donasi']) ? (int)$_GET['page_donasi'] : 1;
$limit_donasi = 15; // Jumlah data per halaman
$offset_donasi = ($page_donasi - 1) * $limit_donasi;

// Query SQL untuk menghitung jumlah total data donasi dengan pencarian
$countQuery_donasi = "SELECT COUNT(*) FROM tbl_t_donasi td
                      LEFT JOIN tbl_kegiatan tk ON td.id_tk = tk.id_tk";

if (!empty($search_donasi)) {
    $countQuery_donasi .= " WHERE td.nama_donatur LIKE :search_donasi OR tk.judul_tk LIKE :search_donasi";
}

$stmt_count_donasi = $conn->prepare($countQuery_donasi);
if (!empty($search_donasi)) {
    $stmt_count_donasi->execute([':search_donasi' => "%$search_donasi%"]);
} else {
    $stmt_count_donasi->execute();
}
$totalData_donasi = $stmt_count_donasi->fetchColumn();
$totalPages_donasi = ceil($totalData_donasi / $limit_donasi);

// Query SQL untuk mengambil data donasi dengan filter pencarian, limit, dan offset
$query_donasi = "SELECT td.*, tk.judul_tk 
                 FROM tbl_t_donasi td
                 LEFT JOIN tbl_kegiatan tk ON td.id_tk = tk.id_tk";

if (!empty($search_donasi)) {
    $query_donasi .= " WHERE td.nama_donatur LIKE :search_donasi OR tk.judul_tk LIKE :search_donasi";
}

$query_donasi .= " LIMIT :limit_donasi OFFSET :offset_donasi";

$stmt_donasi = $conn->prepare($query_donasi);
if (!empty($search_donasi)) {
    $stmt_donasi->bindValue(':search_donasi', "%$search_donasi%", PDO::PARAM_STR);
}
$stmt_donasi->bindValue(':limit_donasi', $limit_donasi, PDO::PARAM_INT);
$stmt_donasi->bindValue(':offset_donasi', $offset_donasi, PDO::PARAM_INT);
$stmt_donasi->execute();

// Pencarian dan Pagination untuk Pengeluaran
$search_pengeluaran = isset($_GET['search_pengeluaran']) ? trim($_GET['search_pengeluaran']) : '';
$page_pengeluaran = isset($_GET['page_pengeluaran']) ? (int)$_GET['page_pengeluaran'] : 1;
$limit_pengeluaran = 5; // Jumlah data per halaman
$offset_pengeluaran = ($page_pengeluaran - 1) * $limit_pengeluaran;

// Query SQL untuk menghitung jumlah total data pengeluaran dengan pencarian
$countQuery_pengeluaran = "SELECT COUNT(*) FROM tbl_t_pengeluaran tp
                           LEFT JOIN tbl_kegiatan tk ON tp.id_tk = tk.id_tk";

if (!empty($search_pengeluaran)) {
    $countQuery_pengeluaran .= " WHERE tp.deks_ttp LIKE :search_pengeluaran OR tk.judul_tk LIKE :search_pengeluaran";
}

$stmt_count_pengeluaran = $conn->prepare($countQuery_pengeluaran);
if (!empty($search_pengeluaran)) {
    $stmt_count_pengeluaran->execute([':search_pengeluaran' => "%$search_pengeluaran%"]);
} else {
    $stmt_count_pengeluaran->execute();
}
$totalData_pengeluaran = $stmt_count_pengeluaran->fetchColumn();
$totalPages_pengeluaran = ceil($totalData_pengeluaran / $limit_pengeluaran);

// Query SQL untuk mengambil data pengeluaran dengan filter pencarian, limit, dan offset
$query_pengeluaran = "SELECT tp.*, tk.judul_tk 
                      FROM tbl_t_pengeluaran tp
                      LEFT JOIN tbl_kegiatan tk ON tp.id_tk = tk.id_tk";

if (!empty($search_pengeluaran)) {
    $query_pengeluaran .= " WHERE tp.deks_ttp LIKE :search_pengeluaran OR tk.judul_tk LIKE :search_pengeluaran";
}

$query_pengeluaran .= " LIMIT :limit_pengeluaran OFFSET :offset_pengeluaran";

$stmt_pengeluaran = $conn->prepare($query_pengeluaran);
if (!empty($search_pengeluaran)) {
    $stmt_pengeluaran->bindValue(':search_pengeluaran', "%$search_pengeluaran%", PDO::PARAM_STR);
}
$stmt_pengeluaran->bindValue(':limit_pengeluaran', $limit_pengeluaran, PDO::PARAM_INT);
$stmt_pengeluaran->bindValue(':offset_pengeluaran', $offset_pengeluaran, PDO::PARAM_INT);
$stmt_pengeluaran->execute();

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
    <style>
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }


    </style>
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
				<a href="kegiatan.php">
					<i class='bx bxs-briefcase-alt-2' ></i>
					<span class="text">Kegiatan</span>
				</a>
			</li>
			<li class="active">
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
					<h1>Data Transaksi</h1>
					<ul class="breadcrumb">
						<li>
							<a href="#">Dashboard</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Data Transaksi</a>
						</li>
					</ul>
				</div>
				<!-- Tombol Tambah Donasi -->
				<a class="btn-download openModalButton" data-modal-target="donationModal">
					<i class='bx bx-add-to-queue'></i>
					<span class="text">Tambah Donasi</span>
				</a>

				<!-- Tombol Tambah Pengeluaran -->
				<a class="btn-download openModalButton" data-modal-target="expenditureModal">
					<i class='bx bx-add-to-queue'></i>
					<span class="text">Tambah Pengeluaran</span>
				</a>

				<a class="btn-download openModalButton" data-modal-target="exportModal">
					<i class='bx bx-add-to-queue'></i>
					<span class="text">Export</span>
				</a>

			</div>
			<div class="table-data">
				<!-- Tabel Daftar Donasi -->
                <div class="order"> 
                    <div class="head">
                        <h3>Daftar Donasi</h3>
                        <form action="transaksi.php" method="GET" style="display: flex; gap: 10px;">
                            <input 
                                type="text" 
                                name="search_donasi" 
                                placeholder="Cari donasi..." 
                                value="<?= htmlspecialchars($search_donasi) ?>" 
                                style="padding: 5px; border: 1px solid #ddd; border-radius: 5px;"
                            />
                            <button type="submit" style="padding: 5px 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
                                Cari
                            </button>
                        </form>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kegiatan</th>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $no = $offset_donasi + 1; // Nomor urut berdasarkan halaman

                        // Menampilkan data donasi
                        while ($donasi = $stmt_donasi->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($donasi['judul_tk']) ?></td>
                                <td><?= htmlspecialchars($donasi['nama_donatur']) ?></td>
                                <td><?= htmlspecialchars($donasi['tgl_ttd']) ?></td>
                                <td>Rp<?= number_format($donasi['jumlah_ttd'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($donasi['metode_ttd']) ?></td>
                                <td>
                                    <span class="status <?= $donasi['status_ttd'] == 'berhasil' ? 'completed' : 'process' ?>">
                                        <?= htmlspecialchars($donasi['status_ttd']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status pending"> 
                                        <a href="transaksi.php?delete_donation&id_ttd=<?= $donasi['id_ttd'] ?>" 
                                        class="btn btn-danger btn-sm" 
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus donasi ini?')">Hapus</a>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="pagination" style="text-align:center">
                        <a href="?page_donasi=<?= max(1, $page_donasi - 1) ?>&search_donasi=<?= urlencode($search_donasi) ?>" class="prev" style="border: 1.5px solid black; padding: 2px;">Previous</a>
                        <span><?= $page_donasi ?> of <?= $totalPages_donasi ?></span>
                        <a href="?page_donasi=<?= min($totalPages_donasi, $page_donasi + 1) ?>&search_donasi=<?= urlencode($search_donasi) ?>" class="next" style="border: 1.5px solid black; padding: 2px;">Next</a>
                    </div>
                </div>

                <!-- Tabel Daftar Pengeluaran -->
                <div class="order">
                    <div class="head">
                        <h3>Daftar Pengeluaran</h3>
                        <form action="transaksi.php" method="GET" style="display: flex; gap: 10px;">
                            <input 
                                type="text" 
                                name="search_pengeluaran" 
                                placeholder="Cari data pengeluaran..." 
                                value="<?= htmlspecialchars($search_pengeluaran) ?>" 
                                style="padding: 5px; border: 1px solid #ddd; border-radius: 5px;"
                            />
                            <button type="submit" style="padding: 5px 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
                                Cari
                            </button>
                        </form>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kegiatan</th>
                                <th>Jumlah</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $offset_pengeluaran + 1; // Nomor urut berdasarkan halaman

                            // Tampilkan data pengeluaran
                            while ($pengeluaran = $stmt_pengeluaran->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($pengeluaran['judul_tk']) ?></td>
                                    <td>Rp<?= number_format($pengeluaran['jumlah_ttp'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($pengeluaran['deks_ttp']) ?></td>
                                    <td>
                                        <span class="status pending">
                                            <a href="transaksi.php?delete_expenditure&id_ttp=<?= $pengeluaran['id_ttp'] ?>" 
                                            class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')">Hapus</a>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="pagination" style="text-align:center; margin-top: 10px;">
                        <a href="?page_pengeluaran=<?= max(1, $page_pengeluaran - 1) ?>&search_pengeluaran=<?= urlencode($search_pengeluaran) ?>" 
                        class="prev" 
                        style="border: 1.5px solid black; padding: 5px;">Previous</a>
                        <span><?= $page_pengeluaran ?> of <?= $totalPages_pengeluaran ?></span>
                        <a href="?page_pengeluaran=<?= min($totalPages_pengeluaran, $page_pengeluaran + 1) ?>&search_pengeluaran=<?= urlencode($search_pengeluaran) ?>" 
                        class="next" 
                        style="border: 1.5px solid black; padding: 5px;">Next</a>
                    </div>
                </div>
			</div>
		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
	<!-- Modal Tambah Donasi -->
    <div id="donationModal" class="modal">
        <div class="modal-box">
            <h3>Tambah Donasi</h3>
            <form method="POST" action="transaksi.php" id="donationForm">
                <div class="mb-3">
                    <label for="id_tk" class="form-label">Pilih Kegiatan</label>
                    <select class="form-select" id="id_tk" name="id_tk" required>
                        <option value="">Pilih Kegiatan</option>
                        <?php
                        $stmt_kegiatan = $conn->query("SELECT id_tk, judul_tk FROM tbl_kegiatan WHERE status_tk = 'open'");
                        while ($row = $stmt_kegiatan->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$row['id_tk']}'>{$row['judul_tk']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="nama_donatur" class="form-label">Nama Donatur</label>
                    <input type="text" class="form-control" id="nama_donatur" name="nama_donatur" required>
                </div>
                <div class="mb-3">
                    <label for="email_donatur" class="form-label">Email Donatur (Opsional)</label>
                    <input type="email" class="form-control" id="email_donatur" name="email_donatur">
                </div>
                <div class="mb-3">
                    <label for="jumlah_ttd" class="form-label">Jumlah Donasi</label>
                    <input type="text" class="form-control" id="jumlah_ttd" name="jumlah_ttd" required>
                </div>
                <div class="mb-3">
                    <label for="metode_ttd" class="form-label">Metode Pembayaran</label>
                    <select class="form-select" id="metode_ttd" name="metode_ttd" required>
                        <option value="">Pilih Metode</option>
                        <option value="Cash">Cash</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="add_donation" class="btn btn-primary">Tambah Donasi</button>
                    <button type="button" class="btn btn-secondary closeModalButton">Batal</button>
                </div>
            </form>
        </div>
    </div>


	<!-- Modal Tambah Pengeluaran -->
	<div id="expenditureModal" class="modal">
        <div class="modal-box">
            <h3>Tambah Pengeluaran</h3>
            <form method="POST" action="transaksi.php" id="expenditureForm">
                <div class="mb-3">
                    <label for="id_tk_pengeluaran" class="form-label">Pilih Kegiatan</label>
                    <select class="form-select" id="id_tk_pengeluaran" name="id_tk" required>
                        <option value="">Pilih Kegiatan</option>
                        <?php
                        $stmt_kegiatan = $conn->query("SELECT id_tk, judul_tk FROM tbl_kegiatan");
                        while ($row = $stmt_kegiatan->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$row['id_tk']}'>{$row['judul_tk']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="jumlah_ttp" class="form-label">Jumlah Pengeluaran</label>
                    <input type="text" class="form-control" id="jumlah_ttp" name="jumlah_ttp" required>
                </div>
                <div class="mb-3">
                    <label for="deks_ttp" class="form-label">Deskripsi Pengeluaran</label>
                    <textarea class="form-control" id="deks_ttp" name="deks_ttp" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="add_expenditure" class="btn btn-primary">Tambah Pengeluaran</button>
                    <button type="button" class="btn btn-secondary closeModalButton">Batal</button>
                </div>
            </form>
        </div>
    </div>

	<!-- Modal Ekspor Data -->
	<!-- Formulir Ekspor -->
	<div id="exportModal" class="modal">
		<div class="modal-box">
			<h3>Ekspor Data</h3>
			<form method="GET" action="export.php">
				<div class="mb-3">
					<label for="id_tk_export" class="form-label">Pilih Kegiatan</label>
					<select class="form-select" id="id_tk_export" name="id_tk" required>
						<option value="" disabled selected>Pilih Kegiatan</option>
						<?php
						$stmt_kegiatan = $conn->query("SELECT id_tk, judul_tk FROM tbl_kegiatan");
						while ($row = $stmt_kegiatan->fetch(PDO::FETCH_ASSOC)) {
							echo "<option value='{$row['id_tk']}'>{$row['judul_tk']}</option>";
						}
						?>
					</select>
				</div>
				<div class="mb-3">
					<label for="jenis_data" class="form-label">Pilih Jenis Data</label>
					<select class="form-select" id="jenis_data" name="jenis_data" required>
						<option value="" disabled selected>Pilih Jenis Data</option>
						<option value="donasi">Donasi</option>
						<option value="pengeluaran">Pengeluaran</option>
					</select>
				</div>
				<div class="mb-3">
					<label for="format" class="form-label">Pilih Format</label>
					<select class="form-select" id="format" name="format" required>
						<option value="" disabled selected>Pilih Format</option>
						<option value="pdf">PDF</option>
						<option value="excel">Excel</option>
					</select>
				</div>
				<div class="modal-actions">
					<button type="submit" name="export_data" class="btn btn-primary">Ekspor</button>
					<button type="button" class="btn btn-secondary closeModalButton">Batal</button>
				</div>
			</form>
		</div>
	</div>




	<script>
        const jumlahTtpInput = document.getElementById('jumlah_ttp');

        jumlahTtpInput.addEventListener('input', function (e) {
            let value = this.value.replace(/[^,\d]/g, ''); // Hanya angka dan koma
            let formattedValue = formatRupiah(value, 'Rp. ');
            this.value = formattedValue;
        });

        // Sebelum form dikirim, pastikan mengirimkan nilai jumlah_ttp sebagai angka mentah
        const expenditureForm = document.getElementById('expenditureForm');
        expenditureForm.addEventListener('submit', function(event) {
            const jumlahTtpValue = jumlahTtpInput.value.replace(/[^0-9]/g, ''); // Hapus format Rupiah
            jumlahTtpInput.value = jumlahTtpValue; // Ganti nilai input dengan angka mentah
        });

        const jumlahTtdInput = document.getElementById('jumlah_ttd');

        jumlahTtdInput.addEventListener('input', function (e) {
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

        // Sebelum form dikirim, pastikan mengirimkan nilai jumlah_ttd sebagai angka mentah
        const donationForm = document.getElementById('donationForm');
        donationForm.addEventListener('submit', function(event) {
            const jumlahTtdValue = jumlahTtdInput.value.replace(/[^0-9]/g, ''); // Hapus format Rupiah
            jumlahTtdInput.value = jumlahTtdValue; // Ganti nilai input dengan angka mentah
        });

        // Semua tombol untuk membuka modal
        const openModalButtons = document.querySelectorAll('.openModalButton');

        // Semua tombol untuk menutup modal
        const closeModalButtons = document.querySelectorAll('.closeModalButton');

        // Fungsi untuk membuka modal
        openModalButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const targetModal = document.getElementById(button.dataset.modalTarget);
                if (targetModal) targetModal.style.display = 'flex';
            });
        });

        // Fungsi untuk menutup modal
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function () {
                const modal = button.closest('.modal');
                if (modal) modal.style.display = 'none';
            });
        });

        // Klik di luar modal untuk menutupnya
        window.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
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
    text-align: left;
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
    transition: border-color 0.3s ease;
}

.modal-box input:focus,
.modal-box textarea:focus,
.modal-box select:focus {
    border-color: var(--blue); /* Border berubah menjadi biru saat aktif */
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
    transition: background-color 0.3s ease;
}

.modal-actions .btn-primary {
    background-color: var(--blue);
    color: var(--light);
    font-weight: bold;
}

.modal-actions .btn-primary:hover {
    background-color: var(--dark-grey);
}

.modal-actions .btn-secondary {
    background-color: var(--orange);
    color: var(--light);
}

.modal-actions .btn-secondary:hover {
    background-color: var(--light-orange);
}

/* Tombol Tambah */
.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: var(--poppins);
    font-weight: 600;
    background-color: var(--yellow);
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    color: var(--dark);
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-download:hover {
    background-color: var(--light-yellow);
}

/* Tombol Ikon */
.btn-download i {
    font-size: 20px;
    color: var(--dark);
}

.btn-download .text {
    font-size: 16px;
    font-weight: bold;
}
</style>

</body>
</html>