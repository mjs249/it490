#!/usr/bin/php
<?php
require_once './vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use \Firebase\JWT\JWT;

function doLogin($username, $password)
{
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = ''; 
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
            "iat" => time(),
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
}

function doRegister($username, $hashedPassword, $email)
{
    $db_host = 'localhost';
    $db_user = 'test';
    $db_pass = '';
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

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

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

$channel->close();
$connection->close();
