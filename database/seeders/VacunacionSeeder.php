<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VacunacionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
