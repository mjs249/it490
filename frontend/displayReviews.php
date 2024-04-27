<?php

session_start();
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

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
//var_dump($response);
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
    <?php include 'navbar.html'; ?>

<h1 style="color: #ef6c00; margin-top: 25px; margin-bottom: 30px;">All Reviews</h1>

<div class="category-filters" style="text-align: center;">
    <h3>Filter by Category:</h3>
    <label><input type="checkbox" class="filter" value="Pizza"> Pizza</label>
    <label><input type="checkbox" class="filter" value="French"> French</label>
    <label><input type="checkbox" class="filter" value="Italian"> Italian</label>
    <label><input type="checkbox" class="filter" value="Mexican"> Mexican</label>
    <label><input type="checkbox" class="filter" value="Chinese"> Chinese</label>
    <label><input type="checkbox" class="filter" value="Thai"> Thai</label>
    <label><input type="checkbox" class="filter" value="Delis"> Delis</label>
    <label><input type="checkbox" class="filter" value="Fast Food"> Fast Food</label>
    <label><input type="checkbox" class="filter" value="Diners"> Diners</label>

</div>

<div class="rating-filters" style="text-align: center;">
    <h3>Filter by Rating:</h3>
    <label><input type="checkbox" class="filter-rating" value="1.0"> 1 Star</label>
    <label><input type="checkbox" class="filter-rating" value="2.0"> 2 Stars</label>
    <label><input type="checkbox" class="filter-rating" value="3.0"> 3 Stars</label>
    <label><input type="checkbox" class="filter-rating" value="4.0"> 4 Stars</label>
    <label><input type="checkbox" class="filter-rating" value="5.0"> 5 Stars</label>
</div>
<div id="reviews">
    <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review" data-category='<?= json_encode(array_map(function($cat) { return $cat['title']; }, json_decode($review['categories'], true))) ?>'>
                <p><strong>Username:</strong> <?= htmlspecialchars($review['username']) ?></p>
                <p><strong>Rating:</strong> <?= htmlspecialchars($review['userRating']) ?></p>
                <p><strong>Review:</strong> <?= htmlspecialchars($review['review']) ?></p>
                <h3>Restaurant Information:</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($review['name']) ?></p>
		<!--<p><strong>Rating:</strong> <?= htmlspecialchars($review['yelpRating']) ?> / 5</p>-->
                <p><strong>Category:</strong> <?= htmlspecialchars(implode(", ", array_map(function($cat) { return $cat['title']; }, json_decode($review['categories'], true)))) ?></p>
		<p><strong>Address:</strong> <?= htmlspecialchars($review['address1'] . ', ' . $review['city']  . ' ' . $review['state'] . ', ' . $review['country'] . ', ' . $review['zip_code']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($review['display_phone']) ?></p>
                <img src="<?= htmlspecialchars($review['image_url']) ?>" alt="Restaurant Image" style="max-width: 200px; height: auto;">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filters = document.querySelectorAll('.filter');
    const ratingFilters = document.querySelectorAll('.filter-rating');
    const reviews = document.querySelectorAll('.review');

    function filterReviews() {
        try {
            const selectedCategories = Array.from(filters).filter(filter => filter.checked).map(filter => filter.value);
            const selectedRatings = Array.from(ratingFilters).filter(filter => filter.checked).map(filter => parseInt(filter.value)); // Convert string values to integers

            reviews.forEach(review => {
                const categories = JSON.parse(review.dataset.category);
                const ratingElement = review.querySelector('p:nth-of-type(2)');
                if (!ratingElement) {
                    throw new Error('Rating element not found in review');
                }
                const ratingText = ratingElement.innerText.trim();
                const ratingValue = parseInt(ratingText.split(':')[1].trim()); // Convert rating value to integer
                const isCategoryMatch = selectedCategories.length === 0 || categories.some(category => selectedCategories.includes(category));
                const isRatingMatch = selectedRatings.length === 0 || selectedRatings.includes(ratingValue);

                if (isCategoryMatch && isRatingMatch) {
                    review.style.display = '';
                } else {
                    review.style.display = 'none';
                }
            });
        } catch (error) {
            console.error('An error occurred while filtering reviews:', error);
        }
    }

    filters.forEach(filter => filter.addEventListener('change', filterReviews));
    ratingFilters.forEach(filter => filter.addEventListener('change', filterReviews));
});
</script>
</body>
</html>
