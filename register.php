  GNU nano 6.2                              register.php                                       
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include RabbitMQ client library files
require_once('/home/mike/it490/path.inc');
require_once('/home/mike/it490/get_host_info.inc');
require_once('/home/mike/it490/rabbitMQLib.inc');

// Check if the form data is submitted
if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Hash the password before sending it through RabbitMQ
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Create a new RabbitMQ client
    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    // Create a request array for registration
    $request = array();
    $request['type'] = "register";
    $request['username'] = $username;
    $request['password'] = $hashedPassword; // Send the hashed password
    $request['email'] = $email;             // Include the email

    // Send the request to the RabbitMQ server
    $response = $client->send_request($request);

    // Process the response
    if ($response === true) {
        echo "Registration successful!";
    } else {
        // Check if the response is an array
        if (is_array($response)) {
            // Convert the array to a string for printing
            $errorMessage = implode(", ", $response);
            echo "Registration failed: " . $errorMessage;
        } else {
            // If the response is not an array, directly print it
            echo "Registration failed: " . $response;
        }
    }
} else {
    echo "Username, Password, and Email are required!";
}
?>




