#!/bin/bash

# Configuration
FRONTEND_URL="http://localhost:80"
BACKEND_URL="http://localhost:8000"
DB_TEST_ENDPOINT="/api/health-check" # Assumed endpoint, need to verify or create

echo "=========================================="
echo " Starting Docker Integration Tests"
echo "=========================================="

# 1. Check if containers are running
echo "[1/3] Checking Docker Containers..."
if docker-compose ps | grep "Up"; then
    echo "✅ Containers are running."
else
    echo "❌ Containers generate errors or are not running."
    exit 1
fi

# 2. Test Frontend
echo "[2/3] Testing Frontend Connectivity ($FRONTEND_URL)..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $FRONTEND_URL)
if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✅ Frontend is reachable (HTTP 200)."
else
    echo "❌ Frontend failed with HTTP $HTTP_CODE."
fi

# 3. Test Backend
echo "[3/3] Testing Backend Connectivity ($BACKEND_URL)..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BACKEND_URL/")
if [ "$HTTP_CODE" -ne 000 ]; then # Accepting any response for now as root might be 404
    echo "✅ Backend is reachable (HTTP $HTTP_CODE)."
else
    echo "❌ Backend failed to respond."
fi

echo "=========================================="
echo " Tests Completed."
echo "=========================================="
