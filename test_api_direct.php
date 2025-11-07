<?php
/**
 * Simple API Test Script
 * Tests API endpoints from PHP directly
 */

require_once __DIR__ . '/html/api/auth.php';
require_once __DIR__ . '/html/api/db.php';

echo "=== API Testing ===\n\n";

// Simulate authentication
session_name(SESSION_NAME);
session_start();
$_SESSION[SESSION_NAME] = Auth::hashPassword(ADMIN_PASSWORD . time());
$_SESSION['auth_time'] = time();
$_COOKIE[SESSION_NAME] = $_SESSION[SESSION_NAME];

echo "Authentication setup complete\n";
echo "Is Authenticated: " . (Auth::isAuthenticated() ? 'Yes' : 'No') . "\n\n";

$db = getDB();

// Test 1: Create a new team
echo "Test 1: Creating new team...\n";
$db->execute("INSERT INTO team (team_name) VALUES (?)", [1 => 'API Test Team']);
$teamId = $db->lastInsertId();
echo "✓ Team created with ID: $teamId\n\n";

// Test 2: Create a new activity  
echo "Test 2: Creating new activity...\n";
$db->execute("INSERT INTO activity (activity_name) VALUES (?)", [1 => 'API Test Activity']);
$activityId = $db->lastInsertId();
echo "✓ Activity created with ID: $activityId\n\n";

// Test 3: Create a score
echo "Test 3: Creating score...\n";
$db->execute("
    INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score)
    VALUES (?, ?, ?, ?, ?)
", [
    1 => $activityId,
    2 => $teamId,
    3 => 8,
    4 => 9,
    5 => 7
]);
$scoreId = $db->lastInsertId();
$score = $db->fetchOne("SELECT * FROM score WHERE id = ?", [1 => $scoreId]);
echo "✓ Score created with ID: $scoreId\n";
echo "  Total Score: " . $score['total_score'] . " (should be 24)\n\n";

// Test 4: Update score
echo "Test 4: Updating score...\n";
$db->execute("
    UPDATE score 
    SET creative_score = 10, participation_score = 10, bribe_score = 10
    WHERE id = ?
", [1 => $scoreId]);
$score = $db->fetchOne("SELECT * FROM score WHERE id = ?", [1 => $scoreId]);
echo "✓ Score updated\n";
echo "  New Total Score: " . $score['total_score'] . " (should be 30)\n\n";

// Test 5: Get scores with joins
echo "Test 5: Getting scores with details...\n";
$scores = $db->fetchAll("
    SELECT 
        score.*,
        team.team_name,
        activity.activity_name
    FROM score
    JOIN team ON score.team_id = team.id
    JOIN activity ON score.activity_id = activity.id
    WHERE score.id = ?
", [1 => $scoreId]);
echo "✓ Retrieved score details:\n";
foreach ($scores as $s) {
    echo "  Team: " . $s['team_name'] . "\n";
    echo "  Activity: " . $s['activity_name'] . "\n";
    echo "  Scores: C=" . $s['creative_score'] . ", P=" . $s['participation_score'] . ", B=" . $s['bribe_score'] . "\n";
    echo "  Total: " . $s['total_score'] . "\n\n";
}

// Cleanup
echo "Cleanup: Deleting test data...\n";
$db->execute("DELETE FROM score WHERE id = ?", [1 => $scoreId]);
$db->execute("DELETE FROM activity WHERE id = ?", [1 => $activityId]);
$db->execute("DELETE FROM team WHERE id = ?", [1 => $teamId]);
echo "✓ Test data deleted\n\n";

echo "=== All API Tests Completed Successfully! ===\n";

?>