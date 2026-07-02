<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /** Populated during run(); other seeders may read this. */
    public static array $orderIds = [];

    // ── Market basket associations ────────────────────────────────────────────
    // Probabilities are expressed as percentages (0-100).
    // Keys are category slugs used to group triggering products.
    private array $basketRules = [
        // trigger slug => [ associated_category_slug => probability% ]
        'gaming-laptops'   => [
            'gaming-mice'        => 65,
            'mechanical-keyboards' => 55,
            'monitors'           => 40,
            'gaming-headsets'    => 50,
            'laptop-bags'        => 45,
        ],
        'ultrabooks'       => [
            'laptop-bags'        => 55,
            'chargers-cables'    => 45,
            'gaming-mice'        => 25,
        ],
        'professional-laptops' => [
            'laptop-bags'        => 50,
            'chargers-cables'    => 40,
            'docking-stations'   => 35,
        ],
        'business-laptops' => [
            'laptop-bags'        => 50,
            'docking-stations'   => 40,
            'chargers-cables'    => 35,
        ],
        'budget-laptops'   => [
            'laptop-bags'        => 40,
            'chargers-cables'    => 35,
        ],
        'monitors'         => [
            'chargers-cables'    => 60,
            'desk-accessories'   => 35,
        ],
        'gaming-monitors'  => [
            'chargers-cables'    => 60,
            'desk-accessories'   => 35,
        ],
        'mechanical-keyboards' => [
            'gaming-mice'        => 70,
            'desk-accessories'   => 40,
        ],
        'gaming-keyboards' => [
            'gaming-mice'        => 70,
            'desk-accessories'   => 40,
        ],
    ];

    // Products grouped by category slug, loaded from DB at runtime.
    private array $productsByCategory = [];
    // All product IDs as a flat array for random selection.
    private array $allProductIds = [];
    // Product details keyed by product_id.
    private array $productDetails = [];

    // ── Payment / shipping data ────────────────────────────────────────────────

    private array $paymentMethods = [
        ['method' => 'paypal', 'title' => 'PayPal'],
        ['method' => 'stripe', 'title' => 'Credit / Debit Card'],
        ['method' => 'cashondelivery', 'title' => 'Cash on Delivery'],
    ];

    // Shipping amount tiers
    private array $shippingTiers = [4.99, 7.99, 9.99, 12.99, 14.99, 19.99];

    // Order status distribution (75 completed, 10 processing, 10 canceled, 5 pending per 100)
    private array $statusPool;

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('  → Seeding orders...');

        $this->buildStatusPool();
        $this->loadProducts();

        if (empty($this->allProductIds)) {
            $this->command->warn('     No products found — skipping OrderSeeder.');
            return;
        }

        // Load customers (prefer CustomerSeeder static array, fall back to DB)
        $customerIds = !empty(CustomerSeeder::$customerIds)
            ? CustomerSeeder::$customerIds
            : DB::table('customers')->pluck('id')->toArray();

        if (empty($customerIds)) {
            $this->command->warn('     No customers found — skipping OrderSeeder.');
            return;
        }

        $customerCount = count($customerIds);

        // Pre-load customer info for order addresses
        $customers = DB::table('customers')
            ->select('id', 'first_name', 'last_name', 'email', 'phone')
            ->get()
            ->keyBy('id');

        $customerAddresses = DB::table('addresses')
            ->where('address_type', 'customer')
            ->select('id', 'customer_id', 'first_name', 'last_name', 'address',
                     'city', 'state', 'country', 'postcode', 'phone', 'default_address')
            ->get()
            ->groupBy('customer_id');

        $now         = now();
        $totalOrders = 5000;
        $batchSize   = 50;

        // Month weights: base=1.0, November=1.8, December=1.6
        // We'll pick a month proportionally when assigning created_at
        $monthWeights = $this->buildMonthWeights(18);

        $orderBatch        = [];
        $orderItemBatch    = [];
        $orderAddrBatch    = [];
        $paymentBatch      = [];
        $invoiceBatch      = [];
        $invoiceItemBatch  = [];
        $shipmentBatch     = [];

        $orderIncrementId  = 100000001;
        $invoiceIncrementId = 200000001;
        $shipmentIncrementId = 300000001;

        $generatedOrders = 0;

        for ($i = 0; $i < $totalOrders; $i++) {
            $customerId = $customerIds[$i % $customerCount];
            $customer   = $customers[$customerId] ?? null;
            if (!$customer) {
                continue;
            }

            // Pick order date based on month weights
            $createdAt = $this->pickOrderDate($now, $monthWeights, $i);

            // Status
            $status = $this->statusPool[$i % count($this->statusPool)];

            // Payment
            $payment = $this->paymentMethods[$i % 3];

            // Shipping
            $shippingAmount = $this->shippingTiers[$i % count($this->shippingTiers)];

            // Select items using basket logic
            $items = $this->buildBasket($i);

            // Calculate totals
            $subtotal = 0.0;
            $totalQty = 0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['qty'];
                $totalQty += $item['qty'];
            }
            $grandTotal = round($subtotal + $shippingAmount, 2);
            $subtotal   = round($subtotal, 2);

            // Customer address for this order
            $addrData = $this->resolveAddress($customerAddresses, $customer, $customerId);

            // ── orders row ────────────────────────────────────────────────────
            $orderRow = [
                'increment_id'             => (string)$orderIncrementId,
                'status'                   => $status,
                'channel_id'               => 1,
                'channel_name'             => 'TechHeaven',
                'channel_type'             => 'shop',
                'customer_id'              => $customerId,
                'customer_email'           => $customer->email,
                'customer_first_name'      => $customer->first_name,
                'customer_last_name'       => $customer->last_name,
                'customer_type'            => 'customer',
                'is_guest'                 => 0,
                'shipping_method'          => 'flatrate_flatrate',
                'shipping_title'           => 'Flat Rate',
                'total_item_count'         => count($items),
                'total_qty_ordered'        => $totalQty,
                'base_currency_code'       => 'USD',
                'channel_currency_code'    => 'USD',
                'order_currency_code'      => 'USD',
                'grand_total'              => $grandTotal,
                'base_grand_total'         => $grandTotal,
                'sub_total'                => $subtotal,
                'base_sub_total'           => $subtotal,
                'tax_amount'               => 0,
                'base_tax_amount'          => 0,
                'shipping_amount'          => $shippingAmount,
                'base_shipping_amount'     => $shippingAmount,
                'discount_amount'          => 0,
                'base_discount_amount'     => 0,
                'sub_total_invoiced'       => $status === 'completed' ? $subtotal : 0,
                'base_sub_total_invoiced'  => $status === 'completed' ? $subtotal : 0,
                'shipping_invoiced'        => $status === 'completed' ? $shippingAmount : 0,
                'base_shipping_invoiced'   => $status === 'completed' ? $shippingAmount : 0,
                'grand_total_invoiced'     => $status === 'completed' ? $grandTotal : 0,
                'base_grand_total_invoiced' => $status === 'completed' ? $grandTotal : 0,
                'created_at'               => $createdAt,
                'updated_at'               => $createdAt,
            ];

            // Payment goes to order_payment table (separate in Bagisto 2.4.x)
            $paymentBatch[] = [
                'order_increment' => $orderIncrementId,
                'method'          => $payment['method'],
                'method_title'    => $payment['title'],
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ];

            $orderBatch[] = $orderRow;

            // Collect order addresses (go into unified `addresses` table)
            $orderAddrBatch[] = array_merge($addrData, [
                'order_id'        => null,
                'order_increment' => $orderIncrementId,
                'address_type'    => 'order_billing',
            ]);
            $orderAddrBatch[] = array_merge($addrData, [
                'order_id'        => null,
                'order_increment' => $orderIncrementId,
                'address_type'    => 'order_shipping',
            ]);

            // Collect items
            foreach ($items as $item) {
                $orderItemBatch[] = [
                    'order_id'          => null,
                    'order_increment'   => $orderIncrementId,
                    'sku'               => $item['sku'],
                    'type'              => 'simple',
                    'name'              => $item['name'],
                    'qty_ordered'       => $item['qty'],
                    'qty_shipped'       => $status === 'completed' ? $item['qty'] : 0,
                    'qty_invoiced'      => $status === 'completed' ? $item['qty'] : 0,
                    'qty_canceled'      => $status === 'canceled' ? $item['qty'] : 0,
                    'qty_refunded'      => 0,
                    'price'             => $item['price'],
                    'base_price'        => $item['price'],
                    'total'             => round($item['price'] * $item['qty'], 2),
                    'base_total'        => round($item['price'] * $item['qty'], 2),
                    'tax_amount'        => 0,
                    'base_tax_amount'   => 0,
                    'discount_amount'   => 0,
                    'base_discount_amount' => 0,
                    'product_id'        => $item['product_id'],
                    'product_type'      => 'Webkul\\Product\\Models\\Product',
                    'weight'            => $item['weight'] ?? 1.00,
                    'total_weight'      => round(($item['weight'] ?? 1.00) * $item['qty'], 2),
                    'created_at'        => $createdAt,
                    'updated_at'        => $createdAt,
                ];
            }

            // Invoice + shipment for completed orders
            if ($status === 'completed') {
                $invoiceBatch[] = [
                    'increment_id'   => (string)$invoiceIncrementId,
                    'state'          => 'paid',
                    'order_id'       => null,
                    'order_increment'=> $orderIncrementId,
                    'grand_total'    => $grandTotal,
                    'base_grand_total'=> $grandTotal,
                    'sub_total'      => $subtotal,
                    'base_sub_total' => $subtotal,
                    'tax_amount'     => 0,
                    'base_tax_amount'=> 0,
                    'shipping_amount'=> $shippingAmount,
                    'base_shipping_amount'=> $shippingAmount,
                    'discount_amount'=> 0,
                    'base_discount_amount'=> 0,
                    'created_at'     => $createdAt,
                    'updated_at'     => $createdAt,
                ];

                foreach ($items as $item) {
                    $invoiceItemBatch[] = [
                        'invoice_increment' => $invoiceIncrementId,
                        'order_increment'   => $orderIncrementId,
                        'sku'               => $item['sku'],
                        'name'              => $item['name'],
                        'qty'               => $item['qty'],
                        'price'             => $item['price'],
                        'base_price'        => $item['price'],
                        'total'             => round($item['price'] * $item['qty'], 2),
                        'base_total'        => round($item['price'] * $item['qty'], 2),
                        'tax_amount'        => 0,
                        'base_tax_amount'   => 0,
                        'discount_amount'   => 0,
                        'base_discount_amount'=> 0,
                        'product_id'        => $item['product_id'],
                        'created_at'        => $createdAt,
                        'updated_at'        => $createdAt,
                    ];
                }

                $shipmentBatch[] = [
                    'status'          => null,
                    'total_qty'       => $totalQty,
                    'total_weight'    => null,
                    'order_id'        => null,
                    'order_increment' => $orderIncrementId,
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt,
                ];

                $invoiceIncrementId++;
                $shipmentIncrementId++;
            }

            $orderIncrementId++;
            $generatedOrders++;

            // Flush batches
            if (count($orderBatch) >= $batchSize) {
                $this->flushBatch(
                    $orderBatch, $orderItemBatch, $orderAddrBatch, $paymentBatch,
                    $invoiceBatch, $invoiceItemBatch, $shipmentBatch
                );
                $orderBatch = $orderItemBatch = $orderAddrBatch = $paymentBatch = [];
                $invoiceBatch = $invoiceItemBatch = $shipmentBatch = [];
            }
        }

        // Flush remainder
        if (!empty($orderBatch)) {
            $this->flushBatch(
                $orderBatch, $orderItemBatch, $orderAddrBatch, $paymentBatch,
                $invoiceBatch, $invoiceItemBatch, $shipmentBatch
            );
        }

        self::$orderIds = DB::table('orders')->pluck('id')->toArray();

        $this->command->info('     Created ' . $generatedOrders . ' orders');
    }

    // ── Flush one batch to the DB ─────────────────────────────────────────────

    private function flushBatch(
        array $orderBatch,
        array $orderItemBatch,
        array $orderAddrBatch,
        array $paymentBatch,
        array $invoiceBatch,
        array $invoiceItemBatch,
        array $shipmentBatch
    ): void {
        // Insert orders and map increment_id → id
        DB::table('orders')->insert(
            array_map(fn($o) => array_diff_key($o, ['order_increment' => 1]), $orderBatch)
        );

        // Retrieve inserted order IDs by increment_id
        $incrementIds = array_column($orderBatch, 'increment_id');
        $orderMap = DB::table('orders')
            ->whereIn('increment_id', $incrementIds)
            ->pluck('id', 'increment_id');

        // Order items
        $mappedItems = array_map(function ($item) use ($orderMap) {
            $orderId = $orderMap[$item['order_increment']] ?? null;
            unset($item['order_increment']);
            $item['order_id'] = $orderId;
            return $item;
        }, $orderItemBatch);

        if (!empty($mappedItems)) {
            DB::table('order_items')->insert($mappedItems);
        }

        // Order addresses (unified addresses table in Bagisto 2.4.x)
        $mappedAddrs = array_map(function ($addr) use ($orderMap) {
            $orderId = $orderMap[$addr['order_increment']] ?? null;
            unset($addr['order_increment']);
            $addr['order_id'] = $orderId;
            return $addr;
        }, $orderAddrBatch);

        if (!empty($mappedAddrs)) {
            DB::table('addresses')->insert($mappedAddrs);
        }

        // Order payments (separate table in Bagisto 2.4.x)
        $mappedPayments = array_map(function ($p) use ($orderMap) {
            $orderId = $orderMap[$p['order_increment']] ?? null;
            unset($p['order_increment']);
            $p['order_id'] = $orderId;
            return $p;
        }, $paymentBatch);

        if (!empty($mappedPayments)) {
            DB::table('order_payment')->insert($mappedPayments);
        }

        // Invoices
        if (!empty($invoiceBatch)) {
            $mappedInvoices = array_map(function ($inv) use ($orderMap) {
                $orderId = $orderMap[$inv['order_increment']] ?? null;
                unset($inv['order_increment']);
                $inv['order_id'] = $orderId;
                return $inv;
            }, $invoiceBatch);

            DB::table('invoices')->insert($mappedInvoices);

            // Map invoice increment → id
            $invIncrIds = array_column($invoiceBatch, 'increment_id');
            $invoiceMap = DB::table('invoices')
                ->whereIn('increment_id', $invIncrIds)
                ->pluck('id', 'increment_id');

            // Invoice items
            $mappedInvItems = [];
            foreach ($invoiceItemBatch as $ii) {
                $invId = $invoiceMap[$ii['invoice_increment']] ?? null;
                unset($ii['invoice_increment'], $ii['order_increment']);
                $ii['invoice_id'] = $invId;
                $mappedInvItems[] = $ii;
            }

            if (!empty($mappedInvItems)) {
                DB::table('invoice_items')->insert($mappedInvItems);
            }
        }

        // Shipments
        if (!empty($shipmentBatch)) {
            $mappedShipments = array_map(function ($s) use ($orderMap) {
                $orderId = $orderMap[$s['order_increment']] ?? null;
                unset($s['order_increment']);
                $s['order_id'] = $orderId;
                return $s;
            }, $shipmentBatch);

            DB::table('shipments')->insert($mappedShipments);
        }
    }

    // ── Product loading ───────────────────────────────────────────────────────

    private function loadProducts(): void
    {
        // Load products with their flat data
        $products = DB::table('products as p')
            ->join('product_flat as pf', 'pf.product_id', '=', 'p.id')
            ->where('pf.locale', 'en')
            ->where('pf.channel', 'default')
            ->where('p.type', 'simple')
            ->select(
                'p.id as product_id',
                'pf.sku',
                'pf.name',
                'pf.price',
                'pf.weight',
            )
            ->get();

        if ($products->isEmpty()) {
            // Fallback: load from products table only
            $products = DB::table('products')
                ->where('type', 'simple')
                ->select('id as product_id', 'sku')
                ->get()
                ->map(function ($p) {
                    $p->name  = 'Product ' . $p->product_id;
                    $p->price = 49.99 + ($p->product_id % 200) * 5;
                    $p->weight = 1.00;
                    return $p;
                });
        }

        foreach ($products as $p) {
            $this->allProductIds[] = $p->product_id;
            $this->productDetails[$p->product_id] = [
                'product_id' => $p->product_id,
                'sku'        => $p->sku ?? 'SKU-' . $p->product_id,
                'name'       => $p->name ?? 'Product ' . $p->product_id,
                'price'      => $p->price ?? 49.99,
                'weight'     => $p->weight ?? 1.00,
            ];
        }

        // Group by category slug using product_categories + category_translations
        $categoryMappings = DB::table('product_categories as pc')
            ->join('category_translations as ct', 'ct.category_id', '=', 'pc.category_id')
            ->where('ct.locale', 'en')
            ->select('pc.product_id', 'ct.slug')
            ->get();

        foreach ($categoryMappings as $cm) {
            if (!isset($this->productsByCategory[$cm->slug])) {
                $this->productsByCategory[$cm->slug] = [];
            }
            $this->productsByCategory[$cm->slug][] = $cm->product_id;
        }
    }

    // ── Basket builder ────────────────────────────────────────────────────────

    private function buildBasket(int $seed): array
    {
        // Pick 1–5 items; use seed for deterministic but varied results
        $maxItems  = 5;
        $itemCount = ($seed % $maxItems) + 1;

        $selectedIds = [];
        $items       = [];

        // Primary product — pick from a weighted category
        $primaryCategory = $this->pickPrimaryCategory($seed);
        $primaryId       = $this->pickProductFromCategory($primaryCategory, $seed);

        if ($primaryId) {
            $selectedIds[] = $primaryId;
            $items[]       = $this->buildItem($primaryId, $seed);
        } elseif (!empty($this->allProductIds)) {
            $primaryId     = $this->allProductIds[$seed % count($this->allProductIds)];
            $selectedIds[] = $primaryId;
            $items[]       = $this->buildItem($primaryId, $seed);
        }

        // Apply basket rules based on primary category
        if ($primaryCategory && isset($this->basketRules[$primaryCategory])) {
            foreach ($this->basketRules[$primaryCategory] as $assocSlug => $probability) {
                if (count($items) >= $itemCount) {
                    break;
                }
                // Deterministic probability check
                $roll = ($seed * 31 + strlen($assocSlug) * 17 + 7) % 100;
                if ($roll < $probability) {
                    $assocId = $this->pickProductFromCategory($assocSlug, $seed + strlen($assocSlug));
                    if ($assocId && !in_array($assocId, $selectedIds)) {
                        $selectedIds[] = $assocId;
                        $items[]       = $this->buildItem($assocId, $seed + 1);
                    }
                }
            }
        }

        // Fill remaining slots with random products
        $attempts = 0;
        while (count($items) < $itemCount && !empty($this->allProductIds) && $attempts < 20) {
            $idx      = ($seed * 97 + $attempts * 13 + 5) % count($this->allProductIds);
            $randId   = $this->allProductIds[$idx];
            if (!in_array($randId, $selectedIds)) {
                $selectedIds[] = $randId;
                $items[]       = $this->buildItem($randId, $seed + $attempts);
            }
            $attempts++;
        }

        return $items;
    }

    private function buildItem(int $productId, int $seed): array
    {
        $detail = $this->productDetails[$productId] ?? [
            'product_id' => $productId,
            'sku'        => 'SKU-' . $productId,
            'name'       => 'Product ' . $productId,
            'price'      => 49.99,
            'weight'     => 1.00,
        ];

        $qty = (($seed + $productId) % 3) + 1;

        return [
            'product_id' => $detail['product_id'],
            'sku'        => $detail['sku'],
            'name'       => $detail['name'],
            'price'      => (float)$detail['price'],
            'weight'     => (float)($detail['weight'] ?? 1.00),
            'qty'        => $qty,
        ];
    }

    private function pickPrimaryCategory(int $seed): ?string
    {
        if (empty($this->productsByCategory)) {
            return null;
        }
        $keys = array_keys($this->productsByCategory);
        return $keys[$seed % count($keys)];
    }

    private function pickProductFromCategory(string $slug, int $seed): ?int
    {
        $pool = $this->productsByCategory[$slug] ?? $this->allProductIds;
        if (empty($pool)) {
            return null;
        }
        return $pool[$seed % count($pool)];
    }

    // ── Date / weight helpers ─────────────────────────────────────────────────

    /**
     * Build a weighted array of (month offset from now, weight) tuples
     * covering the last $months months.
     */
    private function buildMonthWeights(int $months): array
    {
        $now    = now();
        $result = [];

        for ($m = 0; $m < $months; $m++) {
            $date       = $now->copy()->subMonths($m);
            $calMonth   = (int)$date->format('n'); // 1-12
            $weight     = 1.0;

            if ($calMonth === 11) {
                $weight = 1.8; // November — Black Friday
            } elseif ($calMonth === 12) {
                $weight = 1.6; // December — holiday season
            }

            $result[] = ['offset' => $m, 'weight' => $weight];
        }

        return $result;
    }

    /**
     * Pick a random date within the last 18 months, weighted by month.
     * Uses deterministic seeding so repeated runs are stable.
     */
    private function pickOrderDate($now, array $monthWeights, int $seed): string
    {
        // Build cumulative weight array
        $totalWeight = array_sum(array_column($monthWeights, 'weight'));
        $pick        = (($seed * 9973 + 12345) % 10000) / 10000 * $totalWeight;

        $selectedOffset = 0;
        $cumulative     = 0;
        foreach ($monthWeights as $mw) {
            $cumulative += $mw['weight'];
            if ($pick <= $cumulative) {
                $selectedOffset = $mw['offset'];
                break;
            }
        }

        $dayOffset = ($seed * 7 + 3) % 28;
        $hourOffset = ($seed * 3 + 1) % 24;

        return $now->copy()
            ->subMonths($selectedOffset)
            ->subDays($dayOffset)
            ->subHours($hourOffset)
            ->format('Y-m-d H:i:s');
    }

    // ── Address resolver ─────────────────────────────────────────────────────

    private function resolveAddress($customerAddresses, $customer, int $customerId): array
    {
        $addrCollection = $customerAddresses[$customerId] ?? null;
        $addr = $addrCollection ? $addrCollection->first() : null;

        if ($addr) {
            return [
                'first_name'   => $addr->first_name,
                'last_name'    => $addr->last_name,
                'company_name' => null,
                'address'      => $addr->address,
                'postcode'     => $addr->postcode,
                'city'         => $addr->city,
                'state'        => $addr->state,
                'country'      => $addr->country,
                'phone'        => $addr->phone,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        return [
            'first_name'   => $customer->first_name,
            'last_name'    => $customer->last_name,
            'company_name' => null,
            'address'      => '123 Main St',
            'postcode'     => '10001',
            'city'         => 'New York',
            'state'        => 'NY',
            'country'      => 'US',
            'phone'        => $customer->phone ?? '',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }

    // ── Status pool ───────────────────────────────────────────────────────────

    private function buildStatusPool(): void
    {
        $this->statusPool = array_merge(
            array_fill(0, 75, 'completed'),
            array_fill(0, 10, 'processing'),
            array_fill(0, 10, 'canceled'),
            array_fill(0, 5,  'pending'),
        );
    }
}
