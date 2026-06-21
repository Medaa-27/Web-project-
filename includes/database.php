<?php
// Database connection class
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;
    private $error;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $dsn = "mysql:host=" . $this->host . ";port=3306;dbname=" . $this->dbname . ";charset=utf8mb4";
        
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = "Connection failed: " . $e->getMessage();
            error_log($this->error);
            die("A database connection error occurred. Please try again later.");
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prepare statement
    public function prepare($sql) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $errorInfo = $this->conn->errorInfo();
                error_log("PDO Prepare Failed: " . ($errorInfo[2] ?? "Unknown error") . " | SQL: " . $sql);
            }
            return $stmt;
        } catch (PDOException $e) {
            $this->error = "Prepare failed: " . $e->getMessage();
            error_log($this->error . " | SQL: " . $sql);
            return false;
        }
    }
    
    // Execute query
    public function execute($stmt, $params = []) {
        if (!$stmt) {
            throw new Exception('Invalid PDO statement provided to execute()');
        }
        try {
            $stmt->execute($params);
            return $stmt; // Return the statement object
        } catch (PDOException $e) {
            $this->error = "Query failed: " . $e->getMessage();
            throw new Exception($this->error);
        }
    }
    
    // Get single row
    public function getSingle($stmt, $params = []) {
        if (!$stmt) {
            return null;
        }
        $result = $this->execute($stmt, $params);
        if (!$result) {
            return null;
        }
        return $stmt->fetch();
    }
    
    // Get multiple rows
    public function getMultiple($stmt, $params = []) {
        if (!$stmt) {
            return [];
        }
        $result = $this->execute($stmt, $params);
        return $stmt->fetchAll();
    }
    
    // Get row count
    public function rowCount($stmt, $params = []) {
        $this->execute($stmt, $params);
        return $stmt->rowCount();
    }
    
    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Backwards compatibility method used in some API code
    public function getLastInsertId() {
        return $this->lastInsertId();
    }
    
    // Check if a table has a specific column
    public function columnExists($table, $column) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->error = "Column check failed: " . $e->getMessage();
            return false;
        }
    }

    // Begin transaction
    public function beginTransaction() {
        if ($this->conn->inTransaction()) {
            return true; // Already in a transaction
        }
        try {
            return $this->conn->beginTransaction();
        } catch (PDOException $e) {
            $this->error = "Transaction failed to begin: " . $e->getMessage();
            return false;
        }
    }
    
    // Commit transaction
    public function commit() {
        if (!$this->conn->inTransaction()) {
            return true; // No transaction to commit
        }
        try {
            return $this->conn->commit();
        } catch (PDOException $e) {
            $this->error = "Transaction failed to commit: " . $e->getMessage();
            return false;
        }
    }
    
    // Rollback transaction
    public function rollback() {
        if (!$this->conn->inTransaction()) {
            return true; // No transaction to rollback
        }
        try {
            return $this->conn->rollBack();
        } catch (PDOException $e) {
            $this->error = "Transaction failed to rollback: " . $e->getMessage();
            return false;
        }
    }

    // Check if in transaction
    public function inTransaction() {
        return $this->conn->inTransaction();
    }

    // Get last error message
    public function getLastError() {
        return $this->error;
    }
}

// Create global database instance
$db = new Database();
$conn = $db->getConnection();
?>