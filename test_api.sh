#!/bin/bash
# API Testing Script
# Tests all CRUD operations for team, activity, and score endpoints

BASE_URL="http://localhost:8000"
API_URL="${BASE_URL}/api"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test result
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC}: $2"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC}: $2"
        ((TESTS_FAILED++))
    fi
}

# Function to print section header
print_header() {
    echo ""
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}$1${NC}"
    echo -e "${YELLOW}========================================${NC}"
}

# Function to get auth cookie (login)
get_auth_cookie() {
    COOKIE_JAR=$(mktemp)
    curl -s -c "$COOKIE_JAR" -d "password=admin123" "${BASE_URL}/login.php" > /dev/null
    echo "$COOKIE_JAR"
}

# Start testing
echo "Starting API Tests..."
echo "Base URL: $BASE_URL"

# Get authentication cookie
COOKIE_JAR=$(get_auth_cookie)
echo "Authentication cookie obtained: $COOKIE_JAR"

# =====================================
# TEAM API TESTS
# =====================================

print_header "TEAM API TESTS"

# Test 1: GET all teams (public)
echo -n "Test: GET all teams (public)... "
RESPONSE=$(curl -s "${API_URL}/team.php")
if echo "$RESPONSE" | grep -q '"success":true'; then
    print_result 0 "GET all teams"
else
    print_result 1 "GET all teams"
    echo "Response: $RESPONSE"
fi

# Test 2: GET teams with stats
echo -n "Test: GET teams with stats... "
RESPONSE=$(curl -s "${API_URL}/team.php?stats=true")
if echo "$RESPONSE" | grep -q '"success":true' && echo "$RESPONSE" | grep -q 'total_score'; then
    print_result 0 "GET teams with stats"
else
    print_result 1 "GET teams with stats"
    echo "Response: $RESPONSE"
fi

# Test 3: POST new team (authenticated)
echo -n "Test: POST new team (authenticated)... "
RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST -H "Content-Type: application/json" \
    -d '{"team_name":"Test Team API"}' "${API_URL}/team.php")
if echo "$RESPONSE" | grep -q '"success":true'; then
    TEST_TEAM_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
    print_result 0 "POST new team (ID: $TEST_TEAM_ID)"
else
    print_result 1 "POST new team"
    echo "Response: $RESPONSE"
fi

# Test 4: POST duplicate team (should fail)
echo -n "Test: POST duplicate team (should fail)... "
RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST -H "Content-Type: application/json" \
    -d '{"team_name":"Test Team API"}' "${API_URL}/team.php")
if echo "$RESPONSE" | grep -q '"success":false' && echo "$RESPONSE" | grep -q 'already exists'; then
    print_result 0 "POST duplicate team correctly rejected"
else
    print_result 1 "POST duplicate team should have been rejected"
    echo "Response: $RESPONSE"
fi

# Test 5: PUT update team (authenticated)
if [ ! -z "$TEST_TEAM_ID" ]; then
    echo -n "Test: PUT update team... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X PUT -H "Content-Type: application/json" \
        -d "{\"id\":$TEST_TEAM_ID,\"team_name\":\"Updated Test Team\"}" "${API_URL}/team.php")
    if echo "$RESPONSE" | grep -q '"success":true' && echo "$RESPONSE" | grep -q 'Updated Test Team'; then
        print_result 0 "PUT update team"
    else
        print_result 1 "PUT update team"
        echo "Response: $RESPONSE"
    fi
fi

# Test 6: POST without auth (should fail)
echo -n "Test: POST without auth (should fail)... "
RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" \
    -d '{"team_name":"Unauthorized Team"}' "${API_URL}/team.php")
if echo "$RESPONSE" | grep -q '"success":false' && echo "$RESPONSE" | grep -q 'Authentication required'; then
    print_result 0 "POST without auth correctly rejected"
else
    print_result 1 "POST without auth should have been rejected"
    echo "Response: $RESPONSE"
fi

# =====================================
# ACTIVITY API TESTS
# =====================================

print_header "ACTIVITY API TESTS"

# Test 7: GET all activities (public)
echo -n "Test: GET all activities... "
RESPONSE=$(curl -s "${API_URL}/activity.php")
if echo "$RESPONSE" | grep -q '"success":true'; then
    print_result 0 "GET all activities"
else
    print_result 1 "GET all activities"
    echo "Response: $RESPONSE"
fi

# Test 8: POST new activity (authenticated)
echo -n "Test: POST new activity... "
RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST -H "Content-Type: application/json" \
    -d '{"activity_name":"Test Activity API"}' "${API_URL}/activity.php")
if echo "$RESPONSE" | grep -q '"success":true'; then
    TEST_ACTIVITY_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
    print_result 0 "POST new activity (ID: $TEST_ACTIVITY_ID)"
else
    print_result 1 "POST new activity"
    echo "Response: $RESPONSE"
fi

# Test 9: PUT update activity
if [ ! -z "$TEST_ACTIVITY_ID" ]; then
    echo -n "Test: PUT update activity... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X PUT -H "Content-Type: application/json" \
        -d "{\"id\":$TEST_ACTIVITY_ID,\"activity_name\":\"Updated Test Activity\"}" "${API_URL}/activity.php")
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_result 0 "PUT update activity"
    else
        print_result 1 "PUT update activity"
        echo "Response: $RESPONSE"
    fi
fi

# =====================================
# SCORE API TESTS
# =====================================

print_header "SCORE API TESTS"

# Test 10: GET all scores (public)
echo -n "Test: GET all scores... "
RESPONSE=$(curl -s "${API_URL}/score.php")
if echo "$RESPONSE" | grep -q '"success":true'; then
    print_result 0 "GET all scores"
else
    print_result 1 "GET all scores"
    echo "Response: $RESPONSE"
fi

# Test 11: POST new score (authenticated)
if [ ! -z "$TEST_ACTIVITY_ID" ] && [ ! -z "$TEST_TEAM_ID" ]; then
    echo -n "Test: POST new score... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST -H "Content-Type: application/json" \
        -d "{\"activity_id\":$TEST_ACTIVITY_ID,\"team_id\":$TEST_TEAM_ID,\"creative_score\":8,\"participation_score\":9,\"bribe_score\":7}" \
        "${API_URL}/score.php")
    if echo "$RESPONSE" | grep -q '"success":true' && echo "$RESPONSE" | grep -q '"total_score":24'; then
        TEST_SCORE_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
        print_result 0 "POST new score (ID: $TEST_SCORE_ID, Total: 24)"
    else
        print_result 1 "POST new score"
        echo "Response: $RESPONSE"
    fi
fi

# Test 12: POST score with invalid range (should fail)
if [ ! -z "$TEST_ACTIVITY_ID" ]; then
    echo -n "Test: POST score with invalid range (should fail)... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST -H "Content-Type: application/json" \
        -d "{\"activity_id\":$TEST_ACTIVITY_ID,\"team_id\":1,\"creative_score\":11,\"participation_score\":9,\"bribe_score\":7}" \
        "${API_URL}/score.php")
    if echo "$RESPONSE" | grep -q '"success":false' && echo "$RESPONSE" | grep -q 'between 1 and 10'; then
        print_result 0 "POST invalid score correctly rejected"
    else
        print_result 1 "POST invalid score should have been rejected"
        echo "Response: $RESPONSE"
    fi
fi

# Test 13: PUT update score
if [ ! -z "$TEST_SCORE_ID" ]; then
    echo -n "Test: PUT update score... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X PUT -H "Content-Type: application/json" \
        -d "{\"id\":$TEST_SCORE_ID,\"creative_score\":10,\"participation_score\":10,\"bribe_score\":10}" \
        "${API_URL}/score.php")
    if echo "$RESPONSE" | grep -q '"success":true' && echo "$RESPONSE" | grep -q '"total_score":30'; then
        print_result 0 "PUT update score (New Total: 30)"
    else
        print_result 1 "PUT update score"
        echo "Response: $RESPONSE"
    fi
fi

# Test 14: GET scores by activity
if [ ! -z "$TEST_ACTIVITY_ID" ]; then
    echo -n "Test: GET scores by activity... "
    RESPONSE=$(curl -s "${API_URL}/score.php?activity_id=$TEST_ACTIVITY_ID")
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_result 0 "GET scores by activity"
    else
        print_result 1 "GET scores by activity"
        echo "Response: $RESPONSE"
    fi
fi

# =====================================
# CLEANUP
# =====================================

print_header "CLEANUP"

# Delete test score
if [ ! -z "$TEST_SCORE_ID" ]; then
    echo -n "Cleanup: DELETE test score... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X DELETE -H "Content-Type: application/json" \
        -d "{\"id\":$TEST_SCORE_ID}" "${API_URL}/score.php")
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_result 0 "DELETE test score"
    else
        print_result 1 "DELETE test score"
    fi
fi

# Delete test activity
if [ ! -z "$TEST_ACTIVITY_ID" ]; then
    echo -n "Cleanup: DELETE test activity... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X DELETE -H "Content-Type: application/json" \
        -d "{\"id\":$TEST_ACTIVITY_ID}" "${API_URL}/activity.php")
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_result 0 "DELETE test activity"
    else
        print_result 1 "DELETE test activity"
    fi
fi

# Delete test team
if [ ! -z "$TEST_TEAM_ID" ]; then
    echo -n "Cleanup: DELETE test team... "
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -X DELETE -H "Content-Type: application/json" \
        -d "{\"id\":$TEST_TEAM_ID}" "${API_URL}/team.php")
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_result 0 "DELETE test team"
    else
        print_result 1 "DELETE test team"
    fi
fi

# Remove cookie jar
rm -f "$COOKIE_JAR"

# =====================================
# SUMMARY
# =====================================

print_header "TEST SUMMARY"
TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))
echo -e "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    echo -e "\n${RED}Some tests failed! ✗${NC}"
    exit 1
fi
