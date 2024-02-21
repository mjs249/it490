<?php
session_start();
// Set the expiration time to a past value to invalidate the cookie
//setcookie("userToken", "", time() - 3600, "/", "", false, true); 
setcookie("userToken", "", time() - 3600, "/", "", true, true);
session_destroy();
// Redirect to the login page
header("Location: index.php");
exit();
?>
