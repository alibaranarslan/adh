<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            LayoutModuleSeeder::class,
            HeaderThemeSeeder::class,
            CategorySeeder::class,
            SettingsSeeder::class,
            DemoContentSeeder::class,
            CustomerContentSeeder::class,
        ]);
    }
}
