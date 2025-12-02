<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/SMTP.php';

$conn = new mysqli("localhost", "root", "", "tric");
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

$email = trim($_POST['email']);

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Email not found");
$stmt->close();

$pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

$del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$del->bind_param("s", $email);
$del->execute();
$del->close();

$ins = $conn->prepare("
    INSERT INTO password_resets (email, pin, expires_at) 
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
");
$ins->bind_param("ss", $email, $pin);
$ins->execute();
$ins->close();

// Send PIN
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "aqua.tradeph@gmail.com";
    $mail->Password = "tgwo xhdz jkev msel"; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom("aqua.tradeph@gmail.com", "BacRide Bacoor");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Your Password Reset PIN";
    $mail->Body = "
        <h3>Password Reset PIN</h3>
        <p>Your PIN code is: <strong>$pin</strong></p>
        <p>This PIN expires in 1 hour.</p>
    ";

    $mail->send();

    header("Location: resetpin.php");
    exit;

} catch (Exception $e) {
    die("Mailer error: " . $mail->ErrorInfo);
}
?>
