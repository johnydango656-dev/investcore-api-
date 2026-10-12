<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = self::buildDsn();
            $user = getenv('DB_USER') ?: 'postgres';
            $password = getenv('DB_PASSWORD') ?: 'postgres';

            // Check if DATABASE_URL is provided (Render, Railway, Heroku, Neon, Supabase)
            $dbUrl = getenv('DATABASE_URL');
            if ($dbUrl) {
                $parsed = parse_url($dbUrl);
                if ($parsed && isset($parsed['host'], $parsed['path'])) {
                    $host = $parsed['host'];
                    $port = $parsed['port'] ?? 5432;
                    $dbName = ltrim($parsed['path'], '/');
                    $user = $parsed['user'] ?? $user;
                    $password = $parsed['pass'] ?? $password;
                    $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode=prefer";
                }
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Database connection failed: ' . $e->getMessage(),
                ]);
                exit;
            }
        }

        return self::$instance;
    }

    private static function buildDsn(): string
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '5432';
        $dbName = getenv('DB_NAME') ?: 'investment_db';
        $sslMode = getenv('DB_SSL_MODE') ?: 'prefer';

        return "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$sslMode}";
    }
}