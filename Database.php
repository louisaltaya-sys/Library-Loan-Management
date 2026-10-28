<?php
class Database
{
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn === null) {
            try {
                $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
                $config = is_file($configFile) ? require $configFile : [];
                if (!is_array($config)) {
                    error_log('Database configuration must return an array.');
                    http_response_code(503);
                    exit('The application is temporarily unavailable. Please check the database configuration.');
                }

                $host = $config['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
                $port = $config['port'] ?? (getenv('DB_PORT') ?: '3306');
                $db = $config['name'] ?? (getenv('DB_NAME') ?: 'library_management');
                $user = $config['user'] ?? (getenv('DB_USER') ?: 'root');
                $pass = $config['password'] ?? (getenv('DB_PASSWORD') ?: '');

                if (!is_scalar($host) || !is_scalar($port) || !is_scalar($db)
                    || !is_scalar($user) || !is_scalar($pass)
                    || !ctype_digit((string) $port)
                    || (int) $port < 1 || (int) $port > 65535) {
                    error_log('Database configuration contains invalid values.');
                    http_response_code(503);
                    exit('The application is temporarily unavailable. Please check the database configuration.');
                }

                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $host,
                    (int) $port,
                    $db
                );
                $this->conn = new PDO($dsn, $user, $pass, [
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