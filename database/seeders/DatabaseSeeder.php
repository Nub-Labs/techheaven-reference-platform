<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Bagisto core data ──────────────────────────────────────────────
        // In Bagisto 2.x all initial data (locales, currencies, countries,
        // channels, attributes, attribute families, roles) is seeded by the
        // Installer package's DatabaseSeeder.
        $this->call([
            \Webkul\Installer\Database\Seeders\DatabaseSeeder::class,
        ]);

        // ── 2. TechHeaven reference business data ─────────────────────────────
        $this->call(TechHeavenSeeder::class);
    }
}
