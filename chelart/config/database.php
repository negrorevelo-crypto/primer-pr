<?php
declare(strict_types=1);

function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = chelart_setting('DB_HOST', 'localhost');
    $port = chelart_setting('DB_PORT', '3306');
    $name = chelart_setting('DB_NAME');
    $user = chelart_setting('DB_USER');
    $password = chelart_setting('DB_PASSWORD');

    if ($name === '' || $user === '' || $password === '') {
        throw new RuntimeException('Database environment variables are not configured.');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");

    return $pdo;
}

