<?php
/**
 * CASPA School OS – Database connection (PDO singleton)
 * Usage: $pdo = db();
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose credentials or stack traces in production
            $msg = defined('APP_DEBUG') && APP_DEBUG
                ? $e->getMessage()
                : 'Database connection failed.';
            http_response_code(500);
            die(json_encode(['ok' => false, 'error' => $msg]));
        }
    }

    return $pdo;
}
