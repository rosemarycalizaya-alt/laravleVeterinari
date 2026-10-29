<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('No se crean cuentas ni datos automáticamente. Usa huellita:admin o el seeder opcional DemoSeeder.');
    }
}
