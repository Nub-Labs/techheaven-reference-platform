<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  → Seeding admin user and store configuration...');

        $email    = env('ADMIN_EMAIL', 'admin@techheaven.com');
        $password = env('ADMIN_PASSWORD', 'Admin@12345');

        // ── Admin user ────────────────────────────────────────────────────────
        // The Installer seeder creates a default admin role (id=1).
        // We upsert the admin user with TechHeaven credentials.
        $existing = DB::table('admins')->where('email', $email)->first();
        if ($existing) {
            DB::table('admins')->where('email', $email)->update([
                'name'       => 'TechHeaven Admin',
                'password'   => Hash::make($password),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = DB::table('roles')->value('id') ?? 1;
            DB::table('admins')->insert([
                'name'       => 'TechHeaven Admin',
                'email'      => $email,
                'password'   => Hash::make($password),
                'role_id'    => $roleId,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── Channel: columns that still live on `channels` in Bagisto 2.4.x ──
        // name/description moved to channel_translations in 2.4.x
        DB::table('channels')
            ->where('code', 'default')
            ->update([
                'hostname'   => 'localhost',
                'theme'      => 'default',
                'updated_at' => now(),
            ]);

        // ── Channel translation (name, description, SEO) ─────────────────────
        $channelId = DB::table('channels')->where('code', 'default')->value('id');
        if ($channelId) {
            $seo = json_encode([
                'meta_title'       => 'TechHeaven — Premium Consumer Electronics',
                'meta_description' => 'Shop the latest laptops, monitors, audio, gaming gear and smart home devices at TechHeaven. Expert advice, fast shipping, and unbeatable prices.',
                'meta_keywords'    => 'consumer electronics, laptops, gaming, monitors, audio, smart home, TechHeaven',
            ]);

            $exists = DB::table('channel_translations')
                ->where('channel_id', $channelId)
                ->where('locale', 'en')
                ->exists();

            if ($exists) {
                DB::table('channel_translations')
                    ->where('channel_id', $channelId)
                    ->where('locale', 'en')
                    ->update([
                        'name'        => 'TechHeaven',
                        'description' => 'Your destination for premium consumer electronics.',
                        'home_seo'    => $seo,
                        'updated_at'  => now(),
                    ]);
            } else {
                DB::table('channel_translations')->insert([
                    'channel_id'  => $channelId,
                    'locale'      => 'en',
                    'name'        => 'TechHeaven',
                    'description' => 'Your destination for premium consumer electronics.',
                    'home_seo'    => $seo,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $this->command->info('     Admin: ' . $email . ' / ' . $password);
    }
}
