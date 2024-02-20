#!/usr/bin/php
<?php
require_once './vendor/autoload.php';
use \Firebase\JWT\JWT;

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username, $password)
{
    $db_host = '192.168.192.2';
    $db_user = 'admin';
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
        $key = "";
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
    $db_host = '192.168.192.2';
    $db_user = 'admin';
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

function requestProcessor($request)
{
    echo "received request" . PHP_EOL;
    if (!isset($request['type'])) {
        return ['success' => false, 'message' => "ERROR: unsupported message type"];
    }
    switch ($request['type']) {
        case "login":
            return doLogin($request['username'], $request['password']);
        case "register":
            return doRegister($request['username'], $request['password'], $request['email']);
    }
    return ['success' => false, 'message' => "Request type not handled"];
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
$server->process_requests('requestProcessor');
?>
