#!/usr/bin/env bash
# Shared Linux host (e.g. Hostinger) — run from project root:  bash scripts/setup-server.sh
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> Storage directories + public/storage link"
php artisan app:ensure-storage --force || true

if [[ -d public/storage ]] && [[ ! -L public/storage ]]; then
  resolved="$(readlink -f public/storage 2>/dev/null || realpath public/storage 2>/dev/null || true)"
  target="$(readlink -f storage/app/public 2>/dev/null || realpath storage/app/public 2>/dev/null || true)"
  if [[ -n "$resolved" && -n "$target" && "$resolved" != "$target" ]]; then
    echo "WARNING: public/storage is a real directory (not a symlink). Move files to storage/app/public, then: rm -rf public/storage"
  fi
fi

if [[ ! -e public/storage ]]; then
  echo "==> Creating symlink via shell (when PHP cannot)"
  ln -sfn ../storage/app/public public/storage
fi

if [[ -L public/storage ]]; then
  echo "OK: public/storage -> $(readlink public/storage)"
elif [[ -d public/storage ]]; then
  echo "OK: public/storage resolves to $(readlink -f public/storage 2>/dev/null || realpath public/storage)"
fi

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Composer (production)"
composer install --no-dev --optimize-autoloader

echo "==> Laravel optimize"
php artisan config:clear
php artisan migrate --force
php artisan migrate --path=vendor/laravel/passport/database/migrations --force 2>/dev/null || true
php artisan passport:keys --force 2>/dev/null || true

if [[ ! -f storage/app/public/installed ]]; then
  php artisan tinker --execute="Illuminate\Support\Facades\Storage::disk('public')->put('installed', 'OK');" 2>/dev/null || true
fi

php artisan config:cache
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo ""
echo "Done."
echo "  Web root MUST be: $(pwd)/public"
echo "  (Not public_html alone, not the folder that only has artisan.)"
echo "  After deploy, open /deploy-check.php once, then delete public/deploy-check.php"
