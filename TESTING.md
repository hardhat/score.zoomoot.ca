# Database Testing Results

## ✅ All Tests Passed - November 7, 2025

### Database Schema Created Successfully
All three tables created with proper structure:
- `activity` table with id, activity_name, timestamps
- `team` table with id, team_name, timestamps  
- `score` table with id, activity_id, team_id, creative_score, participation_score, bribe_score, total_score (calculated), timestamps

### Sample Data Loaded
- **4 Activities**: Trivia Challenge, Creative Showcase, Team Building Exercise, Presentation Contest
- **5 Teams**: Team Alpha, Team Beta, Team Gamma, Team Delta, Team Epsilon

### Constraint Validation Tests

#### ✅ CHECK Constraint (Score Range 1-10)
```
Error: stepping, CHECK constraint failed: creative_score >= 1 AND creative_score <= 10
```
Correctly rejected score value of 11.

#### ✅ UNIQUE Constraint (One Score Per Team Per Activity)
```
Error: stepping, UNIQUE constraint failed: score.activity_id, score.team_id
```
Correctly prevented duplicate scores for same team/activity combination.

#### ✅ Calculated Field (total_score)
```
INSERT: creative_score=8, participation_score=9, bribe_score=7
RESULT: total_score=24 (automatically calculated)
```

### Sample Queries Verified

#### Scores by Activity
```
Team Beta|Creative Showcase|8|9|7|24
Team Alpha|Creative Showcase|6|7|5|18
Team Gamma|Trivia Challenge|9|10|8|27
Team Alpha|Trivia Challenge|8|9|7|24
Team Beta|Trivia Challenge|7|8|6|21
```

#### Overall Team Standings
```
team_name     activities_participated  total_score  avg_score
------------  -----------------------  -----------  ---------
Team Beta     2                        45           22.5
Team Alpha    2                        42           21.0
Team Gamma    1                        27           27.0
Team Delta    0                        -            -
Team Epsilon  0                        -            -
```

### Foreign Key Relationships
- `score.activity_id` → `activity.id` (ON DELETE CASCADE)
- `score.team_id` → `team.id` (ON DELETE CASCADE)
- Foreign keys enabled with `PRAGMA foreign_keys = ON`

### Database Features Confirmed
- ✅ WAL mode enabled for better concurrency
- ✅ Transaction support working
- ✅ Auto-increment primary keys
- ✅ Timestamp defaults working
- ✅ Joins working correctly

## Testing Commands

### Initialize Database
```bash
php api/init.php init
```

### Verify Schema
```bash
sqlite3 score/zoomoot_scores.db '.schema'
```

### View Data
```bash
sqlite3 score/zoomoot_scores.db 'SELECT * FROM activity;'
sqlite3 score/zoomoot_scores.db 'SELECT * FROM team;'
sqlite3 score/zoomoot_scores.db 'SELECT * FROM score;'
```

### Reset Database
```bash
php api/init.php drop
php api/init.php init
```

---

**Conclusion**: Database layer is fully functional and ready for application integration.
