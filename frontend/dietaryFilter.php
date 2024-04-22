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

    $term = trim($_POST['term']);
    $location = trim($_POST['location']);
    $dietaryFilters = isset($_POST['dietary']) ? implode(" ", $_POST['dietary']) : '';
    $fullTerm = $term . ' ' . $dietaryFilters;

    $request = [
        'type' => "yelpSearch",
        'username' => $username,
        'term' => $fullTerm,
        'location' => $location,
    ];

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
    <title>Dietary Filters</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include 'navbar.html'; ?>

    <h1>Restaurant Search with Dietary Filters</h1>
    <form action="dietaryFilter.php" method="post">
        <label for="term">Search Term:</label>
        <input type="text" id="term" name="term" required><br>

        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required><br>

        <div class="dietary-review">
            <label><input type="checkbox" name="dietary[]" value="Vegan"> Vegan</label>
            <label><input type="checkbox" name="dietary[]" value="Nut-Free"> Nut-Free</label>
            <label><input type="checkbox" name="dietary[]" value="Gluten-Free"> Gluten-Free</label>
            <label><input type="checkbox" name="dietary[]" value="Halal"> Halal</label>
            <label><input type="checkbox" name="dietary[]" value="Kosher"> Kosher</label>
        </div>
        <button type="submit">Search</button>
    </form>

    <div id="results">
        <h2>Results:</h2>
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $business): ?>
                <div class="dietary-review">
                    <p>Name: <?php echo htmlspecialchars($business['name']); ?></p>
                    <p>Rating: <?php echo htmlspecialchars($business['rating']); ?></p>
                    <p>Address: <?php echo htmlspecialchars(implode(", ", $business['location']['display_address'])); ?></p>
                    <img src="<?php echo htmlspecialchars($business['image_url']); ?>" alt="Restaurant Image">
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p></p>
        <?php endif; ?>
    </div>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
</body>
</html>
