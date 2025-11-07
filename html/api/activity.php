<?php
/**
 * Activity API Endpoint
 * 
 * Handles CRUD operations for activities
 * GET: List all activities (public)
 * POST: Create new activity (requires auth)
 * PUT: Update activity (requires auth)
 * DELETE: Delete activity (requires auth)
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
 * GET - List all activities
 */
function handleGet($db) {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single activity
        $activity = $db->fetchOne(
            "SELECT * FROM activity WHERE id = ?",
            [1 => $id]
        );
        
        if (!$activity) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Activity not found'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $activity
        ]);
    } else {
        // Get all activities with optional score statistics
        $includeStats = isset($_GET['stats']) && $_GET['stats'] === 'true';
        
        if ($includeStats) {
            $activities = $db->fetchAll("
                SELECT 
                    activity.id,
                    activity.activity_name,
                    activity.created_at,
                    activity.updated_at,
                    COUNT(score.id) as teams_participated,
                    COALESCE(ROUND(AVG(score.total_score), 2), 0) as avg_score
                FROM activity 
                LEFT JOIN score ON activity.id = score.activity_id 
                GROUP BY activity.id 
                ORDER BY activity.activity_name
            ");
        } else {
            $activities = $db->fetchAll("
                SELECT * FROM activity ORDER BY activity_name
            ");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $activities,
            'count' => count($activities)
        ]);
    }
}

/**
 * POST - Create new activity
 */
function handlePost($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['activity_name']) || empty(trim($input['activity_name']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Activity name is required'
        ]);
        return;
    }
    
    $activityName = trim($input['activity_name']);
    
    // Check if activity already exists
    $existing = $db->fetchOne(
        "SELECT id FROM activity WHERE activity_name = ?",
        [1 => $activityName]
    );
    
    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Activity name already exists'
        ]);
        return;
    }
    
    // Insert new activity
    $db->execute(
        "INSERT INTO activity (activity_name) VALUES (?)",
        [1 => $activityName]
    );
    
    $activityId = $db->lastInsertId();
    
    // Get the created activity
    $activity = $db->fetchOne(
        "SELECT * FROM activity WHERE id = ?",
        [1 => $activityId]
    );
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Activity created successfully',
        'data' => $activity
    ]);
}

/**
 * PUT - Update activity
 */
function handlePut($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Activity ID is required'
        ]);
        return;
    }
    
    if (!isset($input['activity_name']) || empty(trim($input['activity_name']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Activity name is required'
        ]);
        return;
    }
    
    $activityId = $input['id'];
    $activityName = trim($input['activity_name']);
    
    // Check if activity exists
    $existing = $db->fetchOne(
        "SELECT id FROM activity WHERE id = ?",
        [1 => $activityId]
    );
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Activity not found'
        ]);
        return;
    }
    
    // Check if new name conflicts with another activity
    $conflict = $db->fetchOne(
        "SELECT id FROM activity WHERE activity_name = ? AND id != ?",
        [1 => $activityName, 2 => $activityId]
    );
    
    if ($conflict) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Activity name already exists'
        ]);
        return;
    }
    
    // Update activity
    $db->execute(
        "UPDATE activity SET activity_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [1 => $activityName, 2 => $activityId]
    );
    
    // Get updated activity
    $activity = $db->fetchOne(
        "SELECT * FROM activity WHERE id = ?",
        [1 => $activityId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Activity updated successfully',
        'data' => $activity
    ]);
}

/**
 * DELETE - Delete activity
 */
function handleDelete($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Activity ID is required'
        ]);
        return;
    }
    
    $activityId = $input['id'];
    
    // Check if activity exists
    $existing = $db->fetchOne(
        "SELECT id FROM activity WHERE id = ?",
        [1 => $activityId]
    );
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Activity not found'
        ]);
        return;
    }
    
    // Check if activity has any scores
    $scoreCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM score WHERE activity_id = ?",
        [1 => $activityId]
    );
    
    if ($scoreCount['count'] > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete activity with existing scores',
            'score_count' => (int)$scoreCount['count']
        ]);
        return;
    }
    
    // Delete activity
    $db->execute(
        "DELETE FROM activity WHERE id = ?",
        [1 => $activityId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Activity deleted successfully'
    ]);
}

?>