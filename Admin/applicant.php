<?php
// PROCESS FORM SUBMISSION
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Database connection
    $conn = new mysqli("localhost", "root", "", "tric");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $firstname = $_POST['firstname'];
    $lastname  = $_POST['lastname'];
    $email     = $_POST['email'];
    $contact   = $_POST['contact'];

    // ============================
    // VALIDATION SECTION
    // ============================

    // Email must end with .com
    if (!preg_match("/\.com$/", $email)) {
        $message = "Email must end with .com";
    }
    // Contact must start with 09 and be exactly 11 digits
    else if (!preg_match("/^09\d{9}$/", $contact)) {
        $message = "Contact number must start with 09 and be 11 digits long.";
    }
    else {

        // ============================
        // PREVENT DUPLICATE EMAIL
        // ============================
        $checkEmail = $conn->query("SELECT id FROM application WHERE email = '$email' LIMIT 1");

        if ($checkEmail->num_rows > 0) {
            $message = "This email has already applied. Please use a different email.";
        }
        else {

            // ============================
            // FILE UPLOAD HANDLING
            // ============================
            $targetDir = "uploads/";

            // Create folder if not exists
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["requirements"]["name"]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["requirements"]["tmp_name"], $targetFile)) {

                // Insert into database
                $sql = "INSERT INTO application (firstname, lastname, email, contact, requirements, status)
                        VALUES ('$firstname', '$lastname', '$email', '$contact', '$fileName', 'pending')";

                if ($conn->query($sql)) {
                    $message = "success"; // TRIGGER SUCCESS POPUP
                } else {
                    $message = "Database error: " . $conn->error;
                }

            } else {
                $message = "Error uploading the file.";
            }
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply as Rider | TricRide</title>

    <style>
        * {
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: #f2f2f2;
        }
        .container {
            width: 450px;
            background: white;
            padding: 25px;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            margin-bottom: 10px;
        }
        p {
            text-align: center;
            margin-bottom: 20px;
            color: #555;
        }
        .input-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #004c99;
        }
        .message {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            color: red;
        }

        /* Back Button */
        .back-btn {
            display: inline-block;
            margin-bottom: 15px;
            padding: 8px 15px;
            background: #ccc;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .back-btn:hover {
            background: #b3b3b3;
        }
    </style>
</head>
<body>

<div class="container">

    <a href="../index.html" class="back-btn">← Back to Home</a>

    <h2>Apply as a Rider</h2>
    <p>Please fill in the form and upload your requirements.</p>

    <?php if ($message != "" && $message != "success"): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">

        <div class="input-group">
            <label>First Name</label>
            <input type="text" name="firstname" required>
        </div>

        <div class="input-group">
            <label>Last Name</label>
            <input type="text" name="lastname" required>
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" pattern=".*\.com$" required>
        </div>

        <div class="input-group">
            <label>Contact Number</label>
            <input type="text" name="contact" pattern="09[0-9]{9}" maxlength="11" required>
        </div>

        <div class="input-group">
            <label>Upload Requirements (License / Clearance / ID)</label>
            <input type="file" name="requirements" accept="image/*" required>
        </div>

        <button type="submit" class="btn">Submit Application</button>

    </form>

</div>

<!-- SUCCESS POPUP + REDIRECT -->
<?php if ($message === "success"): ?>
<script>
    alert("Your application has been submitted successfully!\nPlease wait for the owner's response.");
    window.location.href = "../index.html"; // Redirect
</script>
<?php endif; ?>

</body>
</html>
