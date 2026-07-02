#!/usr/bin/env bash
#
# TechHeaven Reference Platform — Data Export Script
# Exports MySQL tables to CSV files in exports/ for ML/Data Science consumption
#

set -euo pipefail

EXPORTS_DIR="${EXPORTS_DIR:-/var/www/html/exports}"
MYSQL_HOST="${DB_HOST:-mysql}"
MYSQL_DB="${DB_DATABASE:-techheaven}"
MYSQL_USER="${DB_USERNAME:-techheaven}"
MYSQL_PASS="${DB_PASSWORD:-techheaven_secret}"

MYSQL_CMD="mysql -h${MYSQL_HOST} -u${MYSQL_USER} -p${MYSQL_PASS} -D${MYSQL_DB} --batch --skip-column-names"

echo ""
echo "═══════════════════════════════════════════"
echo "  TechHeaven Data Export"
echo "  Target: ${EXPORTS_DIR}"
echo "═══════════════════════════════════════════"
echo ""

mkdir -p "${EXPORTS_DIR}"

export_csv() {
    local name="$1"
    local query="$2"
    local file="${EXPORTS_DIR}/${name}.csv"

    echo "  → Exporting ${name}..."
    echo "$query" | ${MYSQL_CMD} | sed 's/\t/,/g; s/NULL//g' > "${file}"
    local rows
    rows=$(wc -l < "${file}")
    echo "     ✓ ${rows} rows → ${file}"
}

# ── Products ────────────────────────────────────────────────────────────────

export_csv "products" "
SELECT
    p.id              AS product_id,
    p.sku,
    pt.name,
    pt.url_key,
    p.type,
    af.name           AS attribute_family,
    pt.status,
    pt.new,
    pt.featured,
    pt.price,
    pt.special_price,
    pt.weight,
    cat_t.name        AS category_name,
    cat_t.slug        AS category_slug,
    opt_t.label       AS brand,
    pi.qty            AS stock_quantity,
    SUBSTRING(pt.short_description, 1, 200) AS short_description
FROM products p
LEFT JOIN product_flat pt ON pt.product_id = p.id AND pt.locale = 'en' AND pt.channel = 'default'
LEFT JOIN attribute_families af ON af.id = p.attribute_family_id
LEFT JOIN product_categories pc ON pc.product_id = p.id
LEFT JOIN category_translations cat_t ON cat_t.category_id = pc.category_id AND cat_t.locale = 'en'
LEFT JOIN product_attribute_values pav ON pav.product_id = p.id
    AND pav.attribute_id = (SELECT id FROM attributes WHERE code = 'brand' LIMIT 1)
LEFT JOIN attribute_option_translations opt_t ON opt_t.attribute_option_id = pav.integer_value AND opt_t.locale = 'en'
LEFT JOIN product_inventories pi ON pi.product_id = p.id
ORDER BY p.id;
"

# ── Customers ────────────────────────────────────────────────────────────────

export_csv "customers" "
SELECT
    c.id              AS customer_id,
    c.first_name,
    c.last_name,
    c.email,
    c.gender,
    c.date_of_birth,
    c.phone,
    cg.name           AS customer_group,
    c.is_verified,
    c.status,
    c.created_at      AS registered_at
FROM customers c
LEFT JOIN customer_groups cg ON cg.id = c.customer_group_id
ORDER BY c.id;
"

# ── Customer Addresses ───────────────────────────────────────────────────────

export_csv "customer_addresses" "
SELECT
    ca.id             AS address_id,
    ca.customer_id,
    ca.first_name,
    ca.last_name,
    ca.address1,
    ca.city,
    ca.state,
    ca.postcode,
    ca.country,
    ca.phone,
    ca.default_address
FROM customer_addresses ca
ORDER BY ca.customer_id, ca.id;
"

# ── Orders ───────────────────────────────────────────────────────────────────

export_csv "orders" "
SELECT
    o.id              AS order_id,
    o.increment_id,
    o.customer_id,
    o.customer_email,
    o.customer_first_name,
    o.customer_last_name,
    o.status,
    o.grand_total,
    o.base_grand_total,
    o.sub_total,
    o.tax_amount,
    o.discount_amount,
    o.coupon_code,
    o.shipping_method,
    o.shipping_amount,
    o.total_qty_ordered,
    o.total_item_count,
    o.channel_name,
    o.created_at      AS order_date
FROM orders o
ORDER BY o.id;
"

# ── Order Items ──────────────────────────────────────────────────────────────

export_csv "order_items" "
SELECT
    oi.id             AS order_item_id,
    oi.order_id,
    oi.product_id,
    oi.sku,
    oi.name           AS product_name,
    oi.type,
    oi.price,
    oi.base_price,
    oi.total,
    oi.base_total,
    oi.qty_ordered,
    oi.qty_shipped,
    oi.qty_invoiced,
    oi.qty_canceled,
    oi.discount_amount,
    oi.tax_amount,
    oi.created_at
FROM order_items oi
ORDER BY oi.order_id, oi.id;
"

# ── Invoices ─────────────────────────────────────────────────────────────────

export_csv "invoices" "
SELECT
    i.id              AS invoice_id,
    i.order_id,
    i.state,
    i.grand_total,
    i.base_grand_total,
    i.sub_total,
    i.tax_amount,
    i.discount_amount,
    i.shipping_amount,
    i.total_qty,
    i.created_at      AS invoice_date
FROM invoices i
ORDER BY i.order_id, i.id;
"

# ── Product Reviews ──────────────────────────────────────────────────────────

export_csv "reviews" "
SELECT
    r.id              AS review_id,
    r.product_id,
    r.customer_id,
    r.name            AS reviewer_name,
    r.title,
    r.comment,
    r.rating,
    r.status,
    r.created_at      AS review_date
FROM reviews r
ORDER BY r.product_id, r.created_at;
"

# ── Product Review Aggregates (for ML training) ───────────────────────────────

export_csv "product_review_stats" "
SELECT
    product_id,
    COUNT(*)                        AS total_reviews,
    ROUND(AVG(rating), 2)           AS avg_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS one_star
FROM reviews
WHERE status = 'approved'
GROUP BY product_id
ORDER BY product_id;
"

# ── Customer Order Summary (RFM base table) ───────────────────────────────────

export_csv "customer_rfm" "
SELECT
    o.customer_id,
    COUNT(DISTINCT o.id)            AS frequency,
    ROUND(SUM(o.grand_total), 2)    AS monetary,
    MAX(o.created_at)               AS last_order_date,
    MIN(o.created_at)               AS first_order_date,
    DATEDIFF(NOW(), MAX(o.created_at)) AS recency_days,
    ROUND(SUM(o.grand_total) / COUNT(DISTINCT o.id), 2) AS avg_order_value
FROM orders o
WHERE o.status NOT IN ('canceled', 'closed')
GROUP BY o.customer_id
ORDER BY monetary DESC;
"

# ── Market Basket (order-product pairs for MBA) ───────────────────────────────

export_csv "market_basket" "
SELECT
    oi.order_id,
    o.created_at      AS order_date,
    oi.product_id,
    oi.sku,
    oi.name           AS product_name,
    oi.qty_ordered,
    oi.price
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE o.status NOT IN ('canceled', 'closed')
ORDER BY oi.order_id, oi.product_id;
"

# ── Product Sales by Month (for forecasting) ─────────────────────────────────

export_csv "monthly_product_sales" "
SELECT
    DATE_FORMAT(o.created_at, '%Y-%m')  AS year_month,
    oi.product_id,
    oi.sku,
    SUM(oi.qty_ordered)                  AS units_sold,
    ROUND(SUM(oi.total), 2)              AS revenue
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE o.status NOT IN ('canceled', 'closed')
GROUP BY year_month, oi.product_id, oi.sku
ORDER BY year_month, oi.product_id;
"

# ── Coupons ──────────────────────────────────────────────────────────────────

export_csv "coupons" "
SELECT
    cr.id             AS rule_id,
    cr.name           AS campaign_name,
    c.code            AS coupon_code,
    cr.action_type,
    cr.discount_amount,
    cr.discount_quantity,
    cr.discount_step,
    cr.uses_per_coupon,
    cr.uses_per_customer,
    cr.status,
    cr.starts_from,
    cr.ends_till
FROM cart_rules cr
LEFT JOIN cart_rule_coupons c ON c.cart_rule_id = cr.id
ORDER BY cr.id;
"

# ── Summary ──────────────────────────────────────────────────────────────────

echo ""
echo "═══════════════════════════════════════════"
echo "  Export Complete"
echo "═══════════════════════════════════════════"
echo ""
ls -lh "${EXPORTS_DIR}"/*.csv
echo ""
