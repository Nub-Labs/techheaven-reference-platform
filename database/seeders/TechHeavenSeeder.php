<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TechHeaven Reference Business Seeder
 *
 * Orchestrates the complete seeding of the TechHeaven consumer electronics
 * retailer. Run order matters — later seeders depend on IDs created earlier.
 */
class TechHeavenSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║  TechHeaven Reference Platform — Data Seeder    ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->info('');

        // Re-enable fillable protection after Bagisto installer seeder calls Model::unguard()
        Model::reguard();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            AdminSeeder::class,        // Admin user + store configuration
            CategorySeeder::class,     // 15 categories + subcategories
            BrandAttributeSeeder::class, // Brand attribute + 17 brands
            ProductSeeder::class,      // 320 products with specs, pricing, inventory
            CustomerSeeder::class,     // 1,000 realistic customers
            OrderSeeder::class,        // 5,000 orders across 18 months
            ReviewSeeder::class,       // 5,000 product reviews
            CouponSeeder::class,       // Promotional campaigns
            ContentSeeder::class,      // CMS pages (policies, guides, FAQs)
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('');
        $this->command->info('✓ TechHeaven seeding complete!');
        $this->command->table(
            ['Resource', 'Count'],
            [
                ['Categories', DB::table('categories')->count()],
                ['Products', DB::table('products')->count()],
                ['Customers', DB::table('customers')->count()],
                ['Orders', DB::table('orders')->count()],
                ['Reviews', DB::table('product_reviews')->count()],
                ['CMS Pages', DB::table('cms_pages')->count()],
            ]
        );
    }
}
