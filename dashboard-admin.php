<?php
session_start();
include('db.php');
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai


// Cek apakah pengguna sudah login dan memiliki role 'admin'
if (!isset($_SESSION['id_tmu']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Mengambil semua data kategori
$stmt_kategori = $conn->query("SELECT * FROM tbl_m_kategori");
$kategori = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);

// Mengambil semua data pamflet
$stmt_pamflet = $conn->query("SELECT * FROM tbl_m_pamflet");
$pamflet = $stmt_pamflet->fetchAll(PDO::FETCH_ASSOC);

// Mengambil data kegiatan beserta kategori dan pamflet
$stmt_kegiatan = $conn->query("
    SELECT tk.*, k.nama_tmk, p.foto_tmp
                                                                                 FROM tbl_kegiatan tk
    LEFT JOIN tbl_m_kategori k ON tk.id_tmk = k.id_tmk
    LEFT JOIN tbl_m_pamflet p ON tk.id_tmp = p.id_tmp
");
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
			<li class="active">
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
					<h1>Dashboard</h1>
					<ul class="breadcrumb">
						<li>
							<a href="#">Dashboard</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Home</a>
						</li>
					</ul>
				</div>
			</div>

			<ul class="box-info">
				<li>
                    <a href="kategori.php">
                        <i class='bx bxs-category' ></i>
                    </a>
					<span class="text">
						<h3><?= count($kategori) ?></h3>
						<p>Category</p>
					</span>
				</li>
				<li>
                    <a href="pamflet.php">
                        <i class='bx bxs-image' ></i>
                    </a>
					<span class="text">
						<h3><?= count($pamflet)?></h3>
						<p>Pamflets</p>
					</span>
				</li>
				<li>
                    <a href="kegiatan.php">
                        <i class='bx bxs-briefcase-alt-2'></i>
                    </a>
					<span class="text">
						<h3><?= count($kegiatan)?></h3>
						<p>Kegiatan</p>
					</span>
				</li>
			</ul>


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
								<th>Status</th>
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
										<td>
											<span class="status <?= $item['status_tk'] == 'open' ? 'process' : 'completed' ?>">
												<?= htmlspecialchars($item['status_tk']) ?>
											</span>
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
	

	<script>
		const toggleSidebar = document.querySelector('.toggle-sidebar');
		const sidebar = document.getElementById('sidebar');
		toggleSidebar.addEventListener('click', () => {
			sidebar.classList.toggle('hide');
		});
	</script>
</body>
</html>