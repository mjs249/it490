#!/usr/bin/php
<?php
require_once './vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use \Firebase\JWT\JWT;

function storeRestaurants($restaurants) {
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = 'MikeNuhaJames123!';
    $db_name = 'it490';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $sql = "INSERT INTO restaurants (id, name, image_url, is_closed, url, review_count, categories, rating, latitude, longitude, address, phone, display_phone, distance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);

    foreach ($restaurants as $restaurant) {
        $address = implode(", ", $restaurant['location']['display_address']);
        $categories = json_encode($restaurant['categories']);
        $latitude = $restaurant['coordinates']['latitude'];
        $longitude = $restaurant['coordinates']['longitude'];
        $is_closed = $restaurant['is_closed'] ? 1 : 0;
        $distance = $restaurant['distance'];

        $stmt->bind_param("sssbisiiddsssd", $restaurant['id'], $restaurant['name'], $restaurant['image_url'], $is_closed, $restaurant['url'], $restaurant['review_count'], $categories, $restaurant['rating'], $latitude, $longitude, $address, $restaurant['phone'], $restaurant['display_phone'], $distance);

        if (!$stmt->execute()) {
           
            continue;
        }
    }

    $stmt->close();
    $mysqli->close();
    return ['success' => true, 'message' => 'Restaurants stored successfully.'];
}

function makeReservation($restaurantId, $username, $date, $time, $guests, $specialRequests) {
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = 'MikeNuhaJames123!';
    $db_name = 'it490';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error . "\n"; 
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }

    $sql = "INSERT INTO bookings (username, restaurant_id, booking_date, booking_time, number_of_guests, special_requests, confirmation_code) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo "Prepare failed: " . $mysqli->error . "\n"; 
        $mysqli->close();
        return ['success' => false, 'message' => "Prepare failed: " . $mysqli->error];
    }

    $confirmationCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
    $stmt->bind_param("ssssiss", $username, $restaurantId, $date, $time, $guests, $specialRequests, $confirmationCode);

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error . "\n"; // Print execution error
        $error = $stmt->error;
        $stmt->close();
        $mysqli->close();
        return ['success' => false, 'message' => "Failed to make booking: $error"];
    }

    $stmt->close();
    $mysqli->close();
    return ['success' => true, 'message' => 'Booking made successfully.', 'confirmation_code' => $confirmationCode];
}

function retrieveReviews($restaurantId) {
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = 'MikeNuhaJames123!'; 
    $db_name = 'it490';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($mysqli->connect_error) {
        return ['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error];
    }


    // Prepare SQL statement to select all reviews
    $sql = "SELECT * FROM reviews";
    $result = $mysqli->query($sql);

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    // Close connection
    $mysqli->close();

    return ['success' => true, 'reviews' => $reviews];
}

function submitReview($username, $restaurantId, $rating, $review)

{
    try{
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = 'MikeNuhaJames123!'; 
    $db_name = 'it490';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Prepare and bind
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
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = 'MikeNuhaJames123!'; 
    $db_name = 'it490';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
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
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = 'MikeNuhaJames123!';
    $db_name = 'it490';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

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

    // Check for existing username
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

    // Insert into DB
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

$connection = new AMQPStreamConnection('192.168.192.25', 5672, 'test', 'test');
$channel = $connection->channel();

$channel->queue_declare('db_queue', false, true, false, false);

echo ' [*] Waiting for messages.', "\n";

$callback = function ($msg) use ($channel) {
    echo " [x] Received ", $msg->body, "\n";
    $request = json_decode($msg->body, true);
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
            case "retrieveReviews":
                $response = retrieveReviews($request['restaurantId']);
                break;
            case "makeReservation":
                 if (isset($request['restaurantId'], $request['username'], $request['date'], $request['time'], $request['guests'])) {
                     $response = makeReservation($request['restaurantId'], $request['username'], $request['date'], $request['time'], $request['guests'], $request['specialRequests'] ?? '');
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
            default:
                $response = ['success' => false, 'message' => "Request type not handled"];
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    $responseMsg = new AMQPMessage(
        json_encode($response),
        array('correlation_id' => $msg->get('correlation_id'))
    );

    $channel->basic_publish($responseMsg, '', $msg->get('reply_to'));
    echo " [x] Response sent\n";
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
