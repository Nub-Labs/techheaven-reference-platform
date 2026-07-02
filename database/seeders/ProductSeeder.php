<?php

namespace Database\Seeders;

use Database\Seeders\ProductData\AccessoryData;
use Database\Seeders\ProductData\AudioData;
use Database\Seeders\ProductData\CameraData;
use Database\Seeders\ProductData\ComponentData;
use Database\Seeders\ProductData\KeyboardData;
use Database\Seeders\ProductData\LaptopData;
use Database\Seeders\ProductData\MemoryData;
use Database\Seeders\ProductData\MobileData;
use Database\Seeders\ProductData\MonitorData;
use Database\Seeders\ProductData\MouseData;
use Database\Seeders\ProductData\NetworkingData;
use Database\Seeders\ProductData\PrinterData;
use Database\Seeders\ProductData\SmartHomeData;
use Database\Seeders\ProductData\StorageData;
use Database\Seeders\ProductData\WearableData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Product\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Model;

class ProductSeeder extends Seeder
{
    protected int   $channelId = 1;
    protected int   $familyId  = 1;
    protected array $catMap    = [];
    protected array $brandMap  = [];

    public function __construct(protected ProductRepository $productRepository) {}

    public function run(): void
    {
        $this->command->info('  → Seeding products (this takes several minutes)…');
        $this->loadState();

        $all = array_merge(
            LaptopData::get(),
            MonitorData::get(),
            StorageData::get(),
            MemoryData::get(),
            NetworkingData::get(),
            AudioData::get(),
            KeyboardData::get(),
            MouseData::get(),
            ComponentData::get(),
            SmartHomeData::get(),
            MobileData::get(),
            WearableData::get(),
            CameraData::get(),
            PrinterData::get(),
            AccessoryData::get(),
        );

        $created = 0;
        foreach ($all as $p) {
            try {
                $product = $this->productRepository->create([
                    'type'                => 'simple',
                    'attribute_family_id' => $this->familyId,
                    'sku'                 => $p['sku'],
                ]);

                $payload = [
                    'channel'              => 'default',
                    'locale'               => 'en',
                    'sku'                  => $p['sku'],
                    'name'                 => $p['name'],
                    'url_key'              => Str::slug($p['name']) . '-' . strtolower($p['sku']),
                    'price'                => $p['price'],
                    'special_price'        => $p['special_price'] ?? null,
                    'description'          => $p['description'],
                    'short_description'    => $p['short_description'],
                    'weight'               => $p['weight'],
                    'new'                  => $p['new'] ?? 0,
                    'featured'             => $p['featured'] ?? 0,
                    'status'               => 1,
                    'visible_individually' => 1,
                    'guest_checkout'       => 1,
                    'inventories'          => [$this->channelId => $p['stock']],
                    'categories'           => [$this->getCategoryId($p['category'])],
                    'meta_title'           => $p['name'] . ' — TechHeaven',
                    'meta_description'     => $p['short_description'],
                    'meta_keywords'        => ($p['brand'] ?? '') . ', ' . strtolower($p['name']),
                ];

                if (!empty($p['brand']) && isset($this->brandMap[$p['brand']])) {
                    $payload['brand'] = $this->brandMap[$p['brand']];
                }

                $this->productRepository->update($payload, $product->id);
                $created++;

                if ($created % 25 === 0) {
                    $this->command->info("     → {$created} products created…");
                }
            } catch (\Throwable $e) {
                $this->command->warn("     Skipped {$p['sku']}: " . $e->getMessage());
            }
        }

        $this->command->info("     ✓ {$created} products created");

        // Propagate product→category assignments up the ancestor tree so parent
        // category pages (e.g. /smart-home, /wearables) list their descendants' products.
        DB::statement("
            INSERT IGNORE INTO product_categories (product_id, category_id)
            SELECT DISTINCT pc.product_id, anc.id
            FROM product_categories pc
            JOIN categories leaf ON leaf.id = pc.category_id
            JOIN categories anc  ON anc._lft < leaf._lft AND anc._rgt > leaf._rgt
            WHERE anc.parent_id IS NOT NULL
        ");
    }

    protected function loadState(): void
    {
        $this->channelId = (int) (DB::table('channels')->value('id') ?? 1);
        $this->familyId  = (int) (DB::table('attribute_families')->value('id') ?? 1);

        $this->catMap = !empty(CategorySeeder::$categoryIds)
            ? CategorySeeder::$categoryIds
            : DB::table('category_translations')
                ->whereNotNull('slug')
                ->pluck('category_id', 'slug')
                ->toArray();

        if (!empty(BrandAttributeSeeder::$brandOptionIds)) {
            $this->brandMap = BrandAttributeSeeder::$brandOptionIds;
        } else {
            $attrId = DB::table('attributes')->where('code', 'brand')->value('id');
            if ($attrId) {
                $this->brandMap = DB::table('attribute_options')
                    ->where('attribute_id', $attrId)
                    ->pluck('id', 'admin_name')
                    ->toArray();
            }
        }
    }

    protected function getCategoryId(string $slug): int
    {
        return (int) ($this->catMap[$slug]
            ?? DB::table('category_translations')->where('slug', $slug)->value('category_id')
            ?? 1);
    }
}
