<?php

namespace Database\Factories;

use App\Models\Atencion;
use App\Models\Mascota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Atencion> */
class AtencionFactory extends Factory
{
    protected $model = Atencion::class;

    public function definition(): array
    {
        return ['mascota_id' => Mascota::factory(), 'registrada_por_id' => User::factory(), 'estado' => 'en_espera', 'hora_llegada' => now()];
    }
}
