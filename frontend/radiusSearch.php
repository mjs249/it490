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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['term'], $_POST['location'], $_POST['radius'])) {
    // Create a new instance of rabbitMQClient
    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    $term = trim($_POST['term']);
    $location = trim($_POST['location']);
    $radiusInMiles = trim($_POST['radius']); // Retrieve the radius from POST data
    $radiusInMeters =floor( $radiusInMiles * 1609.34); // Convert miles to meters

    // Prepare the request payload
    $request = [
        'type' => "yelpradSearch",
        'term' => $term,
        'location' => $location,
        'radius' => $radiusInMeters, // Include the radius in the request
    ];

    // Send the request and get the response
    $response = $client->send_request($request);

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
    <div class="navbar">
        <a href="welcome.php">Home</a>
        <a href="userProfile.php">Profile</a>
        <a href="testing.php">Search</a>
        <a href="radiusSearch.php">Search by Location</a>
        <a href="booking.php">Reservations</a>
        <a href="review.php">Leave a Review</a>
    </div>

    <h1>Restaurant Search by Radius</h1>
    <form action="radiusSearch.php" method="post">
        <label for="term">Search Term:</label>
        <input type="text" id="term" name="term" required><br>
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required>
<label for="radius">Radius (miles):</label>
<input type="range" id="radius" name="radius" min="0" max="25" oninput="this.nextElementSibling.value = this.value">
<output>12.5</output><br>
<script>
document.getElementById('radius').oninput = function() {
    this.nextElementSibling.value = this.value;
};
</script>
        <br>
        <button type="submit">Search</button>
    </form>

    <div id="results">
        <h2>Results:</h2>
        <?php foreach ($results as $business): ?>
            <div class="result">
                <p>Name: <?php echo htmlspecialchars($business['name']); ?></p>
                <p>Rating: <?php echo htmlspecialchars($business['rating']); ?></p>
		<!-- <img src="<?php echo htmlspecialchars($business['image_url']); ?>" alt="Restaurant Image"> -->
                <p>Address: <?php echo htmlspecialchars(implode(", ", $business['location']['display_address'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
