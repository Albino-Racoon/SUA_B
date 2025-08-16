<?php
/**
 * Model za serverless operacije
 */

require_once __DIR__ . '/../config/database.php';

class ServerlessOperations {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Analytics operacije
    public function addLog($action, $user_id, $details) {
        $query = "INSERT INTO serverless_logs (action, user_id, details, created_at) 
                  VALUES (:action, :user_id, :details, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":details", $details);
        
        return $stmt->execute();
    }
    
    public function getLogs($limit = 100) {
        $query = "SELECT * FROM serverless_logs ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getLogStats() {
        $query = "SELECT 
                    COUNT(*) as total_logs,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logs,
                    action,
                    COUNT(*) as action_count
                  FROM serverless_logs 
                  GROUP BY action 
                  ORDER BY action_count DESC 
                  LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_logs' => $stats[0]['total_logs'] ?? 0,
            'today_logs' => $stats[0]['today_logs'] ?? 0,
            'popular_actions' => array_column($stats, 'action')
        ];
    }
    
    public function updateLog($id, $action, $user_id, $details) {
        $query = "UPDATE serverless_logs 
                  SET action = :action, user_id = :user_id, details = :details, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":details", $details);
        
        return $stmt->execute();
    }
    
    public function deleteLog($id) {
        $query = "DELETE FROM serverless_logs WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    // Notification operacije
    public function addNotification($user_id, $message, $type, $priority = 'normal') {
        $query = "INSERT INTO serverless_notifications (user_id, message, type, priority, created_at) 
                  VALUES (:user_id, :message, :type, :priority, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":priority", $priority);
        
        return $stmt->execute();
    }
    
    public function getNotifications($user_id = null, $unread_only = false) {
        $query = "SELECT * FROM serverless_notifications";
        $conditions = [];
        $params = [];
        
        if ($user_id) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if ($unread_only) {
            $conditions[] = "read_status = 0";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateNotification($id, $read_status, $message = null) {
        $query = "UPDATE serverless_notifications SET read_status = :read_status";
        $params = [':read_status' => $read_status];
        
        if ($message) {
            $query .= ", message = :message";
            $params[':message'] = $message;
        }
        
        $query .= ", updated_at = NOW() WHERE id = :id";
        $params[':id'] = $id;
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function deleteNotification($id) {
        $query = "DELETE FROM serverless_notifications WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    public function getNotificationStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN read_status = 0 THEN 1 END) as unread,
                    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority
                  FROM serverless_notifications";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Ustvari tabele, če ne obstajajo
    public function createTables() {
        // Tabela za logove
        $logs_table = "CREATE TABLE IF NOT EXISTS serverless_logs (
            id SERIAL PRIMARY KEY,
            action VARCHAR(255) NOT NULL,
            user_id INTEGER NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT NOW(),
            updated_at TIMESTAMP DEFAULT NOW()
        )";
        
        // Tabela za notifikacije
        $notifications_table = "CREATE TABLE IF NOT EXISTS serverless_notifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(100) DEFAULT 'info',
            priority VARCHAR(50) DEFAULT 'normal',
            read_status BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT NOW(),
            updated_at TIMESTAMP DEFAULT NOW()
        )";
        
        try {
            $this->conn->exec($logs_table);
            $this->conn->exec($notifications_table);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating tables: " . $e->getMessage());
            return false;
        }
    }
}
?>
