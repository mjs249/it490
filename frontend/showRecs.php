<?php
session_start();

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
} catch (Exception $e) {
    echo "Error decoding token: " . $e->getMessage();
    exit;
}

$client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

$request = [
    'type' => "getRandomSearchQuery",
    'username' => $username,
];

$recResponse = $client->send_request($request);

if (!empty($recResponse) && isset($recResponse['businesses'])) {
    $recommendation = $recResponse['businesses'][array_rand($recResponse['businesses'])];
} else {
    echo "Error: Response is empty or does not contain businesses.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Recommendation</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include 'navbar.html'; ?>

    <h1 style="color: #ef6c00; margin-top: 25px; margin-bottom: 30px;">Today's Recommendation</h1>

    <?php if (!empty($recommendation)): ?>
        <div id="recommendation" class="result" style="text-align: center;">
            <h2><?php echo htmlspecialchars($recommendation['name']); ?></h2>
            <p>Rating: <?php echo htmlspecialchars($recommendation['rating']); ?> stars</p>
            <p>Address: <?php echo htmlspecialchars(implode(", ", $recommendation['location']['display_address'])); ?></p>
            <img src="<?php echo htmlspecialchars($recommendation['image_url']); ?>" alt="Restaurant Image" style="width:100%;max-width:300px;">
        </div>
    <?php else: ?>
        <p style="color: #333; margin-top: 10px; margin-bottom: 50px;">No recommendation available. Try again later!</p>
    <?php endif; ?>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
