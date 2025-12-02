<?php
$conn = new mysqli("localhost", "root", "", "tric");
$rider_id = intval($_GET['rider_id']);

$result = $conn->query("SELECT latitude, longitude FROM rider WHERE riderId = $rider_id");
$row = $result->fetch_assoc();

echo json_encode([
    "lat" => $row['latitude'],
    "lng" => $row['longitude']
]);
?>
