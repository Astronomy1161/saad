<?php
session_start();

// Require login
if (!isset($_SESSION['id'])) {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "tric");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = (int)$_SESSION['id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$query = "
    SELECT r.rideId, r.pickup_location, r.dropoff_location, r.status, r.fare, r.date_requested, r.date_completed,
           CONCAT(u.firstname, ' ', u.lastname) AS rider_name
    FROM rides r
    JOIN rider rd ON r.rider_id = rd.riderId
    JOIN users u ON rd.user_id = u.id
    WHERE r.user_id = ?
";
$params = [$user_id];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY r.date_requested DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rides = $stmt->get_result();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Ride History | BacRide</title>
<style>
/* Matching dashboard styles */
body { font-family: Arial, sans-serif; background: #0b1b3a; color: #fff; margin:0; padding:0;}
.container { max-width: 900px; margin: 20px auto; background: #07122a; padding: 18px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.4); }
.topbar { display:flex; justify-content:space-between; align-items:center; background:#04b2f7; padding:10px 16px; border-radius:8px; color:#031026; font-weight:bold;}
.dropdown-btn { background:#fff; color:#031026; padding:8px 12px; border-radius:10px; cursor:pointer; position:relative; }
.dropdown-menu { position:absolute; right:12px; top:56px; background:#fff; color:#000; border-radius:10px; display:none; min-width:140px; box-shadow:0 6px 20px rgba(0,0,0,0.3); z-index:2000; }
.dropdown-menu a { display:block; padding:10px 12px; text-decoration:none; color:#000; border-bottom:1px solid #eee;}
.dropdown-menu a:last-child{ border-bottom:none; }
.container-inner { padding: 16px; }
h2 { margin: 6px 0 12px; color:#fff; text-align:center; }
.filter-form { margin-bottom: 20px; text-align: center; }
select { padding: 8px; border-radius: 6px; border: 1px solid #2b3f55; background: #071a2b; color: #fff; }
button { background:#04b2f7; color:#031026; border:none; padding:8px 12px; border-radius:6px; font-weight:700; cursor:pointer; margin-left: 10px; }
.ride-list { margin-top: 20px; }
.ride-item { background: #0a1a2e; padding: 12px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #2b3f55; }
.ride-item p { margin: 4px 0; color: #dbeefe; }
.status { font-weight: bold; }
.status.pending { color: #ffeb3b; }
.status.accepted { color: #2196f3; }
.status.completed { color: #4caf50; }
@media (min-width:900px){ .container{ margin-top:40px; } }
</style>
</head>
<body>

<div class="container">
    <div class="topbar">
        <div>BacRide</div>
        <div style="position:relative;">
            <div class="dropdown-btn" id="menuToggle">☰ Menu</div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="user.php">Dashboard</a> <!-- Adjust to your dashboard file -->
                <a href="profile.php">Update Info</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container-inner">
        <h2>Ride History — Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'User') ?></h2>

        <form method="GET" class="filter-form">
            <label for="status">Filter by Status:</label>
            <select name="status" id="status">
                <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All</option>
                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="accepted" <?= $status_filter == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <div class="ride-list">
            <?php if($rides->num_rows > 0): ?>
                <?php while($ride = $rides->fetch_assoc()): ?>
                    <div class="ride-item">
                        <p><strong>Rider:</strong> <?= htmlspecialchars($ride['rider_name']) ?></p>
                        <p><strong>Pickup:</strong> <?= htmlspecialchars($ride['pickup_location']) ?></p>
                        <p><strong>Dropoff:</strong> <?= htmlspecialchars($ride['dropoff_location']) ?></p>
                        <p><strong>Fare:</strong> ₱<?= htmlspecialchars(number_format($ride['fare'], 2)) ?></p>
                        <p><strong>Status:</strong> <span class="status <?= strtolower($ride['status']) ?>"><?= htmlspecialchars(ucfirst($ride['status'])) ?></span></p>
                        <p><strong>Requested:</strong> <?= htmlspecialchars($ride['date_requested']) ?></p>
                        <?php if($ride['date_completed']): ?>
                            <p><strong>Completed:</strong> <?= htmlspecialchars($ride['date_completed']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#9fc7e6;">No rides found for the selected filter.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

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
</script>

</body>
</html>
