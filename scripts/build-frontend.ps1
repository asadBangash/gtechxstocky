# Build Vue/Vite assets into public/js/ — run on your PC before git push (recommended).
# Usage:  .\scripts\build-frontend.ps1
$ErrorActionPreference = "Stop"
Set-Location (Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path))

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "Node.js is not installed. Install Node 20 LTS, then retry." -ForegroundColor Red
    exit 1
}

Write-Host "Node: $(node -v)" -ForegroundColor Cyan
if (-not (Test-Path "node_modules")) {
    Write-Host "npm install..." -ForegroundColor Cyan
    npm install
}

Write-Host "npm run build (admin + storefront + portal + customer display)..." -ForegroundColor Cyan
npm run build

if (-not (Test-Path "public/js/.vite/manifest.json")) {
    Write-Host "Build failed: manifest missing at public/js/.vite/manifest.json" -ForegroundColor Red
    exit 1
}

Write-Host "OK. Commit and push public/js/ for the server:" -ForegroundColor Green
Write-Host "  git add public/js" -ForegroundColor Yellow
Write-Host "  git commit -m `"Build frontend assets`"" -ForegroundColor Yellow
Write-Host "  git push" -ForegroundColor Yellow
