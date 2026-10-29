<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocumentoClinicoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
