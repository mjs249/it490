<?php

require_once '/home/mike/it490/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function getLatestVersion($packageName, $pdo) {
    $stmt = $pdo->prepare("SELECT MAX(version) as max_version FROM packages WHERE package_name = ?");
    $stmt->execute([$packageName]);
    $row = $stmt->fetch();
    return $row ? $row['max_version'] : 0;
}

function addPackageVersion($packageName, $version, $status, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO packages (package_name, version, status) VALUES (?, ?, ?)");
    $stmt->execute([$packageName, $version, $status]);
    echo "Package version added successfully: $packageName (v$version) with status: $status\n";
}

function processTarball($filename, $pdo) {
    preg_match('/^(.+?)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.tar\.gz$/', $filename, $matches);
    $package_name = $matches[1] ?? '';

    if ($package_name === '') {
        echo "Could not determine package name from filename: $filename\n";
        return;
    }

    $latest_version = getLatestVersion($package_name, $pdo);
    $new_version = is_null($latest_version) ? 1 : $latest_version + 1;
    addPackageVersion($package_name, $new_version, 'new', $pdo);

    $new_filename = "{$package_name}_v{$new_version}.tar.gz";
    $processedDirectoryPath = "/home/mike/Desktop/processed/";
    $newFilePath = $processedDirectoryPath . $new_filename;

    if (!is_dir($processedDirectoryPath)) {
        mkdir($processedDirectoryPath, 0777, true);
    }

    if (rename("/home/mike/Desktop/{$filename}", $newFilePath)) {
        echo "Processed {$filename}: now stored as {$new_filename} in 'processed' directory.\n";
    } else {
        echo "Failed to move {$filename} to 'processed' directory.\n";
    }
}

while (true) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=deploymentDB;charset=utf8mb4", 'new', 'MikeNuhaJames123!', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $tarballs = glob("/home/mike/Desktop/*.tar.gz");
        foreach ($tarballs as $tarball) {
            processTarball(basename($tarball), $pdo);
        }
    } catch (PDOException $e) {
        echo "Database connection error: " . $e->getMessage() . "\n";
    }
    
    sleep(6);
}

?>
