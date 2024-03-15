<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('/home/mike/it490/path.inc');
require_once('/home/mike/it490/get_host_info.inc');
require_once('/home/mike/it490/rabbitMQLib.inc');
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

$message = "";
$results = [];

if (isset($_SESSION['last_search'])) {
    $term = $_SESSION['last_search']['term'];
    $location = $_SESSION['last_search']['location'];
} else {
    $term = '';
    $location = '';
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    if (isset($_POST['submitReview'])) {

        $restaurantId = $_POST['restaurantId'];
        $rating = $_POST['rating'];
        $review = $_POST['review'];

        $request = [

            'type' => "submitReview",
            'restaurantId' => $restaurantId,
            'rating' => $rating,
            'review' => $review,
            'username' => $username,

        ];

        $response = $client->send_request($request);

        if ($response && isset($response['success']) && $response['success']) {

            $message = "Review submitted successfully!";

        } else {

            $message = "Failed to submit review.";
        }
    } else {

        $term = $_POST['term'] ?? '';
        $location = $_POST['location'] ?? '';
        $request = [
            'type' => 'yelpSearch',
            'username' => $username,
            'term' => $term,
            'location' => $location,
        ];

        $response = $client->send_request($request);

        if (isset($response['businesses'])) {

            $results = $response['businesses'];

        } else {

            $message = "Failed to search Yelp. " . ($response['message'] ?? '');
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

    <h1>Leave a Review</h1>
    <form action="review.php" method="post">
        <label for="term">Search Term:</label>
        <input type="text" id="term" name="term" value="<?php echo htmlspecialchars($term); ?>" required><br>
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required><br>
        <button type="submit">Search</button>
    </form>

    <?php if (!empty($message)): ?>
    <p><?php echo $message; ?></p>
    <?php endif; ?>

    <div id="results">
        <h2>Results:</h2>
        <?php foreach ($results as $business): ?>
        <div class="result">
            <p>Name: <?php echo htmlspecialchars($business['name']); ?></p>
            <p>Rating: <?php echo htmlspecialchars($business['rating']); ?></p>
            <p>Address: <?php echo htmlspecialchars(implode(", ", $business['location']['display_address'])); ?></p>
            <form action="review.php" method="post">
                <input type="hidden" name="restaurantId" value="<?php echo htmlspecialchars($business['id']); ?>">
                <label for="rating">Rating:</label>
                <select name="rating" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                <label for="review">Review:</label>
                <input type="text" name="review" required>
                <input type="submit" name="submitReview" value="Submit Review">
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
