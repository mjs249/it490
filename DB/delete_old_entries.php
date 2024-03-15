<?php

require_once 'config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$sql = "DELETE FROM restaurants 
        WHERE id NOT IN (SELECT restaurant_id FROM reviews) 
        AND id NOT IN (SELECT restaurant_id FROM reservations)";

if (!$mysqli->query($sql)) {
    die('Error executing query: ' . $mysqli->error);
}

$mysqli->close();

echo 'Restaurants without foreign key constraints deleted successfully.';
?>
