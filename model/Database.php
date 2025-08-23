<?php
// model/Database.php
final class Database {
    private static ?PDO $pdo = null;

    private function __construct() {}

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dsn = "mysql:host=localhost;dbname=cakeshop;charset=utf8mb4";
            $user = "root";
            $pass = "";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }
}
