<?php
include "Service/database.php";

$id_villa = $_POST['id_villa'] ?? 0;
if (!$id_villa) {
    echo json_encode([]);
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT tanggal_checkin, tanggal_checkout 
    FROM booking 
    WHERE id_villa = ? 
    AND status IN ('Menunggu Pembayaran', 'Lunas')
");

mysqli_stmt_bind_param($stmt, "i", $id_villa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$booked_dates = [];

while ($row = mysqli_fetch_assoc($result)) {
    $start = new DateTime($row['tanggal_checkin']);
    $end = new DateTime($row['tanggal_checkout']);
    $end->modify('-1 day');

    $interval = DatePeriod::create($start, new DateInterval('P1D'), $end);
    foreach ($interval as $date) {
        $booked_dates[] = $date->format('Y-m-d');
    }
    $booked_dates[] = $end->format('Y-m-d');
}

$booked_dates = array_values(array_unique($booked_dates));
echo json_encode($booked_dates);
?>