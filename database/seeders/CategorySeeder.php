<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Category\Models\Category;

class CategorySeeder extends Seeder
{
    // Stores slug → id mapping for use by ProductSeeder
    public static array $categoryIds = [];

    public function run(): void
    {
        $this->command->info('  → Seeding product categories...');

        // Root category (id=1) is created by Bagisto's CategorySeeder.
        // All TechHeaven categories are children of root.
        $rootId = DB::table('categories')->where('parent_id', null)->value('id') ?? 1;

        $tree = $this->getCategoryTree();

        foreach ($tree as $parent) {
            $parentId = $this->createCategory(
                $parent['name'],
                $parent['description'],
                $rootId,
                $parent['position'],
                $parent['slug']
            );
            self::$categoryIds[$parent['slug']] = $parentId;

            foreach ($parent['children'] ?? [] as $position => $child) {
                $childId = $this->createCategory(
                    $child['name'],
                    $child['description'],
                    $parentId,
                    $position + 1,
                    $child['slug'],
                    $parent['slug']
                );
                self::$categoryIds[$child['slug']] = $childId;
            }
        }

        // Rebuild NestedSet _lft/_rgt values — raw DB inserts set them to 0
        Category::fixTree();

        $this->command->info('     Created ' . count(self::$categoryIds) . ' categories');
    }

    private function createCategory(string $name, string $description, int $parentId, int $position, string $slug, string $parentSlug = ''): int
    {
        $urlPath = $parentSlug ? $parentSlug . '/' . $slug : $slug;

        $id = DB::table('categories')->insertGetId([
            'position'     => $position,
            'status'       => 1,
            'display_mode' => 'products_and_description',
            'parent_id'    => $parentId,
            '_lft'         => 0,
            '_rgt'         => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('category_translations')->insert([
            'locale'           => 'en',
            'name'             => $name,
            'slug'             => $slug,
            'url_path'         => $urlPath,
            'description'      => $description,
            'meta_title'       => $name . ' — TechHeaven',
            'meta_description' => 'Shop ' . $name . ' at TechHeaven. Best prices, expert reviews, fast delivery.',
            'meta_keywords'    => strtolower($name) . ', buy ' . strtolower($name) . ', TechHeaven',
            'category_id'      => $id,
        ]);

        return $id;
    }

    private function getCategoryTree(): array
    {
        return [
            [
                'name'        => 'Laptops',
                'slug'        => 'laptops',
                'description' => 'Ultrabooks, professional workstations, 2-in-1s and gaming laptops from all leading brands.',
                'position'    => 1,
                'children'    => [
                    ['slug' => 'ultrabooks', 'name' => 'Ultrabooks & Thin-and-Light', 'description' => 'Lightweight laptops built for productivity on the go.'],
                    ['slug' => 'professional-laptops', 'name' => 'Professional Workstations', 'description' => 'High-performance laptops for creative professionals and power users.'],
                    ['slug' => 'gaming-laptops', 'name' => 'Gaming Laptops', 'description' => 'High-refresh displays, discrete GPUs, and RGB keyboards for serious gamers.'],
                    ['slug' => 'business-laptops', 'name' => 'Business Laptops', 'description' => 'Secure, durable laptops built for enterprise use.'],
                    ['slug' => 'budget-laptops', 'name' => 'Budget Laptops', 'description' => 'Capable everyday laptops at value pricing.'],
                ],
            ],
            [
                'name'        => 'Monitors',
                'slug'        => 'monitors',
                'description' => '4K, OLED, gaming and ultrawide monitors for every workspace.',
                'position'    => 2,
                'children'    => [
                    ['slug' => 'gaming-monitors', 'name' => 'Gaming Monitors', 'description' => 'High-refresh-rate monitors with G-Sync and FreeSync support.'],
                    ['slug' => 'professional-monitors', 'name' => 'Professional Monitors', 'description' => 'Color-accurate displays for photography, video, and design.'],
                    ['slug' => 'ultrawide-monitors', 'name' => 'Ultrawide & Curved', 'description' => '21:9 and 32:9 ultrawide monitors for immersive multitasking.'],
                    ['slug' => 'home-office-monitors', 'name' => 'Home Office Monitors', 'description' => 'Practical everyday monitors with USB-C and ergonomic stands.'],
                ],
            ],
            [
                'name'        => 'Drives & Storage',
                'slug'        => 'drives',
                'description' => 'NVMe SSDs, SATA SSDs, portable drives and NAS storage solutions.',
                'position'    => 3,
                'children'    => [
                    ['slug' => 'nvme-ssds', 'name' => 'NVMe SSDs', 'description' => 'PCIe Gen 4 and Gen 5 M.2 drives for maximum read/write speeds.'],
                    ['slug' => 'sata-ssds', 'name' => 'SATA SSDs', 'description' => 'Affordable SSD upgrades for desktops and laptops.'],
                    ['slug' => 'portable-ssds', 'name' => 'Portable SSDs', 'description' => 'Rugged, pocket-sized SSDs for photographers and creators.'],
                    ['slug' => 'hard-drives', 'name' => 'Hard Drives', 'description' => 'High-capacity spinning hard drives for archival and NAS.'],
                ],
            ],
            [
                'name'        => 'Memory',
                'slug'        => 'memory',
                'description' => 'DDR4 and DDR5 RAM for desktops, laptops and servers.',
                'position'    => 4,
                'children'    => [
                    ['slug' => 'desktop-ram', 'name' => 'Desktop RAM', 'description' => 'DDR4 and DDR5 DIMM modules for desktop PCs.'],
                    ['slug' => 'laptop-ram', 'name' => 'Laptop RAM (SO-DIMM)', 'description' => 'SO-DIMM upgrades for compatible laptops and mini-PCs.'],
                ],
            ],
            [
                'name'        => 'Networking',
                'slug'        => 'networking',
                'description' => 'Wi-Fi 6E routers, mesh systems, switches and network adapters.',
                'position'    => 5,
                'children'    => [
                    ['slug' => 'wifi-routers', 'name' => 'Wi-Fi Routers', 'description' => 'Tri-band Wi-Fi 6E and Wi-Fi 7 routers for whole-home coverage.'],
                    ['slug' => 'mesh-systems', 'name' => 'Mesh Wi-Fi Systems', 'description' => 'Seamless whole-home mesh networking kits.'],
                    ['slug' => 'network-switches', 'name' => 'Switches & Access Points', 'description' => 'Managed and unmanaged switches for wired networks.'],
                ],
            ],
            [
                'name'        => 'Audio',
                'slug'        => 'audio',
                'description' => 'Headphones, earbuds, desktop speakers and DAC/amp combos.',
                'position'    => 6,
                'children'    => [
                    ['slug' => 'headphones', 'name' => 'Headphones', 'description' => 'Over-ear and on-ear headphones with ANC and hi-res audio.'],
                    ['slug' => 'earbuds', 'name' => 'Wireless Earbuds', 'description' => 'True wireless earbuds with ANC and spatial audio.'],
                    ['slug' => 'gaming-headsets', 'name' => 'Gaming Headsets', 'description' => 'Surround-sound headsets with clear microphones for gaming.'],
                    ['slug' => 'speakers', 'name' => 'Desktop Speakers', 'description' => 'Stereo and 2.1 desktop speakers for music and media.'],
                ],
            ],
            [
                'name'        => 'Keyboards',
                'slug'        => 'keyboards',
                'description' => 'Mechanical, membrane and wireless keyboards for work and gaming.',
                'position'    => 7,
                'children'    => [
                    ['slug' => 'mechanical-keyboards', 'name' => 'Mechanical Keyboards', 'description' => 'Full-size, TKL and 75% mechanical keyboards with hot-swap switches.'],
                    ['slug' => 'wireless-keyboards', 'name' => 'Wireless Keyboards', 'description' => 'Bluetooth and 2.4 GHz wireless keyboards for clean desks.'],
                    ['slug' => 'gaming-keyboards', 'name' => 'Gaming Keyboards', 'description' => 'RGB gaming keyboards with rapid-trigger and polling rate technology.'],
                ],
            ],
            [
                'name'        => 'Mice',
                'slug'        => 'mice',
                'description' => 'Productivity mice, gaming mice and vertical ergonomic mice.',
                'position'    => 8,
                'children'    => [
                    ['slug' => 'gaming-mice', 'name' => 'Gaming Mice', 'description' => 'Ultra-lightweight gaming mice with precision optical sensors.'],
                    ['slug' => 'productivity-mice', 'name' => 'Productivity & Office Mice', 'description' => 'Multi-device mice with programmable buttons for power users.'],
                ],
            ],
            [
                'name'        => 'Components',
                'slug'        => 'components',
                'description' => 'GPUs, CPUs, motherboards and PC building essentials.',
                'position'    => 9,
                'children'    => [
                    ['slug' => 'graphics-cards', 'name' => 'Graphics Cards (GPU)', 'description' => 'NVIDIA GeForce and AMD Radeon discrete graphics cards.'],
                    ['slug' => 'processors', 'name' => 'Processors (CPU)', 'description' => 'Intel Core and AMD Ryzen desktop processors.'],
                    ['slug' => 'cooling', 'name' => 'CPU Cooling', 'description' => 'Air coolers and AIO liquid coolers for overclocking.'],
                ],
            ],
            [
                'name'        => 'Smart Home',
                'slug'        => 'smart-home',
                'description' => 'Smart speakers, displays, plugs, lights and home automation.',
                'position'    => 10,
                'children'    => [
                    ['slug' => 'smart-speakers', 'name' => 'Smart Speakers & Displays', 'description' => 'Voice-controlled speakers and touchscreen smart displays.'],
                    ['slug' => 'smart-lighting', 'name' => 'Smart Lighting', 'description' => 'App-controlled bulbs, light strips and switches.'],
                    ['slug' => 'security-cameras', 'name' => 'Home Security Cameras', 'description' => 'Indoor and outdoor security cameras with cloud recording.'],
                ],
            ],
            [
                'name'        => 'Mobile Accessories',
                'slug'        => 'mobile-accessories',
                'description' => 'Cases, chargers, screen protectors and accessories for phones and tablets.',
                'position'    => 11,
                'children'    => [
                    ['slug' => 'phone-cases', 'name' => 'Phone Cases', 'description' => 'Protective and stylish cases for iPhone and Android.'],
                    ['slug' => 'chargers-cables', 'name' => 'Chargers & Cables', 'description' => 'Fast chargers, USB-C hubs and MagSafe accessories.'],
                    ['slug' => 'screen-protectors', 'name' => 'Screen Protectors', 'description' => 'Tempered glass and film protectors for phones and tablets.'],
                ],
            ],
            [
                'name'        => 'Wearables',
                'slug'        => 'wearables',
                'description' => 'Smartwatches and fitness trackers from Apple, Samsung, Garmin and more.',
                'position'    => 12,
                'children'    => [
                    ['slug' => 'smartwatches', 'name' => 'Smartwatches', 'description' => 'Feature-rich smartwatches with health and fitness tracking.'],
                    ['slug' => 'fitness-trackers', 'name' => 'Fitness Trackers', 'description' => 'Lightweight activity trackers with heart rate monitoring.'],
                ],
            ],
            [
                'name'        => 'Cameras',
                'slug'        => 'cameras',
                'description' => 'Mirrorless cameras, action cameras and lenses for every photographer.',
                'position'    => 13,
                'children'    => [
                    ['slug' => 'mirrorless-cameras', 'name' => 'Mirrorless Cameras', 'description' => 'Full-frame and APS-C mirrorless camera bodies.'],
                    ['slug' => 'action-cameras', 'name' => 'Action Cameras', 'description' => 'Waterproof action cameras for adventure and travel.'],
                    ['slug' => 'webcams', 'name' => 'Webcams', 'description' => '1080p and 4K webcams for streaming and video calls.'],
                ],
            ],
            [
                'name'        => 'Printers',
                'slug'        => 'printers',
                'description' => 'Inkjet, laser and all-in-one printers for home and office.',
                'position'    => 14,
                'children'    => [
                    ['slug' => 'inkjet-printers', 'name' => 'Inkjet Printers', 'description' => 'High-quality colour inkjet printers for documents and photos.'],
                    ['slug' => 'laser-printers', 'name' => 'Laser Printers', 'description' => 'Fast monochrome and colour laser printers for office use.'],
                ],
            ],
            [
                'name'        => 'Accessories',
                'slug'        => 'accessories',
                'description' => 'Docking stations, laptop bags, desk mats, cable management and more.',
                'position'    => 15,
                'children'    => [
                    ['slug' => 'docking-stations', 'name' => 'Docking Stations & Hubs', 'description' => 'USB-C and Thunderbolt docks for multi-monitor setups.'],
                    ['slug' => 'laptop-bags', 'name' => 'Laptop Bags & Backpacks', 'description' => 'Padded bags and backpacks for commuters.'],
                    ['slug' => 'desk-accessories', 'name' => 'Desk Accessories', 'description' => 'Monitor arms, desk mats, cable management and ergonomic tools.'],
                    ['slug' => 'ups-power', 'name' => 'UPS & Power', 'description' => 'Uninterruptible power supplies and surge protectors.'],
                ],
            ],
        ];
    }
}
