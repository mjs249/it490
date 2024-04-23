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
    'type' => "retrieveDoNotShows",
    'username' => $username
];

$response = $client->send_request($request);
$favorites = $response['dislikes'] ?? [];
if (!empty($favorites)) {
    error_log("Dislikes: " . print_r($favorites, true));
} else {
    echo "<p>No disliked restaurants found.</p>";
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
    <?php include 'navbar.html'; ?>
    <h1>My Disliked Restaurants</h1>
    <div id="disliked">
        <?php if (!empty($dislike)): ?>
            <?php foreach ($dislike as $dislike): ?>
                <div class="dislike">
                    <h2><?= htmlspecialchars($dislike['name']) ?></h2>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($dislike['phone']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($dislike['address1']) ?></p>
                    <img src="<?= htmlspecialchars($dislike['image_url']) ?>" alt="Restaurant Image" style="max-width: 200px; height: auto;">
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No disliked restaurants found.</p>
        <?php endif; ?>
    </div>
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
