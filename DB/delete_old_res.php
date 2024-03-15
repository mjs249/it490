<?php

require_once 'config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$oneDayAgo = date('Y-m-d', strtotime('-1 day'));

$sql = "DELETE FROM reservations 
        WHERE DATE_ADD(CONCAT(reservation_date, ' ', reservation_time), INTERVAL 1 DAY) <= NOW()";

if (!$mysqli->query($sql)) {
    die('Error executing query: ' . $mysqli->error);
}

$mysqli->close();

echo 'Reservations that are a day past their reservation time have been deleted successfully.';
?>
