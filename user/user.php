<?php
session_start();

// require login
if (!isset($_SESSION['id'])) {
    header("Location: ../login.html");
    exit;
}

// DB connection
$servername = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tric";

$conn = new mysqli($servername, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper: haversine distance in km
function haversine_km($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// fare settings
define('FARE_PER_KM', 20); // pesos per km
define('BASE_FARE', 50);   // minimum fare

// Fetch riders for selection (only active riders — adjust if you have a status column)
$riderStmt = $conn->prepare("
    SELECT r.riderId, u.firstname, u.lastname, r.plate_number
    FROM rider r
    JOIN users u ON r.user_id = u.id
");
$riderStmt->execute();
$ridersResult = $riderStmt->get_result();

// Process booking form
$message = "";
$bookedRider = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize/validate inputs coming from client (hidden fields)
    $user_id = (int)$_SESSION['id'];
    $rider_id = isset($_POST['rider_id']) ? (int)$_POST['rider_id'] : 0;
    $pickup_address = isset($_POST['pickup_address']) ? trim($_POST['pickup_address']) : '';
    $dropoff_address = isset($_POST['dropoff_address']) ? trim($_POST['dropoff_address']) : '';

    $pickup_lat = isset($_POST['pickup_lat']) ? floatval($_POST['pickup_lat']) : null;
    $pickup_lng = isset($_POST['pickup_lng']) ? floatval($_POST['pickup_lng']) : null;
    $dropoff_lat = isset($_POST['dropoff_lat']) ? floatval($_POST['dropoff_lat']) : null;
    $dropoff_lng = isset($_POST['dropoff_lng']) ? floatval($_POST['dropoff_lng']) : null;

    // Basic validation
    if (!$rider_id) {
        $message = "Please select a rider.";
    } elseif ($pickup_lat === null || $pickup_lng === null) {
        $message = "Unable to determine your pickup location. Please allow location access.";
    } elseif ($dropoff_lat === null || $dropoff_lng === null) {
        $message = "Please set a dropoff location using the map.";
    } else {
        // Verify selected rider exists
        $chk = $conn->prepare("SELECT riderId FROM rider WHERE riderId = ?");
        $chk->bind_param("i", $rider_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $message = "Selected rider does not exist. Choose another rider.";
        } else {
            // Calculate server-side distance and fare using haversine
            $distance_km = haversine_km($pickup_lat, $pickup_lng, $dropoff_lat, $dropoff_lng);
            $fare = max(BASE_FARE, $distance_km * FARE_PER_KM);
            $fare = round($fare, 2);

            // Insert ride (prepared)
            $ins = $conn->prepare("INSERT INTO rides (user_id, rider_id, pickup_location, dropoff_location, status, fare, date_requested) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
            if ($ins === false) {
                $message = "Prepare failed: " . htmlspecialchars($conn->error);
            } else {
                $ins->bind_param("iissd", $user_id, $rider_id, $pickup_address, $dropoff_address, $fare);
                if ($ins->execute()) {
                    $bookedRider = $rider_id;
                    $message = "Ride booked successfully! Estimated fare: ₱" . number_format($fare, 2);
                } else {
                    $message = "Error booking ride: " . htmlspecialchars($ins->error);
                }
                $ins->close();
            }
        }
        $chk->close();
    }
}

$riderStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>User BacRide</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css"/>
<style>
/* Keep styling close to your previous design */
:root { --bg:#07122a; --card:#071a2b; --accent:#04b2f7; --muted:#9fc7e6; --text:#cfefff; }
body { margin:0; font-family:Arial,Helvetica,sans-serif; background: #0b1b3a; color: #fff; }
.container { max-width:900px; margin:24px auto; background:var(--card); padding:18px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.45); }
.topbar { display:flex; justify-content:space-between; align-items:center; background:var(--accent); padding:10px 14px; border-radius:8px; color:#031026; font-weight:700; }
.dropdown-btn { background:#fff; color:#031026; padding:8px 12px; border-radius:10px; cursor:pointer; }
.dropdown-menu { position:absolute; right:12px; top:56px; background:#fff; color:#000; border-radius:10px; display:none; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.3); z-index:2000; }
.dropdown-menu a { display:block; padding:10px 12px; text-decoration:none; color:#000; border-bottom:1px solid #eee; }
.container-inner { padding:16px; }
h2 { text-align:center; margin:8px 0 14px; color:#fff; }
.message { text-align:center; color:#b7ffb7; margin-bottom:12px; font-weight:700; }
.form-row { margin-bottom:10px; }
label { display:block; margin-bottom:6px; color:var(--muted); font-weight:600; }
select, input[type="text"], button { width:100%; padding:10px; border-radius:8px; border:1px solid #2b3f55; background:#071a2b; color:#fff; }
input::placeholder { color:#99b6d1; }
button { background:var(--accent); color:#031026; border:none; font-weight:700; cursor:pointer; }
.map-container { height:360px; width:100%; margin-top:10px; border-radius:8px; overflow:hidden; display:block; }
#farePreview { margin-top:8px; padding:10px; border-radius:8px; background:#042033; color:#cfefff; font-weight:700; display:flex; gap:16px; justify-content:space-between; align-items:center; }
.fareItem { font-size:14px; }
.fareBig { font-size:16px; color:#fff; }
.footer-note { margin-top:12px; color:var(--muted); font-size:13px; text-align:center; }
small.hint { color:var(--muted); font-size:12px; display:block; margin-top:6px; }
@media (min-width:900px){ .container{ margin-top:40px; } }
</style>
</head>
<body>

<div class="container">
    <div class="topbar">
        <div>BacRide</div>
        <div style="position:relative;">
            <div class="dropdown-btn" id="menuToggle">☰ Menu</div>
            <div class="dropdown-menu" id="dropdownMenu" role="menu">
                <a href="profile.php">Update Info</a>
                <a href="history.php">History</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container-inner">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'User') ?></h2>

        <?php if ($message !== ""): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" id="rideForm" autocomplete="off">
            <div class="form-row">
                <label for="riderSelect">Select Rider</label>
                <select name="rider_id" required id="riderSelect">
                    <option value="">-- Choose a Rider --</option>
                    <?php while($row = $ridersResult->fetch_assoc()): ?>
                        <option value="<?= (int)$row['riderId'] ?>" <?= $bookedRider==$row['riderId']?'selected':'' ?>>
                            <?= htmlspecialchars($row['firstname'].' '.$row['lastname'].' - '.$row['plate_number']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <label>Pickup Location</label>
                <input type="text" id="pickup_display" placeholder="Detecting location..." readonly required>
            </div>

            <div class="form-row">
                <label>Dropoff Location</label>
                <input type="text" id="dropoff_display" placeholder="Click map to select dropoff" required>
            </div>

            <div id="farePreview">
                <div class="fareItem">Distance: <span id="distanceText">—</span></div>
                <div class="fareItem fareBig">Estimated Fare: ₱<span id="fareText">—</span></div>
            </div>

            <!-- hidden fields -->
            <input type="hidden" name="pickup_lat" id="pickup_lat">
            <input type="hidden" name="pickup_lng" id="pickup_lng">
            <input type="hidden" name="pickup_address" id="pickup_address">
            <input type="hidden" name="dropoff_lat" id="dropoff_lat">
            <input type="hidden" name="dropoff_lng" id="dropoff_lng">
            <input type="hidden" name="dropoff_address" id="dropoff_address">

            <div style="display:flex; gap:8px; margin-top:6px;">
                <button type="button" id="centerOnMe" style="flex:1;">Center Map on Me</button>
                <button type="submit" style="flex:1;">Book Ride</button>
            </div>

            <div id="map" class="map-container"></div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>
<script>
document.getElementById('menuToggle').addEventListener('click', function() {
    const menu = document.getElementById('dropdownMenu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
});

let map = L.map('map').setView([14.4451,120.9564],13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap', maxZoom:19
}).addTo(map);

let pickupMarker = null, dropMarker = null;
const geocoder = L.Control.Geocoder.nominatim();

// Function to reverse geocode and set address
function reverseGeocode(lat, lng, callback) {
    geocoder.reverse(L.latLng(lat, lng), map.options.crs.scale(map.getZoom()), function(results) {
        if (results && results.length > 0) {
            const address = results[0].properties.address;
            const suburb = address.suburb || address.neighbourhood || address.village; // Barangay equivalent
            const road = address.road || address.pedestrian || address.path; // Street equivalent
            let displayAddress = '';
            if (suburb && road) {
                displayAddress = `Barangay ${suburb}, ${road}`;
            } else if (suburb) {
                displayAddress = `Barangay ${suburb}`;
            } else if (road) {
                displayAddress = road;
            } else {
                displayAddress = results[0].name || results[0].properties.display_name; // Fallback to full address
            }
            callback(displayAddress, results[0].name || results[0].properties.display_name); // Return display and full address
        } else {
            callback(`${lat.toFixed(6)}, ${lng.toFixed(6)}`, `${lat.toFixed(6)}, ${lng.toFixed(6)}`); // Fallback to coordinates
        }
    });
}

// detect user location
navigator.geolocation.getCurrentPosition(pos=>{
    const lat=pos.coords.latitude, lng=pos.coords.longitude;
    pickupMarker = L.marker([lat,lng],{draggable:false}).addTo(map).bindPopup("Pickup here").openPopup();
    document.getElementById('pickup_lat').value = lat;
    document.getElementById('pickup_lng').value = lng;
    reverseGeocode(lat, lng, function(displayAddress, fullAddress) {
        document.getElementById('pickup_display').value = displayAddress;
        document.getElementById('pickup_address').value = fullAddress;
    });
    map.setView([lat,lng],14);
    updateFare();
}, err=>{
    alert("Unable to get your location. Please allow location access.");
});

// add drop marker on click
map.on('click', e=>{
    const lat=e.latlng.lat, lng=e.latlng.lng;
    if(dropMarker) dropMarker.setLatLng(e.latlng);
    else dropMarker = L.marker([lat,lng],{draggable:true}).addTo(map).bindPopup("Dropoff here").openPopup();

    document.getElementById('dropoff_lat').value = lat;
    document.getElementById('dropoff_lng').value = lng;
    reverseGeocode(lat, lng, function(displayAddress, fullAddress) {
        document.getElementById('dropoff_display').value = displayAddress;
        document.getElementById('dropoff_address').value = fullAddress;
    });
    updateFare();

    dropMarker.on('dragend', ev=>{
        const pos = ev.target.getLatLng();
        document.getElementById('dropoff_lat').value = pos.lat;
        document.getElementById('dropoff_lng').value = pos.lng;
        reverseGeocode(pos.lat, pos.lng, function(displayAddress, fullAddress) {
            document.getElementById('dropoff_display').value = displayAddress;
            document.getElementById('dropoff_address').value = fullAddress;
        });
        updateFare();
    });
});

document.getElementById('centerOnMe').addEventListener('click', ()=>{
    if(pickupMarker) map.setView(pickupMarker.getLatLng(),14);
});

function updateFare(){
    const pLat=parseFloat(document.getElementById('pickup_lat').value);
    const pLng=parseFloat(document.getElementById('pickup_lng').value);
    const dLat=parseFloat(document.getElementById('dropoff_lat').value);
    const dLng=parseFloat(document.getElementById('dropoff_lng').value);
    if(isNaN(pLat)||isNaN(pLng)||isNaN(dLat)||isNaN(dLng)) return;

    const R = 6371; // km
    const dLatRad = (dLat-pLat)*Math.PI/180;
    const dLngRad = (dLng-pLng)*Math.PI/180;
    const a = Math.sin(dLatRad/2)**2 + Math.cos(pLat*Math.PI/180)*Math.cos(dLat*Math.PI/180)*Math.sin(dLngRad/2)**2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R*c;

    document.getElementById('distanceText').textContent = distance.toFixed(2)+" km";
    const fare = Math.max(<?=BASE_FARE?>, distance*<?=FARE_PER_KM?>);
    document.getElementById('fareText').textContent = fare.toFixed(2);
}
</script>

</body>
</html>
