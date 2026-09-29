#!/usr/bin/env bash
set -euo pipefail

cd /app

# Install Node dependencies on first start (node_modules/ lives in the bind mount),
# and again whenever the lockfile changes.
if [ ! -f package-lock.json ]; then
    echo "[entrypoint] No lockfile yet, running npm install..."
    npm install --no-audit --no-fund
elif [ ! -f node_modules/.package-lock.json ] || [ package-lock.json -nt node_modules/.package-lock.json ]; then
    echo "[entrypoint] Installing npm dependencies..."
    npm ci --no-audit --no-fund
fi

exec "$@"
