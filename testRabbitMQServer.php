<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


function doLogin($username, $password)
{
    // Database connection details
    $db_host = '192.168.192.2';
    $db_user = 'admin';
    $db_pass = ''; 
    $db_name = 'it490';     

    // Create database connection
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Prepare statement to prevent SQL injection
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, verify the password
        $row = $result->fetch_assoc();

        // Debugging: Output expected and received passwords
        echo "Expected password: " . $row['password'] . PHP_EOL;
        echo "Received password: " . $password . PHP_EOL;

        if (password_verify($password, $row['password'])) {
            // Password is correct
            echo "Password verification: Success" . PHP_EOL;
            return true;
        } else {
            // Debugging: Indicate password mismatch
            echo "Password verification: Failed" . PHP_EOL;
        }
    }

    // Close connection
    $mysqli->close();
    return false; // Invalid login credentials
}
function doRegister($username, $password, $email)
{
    // Database connection details
    $db_host = '192.168.192.2';
    $db_user = 'admin';
    $db_pass = ''; 
    $db_name = 'it490';             

    // Create database connection
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Prepare INSERT statement to prevent SQL injection
    $stmt = $mysqli->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $email);

    // Execute the query and check for errors
    if ($stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        return true; // Registration successful
    } else {
        $error = $stmt->error;
        $stmt->close();
        $mysqli->close();
        return "Registration failed: " . $error; // Return error message
    }
}
function requestProcessor($request)
{
    echo "received request" . PHP_EOL;
    var_dump($request);
    if (!isset($request['type'])) {
        return "ERROR: unsupported message type";
    }
    switch ($request['type']) {
        case "login":
            return doLogin($request['username'], $request['password']);
    }
    return array("returnCode" => '0', 'message' => "Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END" . PHP_EOL;
exit();
?>

