<?php

/**
 * Database — Singleton MySQLi
 * Une seule connexion partagée dans toute l'application.
 */
class Database
{
    private static ?mysqli $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): mysqli
    {
        if (self::$instance === null) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($conn->connect_error) {
                error_log("DB connection failed: " . $conn->connect_error);
                die("Service temporairement indisponible.");
            }

            $conn->set_charset('utf8mb4');
            self::$instance = $conn;
        }

        return self::$instance;
    }
}
