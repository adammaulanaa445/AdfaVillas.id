<?php
session_start();
include "../Service/database.php";

// Cek admin login
if (!isset($_SESSION['admin'])) {
    echo "<script>alert('Harus login admin dulu!'); window.location='LoginAdmin.php';</script>";
    exit;
}

// ===== AKSI KONFIRMASI ADMIN =====
if (isset($_GET['konfirmasi'])) {
    $id_pembayaran = $_GET['konfirmasi'];
    
    // 1. Update status pembayaran jadi 'Berhasil'
    mysqli_query($conn, "UPDATE Pembayaran SET status='Berhasil' WHERE id_pembayaran='$id_pembayaran'");
    
    // 2. Ambil id_booking dari pembayaran ini
    $q = mysqli_query($conn, "SELECT id_booking FROM Pembayaran WHERE id_pembayaran='$id_pembayaran'");
    $data = mysqli_fetch_assoc($q);
    $id_booking = $data['id_booking'];
    
    // 3. Update status booking jadi 'Lunas' - HAPUS bagian tanggal_konfirmasi
    mysqli_query($conn, "UPDATE Booking SET status='Lunas' WHERE id_booking='$id_booking'");
    
    // 4. TAMBAHKAN ALERT JS YANG LEBIH INFORMATIF
    ?>
    <script>
        alert('✅ Pembayaran berhasil dikonfirmasi!\n\nBooking ID: <?php echo $id_booking; ?>\nStatus: LUNAS\n\nCustomer akan melihat status pembayaran sebagai "Telah dikonfirmasi admin".');
        window.location='kelolaPembayaran.php';
    </script>
    <?php
    exit;
}

// ===== TAMPILKAN DATA =====
$query = "SELECT p.*, b.tanggal_checkin, b.tanggal_checkout, 
                 v.nama_villa, c.nama as nama_customer
          FROM Pembayaran p
          JOIN Booking b ON p.id_booking = b.id_booking
          JOIN Villa v ON b.id_villa = v.id_villa
          JOIN Customer c ON b.id_customer = c.id_customer
          ORDER BY p.id_pembayaran DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin - Konfirmasi Pembayaran</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
    
    body {
      background: #f0f2f5;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    header {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    
    h1 {
      color: #333;
      margin-bottom: 10px;
    }
    
    .info-box {
      background: #e8f4fd;
      padding: 15px;
      border-radius: 8px;
      margin: 10px 0;
      border-left: 5px solid #2196F3;
    }
    
    table {
      width: 100%;
      background: white;
      border-collapse: collapse;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    th {
      background: #4CAF50;
      color: white;
      font-weight: bold;
    }
    
    tr:hover {
      background: #f9f9f9;
    }
    
    .status {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
    }
    
    .status-menunggu {
      background: #fff3cd;
      color: #856404;
    }
    
    .status-lunas {
      background: #d4edda;
      color: #155724;
    }
    
    .status-gagal {
      background: #f8d7da;
      color: #721c24;
    }
    
    .btn {
      padding: 8px 15px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      margin: 3px;
    }
    
    .btn-konfirmasi {
      background: #4CAF50;
      color: white;
    }
    
    .btn-tolak {
      background: #f44336;
      color: white;
    }
    
    .btn-hapus {
      background: #ff9800;
      color: white;
    }
    
    .btn-kembali {
      background: #6c757d;
      color: white;
      padding: 10px 20px;
      margin-top: 20px;
      display: inline-block;
    }
    
    .btn:hover {
      opacity: 0.9;
      transform: translateY(-2px);
    }
    
    .bukti-link {
      color: #2196F3;
      text-decoration: underline;
      cursor: pointer;
    }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.8);
      z-index: 1000;
    }
    
    .modal-content {
      background: white;
      margin: 5% auto;
      padding: 20px;
      width: 80%;
      max-width: 600px;
      border-radius: 10px;
      position: relative;
    }
    
    .close {
      position: absolute;
      right: 20px;
      top: 10px;
      font-size: 30px;
      cursor: pointer;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>💰 KONFIRMASI PEMBAYARAN MANUAL</h1>
      <p>Admin harus klik tombol konfirmasi untuk setiap pembayaran yang valid</p>
    </header>
    
    <div class="info-box">
      <strong>📋 CARA KERJA:</strong>
      <ol style="margin: 10px 0 0 20px;">
        <li>Klik "Lihat Bukti" untuk cek bukti transfer</li>
        <li>Jika valid, klik "Konfirmasi"</li>
        <li>Status booking otomatis berubah jadi "LUNAS"</li>
        <li>Customer bisa lihat status di halaman mereka</li>
      </ol>
    </div>
    
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Villa</th>
          <th>Tanggal</th>
          <th>Jumlah</th>
          <th>Status</th>
          <th>Bukti</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        while($row = mysqli_fetch_assoc($result)): 
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td>
            <strong><?= $row['nama_customer'] ?></strong><br>
            <small>Booking: <?= $row['id_booking'] ?></small>
          </td>
          <td><?= $row['nama_villa'] ?></td>
          <td>
            <small>Check-in: <?= $row['tanggal_checkin'] ?></small><br>
            <small>Check-out: <?= $row['tanggal_checkout'] ?></small>
          </td>
          <td style="font-weight: bold; color: #2196F3;">
            Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
          </td>
          <td>
            <?php if($row['status'] == 'Berhasil'): ?>
              <span class="status status-lunas">✔ LUNAS</span>
            <?php elseif($row['status'] == 'Gagal'): ?>
              <span class="status status-gagal">✘ DITOLAK</span>
            <?php else: ?>
              <span class="status status-menunggu">⏳ MENUNGGU</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if(!empty($row['bukti'])): ?>
              <a href="#" onclick="lihatBukti('<?= $row['bukti'] ?>')" class="bukti-link">Lihat Bukti</a>
            <?php else: ?>
              <span style="color: #999;">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php 
            // PERBAIKAN: Tampilkan tombol konfirmasi jika status BUKAN 'Berhasil' atau 'Gagal'
            if($row['status'] != 'Berhasil' && $row['status'] != 'Gagal'): 
            ?>
              <a href="kelolaPembayaran.php?konfirmasi=<?= $row['id_pembayaran'] ?>" 
                 class="btn btn-konfirmasi"
                 onclick="return confirm('Konfirmasi pembayaran ini?\n\nCustomer: <?= $row['nama_customer'] ?>\nJumlah: Rp<?= number_format($row['jumlah'], 0, ',', '.') ?>')">
                ✅ Konfirmasi
              </a>
            <?php endif; ?>
            
            <a href="kelolaPembayaran.php?hapus=<?= $row['id_pembayaran'] ?>" 
               class="btn btn-hapus"
               onclick="return confirm('Hapus data pembayaran ini?')">
              🗑️ Hapus
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
        
        <?php if(mysqli_num_rows($result) == 0): ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: 30px; color: #666;">
            Tidak ada data pembayaran yang menunggu konfirmasi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <br>
    <a href="index.php" class="btn btn-kembali">← Kembali ke Dashboard Admin</a>
  </div>
  
  <!-- Modal untuk preview bukti -->
  <div id="modalBukti" class="modal">
    <div class="modal-content">
      <span class="close" onclick="tutupModal()">&times;</span>
      <h3>Bukti Pembayaran</h3>
      <img id="imgBukti" src="" alt="Bukti Pembayaran" style="max-width: 100%; margin-top: 15px; border: 1px solid #ddd;">
    </div>
  </div>

  <script>
    // Fungsi untuk lihat bukti
    function lihatBukti(filename) {
      document.getElementById('imgBukti').src = '../uploads/' + filename;
      document.getElementById('modalBukti').style.display = 'block';
    }
    
    // Fungsi tutup modal
    function tutupModal() {
      document.getElementById('modalBukti').style.display = 'none';
    }
    
    // Klik di luar modal untuk tutup
    window.onclick = function(event) {
      if (event.target == document.getElementById('modalBukti')) {
        tutupModal();
      }
    }
  </script>
</body>
</html>