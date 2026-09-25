<?php

declare(strict_types=1);

namespace Wasla\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Class Database
 * 
 * Manages the PDO connection to the MySQL database.
 * Supports loading credentials from a local .env file with developer-friendly fallbacks.
 */
class Database {
    private static ?PDO $instance = null;

    /**
     * Helper to load .env variables into the environment if the file exists.
     */
    public static function loadEnv(string $dir = __DIR__ . '/../'): void {
        $envFile = $dir . '.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            // Ignore comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split on first '='
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                
                // Only set if not already set in external environment
                if (getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }

    /**
     * Establishes and returns a singleton PDO connection.
     * 
     * @return PDO
     * @throws RuntimeException If connection fails.
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Load environment variables first
            self::loadEnv();

            // Fetch env vars with standard fallback defaults
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_NAME') ?: 'wasla';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            // Define secure and optimized connection options
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 3,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Keep credentials secure: do not include the raw DSN/password in exception message
                throw new RuntimeException("Database connection failed. Please check your configuration: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    /**
     * Establishes and returns a singleton PDO connection or null if it fails.
     * 
     * @return PDO|null
     */
    public static function getConnectionOrNull(): ?PDO {
        try {
            return self::getConnection();
        } catch (RuntimeException $e) {
            return null;
        }
    }
}
