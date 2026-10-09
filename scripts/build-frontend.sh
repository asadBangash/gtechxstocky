#!/usr/bin/env bash
# Build Vue/Vite on the server ONLY if Node is available (often not on shared hosting).
# Preferred: run scripts/build-frontend.ps1 locally and git push public/js/
set -euo pipefail
cd "$(dirname "$0")/.."

if ! command -v node >/dev/null 2>&1; then
  echo "Node.js not found. Build on your PC:  npm run build  then git push public/js/"
  exit 1
fi

echo "Node: $(node -v)"
if [[ ! -d node_modules ]]; then
  npm install
fi

npm run build

test -f public/js/.vite/manifest.json && echo "OK: public/js/.vite/manifest.json"
