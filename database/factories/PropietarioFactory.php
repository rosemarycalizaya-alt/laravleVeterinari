<?php

namespace Database\Factories;

use App\Models\Propietario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Propietario> */
class PropietarioFactory extends Factory
{
    protected $model = Propietario::class;

    public function definition(): array
    {
        return ['nombre_completo' => fake()->name(), 'documento_identidad' => fake()->unique()->numerify('DEMO-######'), 'telefono' => fake()->numerify('000-#######'), 'correo' => fake()->safeEmail()];
    }
}
