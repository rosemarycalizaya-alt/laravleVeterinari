<?php

namespace Database\Factories;

use App\Models\ExpedienteClinico;
use App\Models\Vacunacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vacunacion> */
class VacunacionFactory extends Factory
{
    protected $model = Vacunacion::class;

    public function definition(): array
    {
        return ['expediente_id' => ExpedienteClinico::factory(), 'vacuna' => 'Vacuna ficticia DEMO', 'fecha_aplicacion' => today()];
    }
}
