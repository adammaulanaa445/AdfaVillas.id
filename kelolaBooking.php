<?php
include "../Service/database.php";
$query = "SELECT b.id_booking, b.tanggal_checkin, b.tanggal_checkout, b.jumlah_tamu, b.status,
                 c.nama, c.email, v.nama_villa
          FROM Booking b
          JOIN Customer c ON b.id_customer = c.id_customer
          JOIN Villa v ON b.id_villa = v.id_villa";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Booking</title>
  <link rel="stylesheet" href="JadiSatu.css">
</head>
<body>
  <h2>Daftar Booking</h2>
  <table border="1" cellpadding="8" cellspacing="0">
    <tr>
      <th>ID Booking</th>
      <th>Customer</th>
      <th>Villa</th>
      <th>Check-In</th>
      <th>Check-Out</th>
      <th>Jumlah Tamu</th>
      <th>Status</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <tr>
      <td><?= $row['id_booking'] ?></td>
      <td><?= $row['nama'] ?> (<?= $row['email'] ?>)</td>
      <td><?= $row['nama_villa'] ?></td>
      <td><?= $row['tanggal_checkin'] ?></td>
      <td><?= $row['tanggal_checkout'] ?></td>
      <td><?= $row['jumlah_tamu'] ?></td>
      <td><?= $row['status'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <p><a href="index.php">⬅ Kembali ke Dashboard</a></p>
</body>
</html>
