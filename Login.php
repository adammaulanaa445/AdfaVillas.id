<?php
session_start();
include "Service/database.php";

$error = "";
$success = "";

// Cek apakah ada pesan sukses dari registrasi
if (isset($_SESSION['registrasi_sukses'])) {
    $success = $_SESSION['registrasi_sukses'];
    unset($_SESSION['registrasi_sukses']); // Hapus session setelah ditampilkan
}

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi!";
    } else {
        // Cek apakah customer ada di database
        $check = "SELECT * FROM Customer WHERE Email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            // Verifikasi password
            if (password_verify($password, $row['password'])) {
                // Login sukses
                $_SESSION['id_customer'] = $row['id_customer'];
                $_SESSION['nama'] = $row['Nama'];
                $_SESSION['email'] = $row['Email'];
                
                header("Location: Booking.php");
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Email tidak ditemukan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Login</title>
  <link rel="stylesheet" href="Booking.css">
  <style>
    .error-message {
      color: red;
      text-align: center;
      margin-bottom: 15px;
      font-size: 14px;
    }
    .success-message {
      color: green;
      text-align: center;
      margin-bottom: 15px;
      font-size: 14px;
    }
    .form-links {
      text-align: center;
      margin-top: 15px;
    }
    .form-links a {
      color: #007bff;
      text-decoration: none;
      margin: 0 10px;
    }
    .form-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <form action="login.php" method="POST">
    <h2 style="text-align:center;">Login</h2>

    <!-- Pesan Sukses Registrasi -->
    <?php if (!empty($success)): ?>
      <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <!-- Pesan Error Login -->
    <?php if (!empty($error)): ?>
      <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <label for="email">Email</label>
    <input type="text" id="email" name="email" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" name="submit">Login</button>

    <div class="form-links">
      <a href="lupa_password.php">Lupa Password?</a>
      <a href="registrasi.php">Belum punya akun? Daftar</a>
    </div>
  </form>
</body>
</html> 