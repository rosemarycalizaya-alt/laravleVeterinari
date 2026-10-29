<?php

namespace App\Services;

use App\Models\ExpedienteClinico;
use App\Models\Mascota;
use Illuminate\Support\Facades\DB;

class ExpedienteService
{
    public function abrir(Mascota $mascota): ExpedienteClinico
    {
        return DB::transaction(function () use ($mascota): ExpedienteClinico {
            $mascota = Mascota::query()->lockForUpdate()->findOrFail($mascota->id);

            return $mascota->expediente()->firstOrCreate([], ['codigo' => 'HUE-'.str_pad((string) $mascota->id, 6, '0', STR_PAD_LEFT), 'fecha_apertura' => today()]);
        }, 5);
    }
}
