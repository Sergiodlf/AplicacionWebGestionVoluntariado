$baseUrl = "http://127.0.0.1:8000/api/auth"
$testEmail = "test_change_pass_$(Get-Random)@mail.com"
$testDni = "$(Get-Random -Minimum 10000000 -Maximum 99999999)T"
$initialPass = "password123"
$newPass = "new_secret_456"

if (Test-Path "verify_change_pass.txt") { Remove-Item "verify_change_pass.txt" }

function Log-Output {
    param ([string]$message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path "verify_change_pass.txt" -Value "[$timestamp] $message"
    Write-Host "[$timestamp] $message"
}

try {
    Log-Output "Starting Verification for Change Password"

    # 1. Skip Registration (Using existing user)
    # 11111111A / ana.lopez@mail.com / start123 (manually reset)
    $testEmail = "ana.lopez@mail.com"
    $initialPass = "start123"
    Log-Output "Using existing user: $testEmail"

    # 2. Change Password
    $changeUrl = "$baseUrl/change-password"
    $changeBody = @{
        email = $testEmail
        oldPassword = $initialPass
        newPassword = $newPass
    } | ConvertTo-Json

    Log-Output "Changing password from '$initialPass' to '$newPass'..."
    $changeResp = Invoke-RestMethod -Uri $changeUrl -Method POST -Body $changeBody -ContentType "application/json"
    Log-Output "Response: $($changeResp.message)"

    # 3. Verify OLD Password (Should Fail)
    $loginUrl = "$baseUrl/login"
    $loginOldBody = @{
        email = $testEmail
        password = $initialPass
    } | ConvertTo-Json

    Log-Output "Testing Login with OLD password (should fail)..."
    try {
        $loginOldResp = Invoke-RestMethod -Uri $loginUrl -Method POST -Body $loginOldBody -ContentType "application/json"
        Log-Output "FAILURE: Login with old password succeeded (should have failed)."
    } catch {
        Log-Output "SUCCESS: Login with old password failed as expected."
    }

    # 4. Verify NEW Password (Should Succeed)
    $loginNewBody = @{
        email = $testEmail
        password = $newPass
    } | ConvertTo-Json

    Log-Output "Testing Login with NEW password..."
    $loginNewResp = Invoke-RestMethod -Uri $loginUrl -Method POST -Body $loginNewBody -ContentType "application/json"
    Log-Output "SUCCESS: Login with new password successful. User: $($loginNewResp.nombre)"

} catch {
    Log-Output "ERROR: $($_.Exception.Message)"
     if ($_.Exception.Response) {
         $stream = $_.Exception.Response.GetResponseStream()
         if ($stream) {
            $reader = New-Object System.IO.StreamReader($stream)
            $responseText = $reader.ReadToEnd()
            Log-Output "Details: $responseText"
         }
    }
}
Log-Output "Verification Complete."
