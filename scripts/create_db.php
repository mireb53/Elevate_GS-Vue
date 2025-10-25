<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db = getenv('DB_DATABASE') ?: 'gradsmart';
try {
    $dsn = "mysql:host={$host};port={$port}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$db}' ensured on {$host}:{$port}\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error creating database: ".$e->getMessage()."\n");
    exit(1);
}
