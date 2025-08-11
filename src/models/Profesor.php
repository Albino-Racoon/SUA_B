<?php
/**
 * Model za profesorje
 */

require_once __DIR__ . '/../config/database.php';

class Profesor {
    private $conn;
    private $table_name = "profesorji";
    private $komentarji_table = "komentarji";
    
    public $id;
    public $ime;
    public $url;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Pridobi vse profesorje
    public function getAll($limit = null, $offset = null) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY ime";
        
        if ($limit) {
            $query .= " LIMIT " . $limit;
        }
        if ($offset) {
            $query .= " OFFSET " . $offset;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Pridobi vse profesorje z njihovimi komentarji
    public function getAllWithComments() {
        $query = "SELECT 
                    p.id,
                    p.ime,
                    p.url,
                    p.created_at,
                    p.updated_at,
                    COUNT(k.id) as stevilo_komentarjev
                  FROM " . $this->table_name . " p
                  LEFT JOIN " . $this->komentarji_table . " k ON p.id = k.profesor_id
                  GROUP BY p.id, p.ime, p.url, p.created_at, p.updated_at
                  ORDER BY stevilo_komentarjev DESC, p.ime";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Pridobi profesorja z vsemi komentarji
    public function getByIdWithComments($id) {
        $query = "SELECT 
                    p.id,
                    p.ime,
                    p.url,
                    p.created_at,
                    p.updated_at,
                    k.id as komentar_id,
                    k.komentar as komentar_tekst,
                    k.created_at as komentar_created_at
                  FROM " . $this->table_name . " p
                  LEFT JOIN " . $this->komentarji_table . " k ON p.id = k.profesor_id
                  WHERE p.id = :id
                  ORDER BY k.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Pridobi profesorja po ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Pridobi naključne profesorje za kviz
    public function getRandomForQuiz($count = 10, $selected_professors = []) {
        if (!empty($selected_professors)) {
            $placeholders = str_repeat('?,', count($selected_professors) - 1) . '?';
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE id IN ($placeholders) 
                     ORDER BY RANDOM() LIMIT ?";
            $params = array_merge($selected_professors, [$count]);
        } else {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY RANDOM() LIMIT ?";
            $params = [$count];
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Pridobi naključne komentarje za kviz
    public function getRandomComments($count = 10, $professor_ids = []) {
        if (!empty($professor_ids)) {
            $placeholders = str_repeat('?,', count($professor_ids) - 1) . '?';
            $query = "SELECT k.id, k.komentar, p.ime as profesor_ime 
                     FROM " . $this->komentarji_table . " k
                     JOIN " . $this->table_name . " p ON k.profesor_id = p.id
                     WHERE k.profesor_id IN ($placeholders)
                     ORDER BY RANDOM() LIMIT ?";
            $params = array_merge($professor_ids, [$count]);
        } else {
            $query = "SELECT k.id, k.komentar, p.ime as profesor_ime 
                     FROM " . $this->komentarji_table . " k
                     JOIN " . $this->table_name . " p ON k.profesor_id = p.id
                     ORDER BY RANDOM() LIMIT ?";
            $params = [$count];
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Pridobi vse profesorje za izbiro
    public function getAllForSelection() {
        $query = "SELECT id, ime FROM " . $this->table_name . " ORDER BY ime";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ustvari novega profesorja
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (ime, url) VALUES (:ime, :url)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":ime", $this->ime);
        $stmt->bindParam(":url", $this->url);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // Posodobi profesorja
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET ime = :ime, url = :url, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":ime", $this->ime);
        $stmt->bindParam(":url", $this->url);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    // Izbriši profesorja
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    // Išči profesorje
    public function search($search_term) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ime ILIKE :search_term ORDER BY ime";
        $stmt = $this->conn->prepare($query);
        $search_pattern = "%$search_term%";
        $stmt->bindParam(":search_term", $search_pattern);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Preveri, ali profesor obstaja
    public function exists($id) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}
?>
