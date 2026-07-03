#!/usr/bin/env python3
"""
TechHeaven Reference Platform - RAG Knowledge Base Export

Exports the unstructured content that powers the Ecommerce AI "AI Customer
Support with RAG" playbook into clean, committed data files under data/.

Unlike scripts/export-data.sh (which dumps structured tables to CSV for
analytics), this script extracts the *knowledge base* content - policies, FAQ,
buying guides, and product descriptions - and strips HTML so the text is ready
to chunk and embed.

Output (committed to the repo, consumed by notebooks via raw.githubusercontent):
    data/knowledge_base/policies.json   - support & legal policies (clean text)
    data/knowledge_base/faq.json        - Q&A pairs parsed from the FAQ page
    data/knowledge_base/guides.json     - buying guides
    data/catalog/products.json          - products with full descriptions + specs

Run (maintainer only, from a machine with the TechHeaven DB reachable):
    pip install pymysql beautifulsoup4
    python scripts/export-rag-data.py

Connection defaults assume `docker compose up` exposes MySQL on localhost:3306.
Override via env: TH_DB_HOST, TH_DB_PORT, TH_DB_NAME, TH_DB_USER, TH_DB_PASS
"""

import json
import os
import re
from pathlib import Path

import pymysql
from bs4 import BeautifulSoup

# ── Connection ────────────────────────────────────────────────────────────────

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
DATA_DIR = REPO_ROOT / "data"
KB_DIR = DATA_DIR / "knowledge_base"
CATALOG_DIR = DATA_DIR / "catalog"

# CMS pages grouped by role. Keys are url_key substrings / cms_page ids.
POLICY_URL_KEYS = {
    "return-policy", "refund-policy", "shipping-policy", "warranty-policy",
    "privacy-policy", "payment-policy", "terms-conditions", "terms-of-use",
    "customer-service",
}
GUIDE_URL_KEYS = {"laptop-buying-guide", "monitor-buying-guide"}
FAQ_URL_KEYS = {"faq", "frequently-asked-questions"}


# ── HTML → clean text ─────────────────────────────────────────────────────────

def html_to_text(html: str) -> str:
    """Strip HTML to readable plain text, preserving paragraph breaks."""
    if not html:
        return ""
    soup = BeautifulSoup(html, "html.parser")
    # Turn block elements into newline-separated text.
    for br in soup.find_all("br"):
        br.replace_with("\n")
    lines = []
    for el in soup.find_all(["h1", "h2", "h3", "h4", "p", "li"]):
        text = el.get_text(" ", strip=True)
        if not text:
            continue
        if el.name in ("h1", "h2", "h3", "h4"):
            lines.append(f"\n{text}\n")
        elif el.name == "li":
            lines.append(f"- {text}")
        else:
            lines.append(text)
    out = "\n".join(lines) if lines else soup.get_text(" ", strip=True)
    out = re.sub(r"\n{3,}", "\n\n", out).strip()
    return out


def slug_from(url_key: str) -> str:
    return (url_key or "").strip("/").split("/")[-1]


# ── Extractors ────────────────────────────────────────────────────────────────

def fetch_cms_pages(conn):
    sql = """
        SELECT cp.id, cpt.url_key, cpt.page_title, cpt.html_content,
               cpt.meta_description
        FROM cms_pages cp
        JOIN cms_page_translations cpt
          ON cpt.cms_page_id = cp.id AND cpt.locale = 'en'
        ORDER BY cp.id
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        return cur.fetchall()


def parse_faq(html: str):
    """Parse the FAQ page: h2 = category, h3 = question, following p = answer."""
    soup = BeautifulSoup(html or "", "html.parser")
    pairs = []
    category = "General"
    for el in soup.find_all(["h2", "h3"]):
        if el.name == "h2":
            category = el.get_text(" ", strip=True)
            continue
        question = el.get_text(" ", strip=True)
        answer_parts = []
        for sib in el.find_next_siblings():
            if sib.name in ("h2", "h3"):
                break
            if sib.name in ("p", "ul", "ol"):
                answer_parts.append(sib.get_text(" ", strip=True))
        answer = " ".join(p for p in answer_parts if p).strip()
        if question and answer:
            pairs.append({"category": category, "question": question, "answer": answer})
    return pairs


def export_cms(conn):
    pages = fetch_cms_pages(conn)
    policies, guides, faq = [], [], []

    for pg in pages:
        slug = slug_from(pg["url_key"])
        clean = html_to_text(pg["html_content"])
        record = {
            "id": pg["id"],
            "slug": slug,
            "title": pg["page_title"],
            "content": clean,
        }
        if slug in FAQ_URL_KEYS:
            faq = parse_faq(pg["html_content"])
        elif slug in POLICY_URL_KEYS:
            category = "policy"
            if "return" in slug or "refund" in slug:
                category = "returns"
            elif "shipping" in slug:
                category = "shipping"
            elif "warranty" in slug:
                category = "warranty"
            elif "privacy" in slug or "terms" in slug:
                category = "legal"
            elif "payment" in slug:
                category = "payment"
            policies.append({**record, "category": category})
        elif slug in GUIDE_URL_KEYS:
            guides.append({**record, "category": "buying-guide"})

    return policies, guides, faq


def export_products(conn):
    """Products with full clean descriptions + key specs for product Q&A."""
    sql = """
        SELECT
            p.id                 AS product_id,
            p.sku,
            pf.name,
            pf.url_key           AS slug,
            pf.price,
            pf.special_price,
            pf.short_description,
            pf.description,
            (SELECT cat_t.name
               FROM product_categories pc
               JOIN category_translations cat_t
                 ON cat_t.category_id = pc.category_id AND cat_t.locale = 'en'
              WHERE pc.product_id = p.id
              ORDER BY pc.category_id
              LIMIT 1)           AS category,
            (SELECT opt_t.label
               FROM product_attribute_values pav
               JOIN attribute_option_translations opt_t
                 ON opt_t.attribute_option_id = pav.integer_value AND opt_t.locale = 'en'
              WHERE pav.product_id = p.id
                AND pav.attribute_id = (SELECT id FROM attributes WHERE code = 'brand' LIMIT 1)
              LIMIT 1)           AS brand,
            (SELECT SUM(pi.qty)
               FROM product_inventories pi
              WHERE pi.product_id = p.id) AS stock_quantity
        FROM products p
        JOIN product_flat pf
            ON pf.product_id = p.id AND pf.locale = 'en' AND pf.channel = 'default'
        WHERE pf.status = 1 AND pf.visible_individually = 1
        ORDER BY p.id
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()

    products = []
    for r in rows:
        products.append({
            "product_id": r["product_id"],
            "sku": r["sku"],
            "name": r["name"],
            "slug": r["slug"],
            "brand": r["brand"],
            "category": r["category"],
            "price": float(r["price"]) if r["price"] is not None else None,
            "special_price": float(r["special_price"]) if r["special_price"] is not None else None,
            "stock_quantity": int(r["stock_quantity"]) if r["stock_quantity"] is not None else 0,
            "short_description": html_to_text(r["short_description"]),
            "description": html_to_text(r["description"]),
        })
    return products


# ── Write ─────────────────────────────────────────────────────────────────────

def write_json(path: Path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✓ {len(data):>5} records → {path.relative_to(REPO_ROOT)}")


def main():
    print("\n" + "═" * 50)
    print("  TechHeaven RAG Knowledge Base Export")
    print("═" * 50 + "\n")

    conn = pymysql.connect(**DB)
    try:
        print("  Extracting CMS content...")
        policies, guides, faq = export_cms(conn)
        print("  Extracting product catalog...")
        products = export_products(conn)
    finally:
        conn.close()

    print("\n  Writing files:")
    write_json(KB_DIR / "policies.json", policies)
    write_json(KB_DIR / "faq.json", faq)
    write_json(KB_DIR / "guides.json", guides)
    write_json(CATALOG_DIR / "products.json", products)

    print("\n" + "═" * 50)
    print("  Export complete")
    print("═" * 50 + "\n")


if __name__ == "__main__":
    main()
