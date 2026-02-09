$frontendUrl = "http://localhost:80"
$backendUrl = "http://localhost:8000"

Write-Host "=========================================="
Write-Host " Starting Docker Integration Tests (PS)"
Write-Host "=========================================="

# 1. Check if containers are running
Write-Host "[1/3] Checking Docker Containers..."
$dockerPs = docker-compose -f "docker_compose.yml" ps
if ($dockerPs -match "Up") {
    Write-Host "✅ Containers are running." -ForegroundColor Green
} else {
    Write-Host "❌ Containers generate errors or are not running." -ForegroundColor Red
    exit 1
}

# 2. Test Frontend
Write-Host "[2/3] Testing Frontend Connectivity ($frontendUrl)..."
try {
    $response = Invoke-WebRequest -Uri $frontendUrl -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Frontend is reachable (HTTP 200)." -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Frontend failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 3. Test Backend
Write-Host "[3/3] Testing Backend Connectivity ($backendUrl)..."
try {
    # Backend might return 404 on root, but if it connects it's a success for connectivity test
    $response = Invoke-WebRequest -Uri "$backendUrl/" -UseBasicParsing -ErrorAction SilentlyContinue
    $statusCode = $response.StatusCode
    if ($statusCode -gt 0) {
         Write-Host "✅ Backend is reachable (HTTP $statusCode)." -ForegroundColor Green
    } else {
         Write-Host "❌ Backend failed to respond." -ForegroundColor Red
    }
} catch {
      Write-Host "❌ Backend connection error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "=========================================="
Write-Host " Tests Completed."
Write-Host "=========================================="
