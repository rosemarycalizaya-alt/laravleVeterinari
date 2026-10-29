<?php

namespace Database\Factories;

use App\Models\Atencion;
use App\Models\Consulta;
use App\Models\ExpedienteClinico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Consulta> */
class ConsultaFactory extends Factory
{
    protected $model = Consulta::class;

    public function definition(): array
    {
        return ['expediente_id' => ExpedienteClinico::factory(), 'atencion_id' => fn (array $datos) => Atencion::factory()->create(['mascota_id' => ExpedienteClinico::query()->whereKey($datos['expediente_id'])->firstOrFail()->mascota_id])->id, 'veterinario_id' => User::factory()->state(['rol' => 'veterinario']), 'fecha_consulta' => now(), 'motivo_consulta' => 'Visita ficticia de prueba'];
    }
}
