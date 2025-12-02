<?php
$conn = new mysqli("localhost", "root", "", "tric");
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// Fetch applications
$applications = $conn->query("SELECT * FROM application ORDER BY date_applied DESC") or die($conn->error);

// Fetch only users with role = 'user'
$users = $conn->query("SELECT * FROM users WHERE role='user'") or die($conn->error);

// Fetch riders with their user info
$riders = $conn->query("
    SELECT r.*, u.firstname, u.lastname, u.contact
    FROM rider r
    LEFT JOIN users u ON r.user_id = u.id
") or die($conn->error);

// Fetch rides with both user and rider info
$rides = $conn->query("
    SELECT r.rideId, r.pickup_location, r.dropoff_location, r.status, r.fare,
           u.firstname AS user_first, u.lastname AS user_last,
           rd.firstname AS rider_first, rd.lastname AS rider_last
    FROM rides r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN rider rdr ON r.rider_id = rdr.riderId
    LEFT JOIN users rd ON rdr.user_id = rd.id
    ORDER BY r.date_requested DESC
") or die($conn->error);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | BacRide MOTO</title>
<style>
body { font-family: Arial; background: #f5f5f5; margin: 0; }
.navbar { background: #003366; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; }
.navbar .logo { display: flex; align-items: center; gap: 10px; color: white; font-size: 20px; font-weight: bold; }
.navbar .logo img { width: 40px; height: 40px; border-radius: 5px; }
.dropdown { position: relative; }
.dropbtn { background: none; color: white; border: none; font-size: 18px; cursor: pointer; padding: 10px; }
.dropdown-content { display: none; position: absolute; background-color: white; min-width: 180px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2); z-index: 1; right: 0; }
.dropdown-content a { color: #003366; padding: 12px 16px; text-decoration: none; display: block; }
.dropdown-content a:hover { background-color: #f1f1f1; }
.dropdown:hover .dropdown-content { display: block; }
.container { width: 95%; margin: 20px auto; }
.tab-box { display: none; }
.tab-box.active { display: block; }
.table-box { background: white; padding: 15px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
table, th, td { border: 1px solid #ddd; }
th { background: #003366; color: white; padding: 10px; }
td { padding: 8px; text-align: center; }
.btn { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; color: white; margin: 2px; }
.approve { background: #28a745; }
.reject { background: #dc3545; }
.view { background: #007bff; }
.edit { background: #ffc107; color: black; }
.delete { background: #dc3545; }
</style>
<script>
function showTab(tabId){
    document.querySelectorAll(".tab-box").forEach(tab => tab.classList.remove("active"));
    document.getElementById(tabId).classList.add("active");
}
window.onload = function() {
    document.getElementById("applications").classList.add("active");
};
</script>
</head>
<body>

<div class="navbar">
    <div class="logo">
        <img src="logo.jpg" alt="BacRide Logo">
        BacRide MOTO
    </div>
    <div class="dropdown">
        <button class="dropbtn">Menu ▼</button>
        <div class="dropdown-content">
            <a onclick="showTab('applications')">Applications</a>
            <a onclick="showTab('users')">Users</a>
            <a onclick="showTab('riders')">Riders</a>
            <a onclick="showTab('rides')">Rides</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">

<!-- APPLICATIONS TAB -->
<div id="applications" class="tab-box">
    <div class="table-box">
        <h2>Rider Applications</h2>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Contact</th>
                <th>Requirement</th><th>Status</th><th>Action</th>
            </tr>
            <?php while($row = $applications->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['applicationId']) ?></td>
                <td><?= htmlspecialchars($row['firstname'] . " " . $row['lastname']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['contact']) ?></td>
                <td><a class="view" href="uploads/<?= htmlspecialchars($row['requirements']) ?>" target="_blank">View</a></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td>
                    <?php if($row['status']=="pending"): ?>
                    <a href="approve_application.php?id=<?= $row['applicationId'] ?>"><button class="btn approve">Approve</button></a>
                    <a href="reject_application.php?id=<?= $row['applicationId'] ?>"><button class="btn reject">Reject</button></a>
                    <?php else: ?> - <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- USERS TAB -->
<div id="users" class="tab-box">
    <div class="table-box">
        <h2>Users</h2>
        <table>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Contact</th></tr>
            <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['id']) ?></td>
                <td><?= htmlspecialchars($u['firstname'] . " " . $u['lastname']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['contact']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- RIDERS TAB -->
<div id="riders" class="tab-box">
    <div class="table-box">
        <h2>Riders</h2>
        <table>
            <tr>
                <th>Rider ID</th><th>Name</th><th>Contact</th>
                <th>Plate Number</th><th>Profile</th><th>Address</th><th>Update</th>
            </tr>
            <?php while($r = $riders->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['riderId']) ?></td>
                <td><?= htmlspecialchars($r['firstname']." ".$r['lastname']) ?></td>
                <td><?php if(!empty($r['contact'])): ?>
                    <a class="view" href="tel:<?= htmlspecialchars($r['contact']) ?>"><?= htmlspecialchars($r['contact']) ?></a>
                    <?php else: ?> N/A <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['plate_number']) ?></td>
                <td><?= htmlspecialchars($r['profile']) ?></td>
                <td><?= htmlspecialchars($r['address']) ?></td>
                <td>
                    <a href="edit_rider.php?id=<?= $r['riderId'] ?>"><button class="btn edit">Edit</button></a>
                    <a href="delete_rider.php?id=<?= $r['riderId'] ?>" onclick="return confirm('Are you sure you want to delete this rider?');"><button class="btn delete">Delete</button></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- RIDES TAB -->
<div id="rides" class="tab-box">
    <div class="table-box">
        <h2>Rides</h2>
        <table>
            <tr><th>Ride ID</th><th>Rider</th><th>User</th><th>Pickup</th><th>Dropoff</th><th>Status</th><th>Fare</th></tr>
            <?php while($ride = $rides->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($ride['rideId']) ?></td>
                <td><?= htmlspecialchars($ride['rider_first'] . " " . $ride['rider_last']) ?></td>
                <td><?= htmlspecialchars($ride['user_first']." ".$ride['user_last']) ?></td>
                <td><?= htmlspecialchars($ride['pickup_location']) ?></td>
                <td><?= htmlspecialchars($ride['dropoff_location']) ?></td>
                <td><?= htmlspecialchars($ride['status']) ?></td>
                <td><?= htmlspecialchars($ride['fare']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

</div>
</body>
</html>
