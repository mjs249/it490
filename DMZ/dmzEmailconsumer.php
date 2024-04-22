#!/usr/bin/php
<?php

require_once '/home/mike/it490/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$host = '192.168.192.51';
$port = 5672;
$user = 'test';
$password = 'test';
$responseQueue = 'rmq_to_dmz_queue';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->queue_declare($responseQueue, false, true, false, false);

$responseConsumerCallback = function ($msg) use ($channel) {
    echo " [x] Received response: " . $msg->body . "\n";
    $response = json_decode($msg->body, true);

    if ($response['success'] && !empty($response['reservations'])) {
        foreach ($response['reservations'] as $reservation) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'sysintit490@gmail.com';
                    $mail->Password = '';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('sysintit490@gmail.com', 'Reservation System');
                    $mail->addAddress($reservation['email'], $reservation['username']);
                    $mail->isHTML(false);
                    $mail->Subject = "Reservation Reminder for " . htmlspecialchars($reservation['restaurant_name']);

                    $emailBody = "Hello " . htmlspecialchars($reservation['username']) . ",\n\n"
                        . "You have an upcoming reservation at " . htmlspecialchars($reservation['restaurant_name']) . ".\n"
                        . "Date: " . htmlspecialchars($reservation['reservation_date']) . "\n"
                        . "Time: " . htmlspecialchars($reservation['reservation_time']) . "\n"
                        . "Number of guests: " . htmlspecialchars($reservation['number_of_guests']) . "\n"
                        . (!empty($reservation['special_requests']) ? "Special requests: " . htmlspecialchars($reservation['special_requests']) . "\n" : "")
                        . "Confirmation code: " . htmlspecialchars($reservation['confirmation_code']) . "\n"
                        . "Restaurant address: " . htmlspecialchars($reservation['restaurant_address']) . "\n\n"
                        . "We look forward to your visit.";

                    $mail->Body = $emailBody;
                    $mail->send();
                    echo "Email reminder sent to {$reservation['email']}\n";

                    $phoneNumber = $reservation['phone'];
                    $apiKey = ''; 
                $data = array(
                    'phone' => $phoneNumber,
                    'message' => "Reminder: You have a reservation at " . $reservation['restaurant_name'] .
                        " on " . $reservation['reservation_date'] .
                        " at " . $reservation['reservation_time'] . ".",
                    'key' => $apiKey
                );

                $ch = curl_init('https://textbelt.com/text');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $responseArray = json_decode($response, true);
                     echo "SMS reminder sent to {$phoneNumber}\n";

                
                $updateMsg = new AMQPMessage(json_encode([
                    'type' => 'updateReminderSent',
                    'reservation_id' => $reservation['reservation_id'],
                    'reminder_sent' => 1
                ]), ['content_type' => 'application/json']);
                $channel->basic_publish($updateMsg, '', 'dmz_to_rmq_queue');
            } catch (Exception $e) {
                echo "Failed to send reminders for reservation ID: {$reservation['reservation_id']}. Error: {$e->getMessage()}\n";
            }
        }
    } else {
        echo "No reservation reminders to set\n";
    }
};

$channel->basic_consume($responseQueue, '', false, true, false, false, $responseConsumerCallback);

echo " [*] Waiting for reservation reminders. To exit press CTRL+C\n";
while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
