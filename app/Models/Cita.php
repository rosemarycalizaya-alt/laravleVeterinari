<?php

namespace App\Models;

use Database\Factories\CitaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $inicio
 * @property Carbon $fin
 */
#[Fillable(['mascota_id', 'veterinario_id', 'registrada_por_id', 'inicio', 'fin', 'motivo', 'canal', 'estado', 'observaciones'])]
class Cita extends Model
{
    /** @use HasFactory<CitaFactory> */
    use HasFactory;

    protected $table = 'citas';

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime'];
    }

    /** @return BelongsTo<Mascota, $this> */
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
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

    /** @return HasOne<Atencion, $this> */
    public function atencion(): HasOne
    {
        return $this->hasOne(Atencion::class, 'cita_id');
    }
}
