#!/usr/bin/env python3
"""
TechHeaven Reference Platform - Recommendation Engine Data Export

Exports the transactional data required by the Ecommerce AI
"Product Recommendation Engine" playbook.

Unlike export-rag-data.py (which exports unstructured knowledge base content),
this script exports structured transactional data: orders, order line items,
and product reviews. These are the signals used by association rules,
collaborative filtering, and content-based recommendation algorithms.

Output (committed to the repo, consumed by notebooks via raw.githubusercontent):
    data/transactions/orders.json       - order headers with customer linkage
    data/transactions/order_items.json  - line items with product_id and qty
    data/reviews/reviews.json           - customer ratings per product

Run (maintainer only, from a machine with the TechHeaven DB reachable):
    pip install pymysql
    python scripts/export-recommendation-data.py

Connection defaults assume `docker compose up` exposes MySQL on localhost:3306.
Override via env: TH_DB_HOST, TH_DB_PORT, TH_DB_NAME, TH_DB_USER, TH_DB_PASS
"""

import json
import os
from datetime import datetime
from pathlib import Path

import pymysql

DB = dict(
    host=os.getenv("TH_DB_HOST", "127.0.0.1"),
    port=int(os.getenv("TH_DB_PORT", "3306")),
    database=os.getenv("TH_DB_NAME", "techheaven"),
    user=os.getenv("TH_DB_USER", "techheaven"),
    password=os.getenv("TH_DB_PASS", "techheaven_secret"),
    charset="utf8mb4",
    cursorclass=pymysql.cursors.DictCursor,
)

REPO_ROOT = Path(__file__).resolve().parent.parent
TRANSACTIONS_DIR = REPO_ROOT / "data" / "transactions"
REVIEWS_DIR = REPO_ROOT / "data" / "reviews"


def export_orders(conn) -> list:
    """Order headers - one row per order with customer linkage."""
    sql = """
        SELECT
            o.id            AS order_id,
            o.customer_id,
            o.status,
            ROUND(o.grand_total, 2)         AS grand_total,
            DATE(o.created_at)              AS order_date
        FROM orders o
        WHERE o.customer_id IS NOT NULL
        ORDER BY o.id
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()
    return [
        {
            "order_id":    int(r["order_id"]),
            "customer_id": int(r["customer_id"]),
            "status":      r["status"],
            "grand_total": float(r["grand_total"]),
            "order_date":  str(r["order_date"]),
        }
        for r in rows
    ]


def export_order_items(conn) -> list:
    """
    Order line items with product linkage.

    Filters:
    - parent_id IS NULL: skip configurable product children (they duplicate the parent)
    - product_id IS NOT NULL: skip virtual items and gift cards
    - customer order only: joins back to orders to exclude guest orders
    """
    sql = """
        SELECT
            oi.order_id,
            oi.product_id,
            oi.sku,
            oi.name                         AS product_name,
            oi.qty_ordered,
            ROUND(oi.price, 2)              AS price,
            (SELECT cat_t.name
               FROM product_categories pc
               JOIN category_translations cat_t
                 ON cat_t.category_id = pc.category_id AND cat_t.locale = 'en'
              WHERE pc.product_id = oi.product_id
              ORDER BY pc.category_id
              LIMIT 1)                      AS category
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE oi.product_id IS NOT NULL
          AND oi.parent_id  IS NULL
          AND o.customer_id IS NOT NULL
        ORDER BY oi.order_id, oi.id
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()
    return [
        {
            "order_id":    int(r["order_id"]),
            "product_id":  int(r["product_id"]),
            "sku":         r["sku"] or "",
            "product_name": r["product_name"] or "",
            "qty":         int(r["qty_ordered"] or 1),
            "price":       float(r["price"] or 0),
            "category":    r["category"] or "",
        }
        for r in rows
    ]


def export_reviews(conn) -> list:
    """Approved customer ratings - used as explicit feedback signal."""
    sql = """
        SELECT
            id              AS review_id,
            product_id,
            customer_id,
            rating,
            DATE(created_at) AS review_date
        FROM product_reviews
        WHERE status      = 'approved'
          AND customer_id IS NOT NULL
          AND product_id  IS NOT NULL
        ORDER BY id
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()
    return [
        {
            "review_id":   int(r["review_id"]),
            "product_id":  int(r["product_id"]),
            "customer_id": int(r["customer_id"]),
            "rating":      int(r["rating"]),
            "review_date": str(r["review_date"]),
        }
        for r in rows
    ]


def write_json(path: Path, data: list) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  {len(data):>6} records  ->  {path.relative_to(REPO_ROOT)}")


def main():
    print("\n" + "=" * 52)
    print("  TechHeaven Recommendation Engine Data Export")
    print("=" * 52 + "\n")

    conn = pymysql.connect(**DB)
    try:
        print("  Exporting orders...")
        orders = export_orders(conn)

        print("  Exporting order items...")
        order_items = export_order_items(conn)

        print("  Exporting reviews...")
        reviews = export_reviews(conn)
    finally:
        conn.close()

    print("\n  Writing files:")
    write_json(TRANSACTIONS_DIR / "orders.json",      orders)
    write_json(TRANSACTIONS_DIR / "order_items.json", order_items)
    write_json(REVIEWS_DIR      / "reviews.json",     reviews)

    unique_customers = len({r["customer_id"] for r in orders})
    unique_products  = len({r["product_id"]  for r in order_items})
    print(f"\n  Summary:")
    print(f"    Orders:           {len(orders):>6}")
    print(f"    Order items:      {len(order_items):>6}")
    print(f"    Reviews:          {len(reviews):>6}")
    print(f"    Unique customers: {unique_customers:>6}")
    print(f"    Unique products:  {unique_products:>6}")
    print(f"    Exported at:      {datetime.utcnow().strftime('%Y-%m-%d %H:%M UTC')}")
    print("\n" + "=" * 52 + "\n")


if __name__ == "__main__":
    main()
