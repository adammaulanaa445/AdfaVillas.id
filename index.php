<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: LoginAdmin.php");
    exit;
}
// Jika admin sudah logout, arahkan ke LoginAdmin
if (isset($_SESSION['admin_logged_out'])) {
    header("Location: LoginAdmin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏡 Villa Admin</h2>
        <ul>
            <li><a href="kelolabooking.php">📖 Kelola Booking</a></li>
            <li><a href="kelolaPembayaran.php">💳 Kelola Pembayaran</a></li>
            <li><a href="kelolaVilla.php">🏠 Kelola Villa</a></li>
            <li><a href="Admin/LoginAdmin.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Welcome Admin</h1>
        </div>
        <div class="content">
            <h2>📊 Dashboard</h2>
            <p>Please select a menu on the side to manage the system.</p>
        </div>
    </div>
</body>
</html>
