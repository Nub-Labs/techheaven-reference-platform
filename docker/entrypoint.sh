#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  TechHeaven — Application Entrypoint
#  Runs on every container start. Idempotent — safe to restart.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()  { echo -e "${BLUE}[TechHeaven]${NC} $1"; }
ok()   { echo -e "${GREEN}[TechHeaven]${NC} ✓ $1"; }
warn() { echo -e "${YELLOW}[TechHeaven]${NC} ⚠ $1"; }
err()  { echo -e "${RED}[TechHeaven]${NC} ✗ $1"; }

APP_DIR="/var/www/html"
INSTALLED_FLAG="${APP_DIR}/storage/app/.installed"

cd "${APP_DIR}"

# ─── 1. Configure .env ────────────────────────────────────────────────────────
if [ ! -f "${APP_DIR}/.env" ]; then
    log "Creating .env from .env.example …"
    cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
fi

# Inject runtime environment variables into .env
inject_env() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" "${APP_DIR}/.env"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "${APP_DIR}/.env"
    else
        echo "${key}=${value}" >> "${APP_DIR}/.env"
    fi
}

inject_env "APP_NAME"         "${APP_NAME:-TechHeaven}"
inject_env "APP_ENV"          "${APP_ENV:-local}"
inject_env "APP_DEBUG"        "${APP_DEBUG:-true}"
inject_env "APP_URL"          "${APP_URL:-http://localhost}"
inject_env "APP_ADMIN_URL"    "${APP_ADMIN_URL:-admin}"
inject_env "DB_CONNECTION"    "mysql"
inject_env "DB_HOST"          "${DB_HOST:-mysql}"
inject_env "DB_PORT"          "${DB_PORT:-3306}"
inject_env "DB_DATABASE"      "${DB_DATABASE:-techheaven}"
inject_env "DB_USERNAME"      "${DB_USERNAME:-techheaven}"
inject_env "DB_PASSWORD"      "${DB_PASSWORD:-techheaven_secret}"
inject_env "CACHE_DRIVER"     "${CACHE_DRIVER:-redis}"
inject_env "SESSION_DRIVER"   "${SESSION_DRIVER:-redis}"
inject_env "QUEUE_CONNECTION" "${QUEUE_CONNECTION:-redis}"
inject_env "REDIS_HOST"       "${REDIS_HOST:-redis}"
inject_env "REDIS_PORT"       "${REDIS_PORT:-6379}"
inject_env "MAIL_MAILER"      "smtp"
inject_env "MAIL_HOST"        "${MAIL_HOST:-mailpit}"
inject_env "MAIL_PORT"        "${MAIL_PORT:-1025}"
inject_env "MAIL_ENCRYPTION"  "null"
inject_env "MAIL_FROM_ADDRESS" "${MAIL_FROM_ADDRESS:-no-reply@techheaven.com}"
inject_env "MAIL_FROM_NAME"   "TechHeaven"

# ─── 2. Install PHP dependencies if vendor is missing ────────────────────────
if [ ! -f "${APP_DIR}/vendor/autoload.php" ]; then
    log "Installing PHP dependencies (first run — this takes ~2 min) …"
    composer install --no-interaction --optimize-autoloader
    ok "Dependencies installed"
fi

# ─── 3. Generate APP_KEY if missing ──────────────────────────────────────────
if ! grep -q "^APP_KEY=base64:" "${APP_DIR}/.env" 2>/dev/null; then
    log "Generating application key …"
    php artisan key:generate --force --no-interaction
    ok "App key generated"
fi

# ─── 4. Wait for MySQL ───────────────────────────────────────────────────────
log "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306} …"
attempt=0
max_attempts=90
until bash -c "exec 3<>/dev/tcp/${DB_HOST:-mysql}/${DB_PORT:-3306}" 2>/dev/null; do
    attempt=$((attempt+1))
    if [ "$attempt" -ge "$max_attempts" ]; then
        err "MySQL did not become ready after ${max_attempts} attempts. Exiting."
        exit 1
    fi
    sleep 3
done
ok "MySQL is ready (TCP)"

# Give MySQL a moment to finish initialising users after the port opens
sleep 3

# ─── 5. Wait for Redis ───────────────────────────────────────────────────────
log "Waiting for Redis at ${REDIS_HOST:-redis}:${REDIS_PORT:-6379} …"
until bash -c "exec 3<>/dev/tcp/${REDIS_HOST:-redis}/${REDIS_PORT:-6379}" 2>/dev/null; do
    sleep 1
done
ok "Redis is ready (TCP)"

# ─── 6. Migrate + seed ───────────────────────────────────────────────────────
if [ ! -f "${INSTALLED_FLAG}" ]; then
    warn "First run detected — seeding TechHeaven reference data …"
    warn "This takes 5–15 minutes. Do not interrupt."
    echo ""

    # migrate:fresh drops all tables first — safe to re-run if container crashed mid-seed
    php artisan migrate:fresh --seed --force --no-interaction

    # Create the storage symlink
    php artisan storage:link --force

    # Build product flat / price / inventory indices (required for storefront listings)
    log "Building product search indices …"
    php artisan indexer:index || warn "Indexer had non-fatal errors — products may still be visible"

    # Mark as installed
    touch "${INSTALLED_FLAG}"

    ok "Database seeded successfully!"
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║          TechHeaven is Ready!                        ║${NC}"
    echo -e "${GREEN}╠══════════════════════════════════════════════════════╣${NC}"
    echo -e "${GREEN}║  Storefront : ${APP_URL:-http://localhost}            ${NC}"
    echo -e "${GREEN}║  Admin      : ${APP_URL:-http://localhost}/admin      ${NC}"
    echo -e "${GREEN}║  Admin User : ${ADMIN_EMAIL:-admin@techheaven.com}    ${NC}"
    echo -e "${GREEN}║  Admin Pass : ${ADMIN_PASSWORD:-Admin@12345}          ${NC}"
    echo -e "${GREEN}║  Mailpit    : http://localhost:8025                  ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
else
    log "Running database migrations …"
    php artisan migrate --force --no-interaction
    ok "Database already seeded — skipping seed"
fi

# ─── 7. Warm caches ──────────────────────────────────────────────────────────
log "Warming up application caches …"
php artisan optimize --no-interaction 2>/dev/null || true
ok "Cache warmed"

# ─── 8. Fix permissions ──────────────────────────────────────────────────────
chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache" 2>/dev/null || true

# ─── 9. Start supervisor (nginx + php-fpm) ───────────────────────────────────
log "Starting Nginx + PHP-FPM via Supervisor …"
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
