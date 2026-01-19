<?php
session_start();
include "database.php";

$id_booking = $_GET['id_booking'] ?? '';

header('Content-Type: application/json');

if (empty($id_booking)) {
    echo json_encode(['error' => 'ID Booking kosong']);
    exit;
}

// Cek status booking
$query = "SELECT status FROM Booking WHERE id_booking = '$id_booking'";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => $row['status'],
        'is_confirmed' => ($row['status'] == 'Lunas')
    ]);
} else {
    echo json_encode(['error' => 'Booking tidak ditemukan']);
}
?>