<?php

declare(strict_types=1);

$databaseHost = getenv('EATING_GO_DB_HOST') ?: '127.0.0.1';
$databasePort = getenv('EATING_GO_DB_PORT') ?: '3306';
$databaseName = getenv('EATING_GO_DB_NAME') ?: 'eating_go';
$databaseUser = getenv('EATING_GO_DB_USER') ?: 'root';
$databasePassword = getenv('EATING_GO_DB_PASSWORD') ?: '';

$databaseDsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $databaseHost,
    $databasePort,
    $databaseName
);

try {
    $pdo = new PDO($databaseDsn, $databaseUser, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    throw new RuntimeException('Koneksi database gagal.', 0, $exception);
}