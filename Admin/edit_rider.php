<?php
$conn = new mysqli("localhost", "root", "", "tric");

$id = $_GET['id'];
$r = $conn->query("SELECT * FROM rider WHERE riderId = $id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head><title>Edit Rider</title></head>
<body>

<h2>Edit Rider</h2>

<form method="POST" action="update_rider.php">
    <input type="hidden" name="id" value="<?= $r['riderId'] ?>">

    <label>Plate Number:</label><br>
    <input type="text" name="plate_number" value="<?= $r['plate_number'] ?>" required><br><br>

    <label>Address:</label><br>
    <input type="text" name="address" value="<?= $r['address'] ?>" required><br><br>

    <label>Profile:</label><br>
    <input type="text" name="profile" value="<?= $r['profile'] ?>"><br><br>

    <button type="submit">Save Changes</button>
</form>

</body>
</html>
