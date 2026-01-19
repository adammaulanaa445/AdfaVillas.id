<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "adfavillas";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();

if (!isset($_SESSION['id_customer'])) {
    die("Akses ditolak. Silakan login.");
}

$id_customer = $_SESSION['id_customer'];

$query = "
SELECT 
    c.Nama,
    c.Email,
    c.No_Hp,

    b.id_booking,
    b.tanggal_checkin,
    b.tanggal_checkout,
    b.jumlah_tamu,
    b.deadline_bayar,
    b.status as booking_status,

    p.Payment,
    p.jumlah,
    p.bukti,
    p.tanggal_bayar,
    p.status as payment_status

FROM customer c
JOIN booking b ON c.id_customer = b.id_customer
LEFT JOIN pembayaran p ON b.id_booking = p.id_booking
WHERE c.id_customer = '$id_customer'
ORDER BY b.id_booking DESC
LIMIT 1
";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data pemesanan tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pemesanan</title>
    <style>
        body {
            font-family: Arial;
            background: #f2f4f8;
        }
        .card {
            width: 650px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        h2 {
            text-align: center;
            color: green;
        }
        h3 {
            margin-top: 25px;
            border-bottom: 2px solid #eee;
        }
        .row {
            margin: 8px 0;
        }
        label {
            font-weight: bold;
        }
        img {
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .btn {
            text-align: center;
            margin-top: 30px;
        }
        .btn a {
            background: #0d6efd;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Detail Pemesanan</h2>

    <h3>Data Customer</h3>
    <div class="row"><label>Nama:</label> <?= $data['Nama']; ?></div>
    <div class="row"><label>Email:</label> <?= $data['Email']; ?></div>
    <div class="row"><label>No HP:</label> <?= $data['No_Hp']; ?></div>

    <h3>Data Booking</h3>
    <div class="row"><label>ID Booking:</label> <?= $data['id_booking']; ?></div>
    <div class="row"><label>Check-in:</label> <?= $data['tanggal_checkin']; ?></div>
    <div class="row"><label>Check-out:</label> <?= $data['tanggal_checkout']; ?></div>
    <div class="row"><label>Jumlah Tamu:</label> <?= $data['jumlah_tamu']; ?></div>
    <div class="row"><label>Status Booking:</label> 
        <?php 
        if($data['booking_status'] == 'Lunas') {
            echo '<b style="color:green;">Lunas</b>';
        } else if($data['booking_status'] == 'Menunggu Konfirmasi') {
            echo '<b style="color:orange;">Menunggu Konfirmasi</b>';
        } else {
            echo '<b style="color:red;">' . $data['booking_status'] . '</b>';
        }
        ?>
    </div>
    <div class="row"><label>Deadline Bayar:</label> <?= $data['deadline_bayar']; ?></div>

    <h3>Data Pembayaran</h3>
    <div class="row"><label>Metode Pembayaran:</label> <?= $data['Payment']; ?></div>
    <div class="row"><label>Jumlah Transfer:</label> Rp <?= number_format($data['jumlah'], 0, ',', '.'); ?></div>
    <div class="row"><label>Status Pembayaran:</label> 
        <?php 
        if($data['payment_status'] == 'Berhasil' || $data['booking_status'] == 'Lunas') {
            echo '<b style="color:green;">Telah dikonfirmasi admin</b>';
        } else if($data['payment_status'] == 'Menunggu Konfirmasi') {
            echo '<b style="color:orange;">Menunggu konfirmasi admin</b>';
        } else {
            echo '<b style="color:red;">Belum dibayar</b>';
        }
        ?>
    </div>
    <div class="row"><label>Tanggal Bayar:</label> <?= $data['tanggal_bayar']; ?></div>

    <div class="row">
        <label>Bukti Pembayaran:</label><br>
        <?php if (!empty($data['bukti'])) { ?>
            <img src="uploads/<?= $data['bukti']; ?>" width="220">
        <?php } else { ?>
            <i>Belum upload bukti pembayaran</i>
        <?php } ?>
    </div>

    <div class="btn">
        <a href="MenungguKonfirmasi.php">Kembali</a>
    </div>
</div>

</body>
</html>