$baseUrl = "http://127.0.0.1:8000/api/organizations"

if (Test-Path "verify_org_update.txt") { Remove-Item "verify_org_update.txt" }

function Log-Output {
    param ([string]$message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path "verify_org_update.txt" -Value "[$timestamp] $message"
    Write-Host "[$timestamp] $message"
}

try {
    Log-Output "Starting Verification for Organization Update"
    
    # 1. Get a valid CIF
    Log-Output "Fetching organizations..."
    $orgs = Invoke-RestMethod -Uri $baseUrl -Method GET
    
    if ($orgs.Count -eq 0) {
        Log-Output "ERROR: No organizations found to test."
        exit
    }
    
    $cif = $orgs[0].cif
    Log-Output "Testing with Organization CIF: $cif"

    # 2. Update Organization
    $updateUrl = "$baseUrl/$cif"
    $body = @{
        descripcion = "Descripción actualizada por script de verificación"
        contacto = "Contacto TestScript"
        localidad = "Ciudad Test"
    }
    $jsonBody = $body | ConvertTo-Json -Depth 5 -Compress

    Log-Output "Sending PUT Request to $updateUrl..."
    Log-Output "Body: $jsonBody"

    $response = Invoke-RestMethod -Uri $updateUrl -Method PUT -Body $jsonBody -ContentType "application/json"
    
    Log-Output "Response Received:"
    $jsonResponse = $response | ConvertTo-Json -Depth 5
    Log-Output $jsonResponse

    # 3. Verify Data
    if ($response.descripcion -eq "Descripción actualizada por script de verificación" -and 
        $response.contacto -eq "Contacto TestScript") {
        Log-Output "SUCCESS: Organization updated correctly."
    } else {
        Log-Output "FAILURE: Data mismatch."
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
