<?php
session_start();

require_once('/home/mike/it490/path.inc');
require_once('/home/mike/it490/get_host_info.inc');
require_once('/home/mike/it490/rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    $request = [
        'type' => "register",
        'username' => $username,
        'password' => $hashedPassword,
        'email' => $email
    ];

    $response = $client->send_request($request);

    if ($response && isset($response['success']) && $response['success']) {
        $_SESSION['flash_message'] = 'Registration successful! You may now log in.';
        header('Location: index.php');
        exit();
    } else {
        $errorMessage = isset($response['message']) ? $response['message'] : "Registration failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="passwordValidation.js"></script> <!-- Link to your JavaScript file for validation -->
</head>
<body>
    <h2>Registration Form</h2>
    <?php if (!empty($errorMessage)) { echo "<p style='color:red'>$errorMessage</p>"; } ?>
    <form action="register.php" method="post" onsubmit="return validateForm()">
        Username: <input type="text" name="username" required><br>
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" id="password" required 
                 pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                 title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 or more characters"><br>
        Confirm Password: <input type="password" name="confirm_password" id="confirm_password" required><br>
        <span id="message"></span><br>
        <input type="submit" value="Register">
    </form>
    <p>Already have an account? <a href="index.php">Log in here</a>.</p>
</body>
</html>
