#!/usr/bin/php
<?php

error_reporting(E_ALL);

ini_set('display_errors', '1');

require_once './vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = '192.168.192.51';
$port = 5672;
$user = 'test';
$password = 'test';

$dmzToRmqQueue = 'dmz_to_rmq_queue';
$rmqToDmzQueue = 'rmq_to_dmz_queue';
$dbQueue = 'db_queue';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->queue_declare($dmzToRmqQueue, false, true, false, false);
$channel->queue_declare($rmqToDmzQueue, false, true, false, false);

list($callbackQueue,,) = $channel->queue_declare("", false, false, true, false);

$correlationIdToResponseMap = [];

$dbResponseCallback = function($response) use (&$correlationIdToResponseMap) {
    $corrId = $response->get('correlation_id');
    if (isset($correlationIdToResponseMap[$corrId])) {
        global $channel, $rmqToDmzQueue;
        $replyMsg = new AMQPMessage(
            $response->body,
            array('correlation_id' => $corrId)
        );
        $channel->basic_publish($replyMsg, '', $rmqToDmzQueue);
        echo " [x] Sent DB response back to DMZ\n";
        unset($correlationIdToResponseMap[$corrId]);
    }
};

$channel->basic_consume($callbackQueue, '', false, true, false, false, $dbResponseCallback);

function forwardRequestToDBAsync($request, $correlationId) {
    global $channel, $dbQueue, $callbackQueue;
    $requestMsg = new AMQPMessage(
        json_encode($request),
        array('reply_to' => $callbackQueue, 'correlation_id' => $correlationId, 'content_type' => 'application/json', 'delivery_mode' => 2)
    );
    $channel->basic_publish($requestMsg, '', $dbQueue);
    echo " [x] Sent request to DB VM\n";
}

$dmzConsumerCallback = function ($msg) {
    echo " [x] Received request from DMZ\n";
    $request = json_decode($msg->body, true);
    if (isset($request['type'])) {
        switch ($request['type']) {
            case 'updateReminderSent':
                $correlationId = uniqid();
                global $correlationIdToResponseMap;
                $correlationIdToResponseMap[$correlationId] = true;
                forwardRequestToDBAsync($request, $correlationId);
                break;
            case 'fetchReservationReminders':
                $correlationId = uniqid();
                global $correlationIdToResponseMap;
                $correlationIdToResponseMap[$correlationId] = true;
                forwardRequestToDBAsync($request, $correlationId);
                break;
            default:
                echo " [!] Unhandled request type\n";
                break;
        }
    } else {
        echo " [!] Received message without a type specified\n";
    }
};

$channel->basic_consume($dmzToRmqQueue, '', false, true, false, false, $dmzConsumerCallback);

echo " [*] Waiting for messages. To exit press CTRL+C\n";
while (true) {
    $channel->wait(null, true);
}

$channel->close();
$connection->close();
?>
