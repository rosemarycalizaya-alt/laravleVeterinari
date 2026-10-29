<?php

namespace Database\Factories;

use App\Models\Consulta;
use App\Models\Medicamento;
use App\Models\MedicamentoConsulta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedicamentoConsulta> */
class MedicamentoConsultaFactory extends Factory
{
    protected $model = MedicamentoConsulta::class;

    public function definition(): array
    {
        return ['consulta_id' => Consulta::factory(), 'medicamento_id' => Medicamento::factory(), 'tipo_registro' => 'recetado', 'indicaciones' => 'DEMOSTRACIÓN SIN USO MÉDICO'];
    }
}
