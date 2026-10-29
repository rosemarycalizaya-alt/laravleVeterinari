<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cita> */
class CitaFactory extends Factory
{
    protected $model = Cita::class;

    public function definition(): array
    {
        return ['mascota_id' => Mascota::factory(), 'veterinario_id' => User::factory()->state(['rol' => 'veterinario']), 'registrada_por_id' => User::factory(), 'inicio' => today()->addDay()->setHour(10), 'fin' => today()->addDay()->setHour(11), 'canal' => 'presencial', 'estado' => 'programada'];
    }
}
