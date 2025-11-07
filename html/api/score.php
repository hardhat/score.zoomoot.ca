<?php
/**
 * Score API Endpoint
 * 
 * Handles CRUD operations for scores
 * GET: Retrieve scores (public)
 * POST: Submit new score (requires auth)
 * PUT: Update existing score (requires auth)
 * DELETE: Remove score (requires auth)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

try {
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
            
        case 'POST':
            requireAuthAPI();
            handlePost($db);
            break;
            
        case 'PUT':
            requireAuthAPI();
            handlePut($db);
            break;
            
        case 'DELETE':
            requireAuthAPI();
            handleDelete($db);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}

/**
 * GET - Retrieve scores
 */
function handleGet($db) {
    $id = $_GET['id'] ?? null;
    $activityId = $_GET['activity_id'] ?? null;
    $teamId = $_GET['team_id'] ?? null;
    
    if ($id) {
        // Get single score with team and activity details
        $score = $db->fetchOne("
            SELECT 
                score.*,
                team.team_name,
                activity.activity_name
            FROM score
            JOIN team ON score.team_id = team.id
            JOIN activity ON score.activity_id = activity.id
            WHERE score.id = ?
        ", [1 => $id]);
        
        if (!$score) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Score not found'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $score
        ]);
    } elseif ($activityId) {
        // Get all scores for a specific activity
        $scores = $db->fetchAll("
            SELECT 
                score.*,
                team.team_name,
                activity.activity_name
            FROM score
            JOIN team ON score.team_id = team.id
            JOIN activity ON score.activity_id = activity.id
            WHERE score.activity_id = ?
            ORDER BY score.total_score DESC
        ", [1 => $activityId]);
        
        echo json_encode([
            'success' => true,
            'data' => $scores,
            'count' => count($scores)
        ]);
    } elseif ($teamId) {
        // Get all scores for a specific team
        $scores = $db->fetchAll("
            SELECT 
                score.*,
                team.team_name,
                activity.activity_name
            FROM score
            JOIN team ON score.team_id = team.id
            JOIN activity ON score.activity_id = activity.id
            WHERE score.team_id = ?
            ORDER BY activity.activity_name
        ", [1 => $teamId]);
        
        echo json_encode([
            'success' => true,
            'data' => $scores,
            'count' => count($scores)
        ]);
    } else {
        // Get all scores
        $scores = $db->fetchAll("
            SELECT 
                score.*,
                team.team_name,
                activity.activity_name
            FROM score
            JOIN team ON score.team_id = team.id
            JOIN activity ON score.activity_id = activity.id
            ORDER BY activity.activity_name, score.total_score DESC
        ");
        
        echo json_encode([
            'success' => true,
            'data' => $scores,
            'count' => count($scores)
        ]);
    }
}

/**
 * POST - Submit new score
 */
function handlePost($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['activity_id', 'team_id', 'creative_score', 'participation_score', 'bribe_score'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
            ]);
            return;
        }
    }
    
    $activityId = $input['activity_id'];
    $teamId = $input['team_id'];
    $creativeScore = $input['creative_score'];
    $participationScore = $input['participation_score'];
    $bribeScore = $input['bribe_score'];
    
    // Validate score ranges (1-10)
    if ($creativeScore < 1 || $creativeScore > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Creative score must be between 1 and 10'
        ]);
        return;
    }
    
    if ($participationScore < 1 || $participationScore > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Participation score must be between 1 and 10'
        ]);
        return;
    }
    
    if ($bribeScore < 1 || $bribeScore > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bribe score must be between 1 and 10'
        ]);
        return;
    }
    
    // Verify activity exists
    $activity = $db->fetchOne(
        "SELECT id FROM activity WHERE id = ?",
        [1 => $activityId]
    );
    
    if (!$activity) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Activity not found'
        ]);
        return;
    }
    
    // Verify team exists
    $team = $db->fetchOne(
        "SELECT id FROM team WHERE id = ?",
        [1 => $teamId]
    );
    
    if (!$team) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Team not found'
        ]);
        return;
    }
    
    // Check if score already exists for this team/activity combination
    $existing = $db->fetchOne(
        "SELECT id FROM score WHERE activity_id = ? AND team_id = ?",
        [1 => $activityId, 2 => $teamId]
    );
    
    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Score already exists for this team and activity. Use PUT to update.'
        ]);
        return;
    }
    
    // Insert score
    $db->execute("
        INSERT INTO score (activity_id, team_id, creative_score, participation_score, bribe_score)
        VALUES (?, ?, ?, ?, ?)
    ", [
        1 => $activityId,
        2 => $teamId,
        3 => $creativeScore,
        4 => $participationScore,
        5 => $bribeScore
    ]);
    
    $scoreId = $db->lastInsertId();
    
    // Get the created score with details
    $score = $db->fetchOne("
        SELECT 
            score.*,
            team.team_name,
            activity.activity_name
        FROM score
        JOIN team ON score.team_id = team.id
        JOIN activity ON score.activity_id = activity.id
        WHERE score.id = ?
    ", [1 => $scoreId]);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Score submitted successfully',
        'data' => $score
    ]);
}

/**
 * PUT - Update existing score
 */
function handlePut($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Score ID is required'
        ]);
        return;
    }
    
    $scoreId = $input['id'];
    
    // Check if score exists
    $existing = $db->fetchOne(
        "SELECT * FROM score WHERE id = ?",
        [1 => $scoreId]
    );
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Score not found'
        ]);
        return;
    }
    
    // Use existing values if not provided
    $creativeScore = $input['creative_score'] ?? $existing['creative_score'];
    $participationScore = $input['participation_score'] ?? $existing['participation_score'];
    $bribeScore = $input['bribe_score'] ?? $existing['bribe_score'];
    
    // Validate score ranges
    if ($creativeScore < 1 || $creativeScore > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Creative score must be between 1 and 10'
        ]);
        return;
    }
    
    if ($participationScore < 1 || $participationScore > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Participation score must be between 1 and 10'
        ]);
        return;
    }
    
    if ($bribeScore < 1 || $bribeScore > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bribe score must be between 1 and 10'
        ]);
        return;
    }
    
    // Update score
    $db->execute("
        UPDATE score 
        SET creative_score = ?, 
            participation_score = ?, 
            bribe_score = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ", [
        1 => $creativeScore,
        2 => $participationScore,
        3 => $bribeScore,
        4 => $scoreId
    ]);
    
    // Get updated score with details
    $score = $db->fetchOne("
        SELECT 
            score.*,
            team.team_name,
            activity.activity_name
        FROM score
        JOIN team ON score.team_id = team.id
        JOIN activity ON score.activity_id = activity.id
        WHERE score.id = ?
    ", [1 => $scoreId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Score updated successfully',
        'data' => $score
    ]);
}

/**
 * DELETE - Remove score
 */
function handleDelete($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Score ID is required'
        ]);
        return;
    }
    
    $scoreId = $input['id'];
    
    // Check if score exists
    $existing = $db->fetchOne(
        "SELECT id FROM score WHERE id = ?",
        [1 => $scoreId]
    );
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Score not found'
        ]);
        return;
    }
    
    // Delete score
    $db->execute(
        "DELETE FROM score WHERE id = ?",
        [1 => $scoreId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Score deleted successfully'
    ]);
}

?>