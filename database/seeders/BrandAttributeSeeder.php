<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates a "Brand" select attribute and populates it with all TechHeaven brands.
 * The generated attribute_option IDs are stored in a public static map
 * so ProductSeeder can reference them by brand name.
 */
class BrandAttributeSeeder extends Seeder
{
    public static int $brandAttributeId = 0;
    public static array $brandOptionIds = [];

    public function run(): void
    {
        $this->command->info('  → Seeding brand attribute...');

        // Check if brand attribute already exists (from Bagisto defaults)
        $existing = DB::table('attributes')->where('code', 'brand')->first();

        if ($existing) {
            self::$brandAttributeId = $existing->id;
        } else {
            // Create the attribute
            self::$brandAttributeId = DB::table('attributes')->insertGetId([
                'code'                => 'brand',
                'type'                => 'select',
                'admin_name'          => 'Brand',
                'validation'          => null,
                'position'            => 30,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 1,
                'is_visible_on_front' => 1,
                'is_comparable'       => 1,
                'is_user_defined'     => 1,
                'swatch_type'         => null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // Add translation
            DB::table('attribute_translations')->insert([
                'locale'       => 'en',
                'name'         => 'Brand',
                'attribute_id' => self::$brandAttributeId,
            ]);

            // Assign to default attribute family
            $familyId = DB::table('attribute_families')->value('id') ?? 1;
            $groupId  = DB::table('attribute_groups')
                ->where('attribute_family_id', $familyId)
                ->value('id');

            if ($groupId) {
                $maxPosition = DB::table('attribute_group_mappings')
                    ->where('attribute_group_id', $groupId)
                    ->max('position') ?? 0;

                DB::table('attribute_group_mappings')->insertOrIgnore([
                    'attribute_id'       => self::$brandAttributeId,
                    'attribute_group_id' => $groupId,
                    'position'           => $maxPosition + 1,
                ]);
            }
        }

        // Create brand options
        foreach ($this->getBrands() as $position => $brand) {
            // Check if option already exists
            $existing = DB::table('attribute_options')
                ->where('attribute_id', self::$brandAttributeId)
                ->where('admin_name', $brand['name'])
                ->first();

            if ($existing) {
                self::$brandOptionIds[$brand['name']] = $existing->id;
                continue;
            }

            $optionId = DB::table('attribute_options')->insertGetId([
                'attribute_id' => self::$brandAttributeId,
                'admin_name'   => $brand['name'],
                'sort_order'   => $position + 1,
            ]);

            DB::table('attribute_option_translations')->insert([
                'locale'              => 'en',
                'label'               => $brand['name'],
                'attribute_option_id' => $optionId,
            ]);

            self::$brandOptionIds[$brand['name']] = $optionId;
        }

        $this->command->info('     Registered ' . count(self::$brandOptionIds) . ' brands');
    }

    private function getBrands(): array
    {
        return [
            ['name' => 'Apple'],
            ['name' => 'Dell'],
            ['name' => 'HP'],
            ['name' => 'Lenovo'],
            ['name' => 'ASUS'],
            ['name' => 'MSI'],
            ['name' => 'Samsung'],
            ['name' => 'LG'],
            ['name' => 'Sony'],
            ['name' => 'Logitech'],
            ['name' => 'Corsair'],
            ['name' => 'Razer'],
            ['name' => 'TP-Link'],
            ['name' => 'Kingston'],
            ['name' => 'SanDisk'],
            ['name' => 'WD'],
            ['name' => 'Crucial'],
            ['name' => 'Seagate'],
            ['name' => 'Bose'],
            ['name' => 'Jabra'],
            ['name' => 'Sennheiser'],
            ['name' => 'Keychron'],
            ['name' => 'SteelSeries'],
            ['name' => 'Garmin'],
            ['name' => 'Fitbit'],
            ['name' => 'Canon'],
            ['name' => 'Nikon'],
            ['name' => 'GoPro'],
            ['name' => 'DJI'],
            ['name' => 'Brother'],
            ['name' => 'Epson'],
            ['name' => 'Belkin'],
            ['name' => 'Anker'],
            ['name' => 'Elgato'],
            ['name' => 'NVIDIA'],
            ['name' => 'AMD'],
            ['name' => 'Intel'],
            ['name' => 'APC'],
            ['name' => 'Netgear'],
            ['name' => 'Ubiquiti'],
            ['name' => 'Philips'],
            ['name' => 'Ring'],
            ['name' => 'Arlo'],
        ];
    }
}
