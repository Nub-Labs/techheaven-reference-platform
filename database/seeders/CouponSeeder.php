<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CouponSeeder
 *
 * Seeds promotional coupon campaigns into Bagisto's cart_rules table.
 * Each campaign gets a corresponding cart_rule_coupons record and
 * a cart_rule_translations record for the 'en' locale.
 *
 * action_type values used by Bagisto:
 *   by_percent        — percentage discount on cart subtotal
 *   by_fixed          — fixed amount discount on cart subtotal
 *   to_percent        — reduce cart total to X% of original
 *   to_fixed          — reduce cart total to fixed amount
 *
 * coupon_type:
 *   0 — no coupon required (auto-applied)
 *   1 — specific coupon code required
 *
 * type (discount application):
 *   0 — percentage
 *   1 — fixed amount
 */
class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  → Seeding coupon campaigns...');

        DB::table('cart_rule_coupons')->delete();
        DB::table('cart_rule_translations')->delete();
        DB::table('cart_rules')->delete();

        $campaigns = $this->getCampaigns();

        foreach ($campaigns as $campaign) {
            $ruleId = DB::table('cart_rules')->insertGetId([
                'name'                  => $campaign['name'],
                'description'           => $campaign['description'],
                'action_type'           => $campaign['action_type'],
                'discount_amount'       => $campaign['discount_amount'],
                'discount_quantity'     => $campaign['discount_quantity'] ?? 0,
                'discount_step'         => $campaign['discount_step'] ?? 0,
                'apply_to_shipping'     => $campaign['apply_to_shipping'] ?? 0,
                'free_shipping'         => $campaign['free_shipping'] ?? 0,
                'usage_per_customer'    => $campaign['uses_per_customer'] ?? 0,
                'uses_per_coupon'       => $campaign['uses_per_code'] ?? 0,
                'times_used'            => $campaign['times_used'] ?? 0,
                'coupon_type'           => $campaign['coupon_type'] ?? 1,
                'use_auto_generation'   => $campaign['auto_generate'] ?? 0,
                'status'                => $campaign['is_active'] ?? 1,
                'starts_from'           => $campaign['starts_at'] ?? null,
                'ends_till'             => $campaign['ends_at'] ?? null,
                'sort_order'            => $campaign['sort_order'] ?? 0,
                'conditions'            => $campaign['conditions'] ?? null,
                'end_other_rules'       => 0,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // cart_rule_translations (no description column in Bagisto 2.4.x)
            DB::table('cart_rule_translations')->insert([
                'locale'       => 'en',
                'label'        => $campaign['label'],
                'cart_rule_id' => $ruleId,
            ]);

            // cart_rule_coupons — one coupon code per rule
            DB::table('cart_rule_coupons')->insert([
                'cart_rule_id'  => $ruleId,
                'code'          => $campaign['coupon_code'],
                'usage_limit'   => $campaign['usage_limit'] ?? 0,
                'times_used'    => $campaign['times_used'] ?? 0,
                'is_primary'    => 1,
                'type'          => 1,               // 1 = auto-generated type placeholder
                'expired_at'    => $campaign['ends_at'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $this->command->info('     Created ' . count($campaigns) . ' coupon campaigns');
    }

    private function getCampaigns(): array
    {
        return [
            // ---------------------------------------------------------------
            // 1. WELCOME10 — 10% off first order (new customers only)
            // ---------------------------------------------------------------
            [
                'name'            => 'WELCOME10',
                'label'           => 'Welcome — 10% Off Your First Order',
                'coupon_code'     => 'WELCOME10',
                'description'     => 'Welcome to TechHeaven! Enjoy 10% off your first order when you spend $50 or more. One use per customer. No expiry.',
                'type'            => 0,                 // percent-based
                'action_type'     => 'by_percent',
                'discount_amount' => 10.00,
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 1,               // one-time use per customer
                'uses_per_code'   => 0,                 // unlimited total redemptions
                'usage_limit'     => null,
                'times_used'      => 847,               // historical redemptions
                'coupon_type'     => 1,                 // specific coupon code
                'auto_generate'   => 0,
                'is_active'       => 1,
                'starts_at'       => null,
                'ends_at'         => null,              // no expiry
                'sort_order'      => 1,
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\SubTotal',
                            'operator'  => '>=',
                            'value'     => '50',
                        ],
                    ],
                ]),
            ],

            // ---------------------------------------------------------------
            // 2. SUMMER25 — 25% off sitewide, Jun–Aug 2025
            // ---------------------------------------------------------------
            [
                'name'            => 'SUMMER25',
                'label'           => 'Summer Sale — 25% Off Sitewide',
                'coupon_code'     => 'SUMMER25',
                'description'     => 'Beat the heat with 25% off everything at TechHeaven. Valid June 1 through August 31, 2025. Minimum order $100. Limited to one use per customer.',
                'type'            => 0,
                'action_type'     => 'by_percent',
                'discount_amount' => 25.00,
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 1,
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 2341,
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 0,                 // expired — ended Aug 31 2025
                'starts_at'       => '2025-06-01 00:00:00',
                'ends_at'         => '2025-08-31 23:59:59',
                'sort_order'      => 2,
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\SubTotal',
                            'operator'  => '>=',
                            'value'     => '100',
                        ],
                    ],
                ]),
            ],

            // ---------------------------------------------------------------
            // 3. BLACKFRIDAY — 30% off, Nov 25–30 2025, max discount $200
            // ---------------------------------------------------------------
            [
                'name'            => 'BLACKFRIDAY',
                'label'           => 'Black Friday — 30% Off (Up to $200)',
                'coupon_code'     => 'BLACKFRIDAY',
                'description'     => "TechHeaven's biggest sale of the year. Take 30% off your entire order — up to $200 in savings — when you spend $150 or more. Valid November 25–30, 2025 only.",
                'type'            => 0,
                'action_type'     => 'by_percent',
                'discount_amount' => 30.00,             // 30%
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 1,
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 5218,              // high volume Black Friday
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 0,                 // expired
                'starts_at'       => '2025-11-25 00:00:00',
                'ends_at'         => '2025-11-30 23:59:59',
                'sort_order'      => 3,
                // max_discount enforced at application layer; stored in conditions for reference
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\SubTotal',
                            'operator'  => '>=',
                            'value'     => '150',
                        ],
                    ],
                    'max_discount' => 200,
                ]),
            ],

            // ---------------------------------------------------------------
            // 4. FREESHIP — Free shipping on any order, no minimum, no expiry
            // ---------------------------------------------------------------
            [
                'name'            => 'FREESHIP',
                'label'           => 'Free Shipping on Your Order',
                'coupon_code'     => 'FREESHIP',
                'description'     => 'Apply code FREESHIP at checkout to receive free standard shipping on your order. No minimum purchase required. Valid on standard ground shipping only.',
                'type'            => 1,                 // fixed (shipping override)
                'action_type'     => 'by_fixed',        // fixed discount applied to shipping
                'discount_amount' => 0.00,              // shipping becomes free
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 1,               // applies to shipping amount
                'free_shipping'   => 1,                 // flag: grant free shipping
                'uses_per_customer' => 0,               // unlimited per customer
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 1203,
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 1,
                'starts_at'       => null,
                'ends_at'         => null,
                'sort_order'      => 4,
                'conditions'      => null,
            ],

            // ---------------------------------------------------------------
            // 5. BUNDLEDEAL — Buy 2+ items, get 15% off
            // ---------------------------------------------------------------
            [
                'name'            => 'BUNDLEDEAL',
                'label'           => 'Bundle Deal — 15% Off When You Buy 2 or More',
                'coupon_code'     => 'BUNDLEDEAL',
                'description'     => 'Add 2 or more qualifying products to your cart and save 15% on your entire order. Mix and match across all categories. Cannot be combined with other offers.',
                'type'            => 0,
                'action_type'     => 'by_percent',
                'discount_amount' => 15.00,
                'discount_quantity' => 2,               // applies when qty >= 2
                'discount_step'   => 1,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 0,
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 674,
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 1,
                'starts_at'       => null,
                'ends_at'         => null,
                'sort_order'      => 5,
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\TotalQty',
                            'operator'  => '>=',
                            'value'     => '2',
                        ],
                    ],
                ]),
            ],

            // ---------------------------------------------------------------
            // 6. TECHSAVE20 — 20% off Laptops category, min order $500
            // ---------------------------------------------------------------
            [
                'name'            => 'TECHSAVE20',
                'label'           => 'Save 20% on Laptops — Orders Over $500',
                'coupon_code'     => 'TECHSAVE20',
                'description'     => 'Get 20% off any laptop purchase of $500 or more. Applies to all laptop subcategories: ultrabooks, gaming, professional, business and budget. Not combinable with other discounts.',
                'type'            => 0,
                'action_type'     => 'by_percent',
                'discount_amount' => 20.00,
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 0,
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 398,
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 1,
                'starts_at'       => null,
                'ends_at'         => null,
                'sort_order'      => 6,
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\SubTotal',
                            'operator'  => '>=',
                            'value'     => '500',
                        ],
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Product\\Category',
                            'operator'  => '==',
                            'value'     => 'laptops',
                        ],
                    ],
                ]),
            ],

            // ---------------------------------------------------------------
            // 7. STUDENT15 — 15% off for students, no expiry, min order $75
            // ---------------------------------------------------------------
            [
                'name'            => 'STUDENT15',
                'label'           => 'Student Discount — 15% Off',
                'coupon_code'     => 'STUDENT15',
                'description'     => 'Exclusive discount for students and educators. Save 15% on orders of $75 or more. Valid on all products. Verification may be required at our discretion.',
                'type'            => 0,
                'action_type'     => 'by_percent',
                'discount_amount' => 15.00,
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 0,
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 1521,
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 1,
                'starts_at'       => null,
                'ends_at'         => null,
                'sort_order'      => 7,
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\SubTotal',
                            'operator'  => '>=',
                            'value'     => '75',
                        ],
                    ],
                ]),
            ],

            // ---------------------------------------------------------------
            // 8. EARLYBIRD — Expired Q1 2025 campaign (historical data)
            // ---------------------------------------------------------------
            [
                'name'            => 'EARLYBIRD',
                'label'           => 'Early Bird — 20% Off New Arrivals (Q1 2025)',
                'coupon_code'     => 'EARLYBIRD',
                'description'     => 'Get ahead of the curve — 20% off all products launched in Q1 2025. Valid January 1 through March 31, 2025. Minimum order $80. Campaign has ended.',
                'type'            => 0,
                'action_type'     => 'by_percent',
                'discount_amount' => 20.00,
                'discount_quantity' => 0,
                'discount_step'   => 0,
                'apply_to_shipping' => 0,
                'free_shipping'   => 0,
                'uses_per_customer' => 1,
                'uses_per_code'   => 0,
                'usage_limit'     => null,
                'times_used'      => 3091,              // solid Q1 campaign
                'coupon_type'     => 1,
                'auto_generate'   => 0,
                'is_active'       => 0,                 // inactive / expired
                'starts_at'       => '2025-01-01 00:00:00',
                'ends_at'         => '2025-03-31 23:59:59',
                'sort_order'      => 8,
                'conditions'      => json_encode([
                    'type'        => 'Webkul\\Rule\\Conditions\\Product\\Combine',
                    'aggregator'  => 'all',
                    'value'       => 1,
                    'conditions'  => [
                        [
                            'type'      => 'Webkul\\CartRule\\Rules\\Cart\\SubTotal',
                            'operator'  => '>=',
                            'value'     => '80',
                        ],
                    ],
                ]),
            ],
        ];
    }
}
