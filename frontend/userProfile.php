<?php
session_start();

require_once('./vendor/autoload.php');
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "password"; 

// Verify JWT
if (!isset($_COOKIE['userToken'])) {
    header("Location: index.php"); // Redirect to login page if JWT is not present
    exit;
}

try {
    $decoded = JWT::decode($_COOKIE['userToken'],  new Key($key, 'HS256'));
    // Extract the username from the decoded token
    $username = $decoded->username;
    $email = $decoded->email;
    // Debug: Print out decoded token
//    echo "Decoded token: ";
  //  print_r($decoded);

} catch (Exception $e) {
    // If the token is invalid or expired, redirect to the login page
    echo "Error decoding token: " . $e->getMessage();
    exit;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include 'navbar.html'; ?>

    <h1>User Profile</h1>
    <p>Username: <?php echo htmlspecialchars($username); ?></p>
    <p>Email: <?php echo htmlspecialchars($email); ?></p> 
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>

</body>
</html>
