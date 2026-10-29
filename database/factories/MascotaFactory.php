<?php

namespace Database\Factories;

use App\Models\Mascota;
use App\Models\Propietario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mascota> */
class MascotaFactory extends Factory
{
    protected $model = Mascota::class;

    public function definition(): array
    {
        return ['propietario_id' => Propietario::factory(), 'nombre' => fake()->firstName(), 'especie' => 'Perro', 'sexo' => 'desconocido', 'estado' => 'activa'];
    }
}
