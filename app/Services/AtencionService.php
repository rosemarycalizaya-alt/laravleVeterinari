<?php

namespace App\Services;

use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtencionService
{
    /** @param array{mascota_id: int|string, cita_id?: int|string|null, motivo_ingreso?: string|null, veterinario_id?: int} $datos */
    public function llegada(array $datos, User $usuario): Atencion
    {
        return DB::transaction(function () use ($datos, $usuario): Atencion {
            if (! empty($datos['cita_id'])) {
                $cita = Cita::query()->lockForUpdate()->findOrFail($datos['cita_id']);
                if ($cita->estado !== 'programada' || $cita->atencion()->exists()) {
                    throw ValidationException::withMessages(['cita_id' => 'Esta cita no admite otra llegada.']);
                }
                if ((int) $datos['mascota_id'] !== $cita->mascota_id) {
                    throw ValidationException::withMessages(['mascota_id' => 'La mascota debe coincidir con la cita.']);
                }
                $datos['veterinario_id'] = $cita->veterinario_id;
            }
            $mascota = Mascota::query()->lockForUpdate()->findOrFail($datos['mascota_id']);
            if ($mascota->estado !== 'activa' || $mascota->atenciones()->whereIn('estado', ['en_espera', 'en_atencion'])->exists()) {
                throw ValidationException::withMessages(['mascota_id' => 'La mascota no está activa o ya tiene una atención pendiente.']);
            }

            return Atencion::create([...$datos, 'registrada_por_id' => $usuario->id, 'hora_llegada' => now(), 'estado' => 'en_espera']);
        }, 5);
    }

    public function comenzar(Atencion $atencion, User $veterinario): void
    {
        DB::transaction(function () use ($atencion, $veterinario): void {
            User::query()->lockForUpdate()->findOrFail($veterinario->id);
            $atencion = Atencion::query()->lockForUpdate()->findOrFail($atencion->id);
            if ($atencion->estado !== 'en_espera' || ($atencion->veterinario_id && $atencion->veterinario_id !== $veterinario->id)) {
                throw ValidationException::withMessages(['estado' => 'La atención no está disponible para este profesional.']);
            }
            if (Atencion::where('veterinario_id', $veterinario->id)->where('estado', 'en_atencion')->exists()) {
                throw ValidationException::withMessages(['estado' => 'Ya tienes un paciente en atención. Finaliza su atención primero.']);
            }
            $atencion->update(['estado' => 'en_atencion', 'veterinario_id' => $veterinario->id, 'hora_inicio' => now()]);
        }, 5);
    }

    public function finalizar(Atencion $atencion, User $usuario): void
    {
        DB::transaction(function () use ($atencion, $usuario): void {
            User::query()->lockForUpdate()->findOrFail($usuario->id);
            if ($atencion->cita_id) {
                Cita::query()->lockForUpdate()->findOrFail($atencion->cita_id);
            }
            $atencion = Atencion::query()->lockForUpdate()->findOrFail($atencion->id);
            abort_unless($atencion->veterinario_id === $usuario->id, 403);
            if ($atencion->estado !== 'en_atencion' || ! $atencion->consulta()->exists()) {
                throw ValidationException::withMessages(['estado' => 'Guarda una consulta antes de finalizar la atención.']);
            }
            $atencion->update(['estado' => 'finalizada', 'hora_fin' => now()]);
            $atencion->cita?->update(['estado' => 'atendida']);
        }, 5);
    }

    public function cancelar(Atencion $atencion): void
    {
        DB::transaction(function () use ($atencion): void {
            if ($atencion->cita_id) {
                Cita::query()->lockForUpdate()->findOrFail($atencion->cita_id);
            }
            $atencion = Atencion::query()->lockForUpdate()->findOrFail($atencion->id);
            if ($atencion->estado !== 'en_espera') {
                throw ValidationException::withMessages(['estado' => 'Solo se pueden cancelar llegadas en espera.']);
            }
            $atencion->update(['estado' => 'cancelada', 'hora_fin' => now()]);
            $atencion->cita?->update(['estado' => 'cancelada']);
        }, 5);
    }
}
