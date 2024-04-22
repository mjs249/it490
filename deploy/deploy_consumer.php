#!/usr/bin/php
<?php

require_once '/home/mike/it490/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = 'localhost';
$db = 'deploymentDB';
$user = 'new';
$pass = 'MikeNuhaJames123!';
$charset = 'utf8mb4';

$connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
$channel = $connection->channel();

$channel->queue_declare('qa_to_deployment', false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ($msg) use ($host, $db, $user, $pass, $charset) {
    echo " [x] Received ", $msg->body, "\n";
    $data = json_decode($msg->body, true);

    if (!$data) {
        echo " [!] Error decoding message\n";
        return;
    }

    $status = $data['status'] ?? '';
    $packageName = $data['package'] ?? '';
    $version = $data['version'] ?? '';

    echo " [x] Package: $packageName, Version: $version, Status: $status\n";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($status === 'good') {
            updatePackageStatus($pdo, $packageName, $version, $status);
            transferToProduction($packageName, $version, determineProdIp($packageName));
        } elseif ($status === 'bad') {
            rollbackToPreviousGoodVersion($pdo, $packageName, $version);
        }
    } catch (PDOException $e) {
        echo "Error connecting to database: " . $e->getMessage() . "\n";
    } finally {
        if (isset($pdo)) {
            $pdo = null;
        }
    }
};

$channel->basic_consume('qa_to_deployment', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();

function determineQAIp($packageName) {
    switch ($packageName) {
        case 'rmq_files':
            return '192.168.192.232';
        case 'dmz_files':
            return '192.168.192.52';
        case 'frontend_files':
            return '192.168.192.185';
    }
}

function determineProdIp($packageName) {
    switch ($packageName) {
        case 'rmq_files':
            return '192.168.192.25';
        case 'dmz_files':
            return '192.168.192.165';
        case 'frontend_files':
            return '192.168.192.136';
    }
}

function transferToProduction($packageName, $version, $destinationIp) {
    $processedDir = '/home/mike/Desktop/processed/';
    $productionDestDir = "mike@{$destinationIp}:/home/mike/";
    $tarFile = "{$processedDir}{$packageName}_v{$version}.tar.gz";

    if (file_exists($tarFile)) {
        $scpCommand = "scp $tarFile $productionDestDir";
        echo "Transferring $tarFile to production at $destinationIp...\n";
        shell_exec($scpCommand);
        echo "Transfer completed to $destinationIp.\n";
    } else {
        echo "Tar file for $packageName version $version does not exist.\n";
    }
}

function updatePackageStatus($pdo, $packageName, $version, $newStatus) {
    $sql = "UPDATE packages SET status = :newStatus WHERE package_name = :packageName AND version = :version";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':newStatus' => $newStatus, ':packageName' => $packageName, ':version' => $version]);
    echo "Updated status for $packageName version $version to $newStatus.\n";
}

function transferToQA($packageName, $version, $destinationIp) {
    $processedDir = '/home/mike/Desktop/processed/';
    $qaDestDir = "mike@{$destinationIp}:/home/mike/";
    $tarFile = "{$processedDir}{$packageName}_v{$version}.tar.gz";

    if (file_exists($tarFile)) {
        $scpCommand = "scp $tarFile $qaDestDir";
        echo "Transferring $tarFile to QA at $destinationIp...\n";
        shell_exec($scpCommand);
        echo "Transfer completed to QA at $destinationIp.\n";
    } else {
        echo "Tar file for $packageName version $version does not exist.\n";
    }
}

function rollbackToPreviousGoodVersion($pdo, $packageName, $badVersion) {
    $stmt = $pdo->prepare("SELECT version FROM packages WHERE package_name = ? AND status = 'good' AND version < ? ORDER BY version DESC LIMIT 1");
    $stmt->execute([$packageName, $badVersion]);
    $row = $stmt->fetch();

    if ($row) {
        $goodVersion = $row['version'];
        transferToQA($packageName, $goodVersion, determineQAIp($packageName));
        transferToProduction($packageName, $goodVersion, determineProdIp($packageName));
        $updateStmt = $pdo->prepare("UPDATE packages SET status = 'bad-rolledback' WHERE package_name = ? AND version = ?");
        $updateStmt->execute([$packageName, $badVersion]);
        echo "Rolled back $packageName from version $badVersion to $goodVersion in QA and production.\n";
    } else {
        echo "No 'good' version found to roll back to for package $packageName.\n";
    }
}
?>

