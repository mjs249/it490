<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include RabbitMQ client library files from /home/mike/it490
require_once('/home/mike/it490/path.inc');
require_once('/home/mike/it490/get_host_info.inc');
require_once('/home/mike/it490/rabbitMQLib.inc');

// Check if the form data is submitted
if (isset($_POST['username']) && isset($_POST['password'])) {
    // Create a new RabbitMQ client with the configuration file also located in /home/mike/it4>
    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    // Create a request array
    $request = array();
    $request['type'] = "login";
    $request['username'] = $_POST['username'];
    $request['password'] = $_POST['password'];

    // Send the request to the RabbitMQ server
    $response = $client->send_request($request);

    // Process the response
if ($response) {
    // Redirect to welcome page upon successful login
    session_start();
    $_SESSION['username'] = $_POST['username']; // Store username in session
    header('Location: welcome.php');
    exit(); // Prevent further script execution after redirect
} else {
    echo "Login Failed!";
}
} else {
    echo "Username and Password are required!";
}
?>










