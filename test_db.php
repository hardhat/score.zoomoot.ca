<?php
/**
 * Database Test Script
 * 
 * Simple test to verify database connection and schema
 * Can be run via web server or CLI
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>\n";

try {
    // Check if env.php exists
    if (!file_exists(__DIR__ . '/env.php')) {
        throw new Exception("env.php file not found. Please copy env_sample.php to env.php");
    }
    
    echo "✓ env.php file found\n<br>";
    
    // Test database connection
    require_once __DIR__ . '/html/api/db.php';
    $db = getDB();
    
    echo "✓ Database connection established\n<br>";
    
    // Test database initialization
    require_once __DIR__ . '/html/api/init.php';
    $init = new DatabaseInit();
    
    echo "<h3>Initializing Schema...</h3>\n";
    if ($init->initializeSchema()) {
        echo "✓ Schema initialization successful\n<br>";
        
        echo "<h3>Verifying Schema...</h3>\n";
        if ($init->verifySchema()) {
            echo "✓ Schema verification successful\n<br>";
        }
    }
    
    // Test some basic queries
    echo "<h3>Testing Basic Queries...</h3>\n";
    
    // Get activities
    $activities = $db->fetchAll("SELECT * FROM activity ORDER BY activity_name");
    echo "Activities found: " . count($activities) . "\n<br>";
    foreach ($activities as $activity) {
        echo "- " . htmlspecialchars($activity['activity_name']) . " (ID: {$activity['id']})\n<br>";
    }
    
    // Get teams
    $teams = $db->fetchAll("SELECT * FROM team ORDER BY team_name");
    echo "\nTeams found: " . count($teams) . "\n<br>";
    foreach ($teams as $team) {
        echo "- " . htmlspecialchars($team['team_name']) . " (ID: {$team['id']})\n<br>";
    }
    
    echo "\n<h3>All tests completed successfully!</h3>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n<br>";
    echo "Stack trace:\n<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>