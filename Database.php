<?php
class Database
{
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn === null) {
            try {
                $host = getenv('DB_HOST') ?: '127.0.0.1';
                $port = getenv('DB_PORT') ?: '3306';
                $dbName = getenv('DB_NAME') ?: 'library_management';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASSWORD');
                $password = $password === false ? '' : $password;
                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
                $this->conn = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(503);
                exit('The application is temporarily unavailable. Please check the database configuration.');
            }
        }
        return $this->conn;
    }
}