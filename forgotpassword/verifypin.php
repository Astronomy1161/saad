<?php
session_start();
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "tric");
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

$pin = trim($_POST['pin']);

$stmt = $conn->prepare("
    SELECT email 
    FROM password_resets
    WHERE pin = ?
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("s", $pin);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invalid or expired PIN: $pin";
    exit;
}

$row = $result->fetch_assoc();
$_SESSION['reset_email'] = $row['email'];

header("Location: resetpassword.php");
exit;
?>
