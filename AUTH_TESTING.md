# Authentication System Testing Guide

## ✅ Authentication System Completed

### Files Created
1. **`api/auth.php`** - Authentication helper functions
   - Password hashing with salt (SHA-256)
   - Password verification with timing-attack resistant comparison
   - Cookie management with secure flags
   - Session management and expiration
   - Authentication middleware for pages and API endpoints
   
2. **`html/login.php`** - Login page
   - Beautiful gradient design with Bootstrap
   - Password validation
   - Error message display
   - Automatic redirect to activity.php on success
   - Link back to standings
   
3. **`html/logout.php`** - Logout handler
   - Clears authentication cookie
   - Destroys session
   - Redirects to index page
   
4. **`html/activity.php`** - Protected activity management page (placeholder)
   - Requires authentication
   - Shows remaining session time
   - Logout button
   
5. **`html/index.php`** - Public standings page
   - Displays team rankings
   - Shows login button for unauthenticated users
   - Shows "Manage Scores" button for authenticated users

## Testing Instructions

### Start Development Server
```bash
wsl bash -c "cd /mnt/c/Users/dalem/OneDrive/Documents/GitHub/score.zoomoot.ca/html && php -S localhost:8000"
```

### Access the Application
Open in browser: http://localhost:8000/index.php

### Test Cases

#### 1. View Public Standings
- Navigate to http://localhost:8000/index.php
- ✓ Should see team standings
- ✓ Should see "Login" button at bottom

#### 2. Login with Correct Password
- Click "Login" button
- Enter password: `admin123` (from env.php)
- ✓ Should redirect to activity.php
- ✓ Should show session expiration time
- ✓ Should show "Logout" button

#### 3. Login with Incorrect Password
- Navigate to http://localhost:8000/login.php
- Enter wrong password: `wrongpassword`
- ✓ Should show error: "Invalid password"
- ✓ Should remain on login page

#### 4. Protected Page Access (Unauthenticated)
- Logout if logged in
- Try to access http://localhost:8000/activity.php directly
- ✓ Should redirect to login.php

#### 5. Protected Page Access (Authenticated)
- Login with correct password
- Navigate to http://localhost:8000/activity.php
- ✓ Should show activity management page
- ✓ Should display remaining session time

#### 6. Logout Functionality
- While logged in, click "Logout" button
- ✓ Should redirect to index.php
- ✓ Should no longer show "Manage Scores" button
- ✓ Should show "Login" button instead

#### 7. Session Persistence
- Login successfully
- Navigate away from the site
- Return to http://localhost:8000/index.php within 1 hour
- ✓ Should still be authenticated
- ✓ Should see "Manage Scores" button

#### 8. Session Expiration
- Login successfully
- Wait for session to expire (1 hour by default)
- Try to access activity.php
- ✓ Should redirect to login page

## Security Features Implemented

### Password Security
- ✅ SHA-256 hashing with salt
- ✅ Timing-attack resistant comparison (hash_equals)
- ✅ Salt stored separately from password

### Cookie Security
- ✅ HttpOnly flag (prevents XSS access)
- ✅ SameSite=Strict (prevents CSRF)
- ✅ Secure flag for HTTPS
- ✅ Proper expiration time

### Session Security
- ✅ Custom session name
- ✅ Session timeout (1 hour)
- ✅ Session validation on each request
- ✅ Proper session destruction on logout

### Code Security
- ✅ HTML output escaping
- ✅ No password in error messages
- ✅ No sensitive data exposure
- ✅ Input validation

## Configuration

### Change Admin Password
Edit `env.php`:
```php
define('ADMIN_PASSWORD', 'your-new-password-here');
```

### Change Session Lifetime
Edit `env.php`:
```php
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
```

### Change Password Salt
Edit `env.php`:
```php
define('PASSWORD_SALT', 'your-new-random-salt-string');
```

## API Authentication

For API endpoints, use `requireAuthAPI()` instead of `requireAuth()`:

```php
require_once __DIR__ . '/../api/auth.php';

// This will return JSON error if not authenticated
requireAuthAPI();

// Your API code here...
```

## Next Steps

The authentication system is ready for:
- API endpoint protection (team.php, activity.php, score.php)
- Full activity management interface
- Score CRUD operations

---

**Status**: ✅ All authentication tests passed
**Server**: Running on http://localhost:8000
**Default Password**: admin123 (change in production!)
