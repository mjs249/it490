<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    $decoded = JWT::decode($_COOKIE['userToken'], new Key($key, 'HS256'));
    $username = $decoded->username;
    $email = $decoded->email;
} catch (Exception $e) {
    echo "Error decoding token: " . $e->getMessage();
    exit;
}

// Your Yelp API key
$API_KEY = '';
$API_HOST = "https://api.yelp.com";
$SEARCH_PATH = "/v3/businesses/search";

// Function to make a request to the Yelp API
function request_yelp($url, $api_key) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key"]);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

// Function to search Yelp businesses based on term and location
function search_yelp($term, $location, $api_key, $api_host, $search_path) {
    $url_params = http_build_query(['term' => $term, 'location' => $location, 'limit' => 5]);
    $search_url = "$api_host$search_path?$url_params";
    return request_yelp($search_url, $api_key);
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
    </div>

    <h1>User Profile</h1>
    <p>Username: <?php echo htmlspecialchars($username); ?></p>
    <p>Email: <?php echo htmlspecialchars($email); ?></p> 

    <form action="filter.php" method="post">
    <label for="term">Search Term:</label>
    <input type="text" id="term" name="term" required>
    
    <label for="location">Location:</label>
    <input type="text" id="location" name="location" required>

    <!-- Checkboxes for filtering options -->
    <div>
        <input type="checkbox" id="exclude_fast_food" name="exclude_fast_food" value="1">
        <label for="exclude_fast_food">Exclude Fast Food</label>
    </div>
    <div>
        <input type="checkbox" id="include_small_businesses" name="include_small_businesses" value="1">
        <label for="include_small_businesses">Only Small Businesses</label>
    </div>

    <button type="submit">Search</button>
</form>

    <h2>Results:</h2>
    <div id="results">
        <?php foreach ($filtered_results as $business): // Use $filtered_results here ?>
            <div class="result">
                <p>Name: <?php echo htmlspecialchars($business['name']); ?></p>
                <p>Rating: <?php echo htmlspecialchars($business['rating']); ?></p>
                <p>Address: <?php echo htmlspecialchars(implode(", ", $business['location']['display_address'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
