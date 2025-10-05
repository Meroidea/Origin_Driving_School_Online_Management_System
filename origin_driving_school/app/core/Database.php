<?php
/**
 * Database Connection Class
 * file path: app/core/Database.php
 * 
 * This class handles all database connections and operations using PDO
 * Implements singleton pattern to ensure only one connection exists
 * 
 * @author [Your Group Members Names and IDs]
 * @version 1.0
 */

class Database {
    
    /**
     * PDO instance
     * @var PDO
     */
    private static $instance = null;
    
    /**
     * PDO connection
     * @var PDO
     */
    private $connection;
    
    /**
     * Private constructor to prevent multiple instances
     * Establishes database connection using PDO
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log error and display user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact system administrator.");
        }
    }
    
    /**
     * Get singleton instance of Database
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a SELECT query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Query results
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Select Query Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Execute a SELECT query and return single row
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null Single row or null
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            error_log("Select One Query Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Execute an INSERT query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int|bool Last insert ID or false on failure
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $success = $stmt->execute($params);
            return $success ? $this->connection->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Insert Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute an UPDATE query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return bool Success status
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute a DELETE query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return bool Success status
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Delete Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute any query (for complex operations)
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return bool Success status
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get row count from last query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int Row count
     */
    public function count($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Count Query Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Success status
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if connection is active
     * 
     * @return bool Connection status
     */
    public function isConnected() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Prevent cloning of instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

?>