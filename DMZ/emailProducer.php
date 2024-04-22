#!/usr/bin/php
<?php

require_once './vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = '192.168.192.25';
$port = 5672;
$user = 'test';
$password = 'test';
$exchange = '';
$requestQueue = 'dmz_to_rmq_queue';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->queue_declare($requestQueue, false, true, false, false);

$request = ['type' => 'fetchReservationReminders'];
$msg = new AMQPMessage(
    json_encode($request),
    array('content_type' => 'application/json', 'delivery_mode' => 2)
);
$channel->basic_publish($msg, $exchange, $requestQueue);

echo " [x] Sent request for reservation reminders to DB VM\n";

$channel->close();
$connection->close();
