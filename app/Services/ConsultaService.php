<?php

namespace App\Services;

use App\Models\Atencion;
use App\Models\Consulta;
use App\Models\ExpedienteClinico;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultaService
{
    /** @param array{atencion_id: int|string, expediente_id: int|string, motivo_consulta: string, sintomas?: string|null, examen_fisico?: string|null, peso_kg?: numeric-string|null, temperatura_c?: numeric-string|null, diagnostico?: string|null, tratamiento?: string|null, recomendaciones?: string|null, proximo_control?: string|null} $datos */
    public function guardar(array $datos, User $veterinario, ?Consulta $consulta = null): Consulta
    {
        return DB::transaction(function () use ($datos, $veterinario, $consulta): Consulta {
            $atencion = Atencion::query()->lockForUpdate()->findOrFail($consulta ? $consulta->atencion_id : $datos['atencion_id']);
            abort_unless($atencion->veterinario_id === $veterinario->id, 403);
            if ($atencion->estado !== 'en_atencion') {
                throw ValidationException::withMessages(['atencion_id' => 'Debes comenzar la atención antes de registrar o editar su consulta.']);
            }
            $expediente = ExpedienteClinico::findOrFail($consulta ? $consulta->expediente_id : $datos['expediente_id']);
            if ($atencion->mascota_id !== $expediente->mascota_id) {
                throw ValidationException::withMessages(['expediente_id' => 'El expediente no corresponde al paciente de la atención.']);
            }
            if ($consulta) {
                unset($datos['atencion_id'],$datos['expediente_id']);
                $consulta->update($datos);

                return $consulta;
            }
            if ($atencion->consulta()->exists()) {
                throw ValidationException::withMessages(['atencion_id' => 'Esta atención ya tiene una consulta.']);
            }

            return Consulta::create([...$datos, 'veterinario_id' => $veterinario->id, 'fecha_consulta' => now()]);
        }, 5);
    }

    public function verificarEdicion(Consulta $consulta, User $usuario): void
    {
        abort_unless($consulta->veterinario_id === $usuario->id, 403);
        if ($consulta->atencion->estado !== 'en_atencion') {
            throw ValidationException::withMessages(['consulta_id' => 'La atención está cerrada. Registra una nueva visita para continuar el historial.']);
        }
    }
}
