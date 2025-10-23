<?php
declare(strict_types=1);

if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo instanceof PDO) return $pdo;

        $dbPath = __DIR__ . '/../storage/bilet.db';
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }
}