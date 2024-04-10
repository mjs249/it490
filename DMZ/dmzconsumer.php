#!/usr/bin/php
<?php

require_once '/home/mike/it490/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$API_KEY = 'ZHNgkhkCsyLHSu-cBx5EjZLl0HcJe88pwZEwnrNhazQ4bGc3_bV3ZIfXq3voImaV6wjxSZgDQeJowmqGN42wD3eZ9AYycjbagXjc5QTFsnpDM_iRcmNhnncb5gkLZnYx';
$API_HOST = "https://api.yelp.com";
$SEARCH_PATH = "/v3/businesses/search";

function request_yelp($url, $api_key) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key"]);

    $response = curl_exec($curl);
    if (!$response) {
        echo "Curl error: " . curl_error($curl) . PHP_EOL;
        return null;
    }
    curl_close($curl);

    return $response;
}

function performYelpSearch($requestData) {
    global $API_KEY, $API_HOST, $SEARCH_PATH;
    $term = $requestData['term'] ?? '';
    $location = $requestData['location'] ?? '';
    $radius = $requestData['radius'] ?? '';

    $url_params = http_build_query([
        'term' => $term,
        'location' => $location,
        'radius' => $radius,
        'limit' => 5,
    ]);


    $search_url = "$API_HOST$SEARCH_PATH?$url_params";
    $response = request_yelp($search_url, $API_KEY);

    if ($response) {
        return json_decode($response, true);
    }

    return ['error' => 'Failed to fetch Yelp data'];
}

$connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
$channel = $connection->channel();

$queue = 'dmz_queue';
$channel->queue_declare($queue, false, true, false, false);

$callback = function ($msg) {
    echo "Received Message: " . $msg->body . PHP_EOL;
    $requestData = json_decode($msg->body, true);
    $searchResults = performYelpSearch($requestData);

    if (isset($searchResults['businesses'])) {
        $formattedResponse = [
            'success' => true,
            'businesses' => $searchResults['businesses']
        ];
    } else {
        $formattedResponse = [
            'success' => false,
            'message' => 'Failed to retrieve Yelp search results'
        ];
    }

    $responseMsg = new AMQPMessage(
        json_encode($formattedResponse),
        ['correlation_id' => $msg->get('correlation_id')]
    );

    $msg->delivery_info['channel']->basic_publish(
        $responseMsg,
        '',
        $msg->get('reply_to')
    );

    echo "Sent Yelp search results back" . PHP_EOL;
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue, '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>

