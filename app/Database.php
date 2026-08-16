<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connect(array $config): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $database = is_array($config['database'] ?? null) ? $config['database'] : [];
        $host = trim((string)($database['host'] ?? '127.0.0.1'));
        $port = max(1, (int)($database['port'] ?? 3306));
        $name = trim((string)($database['name'] ?? ''));
        $username = (string)($database['username'] ?? '');
        $password = (string)($database['password'] ?? '');
        $charset = trim((string)($database['charset'] ?? 'utf8mb4'));

        if ($name === '' || $username === '') {
            throw new \RuntimeException('MySQL database configuration is incomplete.');
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $charset)) {
            throw new \RuntimeException('Invalid MySQL charset configuration.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        self::$connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
