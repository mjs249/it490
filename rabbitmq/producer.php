#!/usr/bin/php
<?php
require_once './vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function forwardRequestToDB($request)
{
    $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
    $channel = $connection->channel();

    // Declare a queue for the reply.
    list($callbackQueue, ,) = $channel->queue_declare("", false, false, true, false);

    // Generate a unique correlation ID for this request
    $corrId = uniqid();

    $msg = new AMQPMessage(
        json_encode($request),
        array(
            'correlation_id' => $corrId,
            'reply_to' => $callbackQueue,
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        )
    );

    $channel->basic_publish($msg, '', 'db_queue');

    echo " [x] Sent request to DB VM\n";

    // Wait for the response
    $response = null;
    $channel->basic_consume(
        $callbackQueue,
        '',
        false,
        true,
        false,
        false,
        function ($rep) use (&$response, $corrId) {
            if ($rep->get('correlation_id') == $corrId) {
                $response = json_decode($rep->body, true);
            }
        }
    );

    // Wait for a response with the correct correlation ID
    while (!$response) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();

    return $response; // Return the response received from the consumer
}

function requestProcessor($request)
{
    echo "Received request\n";
    if (!isset($request['type'])) {
        return ['success' => false, 'message' => "ERROR: unsupported message type"];
    }

    // Forward the request and wait for the response
    $response = forwardRequestToDB($request);

    // Return the actual response
    return $response;
}


$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
$server->process_requests('requestProcessor');
?>


