$dni = "11111111A"
$baseUrl = "http://127.0.0.1:8000/api/voluntarios/$dni"

if (Test-Path "verify_update.txt") { Remove-Item "verify_update.txt" }

function Log-Output {
    param ([string]$message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path "verify_update.txt" -Value "[$timestamp] $message"
    Write-Host "[$timestamp] $message"
}

try {
    Log-Output "Starting Verification for Volunteer Update (DNI: $dni)"

    # 1. Update Profile
    $body = @{
        zona = "Centro Historico"
        habilidades = @("Cocina", "Logistica")
        disponibilidad = @("Fines de semana")
        intereses = @("Ayuda humanitaria")
    }
    $jsonBody = $body | ConvertTo-Json -Depth 5 -Compress
    
    Log-Output "Sending PUT Request..."
    Log-Output "Body: $jsonBody"

    $response = Invoke-RestMethod -Uri $baseUrl -Method PUT -Body $jsonBody -ContentType "application/json"
    
    Log-Output "Response Received:"
    $jsonResponse = $response | ConvertTo-Json -Depth 5
    Log-Output $jsonResponse

    # 2. Verify Data
    if ($response.voluntario.zona -eq "Centro Historico" -and 
        $response.voluntario.habilidades -contains "Cocina") {
        Log-Output "SUCCESS: Profile updated correctly."
    } else {
        Log-Output "FAILURE: Profile data mismatch."
    }

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
