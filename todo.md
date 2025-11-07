# TODO - score.zoomoot.ca Project

A lightweight PHP-based score tracking system for zoomoot teams.

## Project Setup & Configuration

- [x] Create `env_sample.php` with configuration template
  - Salt for password hashing
  - Default password
  - Database name/path
- [x] Create `.gitignore` file to exclude sensitive files and database
- [x] Set up basic folder structure validation

## Database Layer

- [x] Create `api/db.php` - SQLite3 database connection handler
- [x] Create `api/init.php` - Database schema initialization
  - Create `activity` table (id, activity_name)
  - Create `team` table (id, team_name)
  - Create `score` table (id, activity_id, team_id, creative_score, participation_score, bribe_score)
- [x] Test database initialization and connection

## Authentication System

- [x] Create `login.php` - Authentication handler
  - Validate password against env.php configuration
  - Set secure cookie with hashed password
  - Redirect to activity.php on success
  - Show error message on failure
- [x] Implement authentication middleware for protected pages
- [x] Add logout functionality

## API Endpoints

- [x] Create `api/team.php` - Team CRUD operations
  - GET: List all teams
  - POST: Create new team
  - PUT: Update team
  - DELETE: Remove team
  - Require authentication cookie for modifications
- [x] Create `api/activity.php` - Activity CRUD operations
  - GET: List all activities
  - POST: Create new activity
  - PUT: Update activity
  - DELETE: Remove activity
  - Require authentication cookie for modifications
- [x] Create `api/score.php` - Score CRUD operations
  - GET: Retrieve scores (by activity, team, or all)
  - POST: Submit new score
  - PUT: Update existing score
  - DELETE: Remove score
  - Require authentication cookie for modifications

## Frontend Pages

- [x] Create `html/index.php` - Public standings page
  - Display team standings/rankings
  - Show login form for activity leaders
  - Implement responsive design with Bootstrap
- [x] Create `html/activity.php` - Activity management page (protected)
  - Dropdown to select activity from database
  - Score entry form for selected activity
  - Display all teams with current scores
  - Add new team option with name input
  - CRUD interface for scores

## Styling & Frontend Framework

- [x] Include Bootstrap CSS/JS for responsive styling
- [x] Include Alpine.js for frontend reactivity
- [x] Create base CSS styles for custom components
- [x] Implement mobile-responsive design
- [x] Add form validation and user feedback

## Features & Functionality

- [x] Implement real-time score updates (Alpine.js)
- [x] Add score calculation and team ranking logic
- [x] Create score entry validation (1-10 range)
- [x] Add confirmation dialogs for destructive operations
- [ ] Implement search/filter functionality for teams and activities

## Testing & Validation

- [x] Test database operations and data integrity
- [x] Validate authentication flow and session management
- [x] Test API endpoints with various scenarios
- [ ] Cross-browser compatibility testing
- [ ] Mobile device testing

## Documentation & Deployment

- [ ] Create installation instructions
- [ ] Document API endpoints and usage
- [ ] Add configuration guide
- [ ] Create backup/restore procedures
- [ ] Set up production deployment guidelines

## Security Considerations

- [ ] Implement CSRF protection
- [ ] Add input sanitization and validation
- [ ] Secure cookie configuration
- [ ] Add rate limiting for API endpoints
- [ ] Implement proper error handling without information disclosure

## Optional Enhancements

- [ ] Add export functionality for scores (CSV, PDF)
- [ ] Implement score history and audit trail
- [ ] Add team logos/images support
- [ ] Create admin dashboard for system management
- [ ] Add email notifications for score updates
- [ ] Implement backup scheduling

---

**Priority Order:**
1. Project setup and database layer
2. Authentication system
3. API endpoints
4. Frontend pages
5. Styling and user experience
6. Testing and security
7. Documentation and deployment