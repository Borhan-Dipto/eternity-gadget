<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ImagineGadgetSeeder::class,
            EcommerceSeeder::class,
            EternityGadgetBrandingSeeder::class,
        ]);
    }
}
