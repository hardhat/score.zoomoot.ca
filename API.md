# API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All `POST`, `PUT`, and `DELETE` operations require authentication. Users must login via `/login.php` which sets a secure cookie.

**Authentication Response (if not authenticated):**
```json
{
  "success": false,
  "error": "Authentication required"
}
```

---

## Team API (`/api/team.php`)

### GET - List All Teams
**Endpoint:** `GET /api/team.php`

**Authentication:** Not required

**Query Parameters:**
- `id` (optional) - Get specific team by ID
- `stats=true` (optional) - Include score statistics

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "team_name": "Team Alpha",
      "created_at": "2025-11-07 05:31:59",
      "updated_at": "2025-11-07 05:31:59"
    }
  ],
  "count": 5
}
```

**With Stats:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "team_name": "Team Alpha",
      "activities_participated": 2,
      "total_score": 42,
      "avg_score": 21.0
    }
  ],
  "count": 5
}
```

### POST - Create New Team
**Endpoint:** `POST /api/team.php`

**Authentication:** Required

**Request Body:**
```json
{
  "team_name": "New Team Name"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Team created successfully",
  "data": {
    "id": 6,
    "team_name": "New Team Name",
    "created_at": "2025-11-07 06:00:00",
    "updated_at": "2025-11-07 06:00:00"
  }
}
```

**Error (409 Conflict - duplicate name):**
```json
{
  "success": false,
  "error": "Team name already exists"
}
```

### PUT - Update Team
**Endpoint:** `PUT /api/team.php`

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1,
  "team_name": "Updated Team Name"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Team updated successfully",
  "data": {
    "id": 1,
    "team_name": "Updated Team Name",
    "updated_at": "2025-11-07 06:00:00"
  }
}
```

### DELETE - Delete Team
**Endpoint:** `DELETE /api/team.php`

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Team deleted successfully"
}
```

**Note:** Deleting a team will cascade delete all associated scores.

---

## Activity API (`/api/activity.php`)

### GET - List All Activities
**Endpoint:** `GET /api/activity.php`

**Authentication:** Not required

**Query Parameters:**
- `id` (optional) - Get specific activity by ID
- `stats=true` (optional) - Include participation statistics

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "activity_name": "Trivia Challenge",
      "created_at": "2025-11-07 05:31:59",
      "updated_at": "2025-11-07 05:31:59"
    }
  ],
  "count": 4
}
```

### POST - Create New Activity
**Endpoint:** `POST /api/activity.php`

**Authentication:** Required

**Request Body:**
```json
{
  "activity_name": "New Activity"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Activity created successfully",
  "data": {
    "id": 5,
    "activity_name": "New Activity",
    "created_at": "2025-11-07 06:00:00",
    "updated_at": "2025-11-07 06:00:00"
  }
}
```

### PUT - Update Activity
**Endpoint:** `PUT /api/activity.php`

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1,
  "activity_name": "Updated Activity Name"
}
```

### DELETE - Delete Activity
**Endpoint:** `DELETE /api/activity.php`

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1
}
```

**Note:** Deleting an activity will cascade delete all associated scores.

---

## Score API (`/api/score.php`)

### GET - Retrieve Scores
**Endpoint:** `GET /api/score.php`

**Authentication:** Not required

**Query Parameters:**
- `id` (optional) - Get specific score by ID
- `activity_id` (optional) - Get all scores for an activity
- `team_id` (optional) - Get all scores for a team

**Response (all scores):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "activity_id": 1,
      "team_id": 1,
      "creative_score": 8,
      "participation_score": 9,
      "bribe_score": 7,
      "total_score": 24,
      "team_name": "Team Alpha",
      "activity_name": "Trivia Challenge",
      "created_at": "2025-11-07 05:35:03",
      "updated_at": "2025-11-07 05:35:03"
    }
  ],
  "count": 5
}
```

### POST - Submit New Score
**Endpoint:** `POST /api/score.php`

**Authentication:** Required

**Request Body:**
```json
{
  "activity_id": 1,
  "team_id": 1,
  "creative_score": 8,
  "participation_score": 9,
  "bribe_score": 7
}
```

**Validation:**
- All scores must be between 1 and 10 (inclusive)
- `activity_id` must reference an existing activity
- `team_id` must reference an existing team
- Each team can only have one score per activity

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Score submitted successfully",
  "data": {
    "id": 6,
    "activity_id": 1,
    "team_id": 1,
    "creative_score": 8,
    "participation_score": 9,
    "bribe_score": 7,
    "total_score": 24,
    "team_name": "Team Alpha",
    "activity_name": "Trivia Challenge"
  }
}
```

**Error (400 Bad Request - invalid score):**
```json
{
  "success": false,
  "error": "Creative score must be between 1 and 10"
}
```

**Error (409 Conflict - duplicate):**
```json
{
  "success": false,
  "error": "Score already exists for this team and activity. Use PUT to update."
}
```

### PUT - Update Score
**Endpoint:** `PUT /api/score.php`

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1,
  "creative_score": 10,
  "participation_score": 10,
  "bribe_score": 10
}
```

**Note:** You can update individual scores or all three. Omitted scores will retain their current values.

**Response:**
```json
{
  "success": true,
  "message": "Score updated successfully",
  "data": {
    "id": 1,
    "creative_score": 10,
    "participation_score": 10,
    "bribe_score": 10,
    "total_score": 30
  }
}
```

### DELETE - Delete Score
**Endpoint:** `DELETE /api/score.php`

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Score deleted successfully"
}
```

---

## Error Responses

### 400 Bad Request
Missing required fields or invalid data:
```json
{
  "success": false,
  "error": "Team name is required"
}
```

### 401 Unauthorized
Authentication required:
```json
{
  "success": false,
  "error": "Authentication required"
}
```

### 404 Not Found
Resource not found:
```json
{
  "success": false,
  "error": "Team not found"
}
```

### 405 Method Not Allowed
Invalid HTTP method:
```json
{
  "success": false,
  "error": "Method not allowed"
}
```

### 409 Conflict
Duplicate entry or constraint violation:
```json
{
  "success": false,
  "error": "Team name already exists"
}
```

### 500 Internal Server Error
Server error (detailed message only if DEBUG_MODE is enabled):
```json
{
  "success": false,
  "error": "Internal server error"
}
```

---

## Examples

### cURL Examples

**Get all teams:**
```bash
curl http://localhost:8000/api/team.php
```

**Get teams with statistics:**
```bash
curl http://localhost:8000/api/team.php?stats=true
```

**Get scores for activity 1:**
```bash
curl http://localhost:8000/api/score.php?activity_id=1
```

**Create a new team (requires authentication):**
```bash
# First login to get cookie
curl -c cookies.txt -d "password=admin123" http://localhost:8000/login.php

# Then use cookie for API request
curl -b cookies.txt -X POST -H "Content-Type: application/json" \
  -d '{"team_name":"New Team"}' \
  http://localhost:8000/api/team.php
```

**Submit a score:**
```bash
curl -b cookies.txt -X POST -H "Content-Type: application/json" \
  -d '{"activity_id":1,"team_id":2,"creative_score":9,"participation_score":8,"bribe_score":10}' \
  http://localhost:8000/api/score.php
```

---

## Features

✅ **RESTful Design** - Standard HTTP methods (GET, POST, PUT, DELETE)  
✅ **JSON Responses** - All responses in JSON format  
✅ **Authentication** - Cookie-based authentication for modifications  
✅ **Validation** - Input validation and constraint checking  
✅ **Error Handling** - Proper HTTP status codes and error messages  
✅ **Calculated Fields** - Automatic total_score calculation  
✅ **Foreign Keys** - Cascade deletes for data integrity  
✅ **Query Filtering** - Get data by ID, activity, or team  
✅ **Statistics** - Optional stats for teams and activities  

---

## Testing

All API endpoints have been tested and verified:
- ✓ Public GET endpoints working
- ✓ Authentication required for modifications
- ✓ Score validation (1-10 range)
- ✓ Duplicate prevention (unique constraints)
- ✓ Cascade deletes
- ✓ Total score calculation
- ✓ Error handling

See `test_api_simple.sh` and `test_api_direct.php` for test scripts.
