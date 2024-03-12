<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once('./vendor/autoload.php');
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "password";

// Check if the JWT exists in the cookies
if (!isset($_COOKIE['userToken'])) {
    header("Location: index.php"); // Redirect to login page if the token is not present
    exit;
}

// Decode the JWT
try {
    JWT::$leeway = 60;
    $decoded = JWT::decode($_COOKIE['userToken'],  new Key($key, 'HS256'));
    // Extract the username from the decoded token
    $username = $decoded->username;
    $email = $decoded->email;
    //Debug: Print out decoded token
    echo "Decoded token: ";
    print_r($decoded);

    //Debug: Print out username
    echo "Username: " . $username;
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
    <title>Welcome</title>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self';">
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <div class="navbar">
        <a href="welcome.php">Home</a>
        <a href="userProfile.php">Profile</a>
        <a href="testing.php">Search</a>
        <a href="radiusSearch.php">Search by Location</a>
        <a href="booking.php">Reservations</a>
        <a href="review.php">Leave a Review</a>
    </div>

<body>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>Glad you're here. You've successfully logged in.</p>
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
