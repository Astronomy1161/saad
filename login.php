<?php
session_start();

$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "tric";

// Connect to database
$conn = new mysqli($servername, $username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Prepare to avoid SQL injection
    $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->bind_result($id, $firstname, $lastname, $db_email, $db_password_hashed, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $db_password_hashed)) {

            // Common session variables
            $_SESSION["id"] = $id;
            $_SESSION["role"] = $role;
            $_SESSION["fullname"] = $firstname . " " . $lastname;

            // Admin redirect
            if ($role == "admin") {
                header("Location: Admin/admin.php");
                exit;

            // Rider redirect
            } elseif ($role == "rider") {

                // Correctly fetch riderId using user_id
                $riderStmt = $conn->prepare("SELECT riderId FROM rider WHERE user_id = ?");
                $riderStmt->bind_param("i", $id);
                $riderStmt->execute();
                $riderStmt->bind_result($riderId);
                $riderStmt->fetch();
                $riderStmt->close();

                if ($riderId) {
                    $_SESSION["riderId"] = $riderId;
                }

                header("Location: user/rider.php");
                exit;

            // User redirect
            } elseif ($role == "user") {

                $_SESSION["userId"] = $id;

                header("Location: user/user.php");
                exit;

            } else {
                echo "<script>alert('Invalid role!'); window.location='login.html';</script>";
            }

        } else {
            echo "<script>alert('Incorrect password!'); window.location='login.html';</script>";
        }

    } else {
        echo "<script>alert('Email not found!'); window.location='login.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
