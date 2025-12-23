<?php
// Set timezone for Vietnam (Asia/Ho_Chi_Minh = UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load environment variables
require_once __DIR__ . '/../../config/env.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'bus_booking');
        $this->username = env('DB_USER', 'root');
        $this->password = env('DB_PASS', '');
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set MySQL timezone to Vietnam (UTC+7)
            $this->conn->exec("SET time_zone = '+07:00'");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
