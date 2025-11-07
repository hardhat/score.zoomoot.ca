<?php
/**
 * Database Schema Initialization
 * 
 * Creates the required tables for the zoomoot score tracking system
 */

require_once __DIR__ . '/db.php';

class DatabaseInit {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Initialize all database tables
     */
    public function initializeSchema() {
        try {
            $this->db->beginTransaction();
            
            $this->createActivityTable();
            $this->createTeamTable();
            $this->createScoreTable();
            $this->insertSampleData();
            
            $this->db->commit();
            
            echo "Database schema initialized successfully!\n";
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Schema initialization failed: " . $e->getMessage());
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create activity table
     */
    private function createActivityTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS activity (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activity_name TEXT NOT NULL UNIQUE CHECK(length(trim(activity_name)) > 0),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->db->execute($sql);
        echo "Created activity table\n";
        
        // Create trigger for updated_at
        $trigger = "
            CREATE TRIGGER IF NOT EXISTS activity_updated_at 
            AFTER UPDATE ON activity
            FOR EACH ROW
            BEGIN
                UPDATE activity SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END
        ";
        $this->db->execute($trigger);
    }
    
    /**
     * Create team table
     */
    private function createTeamTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS team (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                team_name TEXT NOT NULL UNIQUE CHECK(length(trim(team_name)) > 0),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->db->execute($sql);
        echo "Created team table\n";
        
        // Create trigger for updated_at
        $trigger = "
            CREATE TRIGGER IF NOT EXISTS team_updated_at 
            AFTER UPDATE ON team
            FOR EACH ROW
            BEGIN
                UPDATE team SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END
        ";
        $this->db->execute($trigger);
    }
    
    /**
     * Create score table
     */
    private function createScoreTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS score (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activity_id INTEGER NOT NULL,
                team_id INTEGER NOT NULL,
                creative_score INTEGER CHECK(creative_score >= 1 AND creative_score <= 10),
                participation_score INTEGER CHECK(participation_score >= 1 AND participation_score <= 10),
                bribe_score INTEGER CHECK(bribe_score >= 1 AND bribe_score <= 10),
                total_score INTEGER GENERATED ALWAYS AS (creative_score + participation_score + bribe_score) STORED,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (activity_id) REFERENCES activity(id) ON DELETE CASCADE,
                FOREIGN KEY (team_id) REFERENCES team(id) ON DELETE CASCADE,
                UNIQUE(activity_id, team_id)
            )
        ";
        
        $this->db->execute($sql);
        echo "Created score table\n";
        
        // Create trigger for updated_at
        $trigger = "
            CREATE TRIGGER IF NOT EXISTS score_updated_at 
            AFTER UPDATE ON score
            FOR EACH ROW
            BEGIN
                UPDATE score SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END
        ";
        $this->db->execute($trigger);
    }
    
    /**
     * Insert sample data for testing
     */
    private function insertSampleData() {
        // Sample activities
        $activities = [
            'Trivia Challenge',
            'Creative Showcase',
            'Team Building Exercise',
            'Presentation Contest'
        ];
        
        foreach ($activities as $activity) {
            $sql = "INSERT OR IGNORE INTO activity (activity_name) VALUES (?)";
            $this->db->execute($sql, [1 => $activity]);
        }
        
        // Sample teams
        $teams = [
            'Team Alpha',
            'Team Beta',
            'Team Gamma',
            'Team Delta',
            'Team Epsilon'
        ];
        
        foreach ($teams as $team) {
            $sql = "INSERT OR IGNORE INTO team (team_name) VALUES (?)";
            $this->db->execute($sql, [1 => $team]);
        }
        
        echo "Inserted sample data\n";
    }
    
    /**
     * Check if database is properly initialized
     */
    public function verifySchema() {
        try {
            // Check if all tables exist
            $tables = ['activity', 'team', 'score'];
            foreach ($tables as $table) {
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
                $result = $this->db->fetchOne($sql, [1 => $table]);
                if (!$result) {
                    throw new Exception("Table '$table' does not exist");
                }
            }
            
            // Check if sample data exists
            $activityCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM activity")['count'];
            $teamCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM team")['count'];
            
            echo "Schema verification successful:\n";
            echo "- Activities: $activityCount\n";
            echo "- Teams: $teamCount\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "Schema verification failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Drop all tables (for testing/reset)
     */
    public function dropSchema() {
        try {
            $this->db->beginTransaction();
            
            $this->db->execute("DROP TABLE IF EXISTS score");
            $this->db->execute("DROP TABLE IF EXISTS activity");
            $this->db->execute("DROP TABLE IF EXISTS team");
            
            $this->db->commit();
            
            echo "Database schema dropped successfully!\n";
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            echo "Error dropping schema: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $init = new DatabaseInit();
    
    $command = $argv[1] ?? 'init';
    
    switch ($command) {
        case 'init':
            $init->initializeSchema();
            $init->verifySchema();
            break;
        case 'verify':
            $init->verifySchema();
            break;
        case 'drop':
            $init->dropSchema();
            break;
        default:
            echo "Usage: php init.php [init|verify|drop]\n";
            echo "  init   - Initialize database schema\n";
            echo "  verify - Verify schema exists\n";
            echo "  drop   - Drop all tables\n";
    }
}

?>