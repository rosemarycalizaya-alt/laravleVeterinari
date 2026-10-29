<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MedicamentoConsultaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
