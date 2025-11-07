# Zoomoot Score Tracker

A lightweight PHP-based score tracking system for managing team activities and competitive scores.

## Features

- **Team Management**: Create, edit, and manage team information
- **Activity Management**: Track multiple activities/events
- **Score Entry**: Enter and update scores for each team-activity combination
- **Public Standings**: Display real-time rankings and scores
- **Secure Admin Interface**: Password-protected management pages
- **RESTful API**: JSON-based API for all operations
- **SQLite Database**: Lightweight, file-based database with integrity constraints
- **Responsive UI**: Bootstrap-based interface with Alpine.js reactivity

## Quick Start

### Prerequisites

- PHP 8.0+ with SQLite3 extension
- Web server (Apache, Nginx, or PHP built-in server)
- SQLite 3.31.0+

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/score.zoomoot.ca.git
cd score.zoomoot.ca

# 2. Configure environment
cp env_sample.php env.php
nano env.php  # Edit with your settings

# 3. Initialize database
php html/api/init.php

# 4. Start development server
cd html
php -S localhost:8000

# 5. Open browser
# Navigate to http://localhost:8000
```

For detailed installation instructions, see [INSTALLATION.md](INSTALLATION.md).

## Documentation

- **[Installation Guide](INSTALLATION.md)** - Complete setup instructions with troubleshooting
- **[API Documentation](API_DOCUMENTATION.md)** - REST API endpoint reference with examples
- **[Configuration Guide](CONFIGURATION.md)** - Environment configuration and security settings
- **[Backup & Restore](BACKUP_RESTORE.md)** - Database backup and recovery procedures
- **[Deployment Guide](DEPLOYMENT.md)** - Production deployment checklist and guidelines

## Project Structure

```
score.zoomoot.ca/
├── html/                   # Web root directory
│   ├── api/               # REST API endpoints
│   │   ├── activity.php   # Activity CRUD operations
│   │   ├── auth.php       # Authentication handling
│   │   ├── db.php         # Database singleton
│   │   ├── init.php       # Database schema initialization
│   │   ├── score.php      # Score CRUD operations
│   │   └── team.php       # Team CRUD operations
│   ├── activities.php     # Activity management page
│   ├── activity.php       # Score entry page
│   ├── index.php          # Public standings page
│   ├── login.php          # Login page
│   ├── logout.php         # Logout handler
│   └── teams.php          # Team management page
├── score/                 # Database directory
│   └── zoomoot_scores.db  # SQLite database (created on init)
├── env_sample.php         # Sample environment configuration
├── env.php               # Environment configuration (create from sample)
├── .gitignore            # Git ignore rules
└── README.md             # This file
```

## Usage

### Public Access

- **View Standings**: Visit the homepage to see current team rankings and scores

### Admin Access

1. **Login**: Navigate to `/login.php` and enter the admin password (configured in `env.php`)
2. **Manage Activities**: Create, edit, or delete activities (events)
3. **Manage Teams**: Create, edit, or delete teams
4. **Enter Scores**: Record scores for each team in each activity (three scores per entry)
5. **View Results**: See real-time standings and rankings

### API Usage

All API endpoints require authentication. See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for complete details.

**Example - Get all teams**:
```bash
curl http://localhost:8000/api/team.php -b cookies.txt
```

**Example - Create a score**:
```bash
curl -X POST http://localhost:8000/api/score.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"team_id":1,"activity_id":1,"score_1":8,"score_2":7,"score_3":9}'
```

## Database Schema

### Tables

**activity**
- `id` (INTEGER, PRIMARY KEY)
- `activity_name` (TEXT, UNIQUE, NOT NULL)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**team**
- `id` (INTEGER, PRIMARY KEY)
- `team_name` (TEXT, UNIQUE, NOT NULL)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**score**
- `id` (INTEGER, PRIMARY KEY)
- `team_id` (INTEGER, FOREIGN KEY → team.id)
- `activity_id` (INTEGER, FOREIGN KEY → activity.id)
- `score_1` (INTEGER, CHECK: 1-10)
- `score_2` (INTEGER, CHECK: 1-10)
- `score_3` (INTEGER, CHECK: 1-10)
- `total_score` (INTEGER, GENERATED AS sum of scores)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- UNIQUE constraint on (team_id, activity_id)

### Features
- Foreign key constraints with CASCADE DELETE
- CHECK constraints for score validation (1-10 range)
- CHECK constraints for non-empty names
- Automatic timestamp triggers for updated_at
- Generated columns for total_score calculation
- WAL mode for better concurrency

## Testing

Run the included test suites:

```bash
# Database integrity tests
php test_database_integrity.php

# Authentication flow tests
bash test_authentication_flow.sh

# Simple API tests
bash test_api_simple.sh
```

## Security Features

- Session-based authentication with secure cookies
- HttpOnly and SameSite cookie flags
- Password hashing with salt
- SQL injection protection via prepared statements
- CSRF protection via SameSite cookies
- Foreign key constraints prevent orphaned records
- Input validation on all endpoints
- Debug mode toggle for production

## Technology Stack

- **Backend**: PHP 8.0+
- **Database**: SQLite 3.31.0+
- **Frontend**: Bootstrap 5.3.0
- **JavaScript**: Alpine.js 3.x (reactive components)
- **Architecture**: RESTful API, MVC pattern

## Configuration

Key settings in `env.php`:

```php
define('APP_NAME', 'Zoomoot Score Tracker');
define('DB_PATH', __DIR__ . '/score/zoomoot_scores.db');
define('ADMIN_PASSWORD', 'your-secure-password');  // CHANGE THIS
define('PASSWORD_SALT', 'your-random-salt');       // CHANGE THIS
define('SESSION_NAME', 'zoomoot_admin');
define('SESSION_LIFETIME', 3600);  // 1 hour
define('DEBUG_MODE', false);       // Set to false in production
```

**⚠️ Important**: Always change `ADMIN_PASSWORD` and `PASSWORD_SALT` from defaults before deployment.

See [CONFIGURATION.md](CONFIGURATION.md) for detailed configuration options.

## Production Deployment

For production deployment:

1. Follow the [Deployment Guide](DEPLOYMENT.md)
2. Enable HTTPS/SSL
3. Set `DEBUG_MODE = false` in `env.php`
4. Configure automated backups
5. Harden file permissions
6. Configure web server (Apache/Nginx)
7. Set up monitoring and logging

## Backup & Recovery

Backup the SQLite database file regularly:

```bash
# Simple backup
cp score/zoomoot_scores.db backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).db

# Using SQLite backup command
sqlite3 score/zoomoot_scores.db ".backup backups/backup_$(date +%Y%m%d).db"
```

See [BACKUP_RESTORE.md](BACKUP_RESTORE.md) for automated backup scripts and recovery procedures.

## Troubleshooting

### Database locked error
```bash
rm -f score/zoomoot_scores.db-wal
rm -f score/zoomoot_scores.db-shm
```

### Session not persisting
- Check PHP session directory permissions
- Verify `SESSION_LIFETIME` in `env.php`
- Clear browser cookies

### API returns 401 Unauthorized
- Ensure you're logged in first via `/login.php`
- Include session cookie in API requests

See [INSTALLATION.md](INSTALLATION.md#troubleshooting) for more troubleshooting tips.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Create a Pull Request

## License

[Specify your license here - e.g., MIT, GPL, etc.]

## Support

For issues, questions, or feature requests:
- Check the [documentation files](.) for detailed guides
- Review the [troubleshooting section](INSTALLATION.md#troubleshooting)
- Check existing issues in the repository

## Changelog

### Version 1.0.0
- Initial release
- Team and activity management
- Score entry and tracking
- Public standings display
- RESTful API
- Comprehensive documentation
- Test suites
- bootstrap.js for styling
- Alpine.js as the frontend framework

Folder structure:
/html all PHP and HTML content
/score location for the sqlite3 database file

DB structure:
- activity - fields for id, activity name
- team - fields for id, team name
- score - fields for id, activity id, team id, creative score (1-10), participation score (1-10), bribe score (1-10)

Features:
- index.php shows standings for teams, and a login password for activity leaders (submits to login.php)
- login.php shows sets a cookie with the hash of the fixed password defined in the env.php file.  Redirects to activity.php on successful login or shows an error.
- env_sample.php - contains salt, password, db name
- activity.php - has a drop down of events from the activity db table.  When an event is selected, shows a form with all available scores, and allows for CRUD operations the score table for that activity.  Should allow for a new team as the last option, which will prompt for the team name.
- api/db.php Connects to the sqlite3 db.
- api/init.php Initializes the sqlite3 db schema
- api/team.php CRUD on team table, must have cookie set to alter table
- api/activity.php CRUD on activity table, must have cookie set to alter table
- api/score.php CRUD on score table, must have cookie set to alter table
