<?php

namespace App\Models;

use Database\Factories\ExpedienteClinicoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $fecha_apertura
 */
#[Fillable(['mascota_id', 'codigo', 'fecha_apertura', 'alergias', 'enfermedades_cronicas', 'antecedentes_medicos', 'observaciones_generales'])]
class ExpedienteClinico extends Model
{
    /** @use HasFactory<ExpedienteClinicoFactory> */
    use HasFactory;

    protected $table = 'expedientes_clinicos';

    protected function casts(): array
    {
        return ['fecha_apertura' => 'date'];
    }

    /** @return BelongsTo<Mascota, $this> */
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
    }

    /** @return HasMany<Consulta, $this> */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'expediente_id');
    }

    /** @return HasMany<Vacunacion, $this> */
    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class, 'expediente_id');
    }

    /** @return HasMany<DocumentoClinico, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoClinico::class, 'expediente_id');
    }
}
