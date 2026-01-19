<?php
session_start();
include "Service/database.php";

if (!isset($_SESSION['id_customer'])) {
    header("Location: LoginCustomer.php");
    exit;
}

if (!isset($_GET['id_booking'])) {
    echo "<script>alert('ID Booking tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$id_booking = mysqli_real_escape_string($conn, $_GET['id_booking']);

// Fungsi untuk menentukan apakah suatu tanggal adalah weekend
function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    return ($dayOfWeek >= 5); // 5 = Jumat, 6 = Sabtu, 7 = Minggu
}

// Fungsi untuk menghitung total harga berdasarkan tanggal checkin dan checkout
function calculateTotalPrice($basePrice, $checkin, $checkout) {
    $start = new DateTime($checkin);
    $end = new DateTime($checkout);
    $current = clone $start;
    
    $weekdayCount = 0;
    $weekendCount = 0;
    
    while ($current < $end) {
        if (isWeekend($current->format('Y-m-d'))) {
            $weekendCount++;
        } else {
            $weekdayCount++;
        }
        $current->modify('+1 day');
    }
    
    $weekendPrice = $basePrice * 1.2; // +20% untuk weekend
    $totalPrice = ($weekdayCount * $basePrice) + ($weekendCount * $weekendPrice);
    
    return [
        'total' => $totalPrice,
        'weekday_count' => $weekdayCount,
        'weekend_count' => $weekendCount,
        'weekday_price' => $basePrice,
        'weekend_price' => $weekendPrice,
        'nights' => $weekdayCount + $weekendCount
    ];
}

$query = "SELECT b.id_booking, b.tanggal_checkin, b.tanggal_checkout, b.jumlah_tamu, b.status,
                 v.nama_villa, v.harga, v.lokasi_spesifik
          FROM Booking b
          JOIN Villa v ON b.id_villa = v.id_villa
          WHERE b.id_booking='$id_booking'";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    echo "<script>alert('Data booking tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Hitung total harga berdasarkan tanggal booking
$priceDetails = calculateTotalPrice($booking['harga'], $booking['tanggal_checkin'], $booking['tanggal_checkout']);
$totalPrice = $priceDetails['total'];

// Proses ketika form disubmit
if (isset($_POST['submit'])) {
    $Payment = mysqli_real_escape_string($conn, $_POST['Payment']);
    $jumlah = mysqli_real_escape_string($conn, $_POST['jumlah']);
    $status_pembayaran = "Menunggu Konfirmasi Admin";

    // Validasi jumlah pembayaran
    if (abs($jumlah - $totalPrice) > 1000) { // toleransi Rp 1.000
        echo "<script>
                alert('Jumlah pembayaran tidak sesuai dengan total harga (Rp " . number_format($totalPrice, 0, ',', '.') . ")!');
                window.history.back();
              </script>";
        exit;
    }

    $bukti = null;
    if ($_FILES['bukti']['name'] != "") {
        // path upload disesuaikan
        $targetDir = "uploads/";

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // cek ekstensi aman
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            echo "<script>
                    alert('Format file tidak diperbolehkan! Hanya JPG, JPEG, PNG, GIF.');
                    window.history.back();
                  </script>";
            exit;
        }

        // Validasi ukuran file (max 2MB)
        if ($_FILES['bukti']['size'] > 2097152) {
            echo "<script>
                    alert('Ukuran file terlalu besar! Maksimal 2MB.');
                    window.history.back();
                  </script>";
            exit;
        }

        $bukti = time() . "_" . basename($_FILES['bukti']['name']);
        $targetFile = $targetDir . $bukti;
        
        if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $targetFile)) {
            echo "<script>
                    alert('Gagal mengupload file!');
                    window.history.back();
                  </script>";
            exit;
        }
    }

    // 1. Insert data pembayaran ke tabel Pembayaran
    $sql_pembayaran = "INSERT INTO Pembayaran (id_booking, Payment, jumlah, bukti, status, tanggal_bayar)
                       VALUES ('$id_booking', '$Payment', '$jumlah', '$bukti', '$status_pembayaran', NOW())";
    
    if (mysqli_query($conn, $sql_pembayaran)) {
        // 2. Update status booking menjadi "Menunggu Konfirmasi"
        $sql_update_booking = "UPDATE Booking SET status = 'Menunggu Konfirmasi' 
                              WHERE id_booking = '$id_booking'";
        mysqli_query($conn, $sql_update_booking);
        
        // Tampilkan alert JavaScript dan redirect ke halaman menunggu konfirmasi
        echo "<script>
                if(confirm('Pembayaran berhasil dikirim!\\n\\nStatus: Tunggu dikonfirmasi admin\\n\\nKlik OK untuk melihat detail pemesanan.')) {
                    window.location.href = 'MenungguKonfirmasi.php?id_booking=$id_booking';
                } else {
                    window.location.href = 'MenungguKonfirmasi.php?id_booking=$id_booking';
                }
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Gagal menyimpan pembayaran: " . addslashes(mysqli_error($conn)) . "');
                window.history.back();
              </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pembayaran</title>
  <link rel="stylesheet" href="Booking.css">
  <style>
    .form-box {
      max-width: 600px;
      margin: 20px auto;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: #f9f9f9;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .price-details {
      background-color: #e8f5e9;
      padding: 15px;
      border-radius: 5px;
      margin: 15px 0;
      border: 1px solid #c8e6c9;
    }
    
    .price-details h4 {
      margin-top: 0;
      color: #2e7d32;
      border-bottom: 2px solid #4caf50;
      padding-bottom: 5px;
    }
    
    .price-breakdown {
      background-color: #f1f8e9;
      padding: 10px;
      border-radius: 4px;
      margin: 10px 0;
      font-size: 14px;
    }
    
    .weekday-price {
      color: #1565c0;
      font-weight: bold;
    }
    
    .weekend-price {
      color: #ff8f00;
      font-weight: bold;
    }
    
    .total-price {
      font-weight: bold;
      font-size: 18px;
      color: #d32f2f;
      background-color: #ffebee;
      padding: 10px;
      border-radius: 5px;
      text-align: center;
    }
    
    .note {
      font-size: 12px;
      color: #666;
      font-style: italic;
      margin-top: 10px;
      background-color: #fff3cd;
      padding: 8px;
      border-radius: 4px;
      border: 1px solid #ffeaa7;
    }
    
    .required-field::after {
      content: " *";
      color: #f44336;
    }
    
    .payment-info {
      background-color: #e3f2fd;
      padding: 15px;
      border-radius: 5px;
      margin: 15px 0;
      border: 1px solid #bbdefb;
    }
    
    .warning-box {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 5px;
      padding: 10px;
      margin: 10px 0;
      font-size: 14px;
      color: #856404;
    }
    
    .btn-submit {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      width: 100%;
      transition: all 0.3s ease;
      margin-top: 20px;
    }
    
    .btn-submit:hover {
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body>
  <div class="form-box">
    <h2 style="color: #4CAF50; text-align: center; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">Pembayaran Villa</h2>

    <div class="payment-info">
      <p style="font-size: 16px; color: #2196F3; margin-top: 0;"><strong>📋 INFORMASI BOOKING</strong></p>
      <p><b>Villa:</b> <?= htmlspecialchars($booking['nama_villa']) ?></p>
      <p><b>Lokasi:</b> <?= htmlspecialchars($booking['lokasi_spesifik']) ?></p>
      <p><b>Check-in:</b> <?= date('d/m/Y', strtotime($booking['tanggal_checkin'])) ?></p>
      <p><b>Check-out:</b> <?= date('d/m/Y', strtotime($booking['tanggal_checkout'])) ?></p>
      <p><b>Jumlah Tamu:</b> <?= $booking['jumlah_tamu'] ?> orang</p>
      <p><b>Durasi:</b> <?= $priceDetails['nights'] ?> malam</p>
    </div>
    
    <div class="price-details">
      <h4>Detail Harga</h4>
      
      <div class="price-breakdown">
        <p>
          <span class="weekday-price">Weekday (Senin-Kamis):</span><br>
          • Harga: Rp <?= number_format($priceDetails['weekday_price'], 0, ',', '.') ?>/malam<br>
          • Jumlah: <?= $priceDetails['weekday_count'] ?> malam<br>
          • Subtotal: Rp <?= number_format($priceDetails['weekday_count'] * $priceDetails['weekday_price'], 0, ',', '.') ?>
        </p>
        
        <p>
          <span class="weekend-price">Weekend (Jumat-Minggu):</span><br>
          • Harga: Rp <?= number_format($priceDetails['weekend_price'], 0, ',', '.') ?>/malam (+20%)<br>
          • Jumlah: <?= $priceDetails['weekend_count'] ?> malam<br>
          • Subtotal: Rp <?= number_format($priceDetails['weekend_count'] * $priceDetails['weekend_price'], 0, ',', '.') ?>
        </p>
        
        <hr style="border: 1px dashed #ccc; margin: 15px 0;">
        
        <p class="total-price">
          Total Pembayaran: Rp <?= number_format($totalPrice, 0, ',', '.') ?>
        </p>
      </div>
      
      <p class="note">
        Catatan: Harga weekend (Jumat-Minggu) dikenakan kenaikan 20% dari harga weekday.
      </p>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validatePayment()">
      <div class="warning-box">
        <strong>⚠ PERHATIAN:</strong> Setelah mengirim pembayaran, mohon tunggu konfirmasi admin. Status akan berubah otomatis setelah admin mengkonfirmasi.
      </div>

      <label class="required-field"><strong>Metode Pembayaran</strong></label>
      <select name="Payment" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
          <option value="">-- Pilih Metode Pembayaran --</option>
          <option value="Transfer Bank">Transfer Bank (BCA 123456789 - AN: Villa Management)</option>
          <option value="OVO">OVO (0812-3456-7890 - AN: Villa Management)</option>
          <option value="GoPay">GoPay (0812-3456-7890 - AN: Villa Management)</option>
          <option value="Dana">Dana (0812-3456-7890 - AN: Villa Management)</option>
      </select>

      <label class="required-field"><strong>Jumlah Transfer</strong></label>
      <input type="number" name="jumlah" id="jumlah" 
             value="<?= $totalPrice ?>" 
             readonly 
             style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px; background-color: #f5f5f5; cursor: not-allowed;">
      <small style="color: #666; display: block; margin-bottom: 15px;">Jumlah telah dihitung otomatis berdasarkan tanggal booking</small>

      <label class="required-field"><strong>Upload Bukti Transfer</strong></label>
      <input type="file" name="bukti" id="bukti" accept="image/*" required 
             style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;">
      <small style="color: #666; display: block; margin-bottom: 15px;">Format: JPG, JPEG, PNG, GIF (maks. 2MB)</small>

      <button type="submit" name="submit" class="btn-submit">Kirim Pembayaran</button>
    </form>
  </div>

  <script>
    // Validasi form sebelum submit
    function validatePayment() {
      const jumlah = document.getElementById('jumlah').value;
      const totalPrice = <?= $totalPrice ?>;
      const bukti = document.getElementById('bukti').files[0];
      const metode = document.querySelector('select[name="Payment"]').value;
      
      // Validasi metode pembayaran
      if (!metode) {
        alert('Silakan pilih metode pembayaran!');
        return false;
      }
      
      // Validasi jumlah
      if (Math.abs(jumlah - totalPrice) > 1000) {
        alert('Jumlah pembayaran tidak sesuai! Silakan refresh halaman.');
        return false;
      }
      
      // Validasi file
      if (!bukti) {
        alert('Silakan upload bukti transfer!');
        return false;
      }
      
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(bukti.type)) {
        alert('Format file tidak didukung! Hanya gambar JPG, PNG, atau GIF.');
        return false;
      }
      
      if (bukti.size > 2097152) { // 2MB
        alert('Ukuran file terlalu besar! Maksimal 2MB.');
        return false;
      }
      
      // Konfirmasi
      return confirm('Apakah Anda yakin dengan data pembayaran ini?\n\nSetelah mengirim, pembayaran akan menunggu konfirmasi admin.');
    }
  </script>
</body>
</html>