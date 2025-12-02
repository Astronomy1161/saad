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
$message = "";

// Fetch current user data (added contact)
$stmt = $conn->prepare("SELECT firstname, lastname, email, contact FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    
    // Basic validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($contact)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $contact)) {
        $message = "Contact number must be 10-15 digits.";
    } else {
        // Check if email is already taken by another user
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->bind_param("si", $email, $user_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $message = "Email is already in use.";
        } else {
            // Update user info
            $upd = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, contact = ? WHERE id = ?");
            $upd->bind_param("ssssi", $firstname, $lastname, $email, $contact, $user_id);
            
            if ($upd->execute()) {
                $message = "Profile updated successfully!";
                // Update session fullname
                $_SESSION['fullname'] = $firstname . ' ' . $lastname;
                session_regenerate_id(true); // Security: regenerate session ID
                // Refresh user data
                $user['firstname'] = $firstname;
                $user['lastname'] = $lastname;
                $user['email'] = $email;
                $user['contact'] = $contact;
            } else {
                $message = "Error updating profile.";
            }
            $upd->close();
        }
        $chk->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Profile | BacRide</title>
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
.message { text-align:center; color:#b7ffb7; margin-bottom: 12px; font-weight:bold; }
.error { color:#ff6b6b; }
.form-row { margin-bottom: 15px; }
label { display:block; margin-bottom: 6px; color:#9fc7e6; font-weight:600; }
input[type="text"], input[type="email"], input[type="tel"] { width:100%; padding:10px; border-radius:8px; border:1px solid #2b3f55; background:#071a2b; color:#fff; }
input::placeholder { color:#99b6d1; }
button { background:#04b2f7; color:#031026; border:none; padding:10px 15px; border-radius:6px; font-weight:700; cursor:pointer; width:100%; }
button:hover { background:#0288d1; }
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
                <a href="history.php">History</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container-inner">
        <h2>Update Profile — Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'User') ?></h2>

        <?php if($message != ""): ?>
            <div class="message <?= strpos($message, 'Error') !== false ? 'error' : '' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required>
            </div>

            <div class="form-row">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required>
            </div>

            <div class="form-row">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-row">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" value="<?= htmlspecialchars($user['contact']) ?>" placeholder="e.g., 09123456789" required>
            </div>

            <button type="submit">Update Profile</button>
        </form>
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
