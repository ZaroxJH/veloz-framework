<?php

namespace Veloz\Database;

use PDO;

class DB
{
    private static PDO $pdo;
    public static string $databaseError;

    public static function connect($host = null, $port = null, $dbname = null, $username = null, $password = null)
    {
        if (is_array($host)) {
            // If the first argument is an array, use it to set all variables
            $credentials = $host;
            $host = $credentials['DB_HOST'];
            $port = $credentials['DB_PORT'];
            $dbname = $credentials['DB_NAME'];
            $username = $credentials['DB_USERNAME'];
            $password = $credentials['DB_PASSWORD'];
        }

        $host = $host ?? $_ENV['DB_HOST'];
        $port = $port ?? $_ENV['DB_PORT'];
        $dbname = $dbname ?? $_ENV['DB_NAME'];
        $username = $username ?? $_ENV['DB_USERNAME'];
        $password = $password ?? $_ENV['DB_PASSWORD'];

        try {
            self::$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            self::$databaseError = $e->getMessage();
            var_dump($e->getMessage());
            exit;
            return false;
        }

        // Return false if the connection failed
        if (!self::$pdo) {
            self::$databaseError = 'An error occured while trying to connect to the database.';
            return false;
        }

        return true;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    public static function createDatabase()
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];
        $dbname = $_ENV['DB_NAME'];

        try {
            $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            self::$pdo = $pdo;
        } catch (\PDOException $e) {
            self::$databaseError = $e->getMessage();
            return false;
        }

        return true;
    }

    public static function select(string $query, array $params = []): array
    {
        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert(string $query, array $params = []): int
    {
        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);
        return self::$pdo->lastInsertId();
    }

    public static function update(string $query, array $params = []): int
    {
        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function delete(string $query, array $params = []): int
    {
        return self::update($query, $params);
    }

    public static function query(string $query, array $params = []): bool
    {
        return self::update($query, $params);
    }
}
