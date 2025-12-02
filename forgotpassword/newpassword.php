<?php
session_start();
if (!isset($_SESSION['reset_email'])) {
    die("Session expired.");
}

$email = $_SESSION['reset_email'];
$password = $_POST['password'];
$confirm = $_POST['confirm'];

if ($password !== $confirm) {
    die("Passwords do not match");
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$conn = new mysqli("localhost", "root", "", "tric");
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

// Update password
$upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$upd->bind_param("ss", $hashed, $email);
$upd->execute();
$upd->close();

// Delete PIN
$del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$del->bind_param("s", $email);
$del->execute();
$del->close();

unset($_SESSION['reset_email']);

header("Location: ../login.html?reset=success");
exit;
?>
