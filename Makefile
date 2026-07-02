# ─────────────────────────────────────────────────────────────────────────────
#  TechHeaven Reference Platform — Makefile
#  Run `make help` to see all available commands.
# ─────────────────────────────────────────────────────────────────────────────

.PHONY: help up down restart build rebuild logs shell db-shell redis-shell \
        seed reseed fresh-seed migrate migrate-fresh artisan tinker \
        export test-health ps clean nuke

# Colors
BOLD  := \033[1m
RESET := \033[0m
GREEN := \033[0;32m
CYAN  := \033[0;36m

help: ## Show this help message
	@echo ""
	@echo "$(BOLD)TechHeaven Reference Platform$(RESET)"
	@echo ""
	@echo "$(BOLD)Usage:$(RESET) make $(CYAN)<target>$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(CYAN)%-20s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ─── Lifecycle ───────────────────────────────────────────────────────────────

up: ## Start all services (first run seeds the database)
	@echo "$(GREEN)Starting TechHeaven...$(RESET)"
	@cp -n .env.example .env 2>/dev/null || true
	docker compose up -d
	@echo "$(GREEN)Services started. Watch logs: make logs$(RESET)"
	@echo "$(GREEN)Storefront: http://localhost$(RESET)"
	@echo "$(GREEN)Admin:      http://localhost/admin$(RESET)"
	@echo "$(GREEN)Mailpit:    http://localhost:8025$(RESET)"

down: ## Stop all services
	docker compose down

restart: ## Restart all services
	docker compose restart

build: ## Build Docker images (no cache)
	docker compose build --no-cache

rebuild: ## Force rebuild and restart everything
	docker compose down -v
	docker compose build --no-cache
	docker compose up -d

logs: ## Tail logs from all services
	docker compose logs -f

logs-app: ## Tail app container logs only
	docker compose logs -f app

ps: ## Show running containers
	docker compose ps

# ─── Application ─────────────────────────────────────────────────────────────

shell: ## Open bash shell in app container
	docker compose exec app bash

db-shell: ## Open MySQL shell
	docker compose exec mysql mysql -u techheaven -ptechheaven_secret techheaven

redis-shell: ## Open Redis CLI
	docker compose exec redis redis-cli

artisan: ## Run an Artisan command: make artisan CMD="route:list"
	docker compose exec app php artisan $(CMD)

tinker: ## Open Laravel Tinker REPL
	docker compose exec app php artisan tinker

# ─── Database ────────────────────────────────────────────────────────────────

migrate: ## Run pending migrations
	docker compose exec app php artisan migrate --force

migrate-fresh: ## Drop all tables and re-run migrations (no seed)
	docker compose exec app php artisan migrate:fresh --force

seed: ## Run TechHeavenSeeder only (preserves existing data)
	docker compose exec app php artisan db:seed --class=Database\\Seeders\\TechHeavenSeeder --force

reseed: ## Drop, migrate, and reseed everything from scratch
	@echo "$(GREEN)Reseeding TechHeaven — this takes 5–15 minutes...$(RESET)"
	docker compose exec app bash -c "rm -f storage/app/.installed && php artisan migrate:fresh --force && php artisan db:seed --force"
	docker compose exec app touch storage/app/.installed

fresh-seed: reseed ## Alias for reseed

export: ## Export all data to exports/ directory as CSV
	docker compose exec app bash /var/www/html/exports/export-data.sh

# ─── Health & Status ─────────────────────────────────────────────────────────

test-health: ## Check if the storefront responds
	@echo "Testing storefront..."
	@curl -sf http://localhost/ -o /dev/null && echo "$(GREEN)✓ Storefront OK$(RESET)" || echo "$(RESET)✗ Storefront not responding$(RESET)"
	@echo "Testing admin..."
	@curl -sf http://localhost/admin/login -o /dev/null && echo "$(GREEN)✓ Admin OK$(RESET)" || echo "✗ Admin not responding"
	@echo "Testing Mailpit..."
	@curl -sf http://localhost:8025/ -o /dev/null && echo "$(GREEN)✓ Mailpit OK$(RESET)" || echo "✗ Mailpit not responding"

# ─── Cleanup ─────────────────────────────────────────────────────────────────

clean: ## Remove containers and images (keeps volumes/data)
	docker compose down --rmi local

nuke: ## ⚠ Remove EVERYTHING including all data volumes
	@echo "$(BOLD)WARNING: This will delete all database data!$(RESET)"
	@read -p "Type 'yes' to confirm: " confirm && [ "$$confirm" = "yes" ] || exit 1
	docker compose down -v --rmi local
	@echo "All containers, images, and volumes removed."
