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

$client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");
$request = [
    'type' => "retrieveFavorites",
    'username' => $username
];

$response = $client->send_request($request);
$favorites = $response['favorites'] ?? [];
if (!empty($favorites)) {
    error_log("Favorites: " . print_r($favorites, true));
} else {
    echo "<p>No favorite restaurants found.</p>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <h1>My Favorite Restaurants</h1>
    <div id="favorites">
        <?php if (!empty($favorites)): ?>
            <?php foreach ($favorites as $favorite): ?>
                <div class="favorite">
                    <h2><?= htmlspecialchars($favorite['name']) ?></h2>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($favorite['phone']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($favorite['address1']) ?></p>
                    <img src="<?= htmlspecialchars($favorite['image_url']) ?>" alt="Restaurant Image" style="max-width: 200px; height: auto;">
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No favorite restaurants found.</p>
        <?php endif; ?>
    </div>
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
