<?php
session_start();
include "Service/database.php";

if (!isset($_SESSION['id_customer'])) {
    header("Location: LoginCustomer.php");
    exit;
}

if (!isset($_GET['id_booking'])) {
    echo "ID Booking tidak ditemukan!";
    exit;
}

$id_booking = (int)$_GET['id_booking'];

// Ambil data booking + deadline_bayar
$query = "SELECT b.id_booking, b.deadline_bayar, b.status, v.Nama_villa, v.harga
          FROM booking b
          JOIN Villa v ON b.id_villa = v.id_villa
          WHERE b.id_booking = $id_booking";
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    die("Booking tidak ditemukan.");
}

// Jika deadline_bayar NULL, isi dengan 24 jam dari sekarang
if (!$booking['deadline_bayar']) {
    $default_deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
    mysqli_query($conn, "UPDATE booking SET deadline_bayar = '$default_deadline' WHERE id_booking = $id_booking");
    $booking['deadline_bayar'] = $default_deadline;
}

// Cek apakah sudah expired
$now = new DateTime();
$deadline = new DateTime($booking['deadline_bayar']);
$expired = ($now > $deadline);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Metode Pembayaran</title>
<link rel="stylesheet" href="Booking.css">

<style>
    body {
        background: linear-gradient(to right, #4ea5ff, #6bd5ff);
        font-family: Arial, sans-serif;
    }
    .payment-box {
        width: 60%;
        margin: 50px auto;
        background: white;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
    }
    .method-item {
        background: #f1f9ff;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 12px;
        font-size: 17px;
        border-left: 6px solid #4ea5ff;
    }
    .note {
        background: #e8f9ff;
        padding: 12px;
        border-radius: 10px;
        margin-top: 20px;
        font-size: 15px;
        border-left: 4px solid #ff5e7e;
        text-align: center;
    }
    .countdown-box {
        background: #fff3cd;
        padding: 12px;
        border-radius: 10px;
        margin-top: 10px;
        font-size: 16px;
        text-align: center;
        color: #856404;
        border-left: 4px solid #ffc107;
    }
    .expired {
        background: #f8d7da !important;
        color: #721c24 !important;
        border-left: 4px solid #f5c6cb !important;
    }
    .back-btn {
        display: block;
        width: 200px;
        text-align: center;
        margin: 30px auto;
        background: #4ea5ff;
        padding: 12px;
        border-radius: 10px;
        color: white;
        font-weight: bold;
        text-decoration: none;
    }
    .next-btn {
        display: block;
        width: 220px;
        text-align: center;
        margin: 15px auto 0;
        background: #30c96b;
        padding: 12px;
        border-radius: 10px;
        color: white;
        font-weight: bold;
        text-decoration: none;
    }
</style>

</head>
<body>

<div class="payment-box">
    <h2>Metode Pembayaran</h2>

    <div class="method-item"><b>Transfer Bank:</b> BCA 123456789 AdfaVillas</div>
    <div class="method-item"><b>OVO:</b> 0812-3456-7890</div>
    <div class="method-item"><b>GoPay:</b> 0812-3456-7890</div>
    <div class="method-item"><b>Dana:</b> 0812-3456-7890</div>

    <div class="note">
        📌 Setelah pembayaran, upload bukti transfer pada form selanjutnya.
    </div>

    <?php if (!$expired): ?>
        <div class="countdown-box" id="countdown">
            Sisa waktu pembayaran: <span id="timer">Loading...</span>
        </div>
    <?php else: ?>
        <div class="countdown-box expired">
            ⏰ Waktu pembayaran telah HABIS!
        </div>
    <?php endif; ?>

    <?php if (!$expired): ?>
        <a class="next-btn" href="Pembayaran.php?id_booking=<?= $id_booking ?>">
            Lanjut ke Upload Pembayaran →
        </a>
    <?php endif; ?>

    <a class="back-btn" href="Booking.php">← Kembali</a>
</div>

<script>
<?php if (!$expired): ?>
const deadlineStr = '<?= $booking['deadline_bayar'] ?>';
if (deadlineStr) {
    const deadline = new Date(deadlineStr).getTime();

    const countdown = () => {
        const now = new Date().getTime();
        const distance = deadline - now;

        if (distance < 0) {
            document.getElementById('countdown').innerHTML = '⏰ Waktu habis!';
            document.getElementById('countdown').classList.add('expired');
            return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById('timer').innerHTML = `${hours} jam ${minutes} menit ${seconds} detik`;
    };

    countdown();
    setInterval(countdown, 1000);
}
<?php endif; ?>
</script>

</body>
</html>