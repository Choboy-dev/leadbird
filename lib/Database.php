<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = app_config()['database'];
        $host = $db['host'] ?? '';
        $name = $db['name'] ?? '';
        $user = $db['user'] ?? '';

        if ($host === '' || $name === '' || $user === '') {
            throw new RuntimeException('MySQL credentials are not set in .env (DB_HOST, DB_NAME, DB_USER)');
        }

        $port = (int) ($db['port'] ?? 3306);
        $charset = $db['charset'] ?? 'utf8mb4';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        self::$pdo = new PDO($dsn, $user, $db['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
