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
$id_customer = $_SESSION['id_customer'];

// Cek apakah booking milik customer yang login
$query_check = "SELECT b.*, v.nama_villa, v.harga, v.lokasi_spesifik 
                FROM Booking b
                JOIN Villa v ON b.id_villa = v.id_villa
                WHERE b.id_booking = '$id_booking' 
                AND b.id_customer = '$id_customer'";
$result_check = mysqli_query($conn, $query_check);
$booking = mysqli_fetch_assoc($result_check);

if (!$booking) {
    echo "<script>alert('Booking tidak ditemukan atau bukan milik Anda!'); window.location='index.php';</script>";
    exit;
}

// Ambil data pembayaran terbaru
$query_pembayaran = "SELECT * FROM Pembayaran 
                    WHERE id_booking = '$id_booking' 
                    ORDER BY id_pembayaran DESC LIMIT 1";
$result_pembayaran = mysqli_query($conn, $query_pembayaran);
$pembayaran = mysqli_fetch_assoc($result_pembayaran);

// Cek jika admin sudah konfirmasi
$status_pembayaran = isset($pembayaran['status']) ? $pembayaran['status'] : 'Belum Bayar';
$status_booking = $booking['status'];

// Jika sudah dikonfirmasi admin, tampilkan alert dan redirect ke halaman detail
if ($status_pembayaran == 'Berhasil' || $status_booking == 'Lunas') {
    echo "<script>
            alert('🎉 SELAMAT! Pembayaran Anda telah dikonfirmasi admin.\\n\\nStatus: Pembayaran Berhasil\\nBooking Anda sekarang aktif.');
            window.location.href = 'bukti_booking.php?id_booking=$id_booking';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Menunggu Konfirmasi Admin</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Arial', sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    
    .container {
      max-width: 800px;
      width: 100%;
    }
    
    .card {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #4CAF50, #2196F3);
    }
    
    .waiting-icon {
      font-size: 80px;
      color: #FF9800;
      margin-bottom: 20px;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    
    h1 {
      color: #333;
      margin-bottom: 20px;
      font-size: 28px;
    }
    
    .status-box {
      background: #FFF3CD;
      border: 2px solid #FFC107;
      border-radius: 10px;
      padding: 20px;
      margin: 20px 0;
      text-align: left;
    }
    
    .status-title {
      color: #856404;
      font-size: 18px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .status-title i {
      font-size: 24px;
    }
    
    .booking-info {
      background: #E8F5E9;
      border: 1px solid #C8E6C9;
      border-radius: 10px;
      padding: 15px;
      margin: 20px 0;
      text-align: left;
    }
    
    .info-item {
      display: flex;
      justify-content: space-between;
      margin: 8px 0;
      padding: 8px 0;
      border-bottom: 1px dashed #ddd;
    }
    
    .info-item:last-child {
      border-bottom: none;
    }
    
    .info-label {
      font-weight: bold;
      color: #555;
    }
    
    .info-value {
      color: #333;
    }
    
    .buttons {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      justify-content: center;
    }
    
    .btn {
      padding: 12px 30px;
      border: none;
      border-radius: 50px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .note {
      margin-top: 25px;
      color: #666;
      font-size: 14px;
      line-height: 1.6;
      background: #F8F9FA;
      padding: 15px;
      border-radius: 8px;
      border-left: 4px solid #2196F3;
    }
    
    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 20px 0;
    }
    
    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-right: 15px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .timer {
      font-size: 14px;
      color: #666;
      margin-top: 10px;
    }
    
    .time-estimate {
      background: #E3F2FD;
      padding: 10px;
      border-radius: 8px;
      margin: 10px 0;
      font-size: 14px;
      color: #1565C0;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="waiting-icon">
        ⏳
      </div>
      
      <h1>PEMBAYARAN SEDANG DIKONFIRMASI</h1>
      
      <div class="status-box">
        <div class="status-title">
          <span>🔄 STATUS PEMBAYARAN</span>
        </div>
        <div style="font-size: 20px; color: #E65100; font-weight: bold; text-align: center; padding: 10px;">
          MENUNGGU KONFIRMASI ADMIN
        </div>
        <p style="color: #666; margin-top: 10px;">
          Pembayaran Anda telah berhasil dikirim. Tim admin akan memverifikasi bukti transfer Anda dalam waktu 1x24 jam.
        </p>
      </div>
      
      <div class="booking-info">
        <h3 style="color: #2E7D32; margin-bottom: 15px; border-bottom: 2px solid #4CAF50; padding-bottom: 5px;">
          📋 DETAIL BOOKING
        </h3>
        
        <div class="info-item">
          <span class="info-label">ID Booking:</span>
          <span class="info-value"><?= $booking['id_booking'] ?></span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Villa:</span>
          <span class="info-value"><?= htmlspecialchars($booking['nama_villa']) ?></span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Lokasi:</span>
          <span class="info-value"><?= htmlspecialchars($booking['lokasi_spesifik']) ?></span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Check-in:</span>
          <span class="info-value"><?= date('d/m/Y', strtotime($booking['tanggal_checkin'])) ?></span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Check-out:</span>
          <span class="info-value"><?= date('d/m/Y', strtotime($booking['tanggal_checkout'])) ?></span>
        </div>
        
        <?php if (isset($pembayaran['Payment'])): ?>
        <div class="info-item">
          <span class="info-label">Metode Bayar:</span>
          <span class="info-value"><?= $pembayaran['Payment'] ?></span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Jumlah:</span>
          <span class="info-value" style="color: #4CAF50; font-weight: bold;">
            Rp <?= number_format($pembayaran['jumlah'], 0, ',', '.') ?>
          </span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Tanggal Bayar:</span>
          <span class="info-value"><?= date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])) ?></span>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="time-estimate">
        ⏱️ Estimasi waktu konfirmasi: <strong>1x24 jam</strong> (hari kerja)
      </div>
      
      <div class="loading">
        <div class="spinner"></div>
        <span>Memantau status konfirmasi...</span>
      </div>
      
      <div class="timer">
        Terakhir diperbarui: <span id="currentTime"><?= date('H:i:s') ?></span>
      </div>
      
      <div class="buttons">
        <a href="bukti_booking.php?id_booking=<?= $id_booking ?>" class="btn btn-primary">
          🔍 Lihat Detail Pemesanan
        </a>
        <a href="index.php" class="btn btn-secondary">
          🏠 Kembali ke Beranda
        </a>
      </div>
      
      <div class="note">
        <strong>📌 PENTING:</strong>
        <ul style="margin: 10px 0 0 20px;">
          <li>Status akan berubah otomatis setelah admin mengkonfirmasi</li>
          <li>Anda akan menerima notifikasi ketika pembayaran telah dikonfirmasi</li>
          <li>Jika dalam 24 jam belum dikonfirmasi, hubungi customer service</li>
          <li>Pastikan bukti transfer yang Anda upload jelas dan terbaca</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    // Auto refresh status setiap 30 detik
    function refreshStatus() {
      fetch(`check_payment_status.php?id_booking=<?= $id_booking ?>`)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'Berhasil' || data.booking_status === 'Lunas') {
            alert('🎉 SELAMAT! Pembayaran Anda telah dikonfirmasi admin.\n\nStatus: Pembayaran Berhasil\nBooking Anda sekarang aktif.');
            window.location.href = `MenungguKonfirmasi.php?id_booking=<?= $id_booking ?>`;
          }
        })
        .catch(error => console.error('Error:', error));
      
      // Update waktu
      const now = new Date();
      document.getElementById('currentTime').textContent = 
        now.getHours().toString().padStart(2, '0') + ':' +
        now.getMinutes().toString().padStart(2, '0') + ':' +
        now.getSeconds().toString().padStart(2, '0');
    }
    
    // Refresh setiap 30 detik
    setInterval(refreshStatus, 30000);
    
    // Update waktu real-time
    setInterval(() => {
      const now = new Date();
      document.getElementById('currentTime').textContent = 
        now.getHours().toString().padStart(2, '0') + ':' +
        now.getMinutes().toString().padStart(2, '0') + ':' +
        now.getSeconds().toString().padStart(2, '0');
    }, 1000);
    
    // Refresh status saat halaman load
    window.onload = refreshStatus;
  </script>
</body>
</html>