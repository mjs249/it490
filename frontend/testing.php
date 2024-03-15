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

$results = []; // Initialize results array

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['term'], $_POST['location'])) {
    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    $request = [
        'type' => "yelpSearch",
        'username' => $username,
        'term' => trim($_POST['term']),
        'location' => trim($_POST['location']),
    ];

    $response = $client->send_request($request);

    // Debug: Print the entire response for inspection
//    echo "<pre>Response received: ";
//    print_r($response);
//    echo "</pre>";

    if (!empty($response) && isset($response['businesses'])) {
        $results = $response['businesses'];
    } else {
        echo "Failed to retrieve Yelp search results.";
        if (isset($response['message'])) {
            echo " Error: " . htmlspecialchars($response['message']);
        }
    }
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
    <?php include 'navbar.php'; ?>


    <h1>Restaurant Search</h1>
    <form action="testing.php" method="post">
        <label for="term">Search Term:</label>
        <input type="text" id="term" name="term" required><br>
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required>
        <br>
        <button type="submit">Search</button>
    </form>

    <div id="results">
        <h2>Results:</h2>
        <?php foreach ($results as $business): ?>
            <div class="result">
                <p>Name: <?php echo htmlspecialchars($business['name']); ?></p>
                <p>Rating: <?php echo htmlspecialchars($business['rating']); ?></p>
		<img src="<?php echo htmlspecialchars($business['image_url']); ?>" alt="Restaurant Image" style="width:100%;max-width:300px;">
                <p>Address: <?php echo htmlspecialchars(implode(", ", $business['location']['display_address'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
