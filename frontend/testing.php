<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('./vendor/autoload.php');
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

$API_KEY = '';
$API_HOST = "https://api.yelp.com";
$SEARCH_PATH = "/v3/businesses/search";

// Function to make a request to the Yelp API
function request_yelp($url, $api_key) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key"]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) { // Check for cURL errors
        echo 'Curl error: ' . curl_error($curl);
    }
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpcode != 200) { // Check for HTTP errors from the Yelp API
        echo "Yelp API request failed with response code {$httpcode}";
    }if ($httpcode != 200) {
    echo "Yelp API request failed with response code {$httpcode}\n";
    echo "Response Body: " . $response; // Output the response body for debugging
}
    curl_close($curl);

    return $response;
}

// Function to search Yelp businesses based on term and location
function search_yelp($term, $location, $api_key, $api_host, $search_path) {
    $url_params = http_build_query([
        'term' => $term,
        'location' => $location,
        'limit' => 5 // Limit the results to 5
    ]);

    $search_url = "$api_host$search_path?$url_params";

    return request_yelp($search_url, $api_key);
}

$results = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $term = isset($_POST['term']) ? $_POST['term'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : '';

    $response = json_decode(search_yelp($term, $location, $API_KEY, $API_HOST, $SEARCH_PATH), true);
    $results = $response['businesses'] ?? [];
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

    <h1>Restaurant Search</h1>
    <form action="testing.php" method="post">
        <label for="term">Search Term:</label>
        <input type="text" id="term" name="term" required><br>
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required>
<br>        <button type="submit">Search</button>
    </form>

    
    <div id="results">
    <h1>Results:</h1>

        <?php foreach ($results as $business): ?>
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
