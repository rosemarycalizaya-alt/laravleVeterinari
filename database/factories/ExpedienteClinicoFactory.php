<?php

namespace Database\Factories;

use App\Models\ExpedienteClinico;
use App\Models\Mascota;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpedienteClinico> */
class ExpedienteClinicoFactory extends Factory
{
    protected $model = ExpedienteClinico::class;

    public function definition(): array
    {
        return ['mascota_id' => Mascota::factory(), 'codigo' => 'HUE-TEST-'.fake()->unique()->numerify('########'), 'fecha_apertura' => today()];
    }
}
