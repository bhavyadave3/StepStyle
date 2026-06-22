<?php

require_once __DIR__ . '/config.php';

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        try {

            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

        } catch (PDOException $e) {

            die("Database Connection Failed: " . $e->getMessage());

        }
    }

    public function connect(): PDO
    {
        return $this->pdo;
    }
}

$db = (new Database())->connect();