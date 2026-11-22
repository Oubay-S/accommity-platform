#requires -Version 5.1
[CmdletBinding()]
param(
    [switch]$Reset,
    [switch]$Logs,
    [switch]$NoBuild
)

$ErrorActionPreference = 'Continue'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$envFile = Join-Path $root 'deploy\.env'

if (-not (Test-Path $envFile)) {
    Write-Host '[deploy] Création de deploy/.env depuis deploy/.env.example...' -ForegroundColor Cyan
    Copy-Item 'deploy\.env.example' $envFile
}

$hasKey = Get-Content $envFile | Where-Object { $_ -match '^APP_KEY=base64:' } | Select-Object -First 1

if (-not $hasKey) {
    $bytes = New-Object byte[] 32
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $rng.GetBytes($bytes)
    $key = 'base64:' + [Convert]::ToBase64String($bytes)

    $lines = Get-Content $envFile | ForEach-Object {
        if ($_ -match '^APP_KEY=') { "APP_KEY=$key" } else { $_ }
    }
    [System.IO.File]::WriteAllLines($envFile, $lines)
    Write-Host '[deploy] APP_KEY générée.' -ForegroundColor Green
}

$compose = @('compose', '-f', 'docker-compose.deploy.yml', '--env-file', 'deploy/.env')

if ($Reset) {
    Write-Host '[deploy] Suppression des conteneurs et volumes...' -ForegroundColor Yellow
    & docker @compose down -v
    if ($LASTEXITCODE -ne 0) { throw 'docker compose down a échoué.' }
}

$up = $compose + @('up', '-d')
if (-not $NoBuild) { $up += '--build' }

Write-Host '[deploy] Démarrage du stack local...' -ForegroundColor Cyan
& docker @up
if ($LASTEXITCODE -ne 0) { throw 'docker compose up a échoué.' }

Write-Host '[deploy] Attente de l''API (max 5 min)...' -ForegroundColor Cyan
$ready = $false
$deadline = (Get-Date).AddMinutes(5)

while ((Get-Date) -lt $deadline) {
    try {
        $response = Invoke-WebRequest -Uri 'http://localhost:8080/api/annonces' -UseBasicParsing -TimeoutSec 5
        if ($response.StatusCode -eq 200) {
            $ready = $true
            break
        }
    } catch {
        Start-Sleep -Seconds 3
    }
}

if (-not $ready) {
    Write-Host '[deploy] L''API ne répond pas. Derniers logs :' -ForegroundColor Red
    & docker @compose logs --tail=100
    throw 'Déploiement local échoué.'
}

try {
    $front = Invoke-WebRequest -Uri 'http://localhost:8080/' -UseBasicParsing -TimeoutSec 5
    if ($front.StatusCode -ne 200) { throw "HTTP $($front.StatusCode)" }
} catch {
    Write-Host '[deploy] Le frontend ne répond pas correctement.' -ForegroundColor Red
    & docker @compose logs --tail=100 web
    throw 'Déploiement local échoué.'
}

Write-Host ''
Write-Host 'Accommity est disponible :' -ForegroundColor Green
Write-Host '  Application : http://localhost:8080'
Write-Host '  API         : http://localhost:8080/api'
Write-Host '  Mailpit     : http://localhost:8025'
Write-Host ''
Write-Host 'Comptes de démonstration :'
Write-Host '  Admin    : admin@accommity.ma / Admin1234!'
Write-Host '  Hôte     : hote.demo@accommity.ma / Password123!'
Write-Host '  Voyageur : voyageur.demo@accommity.ma / Password123!'
Write-Host ''
Write-Host 'Commandes utiles :'
Write-Host '  Logs        : scripts/deploy-local.ps1 -Logs'
Write-Host '  Reset       : scripts/deploy-local.ps1 -Reset'
Write-Host '  Arrêt       : docker compose -f docker-compose.deploy.yml --env-file deploy/.env down'
Write-Host ''

if ($Logs) {
    & docker @compose logs -f
}
