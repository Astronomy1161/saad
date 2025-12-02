<?php
$conn = new mysqli("localhost", "root", "", "tric");

$rider_id = intval($_POST['rider_id']);
$lat = $_POST['lat'];
$lng = $_POST['lng'];

$conn->query("UPDATE rider SET latitude='$lat', longitude='$lng', last_update=NOW() WHERE riderId='$rider_id'");
echo "ok";
?>
