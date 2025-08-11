<?php
/**
 * Konfiguracija podatkovne baze
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Uporabi environment variables ali fallback na default vrednosti
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'profesorji_db';
        $this->username = $_ENV['DB_USER'] ?? 'postgres';
        $this->password = $_ENV['DB_PASS'] ?? 'postgres';
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // PostgreSQL avtomatsko uporablja UTF-8, ni potrebe po "set names utf8"
        } catch(PDOException $exception) {
            echo "Napaka pri povezavi: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
