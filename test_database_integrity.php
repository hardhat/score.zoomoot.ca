<?php
/**
 * Comprehensive Database Integrity Test
 * 
 * Tests:
 * - Foreign key constraints
 * - Data validation
 * - Unique constraints
 * - Edge cases and boundary conditions
 * - Transaction integrity
 */

require_once __DIR__ . '/html/api/db.php';

$testsPassed = 0;
$testsFailed = 0;

function test($description, $callback) {
    global $testsPassed, $testsFailed;
    echo "\nTest: $description\n";
    try {
        $result = $callback();
        if ($result) {
            echo "✓ PASS\n";
            $testsPassed++;
        } else {
            echo "✗ FAIL\n";
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "✗ FAIL - Exception: " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

echo "=== Database Integrity Tests ===\n";
echo "Initializing test database...\n";

$db = getDB();

// Clean up any previous test data
$db->execute("DELETE FROM score WHERE 1=1");
$db->execute("DELETE FROM team WHERE team_name LIKE 'Test%'");
$db->execute("DELETE FROM activity WHERE activity_name LIKE 'Test%'");

echo "\n--- Foreign Key Constraint Tests ---\n";

test("Foreign key prevents orphaned scores (invalid team_id)", function() use ($db) {
    try {
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (1, 99999, 5, 5, 5)"
        );
        return false; // Should have failed
    } catch (Exception $e) {
        return str_contains($e->getMessage(), 'FOREIGN KEY constraint failed');
    }
});

test("Foreign key prevents orphaned scores (invalid activity_id)", function() use ($db) {
    try {
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (99999, 1, 5, 5, 5)"
        );
        return false; // Should have failed
    } catch (Exception $e) {
        return str_contains($e->getMessage(), 'FOREIGN KEY constraint failed');
    }
});

test("Cascade delete works (deleting team with scores)", function() use ($db) {
    // Create test team and activity
    $db->execute("INSERT INTO team (team_name) VALUES ('Test Team Cascade')", []);
    $teamId = $db->lastInsertId();
    
    // Get an existing activity
    $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
    
    // Create score for this team
    $db->execute(
        "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 5, 5, 5)",
        [1 => $activity['id'], 2 => $teamId]
    );
    
    // Verify score exists
    $scoreBefore = $db->fetchOne("SELECT COUNT(*) as count FROM score WHERE team_id = ?", [1 => $teamId]);
    
    // Delete team - should cascade to scores
    $db->execute("DELETE FROM team WHERE id = ?", [1 => $teamId]);
    
    // Verify score is gone
    $scoreAfter = $db->fetchOne("SELECT COUNT(*) as count FROM score WHERE team_id = ?", [1 => $teamId]);
    
    return $scoreBefore['count'] > 0 && $scoreAfter['count'] == 0;
});

echo "\n--- Data Validation Tests ---\n";

test("Score must be between 1-10 (creative_score)", function() use ($db) {
    try {
        $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
        $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
        
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 11, 5, 5)",
            [1 => $activity['id'], 2 => $team['id']]
        );
        return false; // Should have failed
    } catch (Exception $e) {
        return str_contains($e->getMessage(), 'CHECK constraint failed');
    }
});

test("Score must be between 1-10 (participation_score)", function() use ($db) {
    try {
        $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
        $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
        
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 5, 0, 5)",
            [1 => $activity['id'], 2 => $team['id']]
        );
        return false; // Should have failed
    } catch (Exception $e) {
        return str_contains($e->getMessage(), 'CHECK constraint failed');
    }
});

test("Score must be between 1-10 (bribe_score)", function() use ($db) {
    try {
        $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
        $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
        
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 5, 5, 15)",
            [1 => $activity['id'], 2 => $team['id']]
        );
        return false; // Should have failed
    } catch (Exception $e) {
        return str_contains($e->getMessage(), 'CHECK constraint failed');
    }
});

test("Total score is calculated correctly (generated column)", function() use ($db) {
    $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
    $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
    
    // Insert with specific scores
    $db->execute(
        "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 7, 8, 9)",
        [1 => $activity['id'], 2 => $team['id']]
    );
    
    $scoreId = $db->lastInsertId();
    
    // Retrieve and verify total
    $score = $db->fetchOne("SELECT total_score FROM score WHERE id = ?", [1 => $scoreId]);
    
    // Clean up
    $db->execute("DELETE FROM score WHERE id = ?", [1 => $scoreId]);
    
    return $score['total_score'] == 24; // 7 + 8 + 9
});

echo "\n--- Unique Constraint Tests ---\n";

test("Team names must be unique", function() use ($db) {
    try {
        $db->execute("INSERT INTO team (team_name) VALUES ('Test Unique Team')", []);
        $db->execute("INSERT INTO team (team_name) VALUES ('Test Unique Team')", []);
        
        // Clean up
        $db->execute("DELETE FROM team WHERE team_name = 'Test Unique Team'");
        
        return false; // Should have failed
    } catch (Exception $e) {
        // Clean up any that succeeded
        $db->execute("DELETE FROM team WHERE team_name = 'Test Unique Team'");
        return str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
});

test("Activity names must be unique", function() use ($db) {
    try {
        $db->execute("INSERT INTO activity (activity_name) VALUES ('Test Unique Activity')", []);
        $db->execute("INSERT INTO activity (activity_name) VALUES ('Test Unique Activity')", []);
        
        // Clean up
        $db->execute("DELETE FROM activity WHERE activity_name = 'Test Unique Activity'");
        
        return false; // Should have failed
    } catch (Exception $e) {
        // Clean up any that succeeded
        $db->execute("DELETE FROM activity WHERE activity_name = 'Test Unique Activity'");
        return str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
});

test("One score per team per activity (unique constraint)", function() use ($db) {
    try {
        $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
        $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
        
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 5, 5, 5)",
            [1 => $activity['id'], 2 => $team['id']]
        );
        
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 6, 6, 6)",
            [1 => $activity['id'], 2 => $team['id']]
        );
        
        // Clean up
        $db->execute("DELETE FROM score WHERE activity_id = ? AND team_id = ?", [1 => $activity['id'], 2 => $team['id']]);
        
        return false; // Should have failed
    } catch (Exception $e) {
        // Clean up any that succeeded
        $db->execute("DELETE FROM score WHERE activity_id = ? AND team_id = ?", [1 => $activity['id'], 2 => $team['id']]);
        return str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
});

echo "\n--- Timestamp Tests ---\n";

test("created_at is automatically set", function() use ($db) {
    $db->execute("INSERT INTO team (team_name) VALUES ('Test Timestamp Team')", []);
    $teamId = $db->lastInsertId();
    
    $team = $db->fetchOne("SELECT created_at FROM team WHERE id = ?", [1 => $teamId]);
    
    // Clean up
    $db->execute("DELETE FROM team WHERE id = ?", [1 => $teamId]);
    
    return !empty($team['created_at']) && strtotime($team['created_at']) !== false;
});

test("updated_at is automatically updated", function() use ($db) {
    $db->execute("INSERT INTO team (team_name) VALUES ('Test Update Team')", []);
    $teamId = $db->lastInsertId();
    
    $team1 = $db->fetchOne("SELECT updated_at FROM team WHERE id = ?", [1 => $teamId]);
    
    // Wait a moment
    sleep(1);
    
    // Update the team
    $db->execute("UPDATE team SET team_name = 'Test Update Team Modified' WHERE id = ?", [1 => $teamId]);
    
    $team2 = $db->fetchOne("SELECT updated_at FROM team WHERE id = ?", [1 => $teamId]);
    
    // Clean up
    $db->execute("DELETE FROM team WHERE id = ?", [1 => $teamId]);
    
    return $team1['updated_at'] != $team2['updated_at'];
});

echo "\n--- Transaction Integrity Tests ---\n";

test("Transaction rollback on error", function() use ($db) {
    $db->beginTransaction();
    
    try {
        // Create a team
        $db->execute("INSERT INTO team (team_name) VALUES ('Test Transaction Team')", []);
        $teamId = $db->lastInsertId();
        
        // Try to create invalid score (this should fail)
        $db->execute(
            "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (99999, ?, 5, 5, 5)",
            [1 => $teamId]
        );
        
        $db->commit();
        return false; // Should have rolled back
    } catch (Exception $e) {
        $db->rollback();
        
        // Verify team wasn't created
        $team = $db->fetchOne("SELECT COUNT(*) as count FROM team WHERE team_name = 'Test Transaction Team'");
        return $team['count'] == 0;
    }
});

test("Transaction commit on success", function() use ($db) {
    $db->beginTransaction();
    
    try {
        // Create team
        $db->execute("INSERT INTO team (team_name) VALUES ('Test Commit Team')", []);
        $teamId = $db->lastInsertId();
        
        $db->commit();
        
        // Verify team was created
        $team = $db->fetchOne("SELECT id FROM team WHERE id = ?", [1 => $teamId]);
        
        // Clean up
        $db->execute("DELETE FROM team WHERE id = ?", [1 => $teamId]);
        
        return $team !== false && $team['id'] == $teamId;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
});

echo "\n--- Edge Case Tests ---\n";

test("Empty string team name is rejected", function() use ($db) {
    try {
        $db->execute("INSERT INTO team (team_name) VALUES ('')", []);
        $db->execute("DELETE FROM team WHERE team_name = ''");
        return false; // Should validate
    } catch (Exception $e) {
        return true; // Expected to fail
    }
});

test("Very long team name (255 chars) is accepted", function() use ($db) {
    $longName = str_repeat('A', 255);
    try {
        $db->execute("INSERT INTO team (team_name) VALUES (?)", [1 => $longName]);
        $teamId = $db->lastInsertId();
        
        $team = $db->fetchOne("SELECT team_name FROM team WHERE id = ?", [1 => $teamId]);
        
        // Clean up
        $db->execute("DELETE FROM team WHERE id = ?", [1 => $teamId]);
        
        return strlen($team['team_name']) == 255;
    } catch (Exception $e) {
        return false;
    }
});

test("Score boundaries: minimum values (1,1,1)", function() use ($db) {
    $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
    $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
    
    $db->execute(
        "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 1, 1, 1)",
        [1 => $activity['id'], 2 => $team['id']]
    );
    
    $scoreId = $db->lastInsertId();
    $score = $db->fetchOne("SELECT total_score FROM score WHERE id = ?", [1 => $scoreId]);
    
    // Clean up
    $db->execute("DELETE FROM score WHERE id = ?", [1 => $scoreId]);
    
    return $score['total_score'] == 3;
});

test("Score boundaries: maximum values (10,10,10)", function() use ($db) {
    $team = $db->fetchOne("SELECT id FROM team LIMIT 1");
    $activity = $db->fetchOne("SELECT id FROM activity LIMIT 1");
    
    $db->execute(
        "INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score) VALUES (?, ?, 10, 10, 10)",
        [1 => $activity['id'], 2 => $team['id']]
    );
    
    $scoreId = $db->lastInsertId();
    $score = $db->fetchOne("SELECT total_score FROM score WHERE id = ?", [1 => $scoreId]);
    
    // Clean up
    $db->execute("DELETE FROM score WHERE id = ?", [1 => $scoreId]);
    
    return $score['total_score'] == 30;
});

echo "\n\n=== Test Summary ===\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";

if ($testsFailed == 0) {
    echo "\n✓ All database integrity tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review.\n";
    exit(1);
}
?>
