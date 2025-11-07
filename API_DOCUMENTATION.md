# API Documentation - Zoomoot Score Tracker

This document describes all available REST API endpoints for the Zoomoot Score Tracker system.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Common Patterns](#common-patterns)
- [Team Endpoints](#team-endpoints)
- [Activity Endpoints](#activity-endpoints)
- [Score Endpoints](#score-endpoints)
- [Error Handling](#error-handling)

## Overview

**Base URL**: All API endpoints are located under `/api/`

**Data Format**: All requests and responses use JSON format

**Authentication**: All API endpoints require authentication via session cookie (obtained through login)

**HTTP Methods**:
- `GET` - Retrieve resources
- `POST` - Create new resources
- `PUT` - Update existing resources
- `DELETE` - Delete resources

## Authentication

### Login

Before accessing any API endpoints, you must authenticate:

**Endpoint**: `POST /login.php`

**Request Body**:
```json
{
  "password": "your-admin-password"
}
```

**Request (curl)**:
```bash
curl -X POST http://localhost:8000/login.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "password=your-admin-password" \
  -c cookies.txt
```

**Success Response (302 Redirect)**:
```
HTTP/1.1 302 Found
Location: activity.php
Set-Cookie: zoomoot_admin=abc123...; HttpOnly; SameSite=Strict
```

**Error Response (401 Unauthorized)**:
```html
<!DOCTYPE html>
<!-- Login page with error message -->
```

### Using Authentication

After login, include the session cookie in all subsequent requests:

```bash
curl http://localhost:8000/api/team.php \
  -b cookies.txt
```

### Logout

**Endpoint**: `GET /logout.php`

**Request (curl)**:
```bash
curl http://localhost:8000/logout.php \
  -b cookies.txt \
  -c cookies.txt
```

**Response**: 302 Redirect to index.php with cookie cleared

## Common Patterns

### Successful Response Format

All successful API responses follow this pattern:

```json
{
  "success": true,
  "data": { /* resource data or array */ }
}
```

### Error Response Format

All error responses follow this pattern:

```json
{
  "success": false,
  "error": "Error message describing what went wrong"
}
```

### HTTP Status Codes

- `200 OK` - Request succeeded
- `400 Bad Request` - Invalid input data
- `401 Unauthorized` - Authentication required
- `404 Not Found` - Resource not found
- `409 Conflict` - Cannot delete due to dependencies
- `500 Internal Server Error` - Server-side error

## Team Endpoints

### Get All Teams

Retrieve a list of all teams.

**Endpoint**: `GET /api/team.php`

**Authentication**: Required

**Request**:
```bash
curl http://localhost:8000/api/team.php \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "team_name": "Red Team",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "team_name": "Blue Team",
      "created_at": "2024-01-15 10:31:00",
      "updated_at": "2024-01-15 10:31:00"
    }
  ]
}
```

### Get Single Team

Retrieve a specific team by ID.

**Endpoint**: `GET /api/team.php?id={team_id}`

**Authentication**: Required

**Parameters**:
- `id` (integer, required) - Team ID

**Request**:
```bash
curl "http://localhost:8000/api/team.php?id=1" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "team_name": "Red Team",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "error": "Team not found"
}
```

### Create Team

Create a new team.

**Endpoint**: `POST /api/team.php`

**Authentication**: Required

**Request Body**:
```json
{
  "team_name": "Green Team"
}
```

**Validation Rules**:
- `team_name` is required
- `team_name` must not be empty or only whitespace
- `team_name` must be unique

**Request**:
```bash
curl -X POST http://localhost:8000/api/team.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"team_name":"Green Team"}'
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "team_name": "Green Team",
    "created_at": "2024-01-20 14:22:00",
    "updated_at": "2024-01-20 14:22:00"
  }
}
```

**Error Response (400 Bad Request)**:
```json
{
  "success": false,
  "error": "Team name is required"
}
```

### Update Team

Update an existing team's name.

**Endpoint**: `PUT /api/team.php`

**Authentication**: Required

**Request Body**:
```json
{
  "id": 3,
  "team_name": "Emerald Team"
}
```

**Validation Rules**:
- `id` is required
- `team_name` is required
- `team_name` must not be empty or only whitespace
- `team_name` must be unique (except for current team)

**Request**:
```bash
curl -X PUT http://localhost:8000/api/team.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"id":3,"team_name":"Emerald Team"}'
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "team_name": "Emerald Team",
    "created_at": "2024-01-20 14:22:00",
    "updated_at": "2024-01-20 14:25:00"
  }
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "error": "Team not found"
}
```

### Delete Team

Delete a team (only if no scores exist for this team).

**Endpoint**: `DELETE /api/team.php?id={team_id}`

**Authentication**: Required

**Parameters**:
- `id` (integer, required) - Team ID

**Request**:
```bash
curl -X DELETE "http://localhost:8000/api/team.php?id=3" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "message": "Team deleted successfully"
  }
}
```

**Error Response (409 Conflict)** - Team has associated scores:
```json
{
  "success": false,
  "error": "Cannot delete team with existing scores",
  "score_count": 5
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "error": "Team not found"
}
```

## Activity Endpoints

### Get All Activities

Retrieve a list of all activities.

**Endpoint**: `GET /api/activity.php`

**Authentication**: Required

**Request**:
```bash
curl http://localhost:8000/api/activity.php \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "activity_name": "Tug of War",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "activity_name": "Relay Race",
      "created_at": "2024-01-15 10:31:00",
      "updated_at": "2024-01-15 10:31:00"
    }
  ]
}
```

### Get Single Activity

Retrieve a specific activity by ID.

**Endpoint**: `GET /api/activity.php?id={activity_id}`

**Authentication**: Required

**Parameters**:
- `id` (integer, required) - Activity ID

**Request**:
```bash
curl "http://localhost:8000/api/activity.php?id=1" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "activity_name": "Tug of War",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### Create Activity

Create a new activity.

**Endpoint**: `POST /api/activity.php`

**Authentication**: Required

**Request Body**:
```json
{
  "activity_name": "Scavenger Hunt"
}
```

**Validation Rules**:
- `activity_name` is required
- `activity_name` must not be empty or only whitespace
- `activity_name` must be unique

**Request**:
```bash
curl -X POST http://localhost:8000/api/activity.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"activity_name":"Scavenger Hunt"}'
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "activity_name": "Scavenger Hunt",
    "created_at": "2024-01-20 14:30:00",
    "updated_at": "2024-01-20 14:30:00"
  }
}
```

### Update Activity

Update an existing activity's name.

**Endpoint**: `PUT /api/activity.php`

**Authentication**: Required

**Request Body**:
```json
{
  "id": 3,
  "activity_name": "Treasure Hunt"
}
```

**Request**:
```bash
curl -X PUT http://localhost:8000/api/activity.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"id":3,"activity_name":"Treasure Hunt"}'
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "activity_name": "Treasure Hunt",
    "created_at": "2024-01-20 14:30:00",
    "updated_at": "2024-01-20 14:35:00"
  }
}
```

### Delete Activity

Delete an activity (only if no scores exist for this activity).

**Endpoint**: `DELETE /api/activity.php?id={activity_id}`

**Authentication**: Required

**Parameters**:
- `id` (integer, required) - Activity ID

**Request**:
```bash
curl -X DELETE "http://localhost:8000/api/activity.php?id=3" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "message": "Activity deleted successfully"
  }
}
```

**Error Response (409 Conflict)** - Activity has associated scores:
```json
{
  "success": false,
  "error": "Cannot delete activity with existing scores",
  "score_count": 8
}
```

## Score Endpoints

### Get All Scores

Retrieve all scores with team and activity details.

**Endpoint**: `GET /api/score.php`

**Authentication**: Required

**Request**:
```bash
curl http://localhost:8000/api/score.php \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "team_id": 1,
      "team_name": "Red Team",
      "activity_id": 1,
      "activity_name": "Tug of War",
      "score_1": 8,
      "score_2": 7,
      "score_3": 9,
      "total_score": 24,
      "created_at": "2024-01-20 09:00:00",
      "updated_at": "2024-01-20 09:00:00"
    },
    {
      "id": 2,
      "team_id": 2,
      "team_name": "Blue Team",
      "activity_id": 1,
      "activity_name": "Tug of War",
      "score_1": 9,
      "score_2": 8,
      "score_3": 10,
      "total_score": 27,
      "created_at": "2024-01-20 09:05:00",
      "updated_at": "2024-01-20 09:05:00"
    }
  ]
}
```

### Get Single Score

Retrieve a specific score by ID.

**Endpoint**: `GET /api/score.php?id={score_id}`

**Authentication**: Required

**Parameters**:
- `id` (integer, required) - Score ID

**Request**:
```bash
curl "http://localhost:8000/api/score.php?id=1" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "team_id": 1,
    "team_name": "Red Team",
    "activity_id": 1,
    "activity_name": "Tug of War",
    "score_1": 8,
    "score_2": 7,
    "score_3": 9,
    "total_score": 24,
    "created_at": "2024-01-20 09:00:00",
    "updated_at": "2024-01-20 09:00:00"
  }
}
```

### Get Scores by Activity

Retrieve all scores for a specific activity.

**Endpoint**: `GET /api/score.php?activity_id={activity_id}`

**Authentication**: Required

**Parameters**:
- `activity_id` (integer, required) - Activity ID

**Request**:
```bash
curl "http://localhost:8000/api/score.php?activity_id=1" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "team_id": 1,
      "team_name": "Red Team",
      "activity_id": 1,
      "activity_name": "Tug of War",
      "score_1": 8,
      "score_2": 7,
      "score_3": 9,
      "total_score": 24,
      "created_at": "2024-01-20 09:00:00",
      "updated_at": "2024-01-20 09:00:00"
    }
  ]
}
```

### Create Score

Create a new score entry.

**Endpoint**: `POST /api/score.php`

**Authentication**: Required

**Request Body**:
```json
{
  "team_id": 1,
  "activity_id": 2,
  "score_1": 7,
  "score_2": 8,
  "score_3": 9
}
```

**Validation Rules**:
- `team_id` is required and must exist
- `activity_id` is required and must exist
- `score_1`, `score_2`, `score_3` are required
- All scores must be integers between 1 and 10 (inclusive)
- Combination of `team_id` + `activity_id` must be unique

**Request**:
```bash
curl -X POST http://localhost:8000/api/score.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"team_id":1,"activity_id":2,"score_1":7,"score_2":8,"score_3":9}'
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "team_id": 1,
    "team_name": "Red Team",
    "activity_id": 2,
    "activity_name": "Relay Race",
    "score_1": 7,
    "score_2": 8,
    "score_3": 9,
    "total_score": 24,
    "created_at": "2024-01-20 14:40:00",
    "updated_at": "2024-01-20 14:40:00"
  }
}
```

**Error Response (400 Bad Request)** - Invalid score value:
```json
{
  "success": false,
  "error": "Scores must be between 1 and 10"
}
```

**Error Response (400 Bad Request)** - Duplicate entry:
```json
{
  "success": false,
  "error": "Score for this team and activity already exists"
}
```

### Update Score

Update an existing score entry.

**Endpoint**: `PUT /api/score.php`

**Authentication**: Required

**Request Body**:
```json
{
  "id": 3,
  "score_1": 8,
  "score_2": 9,
  "score_3": 10
}
```

**Validation Rules**:
- `id` is required
- At least one of `score_1`, `score_2`, `score_3` must be provided
- All provided scores must be integers between 1 and 10

**Request**:
```bash
curl -X PUT http://localhost:8000/api/score.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"id":3,"score_1":8,"score_2":9,"score_3":10}'
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "team_id": 1,
    "team_name": "Red Team",
    "activity_id": 2,
    "activity_name": "Relay Race",
    "score_1": 8,
    "score_2": 9,
    "score_3": 10,
    "total_score": 27,
    "created_at": "2024-01-20 14:40:00",
    "updated_at": "2024-01-20 14:45:00"
  }
}
```

### Delete Score

Delete a score entry.

**Endpoint**: `DELETE /api/score.php?id={score_id}`

**Authentication**: Required

**Parameters**:
- `id` (integer, required) - Score ID

**Request**:
```bash
curl -X DELETE "http://localhost:8000/api/score.php?id=3" \
  -b cookies.txt
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "message": "Score deleted successfully"
  }
}
```

**Error Response (404 Not Found)**:
```json
{
  "success": false,
  "error": "Score not found"
}
```

## Error Handling

### Authentication Errors

If you attempt to access any API endpoint without a valid session:

**Response (401 Unauthorized)**:
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

**Action**: Redirect to `/login.php` to authenticate.

### Validation Errors

When input data fails validation:

**Response (400 Bad Request)**:
```json
{
  "success": false,
  "error": "Descriptive error message"
}
```

Common validation errors:
- "Team name is required"
- "Activity name is required"
- "Scores must be between 1 and 10"
- "Team ID and Activity ID are required"
- "Invalid team ID or activity ID"

### Conflict Errors

When attempting to delete a resource that has dependencies:

**Response (409 Conflict)**:
```json
{
  "success": false,
  "error": "Cannot delete team with existing scores",
  "score_count": 5
}
```

**Action**: Delete all dependent scores first, then retry the deletion.

### Not Found Errors

When requesting a non-existent resource:

**Response (404 Not Found)**:
```json
{
  "success": false,
  "error": "Team not found"
}
```

### Server Errors

When an unexpected server error occurs:

**Response (500 Internal Server Error)**:
```json
{
  "success": false,
  "error": "An error occurred while processing your request"
}
```

**Note**: Detailed error messages are only shown when `DEBUG_MODE` is enabled in `env.php`.

## Rate Limiting

Currently, there are no rate limits imposed. For production deployments, consider implementing rate limiting at the web server level.

## Examples

### Complete Workflow Example

```bash
# 1. Login
curl -X POST http://localhost:8000/login.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "password=your-password" \
  -c cookies.txt

# 2. Create a new team
curl -X POST http://localhost:8000/api/team.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"team_name":"Yellow Team"}'

# 3. Create a new activity
curl -X POST http://localhost:8000/api/activity.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"activity_name":"Obstacle Course"}'

# 4. Add a score
curl -X POST http://localhost:8000/api/score.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"team_id":1,"activity_id":1,"score_1":8,"score_2":7,"score_3":9}'

# 5. Update the score
curl -X PUT http://localhost:8000/api/score.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"id":1,"score_1":9,"score_2":8,"score_3":10}'

# 6. Get all scores for an activity
curl "http://localhost:8000/api/score.php?activity_id=1" \
  -b cookies.txt

# 7. Logout
curl http://localhost:8000/logout.php \
  -b cookies.txt \
  -c cookies.txt
```

### JavaScript/Fetch Example

```javascript
// Login
const login = async (password) => {
  const response = await fetch('/login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `password=${encodeURIComponent(password)}`,
    credentials: 'same-origin'
  });
  return response.ok;
};

// Get all teams
const getTeams = async () => {
  const response = await fetch('/api/team.php', {
    credentials: 'same-origin'
  });
  const data = await response.json();
  return data.success ? data.data : [];
};

// Create a team
const createTeam = async (teamName) => {
  const response = await fetch('/api/team.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ team_name: teamName }),
    credentials: 'same-origin'
  });
  const data = await response.json();
  return data;
};

// Update a score
const updateScore = async (id, score1, score2, score3) => {
  const response = await fetch('/api/score.php', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ id, score_1: score1, score_2: score2, score_3: score3 }),
    credentials: 'same-origin'
  });
  const data = await response.json();
  return data;
};

// Delete a team
const deleteTeam = async (id) => {
  const response = await fetch(`/api/team.php?id=${id}`, {
    method: 'DELETE',
    credentials: 'same-origin'
  });
  const data = await response.json();
  return data;
};
```

## Best Practices

1. **Always check `success` field** in responses before accessing `data`
2. **Handle 409 Conflict** responses when deleting teams/activities
3. **Validate input** on client-side before sending to API
4. **Store session cookie** securely and include in all requests
5. **Check for 401 Unauthorized** and redirect to login when session expires
6. **Use HTTPS** in production to protect session cookies
7. **Implement rate limiting** at the application or web server level
8. **Log errors** for debugging but don't expose details to clients

## Related Documentation

- [Installation Guide](INSTALLATION.md) - Setup instructions
- [Configuration Guide](CONFIGURATION.md) - Environment configuration
- [Deployment Guide](DEPLOYMENT.md) - Production deployment

## Support

For issues or questions about the API, refer to the troubleshooting section in the [Installation Guide](INSTALLATION.md).
