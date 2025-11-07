<?php
/**
 * Public Standings Page
 * 
 * Displays team standings and provides login access for activity leaders
 */

require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/auth.php';

// Get all teams with their total scores
$db = getDB();
$standings = $db->fetchAll("
    SELECT 
        team.id,
        team.team_name,
        COUNT(score.id) as activities_participated,
        COALESCE(SUM(score.total_score), 0) as total_score,
        COALESCE(ROUND(AVG(score.total_score), 2), 0) as avg_score
    FROM team 
    LEFT JOIN score ON team.id = score.team_id 
    GROUP BY team.id 
    ORDER BY total_score DESC, avg_score DESC
");

// Check if user is already authenticated
$isAuthenticated = Auth::isAuthenticated();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Standings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .header h1 {
            color: #333;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .standings-table {
            margin-top: 30px;
        }
        .rank-badge {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            font-weight: 700;
            color: white;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #808080); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); }
        .rank-other { background: linear-gradient(135deg, #667eea, #764ba2); }
        .login-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
            text-align: center;
        }
        .stat-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #f1f3f5;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="header">
                <h1>🏆 <?php echo APP_NAME; ?></h1>
                <p class="text-muted mb-0">Live Team Standings</p>
            </div>

            <?php if (count($standings) > 0): ?>
                <div class="standings-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Team</th>
                                <th class="text-center">Activities</th>
                                <th class="text-center">Total Score</th>
                                <th class="text-center">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings as $index => $team): ?>
                                <?php 
                                    $rank = $index + 1;
                                    $rankClass = $rank <= 3 ? "rank-{$rank}" : "rank-other";
                                ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge <?php echo $rankClass; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="stat-badge"><?php echo $team['activities_participated']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo $team['total_score']; ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $team['avg_score']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No teams or scores yet. Activity leaders can add scores after logging in.
                </div>
            <?php endif; ?>

            <div class="login-section">
                <?php if ($isAuthenticated): ?>
                    <p class="text-muted mb-3">You are logged in as an Activity Leader</p>
                    <a href="activity.php" class="btn btn-primary btn-lg me-2">
                        Manage Scores
                    </a>
                    <a href="logout.php" class="btn btn-outline-secondary">
                        Logout
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-3">Activity Leaders: Login to manage scores</p>
                    <a href="login.php" class="btn btn-primary btn-lg">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>