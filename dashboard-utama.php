<?php
session_start();
include('db.php'); // Sertakan koneksi database
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu yang sesuai


$stmt_kategori = $conn->query("SELECT * FROM tbl_m_kategori");
$stmt_pamflet = $conn->query("SELECT * FROM tbl_m_pamflet");

// Mengambil data kegiatan beserta kategori dan pamflet
$stmt_kegiatan = $conn->query("SELECT tk.*, k.nama_tmk, p.foto_tmp 
                                FROM tbl_kegiatan tk
                                LEFT JOIN tbl_m_kategori k ON tk.id_tmk = k.id_tmk
                                LEFT JOIN tbl_m_pamflet p ON tk.id_tmp = p.id_tmp");
$kegiatan = $stmt_kegiatan->fetchAll(PDO::FETCH_ASSOC);

// Menampilkan daftar dokumentasi
$stmt = $conn->query("SELECT * FROM tbl_h_dokumentasi");
$dokumentasis = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_kegiatan = $_GET['kegiatan'] ?? null;

// Ambil detail kegiatan yang dipilih
$selected_kegiatan_title = '';
$selected_kegiatan_status = '';
if ($selected_kegiatan) {
    try {
        // Mengambil detail kegiatan termasuk status
        $stmt = $conn->prepare("SELECT id_tk, judul_tk, status_tk FROM tbl_kegiatan WHERE id_tk = :id_tk");
        $stmt->bindParam(':id_tk', $selected_kegiatan);
        $stmt->execute();
        $selected_kegiatan_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_kegiatan_data) {
            $selected_kegiatan_title = $selected_kegiatan_data['judul_tk'];
            $selected_kegiatan_status = $selected_kegiatan_data['status_tk'];
        }
    } catch (PDOException $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Himatif Care</title>
    <link rel="icon" type="" sizes="180x180" href="img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top" style="background-color: #add8e6; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
        <div class="container-fluid">
            <!-- Logo dan nama navbar di kiri -->
            <a class="navbar-brand me-auto" style="color: navy; font-weight: bold;">
                <img src="img/logo.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px;"> Himatif Care
            </a>

            <!-- Toggler untuk tampilan mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Offcanvas menu -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel" style="background-color: #add8e6;">
                <div class="offcanvas-header" style="background-color: #add8e6; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
                    <img src="img/logo.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px;">
                    <h5 class="offcanvas-title" id="offcanvasNavbarLabel" style="color: navy;">Himatif Care</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <!-- Menambahkan ms-auto untuk mendorong item navbar ke kanan -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link mx-lg-2" href="#about" style="color: navy;">About</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2" href="#how-to-donate" style="color: navy;">Cara Donasi</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2" href="#campaign-donations" style="color: navy;">Campaign</a></li>
                        <a href="login" class="btn" style="background-color :  #add8e6; color : navy ; border-color:#add8e6;">Login</a>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- dokumentasi -->
    <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000" data-aos="zoom-in" data-aos-duration="3000">
        <div class="carousel-inner">
            <?php foreach ($dokumentasis as $index => $dokumentasi): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="uploads/dokumentasi/<?= htmlspecialchars($dokumentasi['foto_thd']) ?>" class="d-block w-100" alt="Dokumentasi">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Dokumentasi <?= $index + 1 ?></h5>
                        <p><?= htmlspecialchars($dokumentasi['desk_thd']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Optional controls for navigation -->
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- tentang -->
    <section id="about" class="py-4" >
        <div class="container" id="con-tentang" >
                <div class="col-md-6" data-aos="fade-up-right" data-aos-duration="3000">
                    <h2 id="judulTentang">Tentang Kami</h2>
                    <p id="keteranganTentang" >
                        Kami adalah Himatif Care yang berdedikasi untuk membantu masyarakat kurang mampu. Dengan dukungan donatur yang peduli, kami mampu menyalurkan bantuan kepada mereka yang membutuhkan. Kami percaya bahwa setiap tindakan kecil dapat membawa perubahan besar, dan kami berkomitmen untuk menciptakan dampak positif dalam hidup mereka yang kurang beruntung.
                    </p>
                </div>
                <div class="col-md-6" data-aos="fade-up-left" data-aos-duration="2000">
                    <img class="foto" src="img/people 1.jpg" id="fotoTentang">
                </div>
        </div>
    </section>
    <!-- Cara Donasi -->
    <section id="how-to-donate" class="py-5" data-aos="fade-up" data-aos-duration="2000">
        <div class="container" id="con-cara" >
            <div class="text-center mb-5">
                <h2 id="judulCara">Cara Berdonasi</h2>
                <p id='ket-cara'>Ikuti langkah mudah berikut untuk memberikan donasi.</p>
            </div>

            <div class="row text-center">
                <!-- Step 1 -->
                <div class="col-md-4 mb-4" data-aos="zoom-in" data-aos-duration="3000" data-aos-delay="200">
                    <div class="p-4" id="con-isi-cara" >
                        <h3 id="judulCara" >1. Pilih Program</h3>
                        <p id="ket-cara">Pilih program donasi yang ingin Anda dukung.</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="col-md-4 mb-4" data-aos="zoom-in" data-aos-duration="3000" data-aos-delay="500">
                    <div class="p-4" id="con-isi-cara">
                        <h3 id="judulCara">2. Transfer Donasi</h3>
                        <p id="ket-cara">Lakukan transfer donasi melalui rekening yang tersedia.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="col-md-4 mb-4" data-aos="zoom-in" data-aos-duration="3000" data-aos-delay="800">
                    <div class="p-4" id="con-isi-cara">
                        <h3 id="judulCara">3. Konfirmasi</h3>
                        <p id="ket-cara">Konfirmasi donasi Anda kepada kami melalui kontak yang tersedia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- campaign -->
    <section id="campaign-donations" class="py-5" data-aos="zoom-in-up" data-aos-duration="3000">
    <div class="container" id="camp-con-luar">
        <h2 class="text-center" id="judul-cmp">Campaign Donasi Kami</h2>
        <p class="text-center lead" id="ket-cmp">
            Lihat dan dukung berbagai campaign donasi yang sedang berlangsung.
        </p>

        <div id="campaignCarousel" class="carousel slide" data-bs-ride="carousel" >
            <!-- Tambahkan navigasi kiri dan kanan -->
            <button class="carousel-control-prev" type="button" data-bs-target="#campaignCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#campaignCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

            <div class="carousel-inner">
                <?php
                $chunks = array_chunk($kegiatan, 3); // Membagi array menjadi kelompok 3
                foreach ($chunks as $index => $chunk): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="row justify-content-center">
                            <?php foreach ($chunk as $item): ?>
                                <div class="col-md-4 col-sm-12 mb-4">
                                    <div class="card p-3" id="card-cmp">
                                        <img src="uploads/pamflet/<?= htmlspecialchars($item['foto_tmp']) ?>" class="card-img-top mb-3" alt="<?= htmlspecialchars($item['judul_tk']) ?>" style="object-fit: cover; height: 200px;">
                                        <h5 id="judul-cmp"><?= htmlspecialchars($item['judul_tk']) ?></h5>
                                        <p id="ket-cmp"><?= htmlspecialchars($item['deks_tk']) ?></p>
                                        <div class="progress mb-2" style="height: 25px; border-radius: 10px;">
                                            <?php
                                            $percentage = ($item['jumlah_terkumpul_tk'] / $item['target_tk']) * 100;
                                            ?>
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                style="width: <?= min($percentage, 100) ?>%;" 
                                                aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?= round($percentage, 2) ?>%
                                            </div>
                                        </div>
                                        <p class="mb-2" id="target-cmp">
                                            Terkumpul : Rp<?= number_format($item['jumlah_terkumpul_tk'], 0, ',', '.') ?> <br>
                                            Target : Rp<?= number_format($item['target_tk'], 0, ',', '.') ?> <br>
                                            Status : 
                                            <span class="<?= ($item['status_tk'] == 'open') ? 'text-success' : 'text-danger'; ?>">
                                                <?= ($item['status_tk'] == 'open') ? 'Terbuka' : 'Tertutup'; ?>
                                            </span>
                                        </p>
                                        <p>
                                            <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#donasiModal" data-kegiatan-title="<?= htmlspecialchars($item['judul_tk']) ?>" data-kegiatan-id="<?= htmlspecialchars($item['id_tk']) ?>" data-status="<?= htmlspecialchars($item['status_tk']) ?>" style="width: 100%;">Mulai Berdonasi</a>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

    <!-- Modal Donasi -->
    <div class="modal fade" id="donasiModal" tabindex="-1" aria-labelledby="donasiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="donasiModalLabel">Form Donasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="proses.php" method="POST" id="dataForm">
                        <!-- Input Nama Lengkap -->
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" id="nama" required>
                        </div>
                        <!-- Input Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" id="email" required>
                        </div>
                        <!-- Input Nominal -->
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal</label>
                            <input type="text" name="nominal" id="nominal" class="form-control" required>
                        </div>
                        <!-- Input Kegiatan -->
                        <div class="mb-3">
                            <label for="kegiatan" class="form-label">Kegiatan</label>
                            <input type="text" name="judul_tk" class="form-control" id="kegiatan" readonly>
                            <input type="hidden" name="id_tk" id="id_tk">
                        </div>
                        <!-- Tombol Submit -->
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- alamat -->
    <section id="Mulai-Donasi" class="py-5"  data-aos="fade-up" data-aos-easing="linear" data-aos-duration="1000">
        <div class="container" id="con-luar-kontak" >
            <div class="map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.5428783954503!2d107.29873607355587!3d-6.323610161874812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69762d4c316603%3A0x50a8005dfd52a897!2sBuana%20Perjuangan%20University!5e0!3m2!1sen!2sid!4v1734587398212!5m2!1sen!2sid"></iframe>
                <h5 class="card-title">
                    Alamat <br>
                    <p class="card-text">
                        Jl. Ronggo Waluyo Karawang-41361 <br> 
                    </p>
                </h5>    
                <p class="card-text">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="navy" class="bi bi-telephone" viewBox="0 0 16 16">
                        <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
                    </svg> :
                    <a href="tel:+621234567" style="text-decoration: none; color: #555;">(021) 123-4567</a> <br> 
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="navy" class="bi bi-envelope-fill" viewBox="0 0 16 16">
                        <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                    </svg> :
                    <a href="mailto:donasi@organisasi.com" style="text-decoration: none; color: #555;">donasi@organisasi.com</a> <br>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="navy" class="bi bi-instagram" viewBox="0 0 16 16">
                        <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334"/>
                    </svg> :
                    <a href="mailto:donasi@organisasi.com" style="text-decoration: none; color: #555;">@himatifubp</a>
                </p>
    
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class=" text-center py-3">
            <p>&copy; 2024 Himatif Care. All Rights Reserved.</p>
    </footer>
    <script src="style/script.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>