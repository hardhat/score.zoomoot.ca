<?php
/**
 * Activity Management Page (Protected)
 * 
 * Allows authenticated users to manage activities (create, update, delete)
 */

require_once __DIR__ . '/api/auth.php';
require_once __DIR__ . '/api/db.php';

// Require authentication
Auth::requireAuth('login.php');

// Get remaining session time
$remainingTime = Auth::getRemainingTime();
$remainingMinutes = floor($remainingTime / 60);
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
        .modal {
            display: none;
        }
        .modal.d-block {
            display: block !important;
        }
        body {
            background: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 60px;
        }
        .header-bar {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .activity-card {
            transition: all 0.3s ease;
            border-left: 4px solid #f5576c;
        }
        .activity-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .score-badge {
            background: #fff3e0;
            color: #e65100;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container" x-data="activityManager()">
        <!-- Header -->
        <div class="header-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Activity Management</h1>
                    <p class="mb-0 opacity-75">Create, update, and manage activities</p>
                </div>
                <div class="text-end">
                    <small class="d-block">Session expires in <span x-text="remainingMinutes"></span> minutes</small>
                    <a href="logout.php" class="btn btn-sm btn-light mt-2">Logout</a>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mb-4">
            <a href="activity.php" class="btn btn-outline-primary">
                <svg width="16" height="16" fill="currentColor" class="bi bi-clipboard-check" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                </svg>
                Score Activities
            </a>
            <a href="teams.php" class="btn btn-outline-secondary">
                <svg width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                </svg>
                Manage Teams
            </a>
            <a href="index.php" class="btn btn-outline-info">
                <svg width="16" height="16" fill="currentColor" class="bi bi-trophy" viewBox="0 0 16 16">
                    <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33.076 33.076 0 0 1 2.5.5zm.099 2.54a2 2 0 0 0 .72 3.935c-.333-1.05-.588-2.346-.72-3.935zm10.083 3.935a2 2 0 0 0 .72-3.935c-.133 1.59-.388 2.885-.72 3.935z"/>
                </svg>
                View Standings
            </a>
        </div>

        <!-- Alert Messages -->
        <div x-show="message" x-cloak class="alert alert-dismissible fade show" :class="messageType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
            <span x-text="message"></span>
            <button type="button" class="btn-close" @click="message = ''"></button>
        </div>

        <!-- Create Activity Button -->
        <div class="mb-4">
            <button @click="openCreateModal()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Create New Activity
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Activities List -->
        <div x-show="!loading" class="row">
            <template x-for="activity in activities" :key="activity.id">
                <div class="col-md-6 mb-3">
                    <div class="card activity-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0" x-text="activity.activity_name"></h5>
                                <div>
                                    <button @click="openEditModal(activity)" class="btn btn-sm btn-outline-primary btn-action me-1" title="Edit">
                                        <svg width="14" height="14" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                        </svg>
                                    </button>
                                    <button @click="confirmDelete(activity)" class="btn btn-sm btn-outline-danger btn-action" title="Delete">
                                        <svg width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="text-muted small">
                                <template x-if="activity.teams_participated !== undefined">
                                    <div>
                                        <span class="score-badge me-2" x-text="activity.teams_participated + ' teams'"></span>
                                        <span class="score-badge" x-text="'Avg: ' + activity.avg_score"></span>
                                    </div>
                                </template>
                                <div class="mt-2">
                                    <small>Created: <span x-text="new Date(activity.created_at).toLocaleDateString()"></span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="activities.length === 0 && !loading" class="col-12">
                <div class="alert alert-info text-center">
                    <p class="mb-0">No activities found. Create your first activity to get started!</p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="showModal" class="modal" :class="{ 'd-block': showModal }" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" x-text="editingActivity ? 'Edit Activity' : 'Create New Activity'"></h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <label for="activityName" class="form-label">Activity Name</label>
                        <input 
                            type="text" 
                            id="activityName" 
                            class="form-control" 
                            x-model="activityName"
                            placeholder="Enter activity name"
                            @keyup.enter="saveActivity()"
                        >
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveActivity()" :disabled="!activityName.trim()">
                            <span x-text="editingActivity ? 'Update Activity' : 'Create Activity'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" class="modal" :class="{ 'd-block': showDeleteModal }" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" @click="showDeleteModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong x-text="activityToDelete?.activity_name"></strong>?</p>
                        <template x-if="activityToDelete?.teams_participated > 0">
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> This activity has <span x-text="activityToDelete.teams_participated"></span> score(s) recorded. 
                                Deletion will be prevented if scores exist.
                            </div>
                        </template>
                        <p class="text-muted small mb-0">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="deleteActivity()">Delete Activity</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function activityManager() {
            return {
                activities: [],
                loading: true,
                message: '',
                messageType: 'success',
                showModal: false,
                showDeleteModal: false,
                editingActivity: null,
                activityToDelete: null,
                activityName: '',
                remainingMinutes: <?php echo $remainingMinutes; ?>,

                init() {
                    this.loadActivities();
                    // Update countdown every minute
                    setInterval(() => {
                        if (this.remainingMinutes > 0) {
                            this.remainingMinutes--;
                        }
                    }, 60000);
                },

                async loadActivities() {
                    this.loading = true;
                    try {
                        const response = await fetch('api/activity.php?stats=true');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.activities = data.data;
                        } else {
                            this.showMessage('Failed to load activities', 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error loading activities: ' + error.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                openCreateModal() {
                    this.editingActivity = null;
                    this.activityName = '';
                    this.showModal = true;
                },

                openEditModal(activity) {
                    this.editingActivity = activity;
                    this.activityName = activity.activity_name;
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.editingActivity = null;
                    this.activityName = '';
                },

                async saveActivity() {
                    if (!this.activityName.trim()) {
                        this.showMessage('Activity name is required', 'error');
                        return;
                    }

                    try {
                        const url = 'api/activity.php';
                        const method = this.editingActivity ? 'PUT' : 'POST';
                        const body = this.editingActivity 
                            ? { id: this.editingActivity.id, activity_name: this.activityName.trim() }
                            : { activity_name: this.activityName.trim() };

                        const response = await fetch(url, {
                            method: method,
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(body)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showMessage(data.message, 'success');
                            this.closeModal();
                            this.loadActivities();
                        } else {
                            this.showMessage(data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error saving activity: ' + error.message, 'error');
                    }
                },

                confirmDelete(activity) {
                    this.activityToDelete = activity;
                    this.showDeleteModal = true;
                },

                async deleteActivity() {
                    if (!this.activityToDelete) return;

                    try {
                        const response = await fetch('api/activity.php', {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: this.activityToDelete.id })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showMessage(data.message, 'success');
                            this.showDeleteModal = false;
                            this.activityToDelete = null;
                            this.loadActivities();
                        } else {
                            this.showMessage(data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error deleting activity: ' + error.message, 'error');
                    }
                },

                showMessage(msg, type = 'success') {
                    this.message = msg;
                    this.messageType = type;
                    setTimeout(() => {
                        this.message = '';
                    }, 5000);
                }
            };
        }
    </script>
</body>
</html>
