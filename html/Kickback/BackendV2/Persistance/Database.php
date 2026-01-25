<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Persistance;

use PDO;
use PDOException;
use Kickback\Backend\Config\ServiceCredentials;

/**
 * New database access class which uses PDO's insetad of mysqli
 * PDO's rely more heavily on the pattern: returns values for data, throws exceptions for errors.
 * the old myslqi based access is generally more status code focused.
 * Since the pattern for handling the status codes is throwing exceptions anyway if they are error codes, PDO makes a little more sense
 * 
 * PDO based database access has the other benefit of being more univserally compatible with multple types of Database as msyqli is specifically for mysql
 * (however this is a benefit we'll probably never see or care for)
 * 
 * Overall, PDO's seem just slightly better than mysqli. Becuase the beneifit is, at a resonable best, minor, old classes shouldn't be required to be refactored to
 * include this new approach however new classes which are made —or refactored— within the new backendV2 architcure, should use this class instead of the old
 * myslqi based one
 * 
 * 
 * I've also made this not use the singleton pattern as that can incure some nasty —and mostly hidden— side-effects.
 * Cheifly, if the singleton connection is used multiple times, it can trickle down the state of previous queries, transactions being a good example.
 * Moving this away from singleton —and static-ness— also allows for dependency injection to be used
 */
final class Database
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? self::createPdoFromConfig();
    }


    public function getConnection(): PDO
    {
        return $this->pdo;
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