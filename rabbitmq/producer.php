#!/usr/bin/php
<?php
require_once '/home/mike/oldit490/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function forwardRequestToDB($request)
{
    $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
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

    $channel->basic_publish($msg, '', 'db_queue');

    echo " [x] Sent request to DB VM\n";

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

    while (!$response) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();

    return $response;
}

function forwardRequestToDMZ($request) {
    $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
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
    if (!isset($request['type'])) {
        return ['success' => false, 'message' => "ERROR: unsupported message type"];
    }

    $response = [];

    switch ($request['type']) {
        case "login":
        case "register":
            $response = forwardRequestToDB($request);
            break;

        case "yelpSearch":
        case "yelpradSearch":
            $logSearchQuery = [
                'type' => "logSearchQuery",
                'username' => $request['username'],
                'term' => $request['term'],
                'location' => $request['location'],
                'radius' => $request['radius'] ?? null,
            ];
            $logResponse = forwardRequestToDB($logSearchQuery);
            unset($request['username']);
            $response = forwardRequestToDMZ($request);
            if ($response['success'] && isset($response['businesses'])) {
                $storeRequest = [
                    'type' => 'storeRestaurants',
                    'restaurants' => $response['businesses']
                ];
                $storeResponse = forwardRequestToDB($storeRequest);
            }
            break;

        case "getRandomSearchQuery":
            $dbRequest = [
                'type' => "fetchRandomSearchQuery",
                'username' => $request['username'],
            ];
            $randomQueryResponse = forwardRequestToDB($dbRequest);
            if ($randomQueryResponse['success'] && isset($randomQueryResponse['query'])) {
                $searchTerm = $randomQueryResponse['query']['term'];
                $searchLocation = $randomQueryResponse['query']['location'];
                $dmzRequest = [
                    'type' => "yelpSearch",
                    'term' => $searchTerm,
                    'location' => $searchLocation,
                ];
                $dmzResponse = forwardRequestToDMZ($dmzRequest);
                if ($dmzResponse['success'] && isset($dmzResponse['businesses'])) {
                    $response = [
                        'success' => true,
                        'businesses' => $dmzResponse['businesses']
                    ];
                } else {
                    $response = ['success' => false, 'message' => "Failed to retrieve recommendations from DMZ"];
                }
            } else {
                $response = ['success' => false, 'message' => "Failed to fetch random search query from DB"];
            }
            break;

        case "fetchReservationsForReminders":
            $dbResponse = forwardRequestToDB([
                'type' => "fetchReservationsForReminders",
            ]);
            $responseMsg = new AMQPMessage(
                json_encode($dbResponse),
                array('content_type' => 'application/json', 'delivery_mode' => 2)
            );
            $channel->basic_publish($responseMsg, '', 'email_notifications_queue');
            break;

        case "submitReview":
        case "retrieveReviews":
        case "makeReservation":
            $response = forwardRequestToDB($request);
            break;

        case "retrieveFavorites":
            $response = forwardRequestToDB($request);
            break;

	case "addFavorite":
		$response= forwardRequestToDB($request);
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
