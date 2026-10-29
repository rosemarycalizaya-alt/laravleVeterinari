<?php

namespace Database\Factories;

use App\Models\Medicamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Medicamento> */
class MedicamentoFactory extends Factory
{
    protected $model = Medicamento::class;

    public function definition(): array
    {
        return ['nombre' => 'DEMOSTRACIÓN '.fake()->unique()->word(), 'principio_activo' => 'Producto ficticio sin uso clínico', 'activo' => true];
    }
}
