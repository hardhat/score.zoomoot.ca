<?php
/**
 * Activity Management Page (Protected)
 * 
 * Allows authenticated users to manage scores for activities
 */

require_once __DIR__ . '/api/auth.php';
require_once __DIR__ . '/api/db.php';

// Require authentication
Auth::requireAuth('login.php');

// Get remaining session time
$remainingTime = Auth::getRemainingTime();
$remainingMinutes = floor($remainingTime / 60);

// Get database instance
$db = getDB();

// Get all activities and teams for the form
$activities = $db->fetchAll("SELECT * FROM activity ORDER BY activity_name");
$teams = $db->fetchAll("SELECT * FROM team ORDER BY team_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Management - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { 
            display: none !important; 
        }
        body {
            background: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 60px;
        }
        .header-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .session-info {
            font-size: 14px;
            opacity: 0.9;
        }
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 20px;
        }
        .score-input {
            max-width: 80px;
        }
        .team-row {
            transition: background-color 0.2s;
        }
        .team-row:hover {
            background-color: #f8f9fa;
        }
        .score-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-action {
            min-width: 80px;
        }
        .activity-selector {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .new-team-form {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">🏆 Activity Score Management</h1>
                    <p class="mb-0 mt-2 session-info">
                        Session expires in <span x-text="sessionMinutes"></span> minutes
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light me-2">
                        View Standings
                    </a>
                    <a href="logout.php" class="btn btn-outline-light">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container" x-data="activityManager()">
        <!-- Navigation -->
        <div class="mb-4">
            <a href="teams.php" class="btn btn-outline-secondary">
                <svg width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                </svg>
                Manage Teams
            </a>
            <a href="activities.php" class="btn btn-outline-secondary">
                <svg width="16" height="16" fill="currentColor" class="bi bi-calendar-event" viewBox="0 0 16 16">
                    <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                </svg>
                Manage Activities
            </a>
        </div>

        <!-- Activity Selector -->
        <div class="activity-selector">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label for="activitySelect" class="form-label fw-bold">Select Activity</label>
                    <select 
                        id="activitySelect" 
                        class="form-select form-select-lg" 
                        x-model="selectedActivityId"
                        @change="loadScores()"
                    >
                        <option value="">-- Choose an activity --</option>
                        <?php foreach ($activities as $activity): ?>
                            <option value="<?php echo $activity['id']; ?>">
                                <?php echo htmlspecialchars($activity['activity_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button 
                        class="btn btn-success w-100" 
                        @click="showNewActivityModal = true"
                    >
                        ➕ New Activity
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!selectedActivityId && !loading" class="content-card empty-state">
            <div class="display-1">🎯</div>
            <h3>Select an Activity</h3>
            <p class="text-muted">Choose an activity from the dropdown above to manage scores</p>
        </div>

        <!-- Scores Management -->
        <div x-show="selectedActivityId && !loading" class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 x-text="'Scores for ' + getActivityName()"></h2>
                <button class="btn btn-primary" @click="saveAllScores()">
                    💾 Save All Scores
                </button>
            </div>

            <!-- Alert Messages -->
            <div x-show="alert.show" :class="'alert alert-' + alert.type" class="alert-dismissible fade show" role="alert">
                <span x-text="alert.message"></span>
                <button type="button" class="btn-close" @click="alert.show = false"></button>
            </div>

            <!-- Scores Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th class="text-center">Creative (1-10)</th>
                            <th class="text-center">Participation (1-10)</th>
                            <th class="text-center">Bribe (1-10)</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="team in teams" :key="team.id">
                            <tr class="team-row">
                                <td>
                                    <strong x-text="team.team_name"></strong>
                                </td>
                                <td class="text-center">
                                    <input 
                                        type="number" 
                                        class="form-control score-input d-inline-block" 
                                        min="1" 
                                        max="10"
                                        x-model.number="team.creative_score"
                                        @input="calculateTotal(team)"
                                    >
                                </td>
                                <td class="text-center">
                                    <input 
                                        type="number" 
                                        class="form-control score-input d-inline-block" 
                                        min="1" 
                                        max="10"
                                        x-model.number="team.participation_score"
                                        @input="calculateTotal(team)"
                                    >
                                </td>
                                <td class="text-center">
                                    <input 
                                        type="number" 
                                        class="form-control score-input d-inline-block" 
                                        min="1" 
                                        max="10"
                                        x-model.number="team.bribe_score"
                                        @input="calculateTotal(team)"
                                    >
                                </td>
                                <td class="text-center">
                                    <span class="score-badge" x-text="team.total_score || '-'"></span>
                                </td>
                                <td class="text-center">
                                    <button 
                                        class="btn btn-sm btn-primary btn-action"
                                        @click="saveScore(team)"
                                        x-show="team.hasChanges"
                                    >
                                        Save
                                    </button>
                                    <button 
                                        class="btn btn-sm btn-danger btn-action"
                                        @click="deleteScore(team)"
                                        x-show="team.score_id && !team.hasChanges"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- New Team Form -->
            <div class="new-team-form">
                <h5 class="mb-3">➕ Add New Team</h5>
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="newTeamName" class="form-label">Team Name</label>
                        <input 
                            type="text" 
                            id="newTeamName" 
                            class="form-control" 
                            x-model="newTeamName"
                            placeholder="Enter team name"
                            @keyup.enter="addNewTeam()"
                        >
                    </div>
                    <div class="col-md-4">
                        <button 
                            class="btn btn-success w-100" 
                            @click="addNewTeam()"
                            :disabled="!newTeamName.trim()"
                        >
                            Add Team
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Activity Modal -->
        <div x-show="showNewActivityModal" x-cloak class="modal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Activity</h5>
                        <button type="button" class="btn-close" @click="showNewActivityModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <label for="newActivityName" class="form-label">Activity Name</label>
                        <input 
                            type="text" 
                            id="newActivityName" 
                            class="form-control" 
                            x-model="newActivityName"
                            placeholder="Enter activity name"
                            @keyup.enter="createActivity()"
                        >
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showNewActivityModal = false">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="createActivity()">Create Activity</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function activityManager() {
            return {
                selectedActivityId: '',
                teams: <?php echo json_encode($teams); ?>,
                activities: <?php echo json_encode($activities); ?>,
                loading: false,
                newTeamName: '',
                newActivityName: '',
                showNewActivityModal: false,
                sessionMinutes: <?php echo $remainingMinutes; ?>,
                alert: {
                    show: false,
                    type: 'info',
                    message: ''
                },

                init() {
                    // Update session timer every minute
                    setInterval(() => {
                        this.sessionMinutes = Math.max(0, this.sessionMinutes - 1);
                    }, 60000);
                },

                getActivityName() {
                    const activity = this.activities.find(a => a.id == this.selectedActivityId);
                    return activity ? activity.activity_name : '';
                },

                async loadScores() {
                    if (!this.selectedActivityId) return;
                    
                    this.loading = true;
                    try {
                        const response = await fetch(`/api/score.php?activity_id=${this.selectedActivityId}`);
                        const data = await response.json();
                        
                        if (data.success) {
                            // Map scores to teams
                            this.teams = this.teams.map(team => {
                                const score = data.data.find(s => s.team_id == team.id);
                                return {
                                    ...team,
                                    score_id: score?.id || null,
                                    creative_score: score?.creative_score || null,
                                    participation_score: score?.participation_score || null,
                                    bribe_score: score?.bribe_score || null,
                                    total_score: score?.total_score || null,
                                    hasChanges: false
                                };
                            });
                        }
                    } catch (error) {
                        this.showAlert('error', 'Failed to load scores');
                    } finally {
                        this.loading = false;
                    }
                },

                calculateTotal(team) {
                    const c = parseInt(team.creative_score) || 0;
                    const p = parseInt(team.participation_score) || 0;
                    const b = parseInt(team.bribe_score) || 0;
                    team.total_score = (c && p && b) ? c + p + b : null;
                    team.hasChanges = true;
                },

                async saveScore(team) {
                    if (!this.validateScore(team)) {
                        this.showAlert('danger', 'All scores must be between 1 and 10');
                        return;
                    }

                    try {
                        const method = team.score_id ? 'PUT' : 'POST';
                        const body = team.score_id ? {
                            id: team.score_id,
                            creative_score: team.creative_score,
                            participation_score: team.participation_score,
                            bribe_score: team.bribe_score
                        } : {
                            activity_id: this.selectedActivityId,
                            team_id: team.id,
                            creative_score: team.creative_score,
                            participation_score: team.participation_score,
                            bribe_score: team.bribe_score
                        };

                        const response = await fetch('/api/score.php', {
                            method: method,
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(body)
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            team.score_id = data.data.id;
                            team.hasChanges = false;
                            this.showAlert('success', `Score saved for ${team.team_name}`);
                        } else {
                            this.showAlert('danger', data.error);
                        }
                    } catch (error) {
                        this.showAlert('danger', 'Failed to save score');
                    }
                },

                async saveAllScores() {
                    const teamsToSave = this.teams.filter(t => t.hasChanges && this.validateScore(t));
                    
                    if (teamsToSave.length === 0) {
                        this.showAlert('info', 'No changes to save');
                        return;
                    }

                    for (const team of teamsToSave) {
                        await this.saveScore(team);
                    }
                },

                async deleteScore(team) {
                    if (!confirm(`Delete score for ${team.team_name}?`)) return;

                    try {
                        const response = await fetch('/api/score.php', {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: team.score_id })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            team.score_id = null;
                            team.creative_score = null;
                            team.participation_score = null;
                            team.bribe_score = null;
                            team.total_score = null;
                            this.showAlert('success', `Score deleted for ${team.team_name}`);
                        } else {
                            this.showAlert('danger', data.error);
                        }
                    } catch (error) {
                        this.showAlert('danger', 'Failed to delete score');
                    }
                },

                async addNewTeam() {
                    if (!this.newTeamName.trim()) return;

                    try {
                        const response = await fetch('/api/team.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ team_name: this.newTeamName.trim() })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.teams.push({
                                ...data.data,
                                score_id: null,
                                creative_score: null,
                                participation_score: null,
                                bribe_score: null,
                                total_score: null,
                                hasChanges: false
                            });
                            this.newTeamName = '';
                            this.showAlert('success', `Team "${data.data.team_name}" added successfully`);
                        } else {
                            this.showAlert('danger', data.error);
                        }
                    } catch (error) {
                        this.showAlert('danger', 'Failed to add team');
                    }
                },

                async createActivity() {
                    if (!this.newActivityName.trim()) return;

                    try {
                        const response = await fetch('/api/activity.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ activity_name: this.newActivityName.trim() })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.activities.push(data.data);
                            this.selectedActivityId = data.data.id;
                            this.newActivityName = '';
                            this.showNewActivityModal = false;
                            this.loadScores();
                            this.showAlert('success', `Activity "${data.data.activity_name}" created successfully`);
                        } else {
                            this.showAlert('danger', data.error);
                        }
                    } catch (error) {
                        this.showAlert('danger', 'Failed to create activity');
                    }
                },

                validateScore(team) {
                    if (!team.creative_score || !team.participation_score || !team.bribe_score) return false;
                    return team.creative_score >= 1 && team.creative_score <= 10 &&
                           team.participation_score >= 1 && team.participation_score <= 10 &&
                           team.bribe_score >= 1 && team.bribe_score <= 10;
                },

                showAlert(type, message) {
                    this.alert = { show: true, type, message };
                    setTimeout(() => { this.alert.show = false; }, 5000);
                }
            };
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>