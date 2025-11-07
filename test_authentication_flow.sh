#!/bin/bash
# Comprehensive Authentication Flow Validation Test
# Tests login, logout, session management, and security

echo "=== Authentication Flow Validation Tests ==="
echo ""

BASE_URL="http://localhost:8000"
COOKIE_FILE="/tmp/auth_test_cookies_$$.txt"
ADMIN_PASSWORD="zoomoot2024"  # Should match env.php
WRONG_PASSWORD="wrongpassword"

TESTS_PASSED=0
TESTS_FAILED=0

# Clean up function
cleanup() {
    rm -f "$COOKIE_FILE"
}

trap cleanup EXIT

# Test function
test() {
    local description="$1"
    local command="$2"
    local expected="$3"
    
    echo "Test: $description"
    result=$(eval "$command" 2>&1)
    
    if echo "$result" | grep -q "$expected"; then
        echo "✓ PASS"
        ((TESTS_PASSED++))
    else
        echo "✗ FAIL"
        echo "  Expected to find: $expected"
        echo "  Got: ${result:0:200}"
        ((TESTS_FAILED++))
    fi
    echo ""
}

echo "--- Login Tests ---"
echo ""

test "Login page is accessible" \
    "curl -s $BASE_URL/login.php" \
    "Login"

test "Login with correct password sets cookie and redirects" \
    "curl -s -c '$COOKIE_FILE' -I -d 'password=$ADMIN_PASSWORD' $BASE_URL/login.php | grep -i location" \
    "activity.php"

test "Login sets authentication cookie" \
    "cat '$COOKIE_FILE'" \
    "zoomoot_admin"

# Save cookie for subsequent tests
COOKIE_VALUE=$(grep zoomoot_admin "$COOKIE_FILE" | awk '{print $7}')

test "Login with wrong password shows error" \
    "curl -s -d 'password=$WRONG_PASSWORD' $BASE_URL/login.php" \
    "Invalid password"

test "Login with empty password shows error" \
    "curl -s -d 'password=' $BASE_URL/login.php" \
    "Please enter a password"

echo "--- Protected Page Access Tests ---"
echo ""

test "activity.php requires authentication (no cookie)" \
    "curl -s -I $BASE_URL/activity.php | grep -i 'location'" \
    "login.php"

test "teams.php requires authentication (no cookie)" \
    "curl -s -I $BASE_URL/teams.php | grep -i 'location'" \
    "login.php"

test "activities.php requires authentication (no cookie)" \
    "curl -s -I $BASE_URL/activities.php | grep -i 'location'" \
    "login.php"

## Manually test with two-step process: login then access
rm -f "$COOKIE_FILE"
curl -s -c "$COOKIE_FILE" -d "password=$ADMIN_PASSWORD" $BASE_URL/login.php > /dev/null

test "activity.php accessible with valid cookie" \
    "curl -s -b '$COOKIE_FILE' $BASE_URL/activity.php | head -50" \
    "Activity"

test "teams.php accessible with valid cookie" \
    "curl -s -b '$COOKIE_FILE' $BASE_URL/teams.php | head -50" \
    "Team"

test "activities.php accessible with valid cookie" \
    "curl -s -b '$COOKIE_FILE' $BASE_URL/activities.php | head -50" \
    "Activity"

echo "--- Session Management Tests ---"
echo ""

# Create a valid session first
rm -f "$COOKIE_FILE"
curl -s -c "$COOKIE_FILE" -L -d "password=$ADMIN_PASSWORD" $BASE_URL/login.php > /dev/null

test "Valid session allows API access" \
    "curl -s -b '$COOKIE_FILE' -X POST -H 'Content-Type: application/json' -d '{\"team_name\":\"Test Session Team\"}' $BASE_URL/api/team.php" \
    "success"

# Clean up the test team
curl -s -b "$COOKIE_FILE" -X DELETE -H 'Content-Type: application/json' -d '{"id":999}' $BASE_URL/api/team.php > /dev/null 2>&1

echo "--- Logout Tests ---"
echo ""

test "Logout clears session" \
    "curl -s -c '$COOKIE_FILE.new' -b '$COOKIE_FILE' -L $BASE_URL/logout.php" \
    "Login"

# Try to access protected page after logout
test "Protected page inaccessible after logout" \
    "curl -s -I -b '$COOKIE_FILE.new' $BASE_URL/activity.php | grep -i 'location'" \
    "login.php"

rm -f "$COOKIE_FILE.new"

echo "--- Security Tests ---"
echo ""

test "Invalid cookie format is rejected" \
    "echo 'localhost:8000	FALSE	/	FALSE	0	zoomoot_admin	invalid_token_format' > '$COOKIE_FILE.bad' && curl -s -I -b '$COOKIE_FILE.bad' $BASE_URL/activity.php | grep -i 'location'" \
    "login.php"

rm -f "$COOKIE_FILE.bad"

test "API rejects unauthorized POST requests" \
    "curl -s -X POST -H 'Content-Type: application/json' -d '{\"team_name\":\"Unauthorized Team\"}' $BASE_URL/api/team.php" \
    "Authentication required"

test "API rejects unauthorized PUT requests" \
    "curl -s -X PUT -H 'Content-Type: application/json' -d '{\"id\":1,\"team_name\":\"Hacked Team\"}' $BASE_URL/api/team.php" \
    "Authentication required"

test "API rejects unauthorized DELETE requests" \
    "curl -s -X DELETE -H 'Content-Type: application/json' -d '{\"id\":1}' $BASE_URL/api/team.php" \
    "Authentication required"

test "Public API endpoints allow GET without auth" \
    "curl -s $BASE_URL/api/team.php" \
    "success"

echo "--- Session Cookie Security ---"
echo ""

# Login and check cookie attributes  
rm -f "$COOKIE_FILE"
HEADERS=$(curl -s -c "$COOKIE_FILE" -i -L -d "password=$ADMIN_PASSWORD" $BASE_URL/login.php 2>&1)

test "Cookie is set in response" \
    "echo '$HEADERS' | grep -i 'set-cookie'" \
    "zoomoot_admin"

test "Cookie has path attribute" \
    "cat '$COOKIE_FILE' | grep zoomoot_admin" \
    "/"

echo "--- Concurrent Session Tests ---"
echo ""

# Create two separate sessions
COOKIE1="/tmp/auth_test_cookie1_$$.txt"
COOKIE2="/tmp/auth_test_cookie2_$$.txt"

curl -s -c "$COOKIE1" -d "password=$ADMIN_PASSWORD" $BASE_URL/login.php > /dev/null
sleep 1
curl -s -c "$COOKIE2" -d "password=$ADMIN_PASSWORD" $BASE_URL/login.php > /dev/null

test "Multiple concurrent sessions are allowed" \
    "curl -s -b '$COOKIE1' $BASE_URL/activity.php | head -50 && curl -s -b '$COOKIE2' $BASE_URL/activity.php | head -50" \
    "Activity"

rm -f "$COOKIE1" "$COOKIE2"

echo ""
echo "=== Test Summary ==="
echo "Tests Passed: $TESTS_PASSED"
echo "Tests Failed: $TESTS_FAILED"
echo "Total Tests: $((TESTS_PASSED + TESTS_FAILED))"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo "✓ All authentication flow tests passed!"
    exit 0
else
    echo "✗ Some tests failed. Please review."
    exit 1
fi
