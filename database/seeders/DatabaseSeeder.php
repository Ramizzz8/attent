<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** "php artisan migrate:fresh --seed" deja la aplicación lista para enseñar. */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
