<?php
// Import namespace Midtrans
namespace Midtrans;

require_once dirname(__FILE__) . '/../../Midtrans.php'; // Pastikan path ini benar

// Konfigurasi Midtrans
Config::$serverKey = 'SB-Mid-server-5QG9o1uGq4o-SH1MqaOkYY13'; // Pastikan ini kunci yang benar
Config::$isProduction = false; // Ubah ke true untuk produksi
Config::$clientKey = 'SB-Mid-client-IKMtvZ_Ay5357iJZ'; // Pastikan ini kunci yang benar

Config::$isSanitized = true;
Config::$is3ds = true;

// Mengambil data donasi dari datacekout.php
$data = include "datacekout.php";

// Cek jika id_ttd ada di URL
if (!isset($_GET['id_ttd']) || empty($_GET['id_ttd'])) {
    die("Donasi ID tidak ditemukan. ID tidak ada di URL.");
} else {
    $id_ttd = $_GET['id_ttd'];  // Ambil id_ttd dari URL
    $nominal = $_GET['nominal']; // Ambil nominal dari URL
}

// Data donasi
$nama_donatur = $data['nama_donatur'];
$email_donatur = $data['email_donatur'];
$judul_kegiatan = $data['judul_tk'];  // Kegiatan yang dipilih
$status = $data['status_ttd']; // Ambil status pembayaran dari database

// Detail transaksi untuk Midtrans
$transaction_details = array(
    'order_id' => $id_ttd,
    'gross_amount' => $nominal,
);

// Detail item
$item_details = array(
    array(
        'id' => 'donasi1',
        'price' => $nominal,
        'quantity' => 1,
        'name' => "Donasi untuk $judul_kegiatan"
    ),
);

// Detail customer
$customer_details = array(
    'first_name' => $nama_donatur,
    'email' => $email_donatur,
);

// Menggabungkan semua detail ke dalam transaksi
$transaction = array(
    'transaction_details' => $transaction_details,
    'customer_details' => $customer_details,
    'item_details' => $item_details,
);

// Mendapatkan Snap Token dari Midtrans
$snap_token = '';
try {
    $snap_token = Snap::getSnapToken($transaction);
} catch (\Exception $e) {
    die("Error Midtrans: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PAYMENT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #b0e0e6; font-family: 'Lucida Sans';">
<nav class="navbar navbar-expand-lg fixed-top" style="background-color: #add8e6; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
    <div class="container-fluid">
        <a class="navbar-brand me-auto" style="color: navy; font-weight: bold;">
            <img src="../../../img/logo.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px;">
            Himatif Care
        </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel" style="background-color: #add8e6;">
            <div class="offcanvas-header" style="background-color: #add8e6; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
                <img src="../../../img/logo.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px;">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel" style="color: navy;">Himatif Care</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="btn" href="../../../dashboard-utama.php" style="color:navy;">Dashboard</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<br>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header text-center">
                <h4>Pembayaran Donasi</h4>
            </div>
            <div class="card-body">
                <p>Terima kasih telah mendukung kegiatan <b><?= htmlspecialchars($judul_kegiatan); ?></b>.</p>
                <p>Silakan selesaikan pembayaran sebesar <b>Rp <?= number_format($nominal, 0, ',', '.'); ?></b>.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button id="pay-button" class="btn btn-primary">Bayar Sekarang</button>
                    <a href="../../../generate_pdf.php?id_ttd=<?= $id_ttd; ?>" target="_blank" class="btn btn-success" id="mutation-button" style="display:none;">Unduh Mutasi (PDF)</a>
                </div>
                <p id="payment-feedback" class="mt-3 text-center"></p>
            </div>
        </div>
    </div>

    <!-- Script untuk Snap Midtrans -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?= \Midtrans\Config::$clientKey; ?>"></script>
    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        var feedbackElement = document.getElementById('payment-feedback');
        var mutationButton = document.getElementById('mutation-button');  // Get mutation button

        payButton.addEventListener('click', function () {
            // Indikator proses pembayaran
            payButton.innerText = 'Processing...';
            payButton.disabled = true;

            snap.pay('<?php echo $snap_token; ?>', {
                onSuccess: function(result){
                    console.log(result);
                    feedbackElement.innerText = "Pembayaran berhasil!";
                    alert("Pembayaran berhasil!");

                    // Show "Mutasi Donasi" button when payment is successful
                    mutationButton.style.display = 'inline-block';  // Show the button
                },
                onPending: function(result){
                    console.log(result);
                    feedbackElement.innerText = "Pembayaran dalam proses, lakukan pembayaran ulang.";
                    alert("Pembayaran dalam proses, lakukan pembayaran ulang. .");
                },
                onError: function(result){
                    console.log(result);
                    feedbackElement.innerText = "Pembayaran gagal.";
                    alert("Pembayaran gagal.");
                    payButton.innerText = 'Bayar Sekarang';
                    payButton.disabled = false;
                },
                onClose: function(){
                    feedbackElement.innerText = "Anda menutup popup tanpa menyelesaikan pembayaran.";
                    alert("Anda menutup popup tanpa menyelesaikan pembayaran.");
                    payButton.innerText = 'Bayar Sekarang';
                    payButton.disabled = false;
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
