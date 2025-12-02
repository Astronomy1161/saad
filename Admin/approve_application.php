<?php
$conn = new mysqli("localhost", "root", "", "tric");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("No application ID provided.");
}

$applicationId = $_GET['id'];

// Get application data
$sql = "SELECT * FROM application WHERE applicationId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();

if (!$app) {
    die("Application not found.");
}

$firstname = $app['firstname'];
$lastname  = $app['lastname'];
$email     = $app['email'];
$contact   = $app['contact'];

$passwordPlain = "12345678";
$hashedPassword = password_hash($passwordPlain, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (firstname, lastname, email, contact, password, role)
        VALUES (?, ?, ?, ?, ?, 'rider')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $firstname, $lastname, $email, $contact, $hashedPassword);
$stmt->execute();

$userId = $conn->insert_id;

// 2. Create rider entry
$sql = "INSERT INTO rider (user_id, plate_number, profile, address, application_id)
        VALUES (?, 'N/A', 'default.jpg', 'Not Set', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $applicationId);
$stmt->execute();

// 3. Update application status
$sql = "UPDATE application SET status = 'approved' WHERE applicationId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $applicationId);
$stmt->execute();

// Redirect to dashboard
header("Location: admin.php");
exit();
?>
