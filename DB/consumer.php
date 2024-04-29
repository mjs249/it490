#!/usr/bin/php
<?php
//ini_set('log_errors', 1);
//ini_set('error_log', '/home/mike/error.log');
require_once '/home/mike/it490/vendor/autoload.php';
require_once 'config.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use \Firebase\JWT\JWT;

function retrieveFavorites($username) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $sql = "SELECT 
                f.id AS favorite_id, 
                f.username, 
                f.created_at, 
                r.id AS restaurant_id, 
                r.name, 
                r.image_url, 
                r.is_closed, 
                r.url, 
                r.review_count, 
                r.categories, 
                r.rating, 
                r.latitude, 
                r.longitude, 
                r.phone, 
                r.display_phone, 
                r.distance, 
                r.address1,
                r.address2, 
                r.address3, 
                r.alias, 
                r.transactions, 
                r.price, 
                r.city, 
                r.zip_code, 
                r.country, 
                r.state, 
                r.display_address
            FROM favorites f
            JOIN restaurants r ON CONCAT('+', f.restaurantId) = r.phone
            WHERE f.username = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }

        $stmt->close();
        $mysqli->close();
        return ['success' => true, 'favorites' => $favorites];
    } else {
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }
}


function addFavorite($username, $restaurantId) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        error_log("Add Favorite Connection Error: " . $mysqli->connect_error);
        echo "Add Favorite Connection Error: " . $mysqli->connect_error . "\n";
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $stmt = $mysqli->prepare("INSERT INTO favorites (username, restaurantId) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Add Favorite Prepare Error: " . $mysqli->error);
        echo "Add Favorite Prepare Error: " . $mysqli->error . "\n";
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }

    $stmt->bind_param("si", $username, $restaurantId);
    if (!$stmt->execute()) {
        error_log("Add Favorite Execute Error: " . $stmt->error);
        echo "Add Favorite Execute Error: " . $stmt->error . "\n";
        $stmt->close();
        $mysqli->close();
        return ['success' => false, 'message' => "Execute failed: " . $stmt->error];
    }

    $stmt->close();
    $mysqli->close();
    return ['success' => true, 'message' => 'Added to favorites successfully.'];
}

function dislike($username, $restaurantId) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        error_log("Dislike Connection Error: " . $mysqli->connect_error);
        echo "Dislike Connection Error: " . $mysqli->connect_error . "\n";
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $stmt = $mysqli->prepare("INSERT INTO dislikes (username, restaurantId) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Dislike Prepare Error: " . $mysqli->error);
        echo "Dislike Prepare Error: " . $mysqli->error . "\n";
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }

    $stmt->bind_param("si", $username, $restaurantId);
    if (!$stmt->execute()) {
        error_log("Dislike Execute Error: " . $stmt->error);
        echo "Dislike Execute Error: " . $stmt->error . "\n";
        $stmt->close();
        $mysqli->close();
        return ['success' => false, 'message' => "Execute failed: " . $stmt->error];
    }

    $stmt->close();
    $mysqli->close();
    return ['success' => true, 'message' => 'Added to dislikes successfully.'];
}

function fetchDislikes($username) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        error_log("Fetch Dislikes Connection Error: " . $mysqli->connect_error);
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $stmt = $mysqli->prepare("SELECT restaurantId FROM dislikes WHERE username = ?");
    if (!$stmt) {
        error_log("Fetch Dislikes Prepare Error: " . $mysqli->error);
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }

    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        error_log("Fetch Dislikes Execute Error: " . $stmt->error);
        $stmt->close();
        $mysqli->close();
        return ['success' => false, 'message' => "Execute failed: " . $stmt->error];
    }

    $result = $stmt->get_result();
    $dislikes = [];
    while ($row = $result->fetch_assoc()) {
     
        $dislikes[] = "+" . $row['restaurantId'];
    }

    $stmt->close();
    $mysqli->close();

    return ['success' => true, 'dislikes' => $dislikes];
}


function updateReminderStatus($reservationId, $reminderSent) {
    
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        throw new Exception('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("UPDATE reservations SET reminder_sent = TRUE WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservationId);

    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error);
    }
    $stmt->close();
}
function fetchReservationsNeedingReminders() {

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        throw new Exception('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("SELECT 
                            r.reservation_id,
                            r.username,
                            r.restaurant_id,
                            r.reservation_date,
                            r.reservation_time,
                            r.number_of_guests,
                            r.special_requests,
                            r.confirmation_code,
                            r.phone,
                            u.email,
                            rest.name AS restaurant_name,
                            CONCAT(rest.address1, ', ', rest.city, ', ', rest.state, ' ', rest.zip_code, ', ', rest.country) AS restaurant_address
                          FROM reservations r
                          INNER JOIN users u ON r.username = u.username
                          INNER JOIN restaurants rest ON r.restaurant_id = rest.id
                          WHERE r.reservation_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                            AND r.reminder_sent = FALSE");

$stmt->execute();
$result = $stmt->get_result();

    $reservations = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
        $result->free();
    } else {
        throw new Exception("Database query failed: " . $mysqli->error);
    }

    return $reservations;
}

function fetchRandomSearchQuery($username) {

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $stmt = $mysqli->prepare("SELECT term, location FROM search_history WHERE username = ? ORDER BY RAND() LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ['success' => true, 'query' => ['term' => $row['term'], 'location' => $row['location']]];
    } else {
        return ['success' => false, 'message' => "No search history found for user: $username"];
    }
}

function logSearchQuery($username, $term, $location, $radius) {

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $stmt = $mysqli->prepare("INSERT INTO search_history (username, term, location, radius) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }

    $stmt->bind_param("sssi", $username, $term, $location, $radius);
    if ($stmt->execute()) {
        return ['success' => true, 'message' => "Search query logged successfully"];
    } else {
        return ['success' => false, 'message' => "Execute failed: " . $stmt->error];
    }
}

function storeRestaurants($restaurants) {

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $baseSql = "INSERT INTO restaurants (id, alias, name, image_url, is_closed, url, review_count, categories, rating, latitude, longitude, transactions, price, address1, address2, address3, city, zip_code, country, state, display_address, phone, display_phone, distance) VALUES ";
    $valueList = [];
    $params = [];
    $types = '';

    $placeHolder = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    foreach ($restaurants as $restaurant) {
        $valueList[] = $placeHolder;
        $price = isset($restaurant['price']) && !empty($restaurant['price']) ? $restaurant['price'] : 0;
        array_push($params, $restaurant['id'], $restaurant['alias'], $restaurant['name'], $restaurant['image_url'], $restaurant['is_closed'] ? 1 : 0,
                   $restaurant['url'], $restaurant['review_count'], json_encode($restaurant['categories']), $restaurant['rating'],
                   $restaurant['coordinates']['latitude'], $restaurant['coordinates']['longitude'], json_encode($restaurant['transactions']),
                   $restaurant['price'], $restaurant['location']['address1'], $restaurant['location']['address2'], $restaurant['location']['address3'],
                   $restaurant['location']['city'], $restaurant['location']['zip_code'], $restaurant['location']['country'], $restaurant['location']['state'],
                   json_encode($restaurant['location']['display_address']), $restaurant['phone'], $restaurant['display_phone'], $restaurant['distance']);
        $types .= 'sssssbisiiddsssssssssssd';
    }

    $sql = $baseSql . implode(', ', $valueList) . " ON DUPLICATE KEY UPDATE name = VALUES(name), image_url = VALUES(image_url), is_closed = VALUES(is_closed), url = VALUES(url), review_count = VALUES(review_count), categories = VALUES(categories), rating = VALUES(rating), latitude = VALUES(latitude), longitude = VALUES(longitude), transactions = VALUES(transactions), price = VALUES(price), address1 = VALUES(address1), address2 = VALUES(address2), address3 = VALUES(address3), city = VALUES(city), zip_code = VALUES(zip_code), country = VALUES(country), state = VALUES(state), display_address = VALUES(display_address), phone = VALUES(phone), display_phone = VALUES(display_phone), distance = VALUES(distance);";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $mysqli->close();
        return ['success' => false, 'message' => "Insertion failed: " . $stmt->error];
    }

    $stmt->close();
    $mysqli->close();
    return ['success' => true, 'message' => 'Restaurants stored successfully.'];
}


function makeReservation($username, $restaurantId, $date, $time, $guests, $phone, $specialRequests) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $sql = "INSERT INTO reservations (username, restaurant_id, reservation_date, reservation_time, number_of_guests, phone, special_requests, confirmation_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }

    $confirmationCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
    $guestsInt = (int) $guests;

    if (!$stmt->bind_param("ssssiiss", $username, $restaurantId, $date, $time, $guestsInt, $phone, $specialRequests, $confirmationCode)) {
        $stmtError = $stmt->error;
        $stmt->close();
        $mysqli->close();
        return ['success' => false, 'message' => "Binding parameters failed: " . $stmtError];
    }

    if (!$stmt->execute()) {
        $stmtError = $stmt->error;
        $stmt->close();
        $mysqli->close();
        return ['success' => false, 'message' => "Execute failed: " . $stmtError];
    }

    $stmt->close();
    $mysqli->close();

    return ['success' => true, 'message' => 'Reservation made successfully.', 'confirmation_code' => $confirmationCode];
}


function retrieveReviews($restaurantId = null) {

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    if ($restaurantId !== null) {
    $sql = "SELECT reviews.id, reviews.restaurant_id, reviews.username, reviews.review, reviews.rating AS userRating, 
            restaurants.name, restaurants.image_url, restaurants.is_closed, 
            restaurants.url, restaurants.review_count, restaurants.categories, restaurants.rating AS yelpRating, 
            restaurants.latitude, restaurants.longitude, restaurants.phone, restaurants.display_phone, 
            restaurants.distance, restaurants.address2, restaurants.address3, restaurants.alias, 
            restaurants.transactions, restaurants.price, restaurants.city, restaurants.zip_code, 
            restaurants.country, restaurants.state, restaurants.display_address, restaurants.address1 
            FROM reviews 
            INNER JOIN restaurants ON reviews.restaurant_id = restaurants.id 
            WHERE restaurants.id = ?";
} else {
    $sql = "SELECT reviews.id, reviews.restaurant_id, reviews.username, reviews.review, reviews.rating AS userRating, 
            restaurants.name, restaurants.image_url, restaurants.is_closed, 
            restaurants.url, restaurants.review_count, restaurants.categories, restaurants.rating AS yelpRating, 
            restaurants.latitude, restaurants.longitude, restaurants.phone, restaurants.display_phone, 
            restaurants.distance, restaurants.address2, restaurants.address3, restaurants.alias, 
            restaurants.transactions, restaurants.price, restaurants.city, restaurants.zip_code, 
            restaurants.country, restaurants.state, restaurants.display_address, restaurants.address1 
            FROM reviews 
            INNER JOIN restaurants ON reviews.restaurant_id = restaurants.id";
}

    if ($stmt = $mysqli->prepare($sql)) {

        if ($restaurantId !== null) {
            $stmt->bind_param("s", $restaurantId);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        $stmt->close();
        $mysqli->close();
        return ['success' => true, 'reviews' => $reviews];
    } else {
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }
}

function submitReview($username, $restaurantId, $rating, $review){
    try{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("INSERT INTO reviews (username, restaurant_id, rating, review) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $username, $restaurantId, $rating, $review);

    if ($stmt->execute()) {
        $mysqli->close();
        return ['success' => true, 'message' => 'Review submitted successfully.'];
    } else {
        $mysqli->close();
        return ['success' => false, 'message' => 'Failed to submit review.'];
    }
  } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login Failed due to an unexpected error.'];
    }
}

function doLogin($username, $password)
{
    try{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $mysqli->close();
        return ['success' => false, 'message' => 'Login Failed! User not found.'];
    }

    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $key = "password";
	$payload = [
            "iss" => "localhost",
            "aud" => "localhost",
            "iat" => time() - 15,
            "exp" => time() + 3600,
            "username" => $username,
            "email" => $row['email']
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        $mysqli->close();
        return ['success' => true, 'jwt' => $jwt];
    } else {
        $mysqli->close();
        return ['success' => false, 'message' => 'Login Failed! Incorrect password.'];
    }
  } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login Failed due to an unexpected error.'];
    }
}

function doRegister($username, $password, $email)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    // Check for existing email
    if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE email = ?")) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $mysqli->close();
            return ['success' => false, 'message' => 'Email already in use.'];
        }
    }

    if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE username = ?")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $mysqli->close();
            return ['success' => false, 'message' => 'Username already in use. Please choose a different username.'];
        }
    } else {
        $mysqli->close();
        return ['success' => false, 'message' => 'Error checking for duplicate username.'];
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertUser = $mysqli->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $insertUser->bind_param("sss", $username, $hashedPassword, $email);

    if ($insertUser->execute()) {
        $mysqli->close();
        return ['success' => true, 'message' => 'Registration successful.'];
    } else {
        $error = $insertUser->error;
        $mysqli->close();
        return ['success' => false, 'message' => "Registration failed: " . $error];
    }
}

$connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
$channel = $connection->channel();

$channel->queue_declare('db_queue', false, true, false, false);

echo ' [*] Waiting for messages.', "\n";



$callback = function ($msg) use ($channel) {

    $request = json_decode($msg->body, true);

    echo "[x] Received type: ", $request['type'], "\n";

    $response = null;



    try {

        switch ($request['type']) {

            case "login":

                $response = doLogin($request['username'], $request['password']);

                break;

            case "register":

                $response = doRegister($request['username'], $request['password'], $request['email']);

                break;

            case "submitReview":

                $response = submitReview($request['username'], $request['restaurantId'], $request['rating'], $request['review']);

                break;

            case "addFavorite":

            	$response = addFavorite($request['username'], $request['restaurantId']);

                break;

            case "dislike":

            	$response = dislike($request['username'], $request['restaurantId']);

                break;

            case "retrieveReviews":

                $response = retrieveReviews();

                break;

            case "retrieveFavorites":

                $response = retrieveFavorites($request['username']);

                break;

            case "makeReservation":

    if (isset($request['username'], $request['restaurantId'], $request['reservationDate'], $request['reservationTime'], $request['guests'], $request['phone'], $request['specialRequests'])) {

        $response = makeReservation($request['username'], $request['restaurantId'], $request['reservationDate'], $request['reservationTime'], $request['guests'], $request['phone'], $request['specialRequests'] ?? '');

    } else {

        $response = ['success' => false, 'message' => 'Missing reservation details.'];

    }

    break;

            case "storeRestaurants":

                if (isset($request['restaurants'])) {

                    $response = storeRestaurants($request['restaurants']);

                } else {

                    $response = ['success' => false, 'message' => 'No restaurant data provided.'];

                }

                break;

            case "updateReminderSent":

                if (isset($request['reservation_id']) && isset($request['reminder_sent'])) {

                    try {

                        updateReminderStatus($request['reservation_id'], $request['reminder_sent']);

                        $response = ['success' => true, 'message' => 'Reminder status updated successfully.'];

                    } catch (Exception $e) {

                        $response = ['success' => false, 'message' => "Failed to update reminder status: " . $e->getMessage()];

                    }

                } else {

                    $response = ['success' => false, 'message' => 'Missing reservation ID or reminder status.'];

                }

                break;

            case "logSearchQuery":

                if (isset($request['username'], $request['term'], $request['location'])) {

                    $radius = $request['radius'] ?? 0;

                    $response = logSearchQuery($request['username'], $request['term'], $request['location'], $radius);

                } else {

                    $response = ['success' => false, 'message' => 'Missing required fields for logging search query.'];

                }

                break;

            case "fetchRandomSearchQuery":

    if (isset($request['username'])) {

        $response = fetchRandomSearchQuery($request['username']);

        $dislikesResponse = fetchDislikes($request['username']);



        if ($dislikesResponse['success']) {

            $response['dislikes'] = $dislikesResponse['dislikes'];

        } else {

            $response['dislikes'] = [];

        }

    } else {

        $response = ['success' => false, 'message' => 'Username not provided.'];

    }

    break;

            case "fetchReservationReminders":

                try {

                    $reservations = fetchReservationsNeedingReminders();

                    $response = ['success' => true, 'reservations' => $reservations];

                } catch (Exception $e) {

                    $response = ['success' => false, 'message' => "Failed to fetch reservations: " . $e->getMessage()];

                }

                break;

            default:

                $response = ['success' => false, 'message' => "Request type not handled"];

                break;

        }

    } catch (Exception $e) {

        $response = ['success' => false, 'message' => $e->getMessage()];

    }



    $responseMsg = new AMQPMessage(

        json_encode($response),

        ['correlation_id' => $msg->get('correlation_id')]

    );



    $channel->basic_publish($responseMsg, '', $msg->get('reply_to'));

    echo "[x] Response sent\n";

};



$channel->basic_qos(null, 1, null);

$channel->basic_consume('db_queue', '', false, true, false, false, $callback);



try {

    while (true) {

        $channel->wait();

    }

} catch (Exception $e) {

    echo 'An error occurred: ', $e->getMessage(), "\n";

    $channel->close();

    $connection->close();

}



?>
