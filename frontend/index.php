<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('/home/mike/it490/path.inc');
require_once('/home/mike/it490/get_host_info.inc');
require_once('/home/mike/it490/rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $client = new rabbitMQClient("/home/mike/it490/testRabbitMQ.ini", "testServer");

    $request = [
        'type' => "login",
        'username' => trim($_POST['username']),
        'password' => trim($_POST['password'])
    ];

    $response = $client->send_request($request);

    if ($response && isset($response['jwt'])) {
        //setcookie("userToken", $response['jwt'], time() + 3600, "/", "", false, true);
        setcookie("userToken", $response['jwt'], time() + 3600, "/", "", true, true); // Secure and HttpOnly 
        header('Location: welcome.php');
        exit();
    } else {
        $errorMessage = isset($response['message']) ? $response['message'] : "Login Failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>
<body>
    <h2>Login Form</h2>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['flash_message']; ?></p>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <?php if (!empty($errorMessage)) { echo "<p style='color:red'>$errorMessage</p>"; } ?>
    <form action="index.php" method="post" onsubmit="return validateLoginForm()">
         Username: <input type="text" name="username" id="username" required><br>
	Password:
    <div class="password-container">
        <input type="password" name="password" id="password" required>
        <span id="togglePassword" class="toggle-password"><i class="fas fa-eye"></i></span>
        <span id="capsLockWarning" class="tooltip">Caps Lock is ON!</span>
    </div>
    <br>
    <input type="submit" value="Login">
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>

    <script src="login.js"></script>
</body>
</html>

