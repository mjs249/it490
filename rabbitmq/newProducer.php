#!/usr/bin/php
<?php
require_once './vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Function to forward requests to the Database VM
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

// Function to forward requests to the DMZ VM
function forwardRequestToDMZ($request) {
    $connection = new AMQPStreamConnection('192.168.192.25', 5672, 'test', 'test');
    $channel = $connection->channel();

    list($callbackQueue, ,) = $channel->queue_declare("", false, false, true, false);
    $corrId = uniqid();

    $msg = new AMQPMessage(
        json_encode($request),
        array(
            'correlation_id' => $corrId,
            'reply_to' => $callbackQueue,
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        )
    );

    $channel->basic_publish($msg, '', 'dmz_queue');
    echo " [x] Sent request to DMZ VM\n";

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
                echo " [x] Received response from DMZ VM\n";
            }
        }
    );

    while (!$response) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();

    return $response;
}

function requestProcessor($request)
{
    echo "Received request\n";
    if (!isset($request['type'])) {
        return ['success' => false, 'message' => "ERROR: unsupported message type"];
    }

    $response = []; // Initialize response array

    switch ($request['type']) {
        case "login":
        case "register":
            // Forward login and register requests to the DB VM
            $response = forwardRequestToDB($request);
            break;
	case "yelpSearch":
	case "yelpradSearch":
    		$response = forwardRequestToDMZ($request);
    		    if ($response['success'] && isset($response['businesses'])) {
        // Prepare a new request for storing search results
        $storeRequest = [
            'type' => 'storeRestaurants',
            'restaurants' => $response['businesses']
        ];
        // Forward to DB VM for storage
        $storeResponse = forwardRequestToDB($storeRequest);
    }
    break;

        case "submitReview":
            // Handle review submission, forwarding it to the DB VM
            $response = forwardRequestToDB($request);
            break;

        case "retrieveReviews":
            // Forward login and register requests to the DB VM
            $response = forwardRequestToDB($request);
            break;

        case "makeReservation":
            // Handle making a reservation, forwarding it to the DB VM
            $response = forwardRequestToDB($request);
            break;

        default:
            $response = ['success' => false, 'message' => "Request type not handled"];
            break;
    }

    return $response;
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
$server->process_requests('requestProcessor');
?>
