<?php

namespace App\Models;

use Database\Factories\ConsultaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $fecha_consulta
 * @property string|null $peso_kg
 * @property string|null $temperatura_c
 * @property Carbon|null $proximo_control
 */
#[Fillable(['expediente_id', 'atencion_id', 'veterinario_id', 'fecha_consulta', 'motivo_consulta', 'sintomas', 'examen_fisico', 'peso_kg', 'temperatura_c', 'diagnostico', 'tratamiento', 'recomendaciones', 'proximo_control'])]
class Consulta extends Model
{
    /** @use HasFactory<ConsultaFactory> */
    use HasFactory;

    protected $table = 'consultas';

    protected function casts(): array
    {
        return ['fecha_consulta' => 'datetime', 'peso_kg' => 'decimal:2', 'temperatura_c' => 'decimal:1', 'proximo_control' => 'date'];
    }

    /** @return BelongsTo<ExpedienteClinico, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteClinico::class, 'expediente_id');
    }

    /** @return BelongsTo<Atencion, $this> */
    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class, 'atencion_id');
    }

    /** @return BelongsTo<User, $this> */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    /** @return HasMany<MedicamentoConsulta, $this> */
    public function medicamentos(): HasMany
    {
        return $this->hasMany(MedicamentoConsulta::class, 'consulta_id');
    }

    /** @return HasMany<Vacunacion, $this> */
    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class, 'consulta_id');
    }

    /** @return HasMany<DocumentoClinico, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoClinico::class, 'consulta_id');
    }
}
