# Laragon / Windows — run from project root:  .\scripts\setup-local.ps1
$ErrorActionPreference = "Stop"
Set-Location (Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path))

Write-Host "Ensuring storage folders and public/storage link..." -ForegroundColor Cyan
php artisan app:ensure-storage --force

$storagePublic = Join-Path $PWD "storage\app\public"
$publicStorage = Join-Path $PWD "public\storage"
if (-not (Test-Path $publicStorage)) {
    Write-Host "Creating public\storage junction (no admin required on same drive)..." -ForegroundColor Yellow
    cmd /c "mklink /J `"$publicStorage`" `"$storagePublic`""
}
if (Test-Path $publicStorage) {
    $target = (Get-Item $publicStorage -Force).Target
    if ($target) { Write-Host "OK: public\storage -> $target" -ForegroundColor Green }
}

if (-not (Test-Path "vendor")) {
    Write-Host "Running composer install..." -ForegroundColor Cyan
    composer install
}

if (-not (Test-Path "node_modules")) {
    Write-Host "Running npm install..." -ForegroundColor Cyan
    npm install
}

if (-not (Test-Path "public/js/.vite/manifest.json")) {
    Write-Host "Vue assets missing — running npm run build..." -ForegroundColor Cyan
    npm run build
}

Write-Host "Done. Document root should be: $(Resolve-Path 'public')" -ForegroundColor Green
