<?php
session_start();
include "../Service/database.php";

// Aktifkan error reporting sementara untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

// Tambah villa (DIPERBAIKI)
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
    $harga = (int)($_POST['harga'] ?? 0);
    $foto = mysqli_real_escape_string($conn, trim($_POST['foto'] ?? ''));

    // Validasi wajib
    if (empty($nama)) {
        $_SESSION['error'] = "Nama villa wajib diisi!";
    } elseif ($harga <= 0) {
        $_SESSION['error'] = "Harga harus lebih dari 0!";
    } else {
        // Simpan ke database
        $query = "INSERT INTO villa (Nama_villa, deskripsi, harga, foto, status)
                  VALUES ('$nama', '$deskripsi', '$harga', '$foto', 'Tersedia')";

        if (mysqli_query($conn, $query)) {
            $_SESSION['pesan'] = "Villa '$nama' berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menyimpan: " . mysqli_error($conn);
        }
    }

    // Redirect selalu
    header("Location: kelola_villa.php");
    exit;
}

// Hapus villa (TIDAK ADA PERUBAHAN)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $query = "DELETE FROM villa WHERE id_villa='$id'";
    mysqli_query($conn, $query);
    header("Location: kelola_villa.php");
    exit;
}

// ===============================================
// 🔥 PERBAIKAN UTAMA: MENGAMBIL DATA VILLA DENGAN STATUS REAL-TIME DARI BOOKING
// ===============================================

$query_villas = "
    SELECT
        v.*,
        CASE
            WHEN EXISTS (
                SELECT 1
                FROM Booking b
                WHERE b.id_villa = v.id_villa
                AND b.status IN ('Menunggu Pembayaran', 'Lunas')
                AND CURDATE() BETWEEN b.tanggal_checkin AND DATE_SUB(b.tanggal_checkout, INTERVAL 1 DAY) 
            ) THEN 'Dibooking'
            ELSE 'Tersedia'
        END AS status_realtime
    FROM
        villa v
";

$villas = mysqli_query($conn, $query_villas);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Villa</title>
    <link rel="stylesheet" href="JadiSatu.css">
</head>
<body>
    <h2>Kelola Data Villa</h2>

    <!-- Tampilkan pesan -->
    <?php
    if (isset($_SESSION['pesan'])) {
        echo "<div style='background:green; color:white; padding:10px; margin-bottom:10px;'>✅ " . $_SESSION['pesan'] . "</div>";
        unset($_SESSION['pesan']);
    }
    if (isset($_SESSION['error'])) {
        echo "<div style='background:red; color:white; padding:10px; margin-bottom:10px;'>❌ " . $_SESSION['error'] . "</div>";
        unset($_SESSION['error']);
    }
    ?>

    <form method="POST">
        <input type="text" name="nama" placeholder="Nama Villa" required>
        <textarea name="deskripsi" placeholder="Deskripsi"></textarea>
        <input type="number" name="harga" placeholder="Harga" required>
        <input type="text" name="foto" placeholder="Link Foto">
        <button type="submit" name="tambah">Tambah Villa</button>
    </form>

    <h3>Daftar Villa</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Nama Villa</th>
            <th>Deskripsi</th>
            <th>Harga</th>
            <th>Foto</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($villas)) { ?>
        <tr>
            <td><?= $row['id_villa'] ?></td>
            <td><?= $row['Nama_villa'] ?></td>
            <td><?= $row['deskripsi'] ?></td>
            <td><?= $row['harga'] ?></td>
            <td><img src="<?= $row['foto'] ?>" width="100"></td>
            <td><?= $row['status_realtime'] ?></td>
            <td>
                <a href="kelola_villa.php?hapus=<?= $row['id_villa'] ?>" onclick="return confirm('Hapus villa ini?')">Hapus</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>