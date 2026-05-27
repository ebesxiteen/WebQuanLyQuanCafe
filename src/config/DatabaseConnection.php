<?php

require_once __DIR__ . '/Env.php';

class DatabaseConnection
{
    protected ?mysqli $connection = null;

    public function __construct()
    {
        if ($this->connection !== null) {
            return;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->connection = new mysqli(
                Env::get('DB_HOST', 'localhost'),
                Env::get('DB_USERNAME', 'root'),
                Env::get('DB_PASSWORD', ''),
                Env::get('DB_DATABASE', 'COFFESHOP'),
                (int) Env::get('DB_PORT', '3306')
            );

            $this->connection->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            throw new RuntimeException('Database connection failed.');
        }
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    public function closeConnection(): void
    {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}
