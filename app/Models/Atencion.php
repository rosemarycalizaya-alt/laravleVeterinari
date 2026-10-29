<?php

namespace App\Models;

use Database\Factories\AtencionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $hora_llegada
 * @property Carbon|null $hora_inicio
 * @property Carbon|null $hora_fin
 */
#[Fillable(['mascota_id', 'cita_id', 'registrada_por_id', 'veterinario_id', 'motivo_ingreso', 'estado', 'hora_llegada', 'hora_inicio', 'hora_fin'])]
class Atencion extends Model
{
    /** @use HasFactory<AtencionFactory> */
    use HasFactory;

    protected $table = 'atenciones';

    protected function casts(): array
    {
        return ['hora_llegada' => 'datetime', 'hora_inicio' => 'datetime', 'hora_fin' => 'datetime'];
    }

    /** @return BelongsTo<Mascota, $this> */
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
    }

    /** @return BelongsTo<Cita, $this> */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    /** @return BelongsTo<User, $this> */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    /** @return BelongsTo<User, $this> */
    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por_id');
    }

    /** @return HasOne<Consulta, $this> */
    public function consulta(): HasOne
    {
        return $this->hasOne(Consulta::class, 'atencion_id');
    }
}
