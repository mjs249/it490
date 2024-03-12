<?php

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('./vendor/autoload.php');
require_once('/home/mike/it490/path.inc');
require_once('/home/mike/it490/get_host_info.inc');
require_once('/home/mike/it490/rabbitMQLib.inc');

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "password";

if (!isset($_COOKIE['userToken'])) {
    header("Location: index.php");
    exit;
}

try {
    $decoded = JWT::decode($_COOKIE['userToken'], new Key($key, 'HS256'));
    $username = $decoded->username;
    $email = $decoded->email;
} catch (Exception $e) {
    echo "Error decoding token: " . $e->getMessage();
    exit;
}

// Fetch all reviews
$client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");
$request = [
    'type' => "retrieveReviews",
];

$response = $client->send_request($request);
$reviews = $response['reviews'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reviews</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <div class="navbar">
        <a href="welcome.php">Home</a>
        <a href="userProfile.php">Profile</a>
        <a href="testing.php">Search</a>
        <a href="displayReviews.php">Reviews</a>
    </div>

    <h1>All Reviews</h1>
    <div id="reviews">
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <p><strong>Username:</strong> <?= htmlspecialchars($review['username']) ?></p>
                    <p><strong>Rating:</strong> <?= htmlspecialchars($review['rating']) ?></p>
                    <p><strong>Review:</strong> <?= htmlspecialchars($review['review']) ?></p>
                    <p><strong>Review:</strong> <?= htmlspecialchars($review['restaurant_id']) ?></p>
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No reviews found.</p>
        <?php endif; ?>
    </div>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
