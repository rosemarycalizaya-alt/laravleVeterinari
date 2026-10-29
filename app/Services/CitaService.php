<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CitaService
{
    /** @param array{mascota_id: int|string, veterinario_id: int|string, registrada_por_id?: int, inicio: string, fin: string, motivo?: string|null, canal: string, estado: string, observaciones?: string|null} $datos */
    public function guardar(array $datos, ?Cita $cita = null): Cita
    {
        $datos['inicio'] = Carbon::parse($datos['inicio'])->format('Y-m-d H:i:s');
        $datos['fin'] = Carbon::parse($datos['fin'])->format('Y-m-d H:i:s');

        return DB::transaction(function () use ($datos, $cita): Cita {
            $ids = array_unique(array_filter([$datos['veterinario_id'], $cita?->veterinario_id]));
            $profesionales = User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $profesional = $profesionales->firstWhere('id', $datos['veterinario_id']);
            if (! $profesional || ! $profesional->puedeAtender()) {
                throw ValidationException::withMessages(['veterinario_id' => 'Selecciona un veterinario activo.']);
            }
            if ($cita) {
                $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id);
                if ($cita->atencion()->exists()) {
                    throw ValidationException::withMessages(['estado' => 'La cita ya tiene una llegada registrada; gestiona su atención.']);
                }
            }
            if (($datos['estado']) === 'atendida') {
                throw ValidationException::withMessages(['estado' => 'Finaliza la atención para marcar la cita como atendida.']);
            }
            if (! in_array($datos['estado'], ['cancelada', 'no_asistio'], true)) {
                $conflicto = Cita::query()->where('veterinario_id', $datos['veterinario_id'])->whereIn('estado', ['programada', 'atendida'])->when($cita, fn ($q) => $q->where('id', '!=', $cita->id))->where('inicio', '<', $datos['fin'])->where('fin', '>', $datos['inicio'])->exists();
                if ($conflicto) {
                    throw ValidationException::withMessages(['inicio' => 'El veterinario ya tiene una cita en ese horario.']);
                }
            }
            if ($cita) {
                $cita->update($datos);

                return $cita;
            }

            return Cita::create($datos);
        }, 5);
    }
}
