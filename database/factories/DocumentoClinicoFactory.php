<?php

namespace Database\Factories;

use App\Models\DocumentoClinico;
use App\Models\ExpedienteClinico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentoClinico> */
class DocumentoClinicoFactory extends Factory
{
    protected $model = DocumentoClinico::class;

    public function definition(): array
    {
        return ['expediente_id' => ExpedienteClinico::factory(), 'subido_por_id' => User::factory()->state(['rol' => 'veterinario']), 'nombre_archivo' => 'prueba.pdf', 'ruta_privada' => 'documentos/prueba.pdf'];
    }
}
