#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  TechHeaven Reference Platform — One-Command Setup
#
#  Usage: ./setup.sh
#
#  What it does:
#    1. Downloads Bagisto 2.4.7 into ./bagisto/ (skipped if already present)
#    2. Copies our custom seeders into ./bagisto/database/seeders/
#    3. Starts Docker (builds image, boots services)
#
#  On first Docker boot the app container will:
#    - Run composer install (~2 min)
#    - Run migrate:fresh --seed (~8-12 min for full dataset)
#
#  Total cold-start time: ~12-15 min
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

BAGISTO_VERSION="2.4.7"
BAGISTO_DIR="$(pwd)/bagisto"
SEEDERS_SRC="$(pwd)/database/seeders"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

step() { echo -e "\n${BLUE}▶ $1${NC}"; }
ok()   { echo -e "${GREEN}✓ $1${NC}"; }
warn() { echo -e "${YELLOW}⚠ $1${NC}"; }

# ─── Preflight checks ─────────────────────────────────────────────────────────
if ! command -v composer &>/dev/null; then
    echo "Error: composer not found. Install from https://getcomposer.org"
    exit 1
fi
if ! command -v docker &>/dev/null; then
    echo "Error: docker not found. Install Docker Desktop from https://docker.com"
    exit 1
fi

# ─── 0. Bootstrap .env ───────────────────────────────────────────────────────
if [ ! -f .env ]; then
    step "Creating .env from .env.example…"
    cp .env.example .env
    ok ".env created — edit it if you need custom ports or credentials"
fi

# ─── 1. Download Bagisto 2.4.7 ───────────────────────────────────────────────
if [ -f "${BAGISTO_DIR}/composer.json" ]; then
    warn "Bagisto already present at ./bagisto/ — skipping download"
else
    step "Downloading Bagisto ${BAGISTO_VERSION}…"
    composer create-project bagisto/bagisto:"${BAGISTO_VERSION}" bagisto \
        --no-interaction \
        --prefer-dist
    ok "Bagisto ${BAGISTO_VERSION} downloaded"
fi

# ─── 2. Sync our custom seeders ──────────────────────────────────────────────
# The docker-compose volume mount handles this at runtime, but we also copy
# them now so they're available if you run composer/artisan outside Docker.
step "Syncing custom seeders into ./bagisto/database/seeders/ …"
cp -r "${SEEDERS_SRC}/." "${BAGISTO_DIR}/database/seeders/"
ok "Seeders synced"

# ─── 3. Build image and start services ───────────────────────────────────────
step "Building Docker image and starting services…"
docker compose up -d --build
ok "Services started"

# ─── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         TechHeaven — Setup in progress                  ║${NC}"
echo -e "${GREEN}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║  The app container is now installing dependencies and   ║${NC}"
echo -e "${GREEN}║  seeding 1 000 customers + 5 000 orders (~12 min).      ║${NC}"
echo -e "${GREEN}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║  Monitor:   docker compose logs -f app                  ║${NC}"
echo -e "${GREEN}║  Storefront: http://localhost                           ║${NC}"
echo -e "${GREEN}║  Admin:      http://localhost/admin                     ║${NC}"
echo -e "${GREEN}║  Creds:      admin@techheaven.com / Admin@12345         ║${NC}"
echo -e "${GREEN}║  Mailpit:    http://localhost:8025                      ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
