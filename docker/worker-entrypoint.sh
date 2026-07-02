#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  TechHeaven — Worker Container Entrypoint (queue / scheduler)
#  Waits for the main app to finish installation, then runs the given command.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="/var/www/html"
INSTALLED_FLAG="${APP_DIR}/storage/app/.installed"

cd "${APP_DIR}"

echo "[Worker] Waiting for app to complete first-run setup …"
attempt=0
max_attempts=300  # wait up to 10 minutes
until [ -f "${INSTALLED_FLAG}" ]; do
    attempt=$((attempt+1))
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "[Worker] App setup flag never appeared. Starting anyway."
        break
    fi
    sleep 2
done
echo "[Worker] App is ready. Starting worker: $*"

exec "$@"
