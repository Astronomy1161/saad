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

// Get riderId for the logged-in user
$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT riderId FROM rider WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("You are not registered as a rider.");
}
$rider = $result->fetch_assoc();
$rider_id = $rider['riderId'];
$stmt->close();

$message = "";
$accepted_ride = null; // To hold accepted ride details

// Handle ride acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_ride'])) {
    $ride_id = intval($_POST['ride_id']);
    
    // First, check if the ride is still pending and assigned to this rider
    $chk = $conn->prepare("SELECT status FROM rides WHERE rideId = ? AND rider_id = ?");
    $chk->bind_param("ii", $ride_id, $rider_id);
    $chk->execute();
    $chk->bind_result($status);
    $chk->fetch();
    $chk->close();
    
    if ($status === 'pending') {
        // Update status to 'accepted'
        $stmt = $conn->prepare("UPDATE rides SET status = 'accepted' WHERE rideId = ? AND rider_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $ride_id, $rider_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = "Ride accepted successfully! View details and map below.";
            // Fetch accepted ride details for display
            $ride_stmt = $conn->prepare("SELECT r.pickup_location, r.dropoff_location, r.fare, CONCAT(u.firstname, ' ', u.lastname) AS passenger_name 
                                         FROM rides r 
                                         JOIN users u ON r.user_id = u.id 
                                         WHERE r.rideId = ?");
            $ride_stmt->bind_param("i", $ride_id);
            $ride_stmt->execute();
            $ride_result = $ride_stmt->get_result();
            $accepted_ride = $ride_result->fetch_assoc();
            $accepted_ride['rideId'] = $ride_id; // Add rideId for completion
            $ride_stmt->close();
        } else {
            $message = "Error accepting ride.";
        }
        $stmt->close();
    } else {
        $message = "Ride is no longer available or already accepted.";
    }
}

// Handle ride completion (for accepted ride)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_ride'])) {
    $ride_id = intval($_POST['ride_id']);
    $stmt = $conn->prepare("UPDATE rides SET status = 'completed', date_completed = NOW() WHERE rideId = ? AND rider_id = ? AND status = 'accepted'");
    $stmt->bind_param("ii", $ride_id, $rider_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $message = "Ride marked as completed!";
    } else {
        $message = "Error completing ride.";
    }
    $stmt->close();
}

// Fetch pending rides (with passenger name)
$stmt = $conn->prepare("SELECT r.rideId, r.pickup_location, r.dropoff_location, r.fare, r.date_requested, CONCAT(u.firstname, ' ', u.lastname) AS passenger_name 
                        FROM rides r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.rider_id = ? AND r.status = 'pending' 
                        ORDER BY r.date_requested DESC");
$stmt->bind_param("i", $rider_id);
$stmt->execute();
$pending_rides = $stmt->get_result();

// Fetch completed rides (with passenger name)
$stmt = $conn->prepare("SELECT r.rideId, r.pickup_location, r.dropoff_location, r.fare, r.date_requested, r.date_completed, CONCAT(u.firstname, ' ', u.lastname) AS passenger_name 
                        FROM rides r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.rider_id = ? AND r.status = 'completed' 
                        ORDER BY r.date_completed DESC");
$stmt->bind_param("i", $rider_id);
$stmt->execute();
$completed_rides = $stmt->get_result();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Rider Dashboard | BacRide</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css"/>
<style>
/* Simple styling, similar to user dashboard */
body { font-family: Arial, sans-serif; background: #0b1b3a; color: #fff; margin:0; padding:0;}
.container { max-width: 900px; margin: 20px auto; background: #07122a; padding: 18px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.4); }
.topbar { display:flex; justify-content:space-between; align-items:center; background:#04b2f7; padding:10px 16px; border-radius:8px; color:#031026; font-weight:bold;}
.dropdown-btn { background:#fff; color:#031026; padding:8px 12px; border-radius:10px; cursor:pointer; position:relative; }
.dropdown-menu { position:absolute; right:12px; top:56px; background:#fff; color:#000; border-radius:10px; display:none; min-width:140px; box-shadow:0 6px 20px rgba(0,0,0,0.3); z-index:2000; }
.dropdown-menu a { display:block; padding:10px 12px; text-decoration:none; color:#000; border-bottom:1px solid #eee;}
.dropdown-menu a:last-child{ border-bottom:none; }
.container-inner { padding: 16px; }
h2 { margin: 6px 0 12px; color:#fff; text-align:center; }
.message { text-align:center; color:#b7ffb7; margin-bottom: 12px; font-weight:bold; }
.ride-list { margin-top: 20px; }
.ride-item { background: #0a1a2e; padding: 12px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #2b3f55; }
.ride-item p { margin: 4px 0; color: #dbeefe; }
.map-container { height: 400px; width: 100%; margin-top: 20px; border-radius: 8px; overflow: hidden; }
button { background:#04b2f7; color:#031026; border:none; padding:8px 12px; border-radius:6px; font-weight:700; cursor:pointer; }
button:hover { background:#0288d1; }
@media (min-width:900px){
  .container{ margin-top:40px; }
}
</style>
</head>
<body>

<div class="container">
    <div class="topbar">
        <div>BacRide Rider</div>
        <div style="position:relative;">
            <div class="dropdown-btn" id="menuToggle">☰ Menu</div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="rider_profile.php">Profile</a>
                <a href="rider_history.php">Ride History</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container-inner">
    <h2>Pending Rides</h2>

    <?php if($message != ""): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="ride-list">
        <?php if($pending_rides->num_rows > 0): ?>
            <?php while($ride = $pending_rides->fetch_assoc()): ?>
                <div class="ride-item">
                    <p><strong>Passenger:</strong> <?= htmlspecialchars($ride['passenger_name']) ?></p>
                    <p><strong>Pickup:</strong> <?= htmlspecialchars($ride['pickup_location']) ?></p>
                    <p><strong>Dropoff:</strong> <?= htmlspecialchars($ride['dropoff_location']) ?></p>
                    <p><strong>Fare:</strong> ₱<?= htmlspecialchars($ride['fare']) ?></p>
                    <p><strong>Requested:</strong> <?= htmlspecialchars($ride['date_requested']) ?></p>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="ride_id" value="<?= (int)$ride['rideId'] ?>">
                        <button type="submit" name="accept_ride" onclick="return confirm('Accept this ride?')">Accept Ride</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#9fc7e6;">No pending rides at the moment.</p>
        <?php endif; ?>
    </div>

    <?php if($accepted_ride): ?>
        <h2 style="margin-top:30px;">Accepted Ride Details</h2>
        <div class="ride-item">
            <p><strong>Passenger:</strong> <?= htmlspecialchars($accepted_ride['passenger_name']) ?></p>
            <p><strong>Pickup:</strong> <?= htmlspecialchars($accepted_ride['pickup_location']) ?></p>
            <p><strong>Dropoff:</strong> <?= htmlspecialchars($accepted_ride['dropoff_location']) ?></p>
            <p><strong>Fare:</strong> ₱<?= htmlspecialchars($accepted_ride['fare']) ?></p>
            <form method="POST" style="display:inline; margin-top:10px;">
                <input type="hidden" name="ride_id" value="<?= (int)$accepted_ride['rideId'] ?>">
                <button type="submit" name="complete_ride" onclick="return confirm('Mark this ride as completed?')">Mark as Completed</button>
            </form>
        </div>
        <div id="map" class="map-container"></div>
    <?php endif; ?>

    <h2 style="margin-top:30px;">Completed Rides</h2>
    <div class="ride-list">
        <?php if($completed_rides->num_rows > 0): ?>
            <?php while($ride = $completed_rides->fetch_assoc()): ?>
                <div class="ride-item">
                    <p><strong>Passenger:</strong> <?= htmlspecialchars($ride['passenger_name']) ?></p>
                    <p><strong>Pickup:</strong> <?= htmlspecialchars($ride['pickup_location']) ?></p>
                    <p><strong>Dropoff:</strong> <?= htmlspecialchars($ride['dropoff_location']) ?></p>
                    <p><strong>Fare:</strong> ₱<?= htmlspecialchars($ride['fare']) ?></p>
                    <p><strong>Requested:</strong> <?= htmlspecialchars($ride['date_requested']) ?></p>
                    <p><strong>Completed:</strong> <?= htmlspecialchars($ride['date_completed']) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#9fc7e6;">No completed rides yet.</p>
        <?php endif; ?>
    </div>
</div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>
<script>
/* DROPDOWN TOGGLE */
document.getElementById("menuToggle").addEventListener("click", () => {
    const menu = document.getElementById("dropdownMenu");
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
});

// Close menu on outside click
document.addEventListener("click", (e) => {
    const menu = document.getElementById("dropdownMenu");
    const toggle = document.getElementById("menuToggle");
    if (!toggle.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = "none";
    }
});

<?php if($accepted_ride): ?>
let map = L.map('map').setView([14.4451, 120.9564], 13); // Default center
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19
}).addTo(map);

const geocoder = L.Control.Geocoder.nominatim();

// Function to geocode address to lat/lng
function geocodeAddress(address, callback) {
    geocoder.geocode(address, function(results) {
        if (results && results.length > 0) {
            const latlng = results[0].center;
            callback(latlng);
        } else {
            alert(`Could not geocode address: ${address}`);
            callback(null);
        }
    });
}

// Geocode and add markers for pickup and dropoff
geocodeAddress('<?= addslashes($accepted_ride['pickup_location']) ?>', function(pickupLatLng) {
    if (pickupLatLng) {
        L.marker(pickupLatLng, {icon: L.icon({iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41]})}).addTo(map).bindPopup("Pickup Location").openPopup();
        map.setView(pickupLatLng, 14);
    }
});

geocodeAddress('<?= addslashes($accepted_ride['dropoff_location']) ?>', function(dropoffLatLng) {
    if (dropoffLatLng) {
        L.marker(dropoffLatLng, {icon: L.icon({iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', iconSize: [25, 41], iconAnchor: [12, 41]})}).addTo(map).bindPopup("Dropoff Location");
    }
});
<?php endif; ?>
</script>

</body>
</html>
