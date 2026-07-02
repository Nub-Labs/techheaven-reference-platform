# TechHeaven Reference Platform

> **The "Northwind Traders" of consumer electronics e-commerce for AI and Data Science.**

TechHeaven is an open-source, production-quality Bagisto 2.x e-commerce reference implementation. It is **not a real store** — it is a richly-seeded reference dataset for building and demonstrating AI, ML, and Data Science applications.

---

## What Is This?

TechHeaven is a fully seeded consumer electronics retailer with:

| Resource | Count |
|---|---|
| Product categories | 62 (15 parents + 47 children) |
| Brands | 43 |
| Products | ~320 (real SKUs, specs, pricing) |
| Customers | 1,000 |
| Orders | 5,000 (18 months of history) |
| Reviews | 5,000 |
| CMS Pages | 17 |

The data is crafted to power real AI/ML projects, not just fill a database.

---

## Quick Start

**Requirements:** Docker, Docker Compose, Composer

```bash
git clone https://github.com/your-org/techheaven-reference-platform.git
cd techheaven-reference-platform
./setup.sh
```

`setup.sh` will:
1. Download Bagisto 2.4.7 via Composer into `./bagisto/`
2. Sync the custom seeders
3. Build the Docker image and start all services
4. On first boot: run migrations and seed all TechHeaven data (~12–15 min)

Access the store at **http://localhost** and the admin at **http://localhost/admin**

Default admin credentials: `admin@techheaven.com` / `Admin@12345`

---

## Ports

| Service | Default Port | Override in .env |
|---|---|---|
| Storefront + Admin | 80 | `APP_PORT` |
| MySQL | 3306 | `MYSQL_EXTERNAL_PORT` |
| Redis | 6379 | `REDIS_EXTERNAL_PORT` |
| Mailpit UI | 8025 | `MAILPIT_UI_PORT` |

---

## Make Commands

```bash
make up          # Start all services
make down        # Stop all services
make restart     # Restart all services
make build       # Build images without cache
make logs        # Stream logs from all services
make shell       # Open a bash shell in the app container
make db-shell    # Open a MySQL shell
make artisan CMD=migrate  # Run any artisan command
make seed        # Run just the TechHeaven seeder
make fresh-seed  # Wipe data + re-migrate + re-seed
make export      # Export CSV files to exports/
make test-health # Run health checks
make nuke        # Remove everything including volumes
```

---

## Architecture

```
techheaven-reference-platform/
├── docker/
│   ├── Dockerfile              # Multi-stage: base → app (nginx+fpm) → worker
│   ├── entrypoint.sh           # First-run setup + supervisor start
│   ├── worker-entrypoint.sh    # Waits for .installed flag, then starts queue
│   ├── nginx/default.conf      # nginx with unix socket, gzip, security headers
│   ├── supervisor/supervisord.conf
│   ├── php/php.ini
│   └── php/php-fpm.conf
├── docker-compose.yml          # 6 services: app, queue, scheduler, mysql, redis, mailpit
├── database/
│   └── seeders/
│       ├── TechHeavenSeeder.php      # Orchestrator
│       ├── AdminSeeder.php
│       ├── CategorySeeder.php
│       ├── BrandAttributeSeeder.php
│       ├── ProductSeeder.php
│       │   └── ProductData/          # 15 data-only classes
│       ├── CustomerSeeder.php
│       ├── OrderSeeder.php
│       ├── ReviewSeeder.php
│       ├── CouponSeeder.php
│       └── ContentSeeder.php
├── scripts/
│   └── export-data.sh          # Exports MySQL → exports/*.csv
└── exports/                    # CSV output (bind-mounted from container)
```

---

## AI/ML Use Cases

TechHeaven is designed as a reference dataset for common AI/ML applications:

| Use Case | Dataset |
|---|---|
| **Customer Segmentation (RFM + K-Means)** | `customer_rfm.csv` |
| **Market Basket Analysis (Apriori/FP-Growth)** | `market_basket.csv` |
| **Recommendation System (Collaborative Filtering)** | `order_items.csv` + `reviews.csv` |
| **RAG Customer Support AI** | `products.csv` + CMS pages |
| **AI Voice Agent Commerce** | Full product catalogue |
| **Churn Prediction** | `customer_rfm.csv` + `orders.csv` |
| **Inventory Demand Forecasting** | `monthly_product_sales.csv` |

### Export Data for ML

```bash
make export
ls exports/
```

This generates CSV files ready for pandas, Jupyter, or any ML pipeline.

---

## Product Categories

TechHeaven covers 15 top-level consumer electronics categories:

1. Laptops (ultrabooks, professional, gaming, business, budget)
2. Monitors (gaming, professional, ultrawide, home office)
3. Storage (NVMe SSDs, SATA SSDs, portable SSDs, hard drives)
4. Memory (desktop DDR4/DDR5, laptop SO-DIMM)
5. Networking (routers, mesh systems, switches)
6. Audio (headphones, earbuds, gaming headsets, speakers)
7. Keyboards (mechanical, wireless, gaming)
8. Mice (gaming, productivity)
9. Components (GPUs, CPUs, cooling)
10. Smart Home (speakers/displays, lighting, security cameras)
11. Mobile Accessories (cases, chargers, screen protectors)
12. Wearables (smartwatches, fitness trackers)
13. Cameras (mirrorless, action, webcams)
14. Printers (inkjet, laser)
15. Accessories (docking stations, laptop bags, desk, UPS)

---

## Environment Variables

Copy `.env.example` to `.env` (done automatically by `setup.sh`) and adjust:

```env
APP_PORT=80
APP_URL=http://localhost
ADMIN_EMAIL=admin@techheaven.com
ADMIN_PASSWORD=Admin@12345
DB_DATABASE=techheaven
DB_USERNAME=techheaven
DB_PASSWORD=techheaven_secret
DB_ROOT_PASSWORD=root_secret
MYSQL_EXTERNAL_PORT=3306
REDIS_EXTERNAL_PORT=6379
MAILPIT_UI_PORT=8025
MAILPIT_SMTP_PORT=1025
```

---

## Re-Seeding

To wipe all data and start fresh:

```bash
make nuke && make up
# or, preserving containers/images:
make fresh-seed
```

The `.installed` flag at `storage/app/.installed` prevents automatic re-seeding on container restart. Delete it to force re-seeding on next start.

---

## Licence

MIT — free to use, modify, and distribute for any purpose including commercial use. Attribution appreciated but not required.
