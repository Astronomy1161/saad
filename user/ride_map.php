<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "tric");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ride_id = intval($_GET['rideId']);
$stmt = $conn->prepare("SELECT pickup_location, dropoff_location 
                        FROM rides 
                        WHERE rideId = ?");
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();
$ride = $result->fetch_assoc();
$stmt->close();
$conn->close();

$pickup = $ride['pickup_location'];
$dropoff = $ride['dropoff_location'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Ride Map</title>
<style>
  #map { height: 500px; width: 100%; }
</style>
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
<script>
function initMap() {
    const geocoder = new google.maps.Geocoder();
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: { lat: 14.444, lng: 120.993 } // fallback center (Bacoor area)
    });

    // Pickup marker
    geocoder.geocode({ address: "<?= addslashes($pickup) ?>" }, function(results, status) {
        if (status === "OK") {
            map.setCenter(results[0].geometry.location);
            new google.maps.Marker({
                map: map,
                position: results[0].geometry.location,
                label: "P",
                title: "Pickup: <?= htmlspecialchars($pickup) ?>"
            });
        }
    });

    // Dropoff marker
    geocoder.geocode({ address: "<?= addslashes($dropoff) ?>" }, function(results, status) {
        if (status === "OK") {
            new google.maps.Marker({
                map: map,
                position: results[0].geometry.location,
                label: "D",
                title: "Dropoff: <?= htmlspecialchars($dropoff) ?>"
            });
        }
    });
}
</script>
</head>
<body onload="initMap()">
<h2>Ride Location</h2>
<p><strong>Pickup:</strong> <?= htmlspecialchars($pickup) ?></p>
<p><strong>Dropoff:</strong> <?= htmlspecialchars($dropoff) ?></p>
<div id="map"></div>
</body>
</html>
