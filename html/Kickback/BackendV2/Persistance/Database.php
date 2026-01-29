<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Persistance;

use PDO;
use PDOException;
use Kickback\Backend\Config\ServiceCredentials;

final class Database
{
    private static ?PDO $conn = null;

    public static function getConnection(): ?PDO {
    // If a connection exists but has been closed (for example, by other code
    // calling \mysqli::close()), clear it so a fresh connection can be created.
    if (self::$conn !== null) {
        try {
            if (!@self::$conn->ping()) {
                self::$conn = null;
            }
        } catch (\Throwable $e) {
            // Certain operations on closed mysqli objects throw Errors instead of warnings.
            // Reset the connection so a new one can be created safely.
            self::$conn = null;
        }

        if (self::$conn === null) {
            self::$conn = static::createPdoFromConfig();
        }

        return self::$conn;
    }

    private static function createPdoFromConfig(): PDO
    {
        $host = ServiceCredentials::get('sql_server_host');
        $username = ServiceCredentials::get('sql_username');
        $password = ServiceCredentials::get('sql_password');
        $dbName = ServiceCredentials::get('sql_server_db_name');


        assert(is_string($host));
        assert(is_string($username));
        assert(is_string($password));
        assert(is_string($dbName));


        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbName);

        try 
        {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                ]);


            // Optional: if you truly need these (often unnecessary if DSN charset is set)
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");


            return $pdo;
        } 
        catch (PDOException $e) 
        {
            throw new \RuntimeException('Connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}