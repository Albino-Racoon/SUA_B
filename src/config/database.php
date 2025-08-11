<?php
/**
 * Konfiguracija podatkovne baze
 */

class Database {
    private $host = "db";
    private $db_name = "profesorji_db";
    private $username = "postgres";
    private $password = "postgres";
    private $conn;
    
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
