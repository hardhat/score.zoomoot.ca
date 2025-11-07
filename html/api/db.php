<?php
/**
 * Database Connection Handler
 * 
 * Provides SQLite3 database connection and basic operations
 */

// Load environment configuration
require_once __DIR__ . '/../../env.php';

class Database {
    private $db;
    private static $instance = null;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Connect to SQLite database
     */
    private function connect() {
        try {
            // Ensure the score directory exists
            $scoreDir = dirname(DB_PATH);
            if (!is_dir($scoreDir)) {
                if (!mkdir($scoreDir, 0755, true)) {
                    throw new Exception("Failed to create score directory: $scoreDir");
                }
            }
            
            // Open SQLite database
            $this->db = new SQLite3(DB_PATH);
            
            // Enable foreign key constraints
            $this->db->exec('PRAGMA foreign_keys = ON');
            
            // Set WAL mode for better concurrency
            $this->db->exec('PRAGMA journal_mode = WAL');
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Get the database connection
     */
    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Execute a query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $this->db->lastErrorMsg());
            }
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $result = $stmt->execute();
            
            if ($result === false) {
                throw new Exception("Query execution failed: " . $this->db->lastErrorMsg());
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute a query and return all results as array
     */
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $rows = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Execute a query and return single row
     */
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    /**
     * Execute an insert/update/delete query
     */
    public function execute($sql, $params = []) {
        $this->query($sql, $params);
        return $this->db->changes();
    }
    
    /**
     * Get last inserted row ID
     */
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->exec('BEGIN TRANSACTION');
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->db->exec('COMMIT');
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->db->exec('ROLLBACK');
    }
    
    /**
     * Close database connection
     */
    public function close() {
        if ($this->db) {
            $this->db->close();
            $this->db = null;
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {}
}

/**
 * Helper function to get database instance
 */
function getDB() {
    return Database::getInstance();
}

?>