#!/usr/bin/php
<?php

require_once './vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$package_name = 'frontend_files';

$connection = new AMQPStreamConnection('192.168.192.188', 5672, 'test', 'test');
$channel = $connection->channel();

$channel->queue_declare('qa_to_deployment', false, true, false, false);

echo "Enter the version number: ";
$version = trim(fgets(STDIN));

echo "Enter the status (good/bad): ";
$status = strtolower(trim(fgets(STDIN)));

if ($status !== 'good' && $status !== 'bad') {
    echo "Invalid status. Please enter 'good' or 'bad'.\n";
    exit(1);
}

$messageBody = json_encode(['status' => $status, 'package' => $package_name, 'version' => $version]);

$msg = new AMQPMessage($messageBody, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

$channel->basic_publish($msg, '', 'qa_to_deployment');

echo " [x] Sent QA status message to deployment server.\n";

$channel->close();
$connection->close();

?>
