<?php
session_start();
include "Service/database.php";

$success = "";
$error = "";

if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $nohp = $_POST['nohp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi
    if (empty($nama) || empty($email) || empty($nohp) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah email sudah terdaftar
        $check = "SELECT * FROM Customer WHERE Email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Hash password sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Simpan customer baru
            $insert = "INSERT INTO Customer (Nama, Email, No_Hp, password) 
                       VALUES ('$nama', '$email', '$nohp', '$hashed_password')";
            
            if (mysqli_query($conn, $insert)) {
                $success = "Registrasi berhasil! Silakan login.";
                // Optional: auto login setelah registrasi
                // $_SESSION['id_customer'] = mysqli_insert_id($conn);
                // header("Location: Booking.php");
                // exit;
            } else {
                $error = "Registrasi gagal: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi</title>
  <link rel="stylesheet" href="Booking.css">
</head>
<body>
  <form action="registrasi.php" method="POST">
    <h2 style="text-align:center;">Registrasi</h2>

    <?php if (!empty($error)): ?>
      <div style="color: red; text-align: center;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div style="color: green; text-align: center;"><?= $success ?></div>
    <?php endif; ?>

    <label for="nama">Nama Lengkap</label>
    <input type="text" id="nama" name="nama" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="nohp">No HP</label>
    <input type="text" id="nohp" name="nohp" pattern="08[0-9]{9,}" placeholder="08xxxxxxxx" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" minlength="8" required>

    <label for="confirm_password">Konfirmasi Password</label>
    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

    <button type="submit" name="register">Daftar</button>

    <div style="text-align: center; margin-top: 15px;">
      <a href="login.php">Sudah punya akun? Login</a>
    </div>
  </form>
</body>
</html>