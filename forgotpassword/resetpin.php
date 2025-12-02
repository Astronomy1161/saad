<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter PIN</title>
    <link rel="stylesheet" href="forgot.css">
</head>
<body>

<div class="background"></div>

<header>
    <nav>
        <div class="logo">
            <img src="logo.jpg" alt="BacRide MOTO Logo" />
            <div class="logo-text">BacRide MOTO</div>
        </div>
    </nav>
</header>

<div class="container">
    <h2>ENTER YOUR<br>RESET PIN</h2>

    <form action="verifypin.php" method="POST">
        <input type="text" name="pin" placeholder="Enter 6-digit PIN" maxlength="6" pattern="\d{6}" required>
        <button type="submit">Verify PIN</button>
    </form>

</div>

<footer>
    ©BacRide MOTO 2025. ALL RIGHTS RESERVED
</footer>

</body>
</html>
