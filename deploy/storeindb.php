<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home/mike/it490/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = 'localhost';
$db = 'deploymentDB';
$user = 'new';
$pass = 'MikeNuhaJames123!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function getLatestVersion($packageName, $pdo) {
    $stmt = $pdo->prepare("SELECT MAX(version) as max_version FROM packages WHERE package_name = ?");
    $stmt->execute([$packageName]);
    $row = $stmt->fetch();
    return $row ? $row['max_version'] : 0;
}

function addPackageVersion($packageName, $version, $status, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO packages (package_name, version, status) VALUES (?, ?, ?)");
    $stmt->execute([$packageName, $version, $status]);
}

function processTarball($filename, $pdo) {
    echo "Processing file: $filename\n";
    preg_match('/^(.+?)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.tar\.gz$/', $filename, $matches);
    $package_name = $matches[1] ?? '';
    if ($package_name === '') {
        echo "Could not determine package name from filename: $filename\n";
        return;
    }
    $latest_version = getLatestVersion($package_name, $pdo);
    $new_version = $latest_version + 1;
    addPackageVersion($package_name, $new_version, 'new', $pdo);
    $new_filename = "{$package_name}_v{$new_version}.tar.gz";
    $storagePath = "/home/mike/Desktop/{$new_filename}";
    rename("/home/mike/Desktop/{$filename}", $storagePath);
    echo "Processed {$filename}: now stored as {$new_filename}\n";
}

$desktopDirectory = '/home/mike/Desktop';
while (true) {
    $files = scandir($desktopDirectory);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'tar.gz') {
            processTarball($file, $pdo);
        }
    }
    sleep(1);
}

?>
