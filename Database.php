<?php
class Database
{
    private string $host = "127.0.0.1";
    private string $dbName = "library_management";
    private string $username = "root";
    private string $password = "";
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}