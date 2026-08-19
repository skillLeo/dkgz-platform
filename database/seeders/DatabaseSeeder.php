<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Local default: the production dataset plus demo records, so every screen and
 * every empty state can be seen with realistic data. DemoSeeder refuses to run
 * in production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
