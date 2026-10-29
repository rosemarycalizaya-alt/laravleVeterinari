<?php

namespace App\Models;

use Database\Factories\MascotaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $fecha_nacimiento
 * @property bool|null $esterilizado
 */
#[Fillable(['propietario_id', 'nombre', 'especie', 'raza', 'sexo', 'fecha_nacimiento', 'color', 'microchip', 'esterilizado', 'estado', 'foto_ruta', 'observaciones'])]
class Mascota extends Model
{
    /** @use HasFactory<MascotaFactory> */
    use HasFactory;

    protected $table = 'mascotas';

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date', 'esterilizado' => 'boolean'];
    }

    /** @return BelongsTo<Propietario, $this> */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    /** @return HasOne<ExpedienteClinico, $this> */
    public function expediente(): HasOne
    {
        return $this->hasOne(ExpedienteClinico::class, 'mascota_id');
    }

    /** @return HasMany<Cita, $this> */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'mascota_id');
    }

    /** @return HasMany<Atencion, $this> */
    public function atenciones(): HasMany
    {
        return $this->hasMany(Atencion::class, 'mascota_id');
    }
}
