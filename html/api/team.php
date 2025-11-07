<?php
/**
 * Team API Endpoint
 * 
 * Handles CRUD operations for teams
 * GET: List all teams (public)
 * POST: Create new team (requires auth)
 * PUT: Update team (requires auth)
 * DELETE: Delete team (requires auth)
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
 * GET - List all teams
 */
function handleGet($db) {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single team
        $team = $db->fetchOne(
            "SELECT * FROM team WHERE id = ?",
            [1 => $id]
        );
        
        if (!$team) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Team not found'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $team
        ]);
    } else {
        // Get all teams with optional score statistics
        $includeStats = isset($_GET['stats']) && $_GET['stats'] === 'true';
        
        if ($includeStats) {
            $teams = $db->fetchAll("
                SELECT 
                    team.id,
                    team.team_name,
                    team.created_at,
                    team.updated_at,
                    COUNT(score.id) as activities_participated,
                    COALESCE(SUM(score.total_score), 0) as total_score,
                    COALESCE(ROUND(AVG(score.total_score), 2), 0) as avg_score
                FROM team 
                LEFT JOIN score ON team.id = score.team_id 
                GROUP BY team.id 
                ORDER BY team.team_name
            ");
        } else {
            $teams = $db->fetchAll("
                SELECT * FROM team ORDER BY team_name
            ");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $teams,
            'count' => count($teams)
        ]);
    }
}

/**
 * POST - Create new team
 */
function handlePost($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['team_name']) || empty(trim($input['team_name']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Team name is required'
        ]);
        return;
    }
    
    $teamName = trim($input['team_name']);
    
    // Check if team already exists
    $existing = $db->fetchOne(
        "SELECT id FROM team WHERE team_name = ?",
        [1 => $teamName]
    );
    
    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Team name already exists'
        ]);
        return;
    }
    
    // Insert new team
    $db->execute(
        "INSERT INTO team (team_name) VALUES (?)",
        [1 => $teamName]
    );
    
    $teamId = $db->lastInsertId();
    
    // Get the created team
    $team = $db->fetchOne(
        "SELECT * FROM team WHERE id = ?",
        [1 => $teamId]
    );
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Team created successfully',
        'data' => $team
    ]);
}

/**
 * PUT - Update team
 */
function handlePut($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Team ID is required'
        ]);
        return;
    }
    
    if (!isset($input['team_name']) || empty(trim($input['team_name']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Team name is required'
        ]);
        return;
    }
    
    $teamId = $input['id'];
    $teamName = trim($input['team_name']);
    
    // Check if team exists
    $existing = $db->fetchOne(
        "SELECT id FROM team WHERE id = ?",
        [1 => $teamId]
    );
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Team not found'
        ]);
        return;
    }
    
    // Check if new name conflicts with another team
    $conflict = $db->fetchOne(
        "SELECT id FROM team WHERE team_name = ? AND id != ?",
        [1 => $teamName, 2 => $teamId]
    );
    
    if ($conflict) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Team name already exists'
        ]);
        return;
    }
    
    // Update team
    $db->execute(
        "UPDATE team SET team_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [1 => $teamName, 2 => $teamId]
    );
    
    // Get updated team
    $team = $db->fetchOne(
        "SELECT * FROM team WHERE id = ?",
        [1 => $teamId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Team updated successfully',
        'data' => $team
    ]);
}

/**
 * DELETE - Delete team
 */
function handleDelete($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Team ID is required'
        ]);
        return;
    }
    
    $teamId = $input['id'];
    
    // Check if team exists
    $existing = $db->fetchOne(
        "SELECT id FROM team WHERE id = ?",
        [1 => $teamId]
    );
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Team not found'
        ]);
        return;
    }
    
    // Check if team has any scores
    $scoreCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM score WHERE team_id = ?",
        [1 => $teamId]
    );
    
    if ($scoreCount['count'] > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete team with existing scores',
            'score_count' => (int)$scoreCount['count']
        ]);
        return;
    }
    
    // Delete team
    $db->execute(
        "DELETE FROM team WHERE id = ?",
        [1 => $teamId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Team deleted successfully'
    ]);
}

?>